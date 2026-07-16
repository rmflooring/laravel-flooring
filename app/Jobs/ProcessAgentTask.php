<?php

namespace App\Jobs;

use App\Models\AgentMessage;
use App\Models\AgentNotification;
use App\Models\AgentNotificationSetting;
use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Services\Agent\AgentToolRegistry;
use App\Services\Agent\AgentToolValidationException;
use App\Services\Agent\AttachDocumentService;
use App\Services\Agent\AttachImagesService;
use App\Services\Agent\ClaudeAgentService;
use App\Services\Agent\FindOpportunityService;
use App\Services\Agent\UpdateOpportunityService;
use App\Services\GraphMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAgentTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    private const MAX_TOOL_ITERATIONS = 5;

    private const SYSTEM_PROMPT = <<<'TEXT'
        You are the Floor Manager AI Agent. You process staff emails forwarded to the
        agent inbox and take action via a small set of predefined tools — you never
        have direct database or shell access.

        If no opportunity is already resolved for this task and the email appears to
        reference an existing job, call find_opportunity first with whatever of client
        name, job site address, or claim number the email actually mentions. If it
        returns an ambiguous or empty result, use request_clarification rather than
        guessing — do not call any other tool with an opportunity_id you're not certain of.

        Only call attach_images when the email is clearly about the opportunity already
        resolved for this task and contains photo attachments. Only call attach_document
        when the email contains a non-photo document attachment (PDF, Word doc, or
        scanned document) such as a scope of work, contract, or insurance certificate,
        and is clearly about the opportunity already resolved for this task.

        Only call update_opportunity for the two fields it supports: whether an RFM
        (site measure) is required, and assigning a project manager by name. It requires
        an opportunity already resolved for this task. Any other requested change
        (status, job number, sales person, customer details, etc.) is out of scope —
        use request_clarification or no_actionable_intent instead.

        If you cannot confidently determine what's being asked, or the email doesn't
        relate to the resolved opportunity, call request_clarification with a specific
        question. If the email is not an actionable request at all (spam, newsletter,
        unrelated forward), call no_actionable_intent. Call exactly one tool to conclude
        the task (find_opportunity does not conclude the task — keep reasoning after it).
        TEXT;

    public function __construct(public readonly int $taskId) {}

    public function handle(
        ClaudeAgentService $claude,
        AttachImagesService $attachImages,
        AttachDocumentService $attachDocument,
        FindOpportunityService $findOpportunity,
        UpdateOpportunityService $updateOpportunity,
        GraphMailService $mailer,
    ): void {
        $task = AgentTask::find($this->taskId);
        if (! $task) {
            return;
        }

        $this->resolveOpportunity($task);

        $userContent = $this->buildUserMessage($task);
        $messages = [['role' => 'user', 'content' => $userContent]];

        $result = null;

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $claude->sendWithTools($messages, AgentToolRegistry::forEmail(), self::SYSTEM_PROMPT);
            $content = $response['content'] ?? [];
            $stopReason = $response['stop_reason'] ?? null;

            $toolUses = array_values(array_filter($content, fn (array $b) => ($b['type'] ?? null) === 'tool_use'));

            if ($stopReason !== 'tool_use' || empty($toolUses)) {
                $result = $this->finalizeFromText($task, $content);
                break;
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolResults = [];
            $terminal = null;
            foreach ($toolUses as $toolUse) {
                [$toolResult, $maybeTerminal] = $this->dispatchTool(
                    $task,
                    $attachImages,
                    $attachDocument,
                    $findOpportunity,
                    $updateOpportunity,
                    $toolUse,
                );
                $toolResults[] = $toolResult;
                if ($maybeTerminal !== null) {
                    $terminal = $maybeTerminal;
                }
            }
            $messages[] = ['role' => 'user', 'content' => $toolResults];

            if ($terminal !== null) {
                $result = $terminal;
                break;
            }
        }

        if ($result === null) {
            // Ran out of iterations without a terminal tool call — don't guess.
            $result = ['status' => 'pending_clarification', 'summary' => 'Could not resolve the request automatically.', 'task_type' => 'other'];
            $this->logMessage($task, 'agent', $result['summary']);
        }

        $task->status = $result['status'];
        $task->extracted_intent = $result['summary'];
        $task->task_type = $result['task_type'] ?? 'other';
        $task->save();

        $this->notifyRequester($task, $mailer, $result);
    }

    /**
     * Minimal deterministic job-number lookup, run before Claude sees the email at all.
     * Job numbers look like "26-0001". This is a fast path for the common case where the
     * job number is right there in the email — Claude's find_opportunity tool (Module 3)
     * handles everything else (name/address/claim number fuzzy matching).
     */
    private function resolveOpportunity(AgentTask $task): void
    {
        $haystack = ($task->raw_content ?? '');
        if (! preg_match_all('/\b\d{2}-\d{4}\b/', $haystack, $matches)) {
            return;
        }

        $candidates = array_unique($matches[0]);
        $opportunities = Opportunity::whereIn('job_no', $candidates)->get(['id', 'job_no']);

        if ($opportunities->count() === 1) {
            $task->opportunity_id = $opportunities->first()->id;
            $task->save();
        }
    }

    private function buildUserMessage(AgentTask $task): string
    {
        $attachmentList = collect($task->attachments ?? [])
            ->map(fn (array $a, int $i) => "  [{$i}] {$a['original_name']} ({$a['mime_type']}, " . number_format($a['size']) . ' bytes)')
            ->implode("\n");

        $opportunityLine = $task->opportunity_id
            ? "Resolved opportunity_id: {$task->opportunity_id}"
            : 'Resolved opportunity_id: none (no unambiguous job number found in the email)';

        return <<<TEXT
            From: {$task->requester_email}
            {$opportunityLine}

            Email content:
            {$task->raw_content}

            Attachments:
            {$attachmentList}
            TEXT;
    }

    /**
     * @return array{0: array, 1: ?array} [tool_result content block, terminal result or null]
     */
    private function dispatchTool(
        AgentTask $task,
        AttachImagesService $attachImages,
        AttachDocumentService $attachDocument,
        FindOpportunityService $findOpportunity,
        UpdateOpportunityService $updateOpportunity,
        array $toolUse,
    ): array {
        $name = $toolUse['name'];
        $input = $toolUse['input'] ?? [];
        $toolUseId = $toolUse['id'];

        $this->logMessage($task, 'agent', "Called tool `{$name}` with input: " . json_encode($input));

        try {
            switch ($name) {
                case 'attach_images':
                    $summary = $attachImages->execute(
                        $task,
                        (int) ($input['opportunity_id'] ?? 0),
                        array_map('intval', $input['attachment_indices'] ?? []),
                        $input['label'] ?? null,
                        $input['category'] ?? '',
                    );
                    $terminal = [
                        'status' => 'completed',
                        'summary' => "Attached {$summary['count']} image(s) as \"{$input['category']}\" to opportunity {$task->opportunity_id}.",
                        'task_type' => 'attach_images',
                    ];
                    $this->logMessage($task, 'agent', $terminal['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($summary)],
                        $terminal,
                    ];

                case 'attach_document':
                    $summary = $attachDocument->execute(
                        $task,
                        (int) ($input['opportunity_id'] ?? 0),
                        (int) ($input['attachment_index'] ?? -1),
                        $input['label'] ?? null,
                        $input['document_type'] ?? '',
                    );
                    $terminal = [
                        'status' => 'completed',
                        'summary' => "Attached document as \"{$input['document_type']}\" to opportunity {$task->opportunity_id}.",
                        'task_type' => 'attach_document',
                    ];
                    $this->logMessage($task, 'agent', $terminal['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($summary)],
                        $terminal,
                    ];

                case 'find_opportunity':
                    $found = $findOpportunity->execute(
                        $task,
                        $input['client_name'] ?? null,
                        $input['address'] ?? null,
                        $input['claim_number'] ?? null,
                    );

                    // Not terminal — Claude keeps reasoning with the (possibly newly
                    // resolved) opportunity_id in subsequent tool calls.
                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($found)],
                        null,
                    ];

                case 'update_opportunity':
                    $updated = $updateOpportunity->execute(
                        $task,
                        (int) ($input['opportunity_id'] ?? 0),
                        array_key_exists('requires_rfm', $input) ? (bool) $input['requires_rfm'] : null,
                        $input['project_manager_name'] ?? null,
                    );
                    $changeList = collect($updated['changes'])
                        ->map(fn ($v, $k) => "{$k}=" . (is_bool($v) ? ($v ? 'true' : 'false') : $v))
                        ->implode(', ');
                    $terminal = [
                        'status' => 'completed',
                        'summary' => "Updated opportunity {$task->opportunity_id}: {$changeList}.",
                        'task_type' => 'update_opportunity',
                    ];
                    $this->logMessage($task, 'agent', $terminal['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($updated)],
                        $terminal,
                    ];

                case 'request_clarification':
                    $question = $input['question'] ?? 'Could you clarify this request?';
                    $this->logMessage($task, 'agent', $question);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => 'Question recorded.'],
                        ['status' => 'pending_clarification', 'summary' => $question, 'task_type' => 'other'],
                    ];

                case 'no_actionable_intent':
                    $summary = "Couldn't determine an actionable request in this email.";
                    $this->logMessage($task, 'agent', $summary);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => 'Acknowledged.'],
                        ['status' => 'ignored', 'summary' => $summary, 'task_type' => 'no_actionable_intent'],
                    ];

                default:
                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => "Unknown tool \"{$name}\".", 'is_error' => true],
                        null,
                    ];
            }
        } catch (AgentToolValidationException $e) {
            $this->logMessage($task, 'agent', "Tool `{$name}` failed validation: {$e->getMessage()}");

            return [
                ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => $e->getMessage(), 'is_error' => true],
                null,
            ];
        }
    }

    private function finalizeFromText(AgentTask $task, array $content): array
    {
        $text = collect($content)
            ->filter(fn (array $b) => ($b['type'] ?? null) === 'text')
            ->pluck('text')
            ->implode("\n");

        $summary = $text !== '' ? $text : 'No actionable tool call was made.';
        $this->logMessage($task, 'agent', $summary);

        return ['status' => 'pending_clarification', 'summary' => $summary, 'task_type' => 'other'];
    }

    private function logMessage(AgentTask $task, string $sender, string $body): void
    {
        AgentMessage::create(['task_id' => $task->id, 'sender' => $sender, 'body' => $body]);
    }

    private function notifyRequester(AgentTask $task, GraphMailService $mailer, array $result): void
    {
        if (! $task->requester_email) {
            return;
        }

        $subject = match ($result['status']) {
            'completed' => 'Your request has been completed',
            'pending_clarification' => 'We need a bit more info',
            default => "We couldn't process your request",
        };

        $dashboardUrl = url('/pages/agent-tasks/' . $task->id);

        $body = match ($result['status']) {
            'completed' => "Done — {$result['summary']}\n\nView details: {$dashboardUrl}",
            'pending_clarification' => "Got your request — we need a bit more info before we can proceed:\n\n{$result['summary']}\n\nRespond here: {$dashboardUrl}",
            default => "We couldn't determine what you'd like us to do with that email.\n\nIf this was a mistake, reply with more detail or check: {$dashboardUrl}",
        };

        $sent = $mailer->send($task->requester_email, $subject, $body, 'agent_task_' . $result['status']);
        if ($sent) {
            AgentNotification::create(['task_id' => $task->id, 'sent_to' => $task->requester_email, 'type' => 'requester_reply']);
        }

        $settings = AgentSetting::current();
        if ($settings->admin_notification_email
            && AgentNotificationSetting::bccEnabledFor($task->task_type)
        ) {
            $bccSent = $mailer->send($settings->admin_notification_email, '[Agent BCC] ' . $subject, $body, 'agent_task_bcc');
            if ($bccSent) {
                AgentNotification::create([
                    'task_id' => $task->id,
                    'sent_to' => $settings->admin_notification_email,
                    'type' => 'bcc_admin',
                ]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Agent] ProcessAgentTask job failed permanently', [
            'task_id' => $this->taskId,
            'message' => $e->getMessage(),
        ]);

        $task = AgentTask::find($this->taskId);
        if ($task) {
            $task->status = 'failed';
            $task->save();
        }
    }
}
