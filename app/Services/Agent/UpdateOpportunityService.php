<?php

namespace App\Services\Agent;

use App\Models\AgentMessage;
use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Models\ProjectManager;
use App\Services\Agent\Concerns\ValidatesAgentAttachments;

/**
 * Executes the `update_opportunity` Claude tool. Deliberately narrow field scope for v1
 * (confirmed with the business owner): only `requires_rfm` (boolean) and
 * `project_manager_id` (resolved from a freetext name, never accepted as a raw ID).
 * Excluded on purpose: `status` (gated lifecycle transition, has its own business rules
 * in OpportunityController::update — e.g. blocking "Lost" while active sales exist),
 * `job_no` (job identifier, human-only), `status_reason` (only meaningful when status is
 * Lost/Closed, which this tool can't set), `sales_person_1/2` (not real FKs today —
 * validated as plain strings in OpportunityController, not safe to populate from
 * agent-inferred text), and customer-linkage fields (structural, human-only).
 */
class UpdateOpportunityService
{
    use ValidatesAgentAttachments;

    /** Fuzzy-match closeness threshold for the directory-wide PM fallback's last pass —
     *  only reached when the opportunity's own customer scope, and a global exact/starts-
     *  with search, both found nothing. Compares full names (e.g. "Matt" vs "Matt Van
     *  Brunt" scores well below this on plain similar_text() — that case is already
     *  caught by the starts-with pass), so this threshold is only for catching genuine
     *  spelling variants (e.g. "Mathew" vs "Matthew"). */
    private const PM_FUZZY_THRESHOLD = 0.6;

    /** Required lead over the runner-up for the fuzzy pass to auto-pick rather than
     *  throw as ambiguous — same principle as CreateOpportunityService::PARENT_FUZZY_MARGIN. */
    private const PM_FUZZY_MARGIN = 0.15;

    /**
     * @return array{opportunity_id: int, changes: array<string, mixed>, previous_values: array<string, mixed>}
     */
    public function execute(
        AgentTask $task,
        int $opportunityId,
        ?bool $requiresRfm,
        ?string $projectManagerName,
    ): array {
        $opportunity = $this->assertOpportunityMatches($task, $opportunityId);

        if ($requiresRfm === null && $projectManagerName === null) {
            throw new AgentToolValidationException(
                'At least one of requires_rfm or project_manager_name is required.'
            );
        }

        $changes = [];

        if ($requiresRfm !== null) {
            $changes['requires_rfm'] = $requiresRfm;
        }

        if ($projectManagerName !== null) {
            $changes['project_manager_id'] = $this->resolveProjectManagerId($task, $opportunity, $projectManagerName);
        }

        // Captured before the update so undo_last_action can restore these exact values.
        $previousValues = $opportunity->only(array_keys($changes));

        $changes['updated_by'] = $task->requester_user_id;
        $opportunity->update($changes);

        unset($changes['updated_by']);

        return [
            'opportunity_id' => $opportunity->id,
            'changes' => $changes,
            'previous_values' => $previousValues,
        ];
    }

    /**
     * Exact (case-insensitive) name match first, falling back to a "starts with" match
     * (e.g. "Andrew" -> "Andrew Bou-Antoun") only if nothing exact was found anywhere —
     * still no arbitrary fuzzy guessing for an FK write, just tolerating a bare first
     * name, which is how these get mentioned in practice (email correction requests,
     * e.g. "update the PM to Andrew"). Scoped first to the opportunity's parent customer
     * (where project managers are normally attached, per
     * OpportunityController::projectManagersForCustomer), falling back to the job-site
     * customer if none found there.
     *
     * If the customer-scoped search comes up completely empty (not ambiguous — an
     * ambiguous scoped match still throws immediately, same as before), falls back to
     * searching the full project-manager directory (the same list `/admin/project-
     * managers` shows, across every customer) for the closest match — exact, then
     * starts-with, then a similar_text() closeness pass — before giving up. This covers
     * the common real case where the referrer's own PM is mentioned before that
     * referrer's customer record even has any project managers attached yet (e.g. a
     * brand-new standalone customer created moments earlier by create_opportunity). A
     * directory-wide match is logged for audit, since it's a wider net than the
     * customer-scoped one.
     */
    private function resolveProjectManagerId(AgentTask $task, Opportunity $opportunity, string $name): int
    {
        $name = trim($name);
        $customerIds = array_filter([$opportunity->parent_customer_id, $opportunity->job_site_customer_id]);

        $match = $this->findProjectManager($customerIds, $name, exact: true)
            ?? $this->findProjectManager($customerIds, $name, exact: false);

        if ($match !== null) {
            return $match;
        }

        $directoryMatch = $this->findProjectManagerGlobally($task, $name);
        if ($directoryMatch !== null) {
            return $directoryMatch;
        }

        throw new AgentToolValidationException(
            "No project manager named \"{$name}\" found for this opportunity's customer, or anywhere in the project manager directory."
        );
    }

    /**
     * @param  iterable<int>  $customerIds  Checked in order; the first scope with any
     *     match (exact or ambiguous) wins/throws without falling through to the next.
     * @return ?int  The matched PM's id, or null if no scope had any match at all.
     */
    private function findProjectManager(iterable $customerIds, string $name, bool $exact): ?int
    {
        $needle = mb_strtolower($name);

        foreach ($customerIds as $customerId) {
            $query = ProjectManager::where('customer_id', $customerId);
            $query = $exact
                ? $query->whereRaw('LOWER(name) = ?', [$needle])
                : $query->whereRaw('LOWER(name) LIKE ?', [$needle . ' %']);

            $matches = $query->get(['id', 'name']);

            if ($matches->count() === 1) {
                return $matches->first()->id;
            }

            if ($matches->count() > 1) {
                throw new AgentToolValidationException(
                    "Multiple project managers named \"{$name}\" found for this customer — cannot resolve unambiguously."
                );
            }
        }

        return null;
    }

    /**
     * Directory-wide fallback, only reached once the opportunity's own customer scope
     * has zero matches. Same exact-then-starts-with pattern as findProjectManager(), just
     * unscoped (no customer_id filter), plus a third similar_text() closeness pass for
     * genuine spelling variants. Every candidate considered is logged for audit,
     * regardless of outcome, per the spec's "log every fuzzy-matching decision with a
     * confidence score" principle — this is a wider net than the customer-scoped passes,
     * so it's worth a clear audit trail even on a clean exact/starts-with hit.
     */
    private function findProjectManagerGlobally(AgentTask $task, string $name): ?int
    {
        $needle = mb_strtolower($name);

        $exact = ProjectManager::whereRaw('LOWER(name) = ?', [$needle])->get(['id', 'name']);
        if ($exact->count() === 1) {
            $this->logDirectoryPmMatch($task, $name, $exact->first(), 'exact', [], 1.0);

            return $exact->first()->id;
        }
        if ($exact->count() > 1) {
            throw new AgentToolValidationException(
                "Multiple project managers named \"{$name}\" found across the directory — cannot resolve unambiguously."
            );
        }

        $startsWith = ProjectManager::whereRaw('LOWER(name) LIKE ?', [$needle . ' %'])->get(['id', 'name']);
        if ($startsWith->count() === 1) {
            $this->logDirectoryPmMatch($task, $name, $startsWith->first(), 'starts-with', [], 1.0);

            return $startsWith->first()->id;
        }
        if ($startsWith->count() > 1) {
            throw new AgentToolValidationException(
                "Multiple project managers named \"{$name}\" found across the directory — cannot resolve unambiguously."
            );
        }

        $scored = ProjectManager::get(['id', 'name'])
            ->map(function (ProjectManager $pm) use ($needle) {
                $candidate = mb_strtolower(trim($pm->name));
                similar_text($needle, $candidate, $percent);

                return ['id' => $pm->id, 'name' => $pm->name, 'score' => $percent / 100];
            })
            ->filter(fn (array $c) => $c['score'] >= self::PM_FUZZY_THRESHOLD)
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            $this->logDirectoryPmMatch($task, $name, null, 'fuzzy', [], null);

            return null;
        }

        if ($scored->count() > 1 && ($scored[0]['score'] - $scored[1]['score']) < self::PM_FUZZY_MARGIN) {
            $this->logDirectoryPmMatch($task, $name, null, 'fuzzy', $scored->take(3)->all(), null);

            throw new AgentToolValidationException(
                "\"{$name}\" is ambiguous against the project manager directory (closest candidates: "
                . $scored->take(3)->map(fn (array $c) => "{$c['name']} (" . round($c['score'] * 100) . '%)')->implode(', ')
                . ') — cannot resolve unambiguously.'
            );
        }

        $best = $scored->first();
        $this->logDirectoryPmMatch($task, $name, (object) $best, 'fuzzy', $scored->take(3)->all(), $best['score']);

        return $best['id'];
    }

    /** @param array<int, array{id: int, name: string, score: float}> $topCandidates */
    private function logDirectoryPmMatch(
        AgentTask $task,
        string $requestedName,
        ?object $matched,
        string $pass,
        array $topCandidates,
        ?float $confidence,
    ): void {
        if ($matched !== null) {
            $confidenceNote = $confidence !== null && $confidence < 1.0 ? ' (' . round($confidence * 100) . '% similar)' : '';
            $body = "update_opportunity directory-wide PM match for \"{$requestedName}\": matched to \"{$matched->name}\" (PM #{$matched->id}) via {$pass} pass{$confidenceNote} — not scoped to this opportunity's own customer, verify this is correct.";
        } elseif (! empty($topCandidates)) {
            $candidateList = collect($topCandidates)
                ->map(fn (array $c) => "{$c['name']} (" . round($c['score'] * 100) . '%)')
                ->implode(', ');
            $body = "update_opportunity directory-wide PM match for \"{$requestedName}\": ambiguous via {$pass} pass. Candidates: {$candidateList}.";
        } else {
            $body = "update_opportunity directory-wide PM match for \"{$requestedName}\": no match found via {$pass} pass.";
        }

        AgentMessage::create([
            'task_id' => $task->id,
            'sender' => 'agent',
            'body' => $body,
        ]);
    }
}
