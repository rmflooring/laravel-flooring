<?php

namespace App\Services\Agent;

use App\Models\AgentQuery;
use App\Models\User;

/**
 * Orchestrates one staff chat question through Claude's tool-use loop. Unlike
 * ProcessAgentTask (queued, email-triggered), this runs synchronously in the request —
 * a staff member is waiting on the answer. Same dispatch-loop shape otherwise: iterate,
 * dispatch tool_use blocks by name, feed results back, stop when Claude returns final
 * text or the iteration cap is hit.
 */
class RunKnowledgeChatQuery
{
    private const MAX_TOOL_ITERATIONS = 5;

    private const SYSTEM_PROMPT = <<<'TEXT'
        You are the Floor Manager staff knowledge assistant. Staff ask you questions and
        you answer using only the tools available to you — you never have direct database
        access, and you never take any write/update action; every tool here is read-only.

        Use search_knowledge_base for questions about protocols, policies, SOPs, FAQs, or
        general pricing guidance written up as knowledge (e.g. markup rules, pricing
        policy). Use search_labour_catalog for what we charge to install/service
        something, and search_material_catalog for what a specific product costs — these
        are the live catalogs, not written docs, so prefer them for a specific rate or
        product price question. Use get_work_order_status, check_inventory,
        get_customer_estimate, or get_schedule_for_crew for other live operational data.
        Use more than one tool if a question needs it (e.g. a knowledge-base protocol plus
        a specific labour rate). If a tool result has "authorized": false, tell the user
        plainly that they don't have access to that information — don't try another tool
        to work around it, and don't speculate about why.

        If search_knowledge_base or a live-data tool finds nothing relevant, say so rather
        than guessing or inventing an answer. When you do have an answer, cite what it's
        based on (which knowledge entry, or which record you looked up) so the user can
        verify it themselves.
        TEXT;

    public function __construct(
        private ClaudeAgentService $claude,
        private KnowledgeSearchService $searchKnowledgeBase,
        private SearchLabourCatalogService $searchLabourCatalog,
        private SearchMaterialCatalogService $searchMaterialCatalog,
        private GetWorkOrderStatusService $getWorkOrderStatus,
        private CheckInventoryService $checkInventory,
        private GetCustomerEstimateService $getCustomerEstimate,
        private GetScheduleForCrewService $getScheduleForCrew,
    ) {}

    public function run(User $user, string $question): AgentQuery
    {
        $messages = [['role' => 'user', 'content' => $question]];

        $toolsUsed = [];
        $sourceRefs = [];
        $answer = null;

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->claude->sendWithTools($messages, AgentToolRegistry::forChat(), self::SYSTEM_PROMPT);
            $content = $response['content'] ?? [];
            $stopReason = $response['stop_reason'] ?? null;

            $toolUses = array_values(array_filter($content, fn (array $b) => ($b['type'] ?? null) === 'tool_use'));

            if ($stopReason !== 'tool_use' || empty($toolUses)) {
                $answer = $this->extractText($content) ?: 'I wasn\'t able to come up with an answer to that.';
                break;
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolResults = [];
            foreach ($toolUses as $toolUse) {
                $toolsUsed[] = $toolUse['name'];
                [$result, $refs] = $this->dispatchTool($user, $toolUse);
                $sourceRefs = array_merge($sourceRefs, $refs);

                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolUse['id'],
                    'content' => json_encode($result),
                ];
            }
            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        if ($answer === null) {
            $answer = 'I wasn\'t able to fully answer that within the allowed number of steps — try rephrasing or narrowing the question.';
        }

        return AgentQuery::create([
            'user_id' => $user->id,
            'question' => $question,
            'tools_used' => array_values(array_unique($toolsUsed)),
            'answer' => $answer,
            'source_refs' => $sourceRefs,
        ]);
    }

    /**
     * @return array{0: array, 1: array} [tool result payload, source refs to record]
     */
    private function dispatchTool(User $user, array $toolUse): array
    {
        $name = $toolUse['name'];
        $input = $toolUse['input'] ?? [];

        try {
            switch ($name) {
                case 'search_knowledge_base':
                    $matches = $this->searchKnowledgeBase->search($user, (string) ($input['query'] ?? ''));

                    return [
                        ['authorized' => true, 'results' => $matches],
                        collect($matches)->map(fn (array $m) => [
                            'type' => 'knowledge_entry',
                            'id' => $m['knowledge_entry_id'],
                            'title' => $m['title'],
                        ])->unique('id')->values()->all(),
                    ];

                case 'search_labour_catalog':
                    $result = $this->searchLabourCatalog->execute($user, (string) ($input['query'] ?? ''));

                    return [$result, $this->catalogRefs($result, 'labour_item', 'description')];

                case 'search_material_catalog':
                    $result = $this->searchMaterialCatalog->execute($user, (string) ($input['query'] ?? ''));

                    return [$result, $this->catalogRefs($result, 'material', 'name')];

                case 'get_work_order_status':
                    $result = $this->getWorkOrderStatus->execute($user, (string) ($input['order_id'] ?? ''));

                    return [$result, $this->liveRefIfFound($result, 'work_order', $result['wo_number'] ?? null)];

                case 'check_inventory':
                    $result = $this->checkInventory->execute($user, (string) ($input['sku'] ?? ''));

                    return [$result, $this->liveRefIfFound($result, 'inventory', $result['sku'] ?? null)];

                case 'get_customer_estimate':
                    $result = $this->getCustomerEstimate->execute($user, (string) ($input['estimate_id'] ?? ''));

                    return [$result, $this->liveRefIfFound($result, 'estimate', $result['estimate_number'] ?? null)];

                case 'get_schedule_for_crew':
                    $result = $this->getScheduleForCrew->execute(
                        $user,
                        (string) ($input['crew_id'] ?? ''),
                        (string) ($input['date'] ?? ''),
                    );

                    return [$result, $this->liveRefIfFound($result, 'crew_schedule', $result['crew'] ?? null)];

                default:
                    return [['authorized' => false, 'message' => "Unknown tool \"{$name}\"."], []];
            }
        } catch (AgentToolValidationException $e) {
            return [['authorized' => true, 'error' => $e->getMessage()], []];
        }
    }

    private function catalogRefs(array $result, string $type, string $labelKey): array
    {
        if (($result['authorized'] ?? false) !== true) {
            return [];
        }

        return collect($result['results'] ?? [])
            ->map(fn (array $row) => ['type' => $type, 'label' => $row[$labelKey] ?? null])
            ->filter(fn (array $ref) => $ref['label'] !== null)
            ->values()
            ->all();
    }

    private function liveRefIfFound(array $result, string $type, ?string $label): array
    {
        if (($result['authorized'] ?? false) !== true || ($result['found'] ?? true) !== true || $label === null) {
            return [];
        }

        return [['type' => $type, 'label' => $label]];
    }

    private function extractText(array $content): string
    {
        return collect($content)
            ->filter(fn (array $b) => ($b['type'] ?? null) === 'text')
            ->map(fn (array $b) => $b['text'] ?? '')
            ->implode("\n");
    }
}
