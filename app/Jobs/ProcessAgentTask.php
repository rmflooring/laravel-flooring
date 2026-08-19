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
use App\Services\Agent\CheckStatusService;
use App\Services\Agent\ClaudeAgentService;
use App\Services\Agent\CreateOpportunityService;
use App\Services\Agent\FindOpportunityService;
use App\Services\Agent\LogCommunicationService;
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
        name, job site address, claim number, or job/reference number the email actually
        mentions — this includes a fresh, standalone email that simply asks you to act on
        an existing job by number (e.g. "please update job # 00705807..."), not just
        emails that are replies to something you already sent. If it returns an ambiguous
        or empty result, use request_clarification rather than guessing — do not call any
        other tool with an opportunity_id you're not certain of.

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

        A referral company's job information often comes as structured data (a table or
        labeled fields) rather than a sentence — treat a "PM Contact"/"Project Manager"
        field the same as an explicit instruction to assign that person, and call
        update_opportunity with it (after create_opportunity/find_opportunity has resolved
        the opportunity) just as you would for "please set the PM to X". Don't require the
        email to phrase it as a request — a labeled field in the referrer's own job data is
        the instruction.

        Only call create_opportunity if find_opportunity has already been tried and found
        nothing (or only low-confidence matches), and the email is clearly about a job
        that does not exist in Floor Manager yet — never call it when an opportunity is
        already resolved for this task. A duplicate check runs automatically; if it
        blocks creation, use request_clarification rather than retrying or forcing it.

        Call check_status when the email is a status inquiry about the opportunity
        already resolved for this task (e.g. "any update on this job?", "what's the
        status of the claim?") — it is read-only and its result becomes the reply, so
        don't also call log_communication for the same email.

        Call log_communication when the email is clearly about the opportunity already
        resolved for this task and contains information worth preserving on the activity
        log (a client update, an adjuster call, a vendor note, etc.) that isn't better
        captured by attach_images, attach_document, or update_opportunity. Don't log
        communication that's purely a status inquiry — use check_status for that instead.

        An email can ask for more than one thing at once — e.g. create a new opportunity
        AND attach photos that were included AND assign a project manager, all from one
        forward. Do all of it: keep calling tools (attach_images/attach_document for any
        attachments, update_opportunity for a mentioned PM or RFM need, log_communication
        for anything else worth recording) until you've addressed everything the email
        actually asked for, then stop calling tools — you don't need to announce that
        you're done, just stop. Only find_opportunity is purely a lookup step you always
        continue past. If nothing more is needed after one action, stop after that one.

        If you cannot confidently determine what's being asked, or the email doesn't
        relate to the resolved opportunity, call request_clarification with a specific
        question — this ends the task immediately, so only call it once you're sure no
        further action should be taken. If the email is not an actionable request at all
        (spam, newsletter, unrelated forward), call no_actionable_intent, which also ends
        the task immediately.
        TEXT;

    public function __construct(public readonly int $taskId) {}

    public function handle(
        ClaudeAgentService $claude,
        AttachImagesService $attachImages,
        AttachDocumentService $attachDocument,
        FindOpportunityService $findOpportunity,
        UpdateOpportunityService $updateOpportunity,
        CreateOpportunityService $createOpportunity,
        LogCommunicationService $logCommunication,
        CheckStatusService $checkStatus,
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
        $completedActions = [];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $claude->sendWithTools($messages, AgentToolRegistry::forEmail(), self::SYSTEM_PROMPT);
            $content = $response['content'] ?? [];
            $stopReason = $response['stop_reason'] ?? null;

            $toolUses = array_values(array_filter($content, fn (array $b) => ($b['type'] ?? null) === 'tool_use'));

            if ($stopReason !== 'tool_use' || empty($toolUses)) {
                $result = $this->finalize($task, $content, $completedActions);
                break;
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolResults = [];
            $hardStop = null;
            foreach ($toolUses as $toolUse) {
                [$toolResult, $event] = $this->dispatchTool(
                    $task,
                    $attachImages,
                    $attachDocument,
                    $findOpportunity,
                    $updateOpportunity,
                    $createOpportunity,
                    $logCommunication,
                    $checkStatus,
                    $toolUse,
                );
                $toolResults[] = $toolResult;

                if ($event === null) {
                    continue;
                }
                if ($event['terminal']) {
                    $hardStop = $event;
                } else {
                    $completedActions[] = $event;
                }
            }
            $messages[] = ['role' => 'user', 'content' => $toolResults];

            if ($hardStop !== null) {
                $result = $this->finalizeHardStop($hardStop, $completedActions);
                break;
            }
        }

        if ($result === null) {
            // Ran out of iterations. If real actions already happened, report them rather
            // than discarding that work — otherwise fall back to the old "couldn't
            // resolve" message.
            $result = empty($completedActions)
                ? ['status' => 'pending_clarification', 'summary' => 'Could not resolve the request automatically.', 'task_type' => 'other']
                : $this->finalizeFromActions($completedActions);

            if (empty($completedActions)) {
                $this->logMessage($task, 'agent', $result['summary']);
            }
        }

        $task->status = $result['status'];
        $task->extracted_intent = $result['summary'];
        $task->task_type = $result['task_type'] ?? 'other';
        $task->undo_data = $result['undo_data'] ?? null;
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

        $base = <<<TEXT
            From: {$task->requester_email}
            {$opportunityLine}

            Email content:
            {$task->raw_content}

            Attachments:
            {$attachmentList}
            TEXT;

        return $base . $this->buildPriorThreadSummary($task);
    }

    /**
     * On a resumed task (e.g. a dashboard reply to a pending_clarification task), the
     * conversation object built for the earlier run isn't retained — the raw tool_use/
     * tool_result blocks Claude needs for a true multi-turn history were never stored
     * (agent_messages only logs human-readable summaries). Instead, fold the existing
     * thread in as plain-text context on this fresh run's single user turn, so Claude
     * sees what it already asked and how the user answered before it reasons/re-calls
     * tools — otherwise a reply would just replay the original email and likely ask the
     * same clarifying question again.
     */
    private function buildPriorThreadSummary(AgentTask $task): string
    {
        $messages = $task->messages()->orderBy('created_at')->get();

        if ($messages->isEmpty()) {
            return '';
        }

        $thread = $messages
            ->map(fn (AgentMessage $m) => '[' . ($m->sender === 'user' ? 'user reply' : 'you, previously') . "]: {$m->body}")
            ->implode("\n");

        return <<<TEXT


            This task has already been worked on. Here is the prior thread, oldest first
            (your own earlier questions/actions, and any reply from the user) — take it
            into account instead of starting over, and do not ask a question that's
            already been answered below:
            {$thread}
            TEXT;
    }

    /**
     * @return array{0: array, 1: ?array} [tool_result content block, event or null]
     *     Event shape: {terminal: bool, status?, summary, task_type, undo_data?}.
     *     terminal=true (request_clarification/no_actionable_intent) ends the task
     *     immediately. terminal=false (every write action) is accumulated into
     *     $completedActions by the caller and the loop continues. null (find_opportunity,
     *     unknown tools, validation errors) means no event — Claude just keeps reasoning.
     */
    private function dispatchTool(
        AgentTask $task,
        AttachImagesService $attachImages,
        AttachDocumentService $attachDocument,
        FindOpportunityService $findOpportunity,
        UpdateOpportunityService $updateOpportunity,
        CreateOpportunityService $createOpportunity,
        LogCommunicationService $logCommunication,
        CheckStatusService $checkStatus,
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
                    $event = [
                        'terminal' => false,
                        'summary' => "Attached {$summary['count']} image(s) as \"{$input['category']}\" to opportunity {$task->opportunity_id}.",
                        'task_type' => 'attach_images',
                        'undo_data' => ['type' => 'attach_images', 'document_ids' => $summary['document_ids']],
                    ];
                    $this->logMessage($task, 'agent', $event['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($summary)],
                        $event,
                    ];

                case 'attach_document':
                    $summary = $attachDocument->execute(
                        $task,
                        (int) ($input['opportunity_id'] ?? 0),
                        (int) ($input['attachment_index'] ?? -1),
                        $input['label'] ?? null,
                        $input['document_type'] ?? '',
                    );
                    $event = [
                        'terminal' => false,
                        'summary' => "Attached document as \"{$input['document_type']}\" to opportunity {$task->opportunity_id}.",
                        'task_type' => 'attach_document',
                        'undo_data' => ['type' => 'attach_document', 'document_ids' => [$summary['document_id']]],
                    ];
                    $this->logMessage($task, 'agent', $event['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($summary)],
                        $event,
                    ];

                case 'find_opportunity':
                    $found = $findOpportunity->execute(
                        $task,
                        $input['client_name'] ?? null,
                        $input['address'] ?? null,
                        $input['claim_number'] ?? null,
                        $input['job_no'] ?? null,
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
                    $event = [
                        'terminal' => false,
                        'summary' => "Updated opportunity {$task->opportunity_id}: {$changeList}.",
                        'task_type' => 'update_opportunity',
                        'undo_data' => [
                            'type' => 'update_opportunity',
                            'opportunity_id' => $updated['opportunity_id'],
                            'previous_values' => $updated['previous_values'],
                        ],
                    ];
                    $this->logMessage($task, 'agent', $event['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($updated)],
                        $event,
                    ];

                case 'create_opportunity':
                    $created = $createOpportunity->execute(
                        $task,
                        (string) ($input['client_name'] ?? ''),
                        $input['parent_customer_name'] ?? null,
                        $input['address'] ?? null,
                        $input['claim_number'] ?? null,
                        $input['insurance_company'] ?? null,
                        $input['adjuster'] ?? null,
                        $input['policy_number'] ?? null,
                        $input['dol'] ?? null,
                        array_key_exists('requires_rfm', $input) ? (bool) $input['requires_rfm'] : null,
                        $input['job_no'] ?? null,
                    );
                    $intakeNote = empty($created['incomplete_intake_fields'])
                        ? ''
                        : ' (incomplete intake — missing: ' . implode(', ', $created['incomplete_intake_fields']) . ')';
                    $event = [
                        'terminal' => false,
                        'summary' => "Created opportunity {$created['opportunity_id']} for customer {$created['customer_id']}.{$intakeNote}",
                        'task_type' => 'create_opportunity',
                    ];
                    $this->logMessage($task, 'agent', $event['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($created)],
                        $event,
                    ];

                case 'log_communication':
                    $logged = $logCommunication->execute(
                        $task,
                        (int) ($input['opportunity_id'] ?? 0),
                        (string) ($input['summary'] ?? ''),
                        $input['from'] ?? null,
                        (string) ($input['category'] ?? ''),
                    );
                    $event = [
                        'terminal' => false,
                        'summary' => "Logged {$logged['category']} communication on opportunity {$task->opportunity_id}.",
                        'task_type' => 'log_communication',
                        'undo_data' => ['type' => 'log_communication', 'note_id' => $logged['note_id']],
                    ];
                    $this->logMessage($task, 'agent', $event['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($logged)],
                        $event,
                    ];

                case 'check_status':
                    $status = $checkStatus->execute($task, (int) ($input['opportunity_id'] ?? 0));
                    $event = [
                        'terminal' => false,
                        'summary' => $this->formatStatusSummary($status),
                        'task_type' => 'check_status',
                    ];
                    $this->logMessage($task, 'agent', $event['summary']);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => json_encode($status)],
                        $event,
                    ];

                case 'request_clarification':
                    $question = $input['question'] ?? 'Could you clarify this request?';
                    $this->logMessage($task, 'agent', $question);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => 'Question recorded.'],
                        ['terminal' => true, 'status' => 'pending_clarification', 'summary' => $question, 'task_type' => 'other'],
                    ];

                case 'no_actionable_intent':
                    $summary = "Couldn't determine an actionable request in this email.";
                    $this->logMessage($task, 'agent', $summary);

                    return [
                        ['type' => 'tool_result', 'tool_use_id' => $toolUseId, 'content' => 'Acknowledged.'],
                        ['terminal' => true, 'status' => 'ignored', 'summary' => $summary, 'task_type' => 'no_actionable_intent'],
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

    /**
     * Turns CheckStatusService's structured result into the plain-text summary that
     * becomes the auto-reply email answering a status-inquiry.
     */
    private function formatStatusSummary(array $status): string
    {
        $lines = [];
        $lines[] = 'Job ' . ($status['job_no'] ?? '(no job number)') . ' — status: ' . ($status['status'] ?? 'unknown')
            . ($status['status_reason'] ? " ({$status['status_reason']})" : '');
        $lines[] = 'Project manager: ' . ($status['project_manager'] ?? 'not assigned');

        if ($status['requires_rfm']) {
            $rfmLine = 'RFM (site measure): ';
            $rfmLine .= $status['latest_rfm_status']
                ? ucfirst($status['latest_rfm_status'])
                : 'required, not yet scheduled';
            if ($status['latest_rfm_scheduled_at']) {
                $rfmLine .= ' — scheduled ' . $status['latest_rfm_scheduled_at'];
            }
            $lines[] = $rfmLine;
        }

        if ($status['latest_estimate_status']) {
            $lines[] = 'Latest estimate status: ' . $status['latest_estimate_status'];
        }
        if ($status['latest_sale_status']) {
            $lines[] = 'Latest sale status: ' . $status['latest_sale_status'];
        }

        return implode("\n", $lines);
    }

    /**
     * Claude stopped calling tools (returned final text, or none at all). If it had
     * already completed real actions this task, that's a normal, successful multi-action
     * conclusion — not a failure to resolve anything.
     *
     * @param  array<int, array{summary: string, task_type: string, undo_data?: array}>  $completedActions
     */
    private function finalize(AgentTask $task, array $content, array $completedActions): array
    {
        $text = collect($content)
            ->filter(fn (array $b) => ($b['type'] ?? null) === 'text')
            ->pluck('text')
            ->implode("\n");

        if (empty($completedActions)) {
            $summary = $text !== '' ? $text : 'No actionable tool call was made.';
            $this->logMessage($task, 'agent', $summary);

            return ['status' => 'pending_clarification', 'summary' => $summary, 'task_type' => 'other'];
        }

        $result = $this->finalizeFromActions($completedActions);
        if ($text !== '') {
            $result['summary'] = trim($result['summary'] . ' ' . $text);
        }

        return $result;
    }

    /**
     * @param  array<int, array{summary: string, task_type: string, undo_data?: array}>  $completedActions
     */
    private function finalizeFromActions(array $completedActions): array
    {
        return [
            'status' => 'completed',
            'summary' => collect($completedActions)->pluck('summary')->implode(' '),
            'task_type' => $completedActions[0]['task_type'],
            'undo_data' => collect($completedActions)->pluck('undo_data')->filter()->values()->all() ?: null,
        ];
    }

    /**
     * A hard-stop tool (request_clarification/no_actionable_intent) ended the task. If
     * actions had already completed earlier in the same task, fold their summaries in so
     * real completed work isn't silently hidden from the reply — but the hard-stop's own
     * status (pending_clarification/ignored) still governs, since there's now an open
     * question or the rest was deemed not actionable.
     *
     * @param  array{status: string, summary: string, task_type: string}  $hardStop
     * @param  array<int, array{summary: string, task_type: string, undo_data?: array}>  $completedActions
     */
    private function finalizeHardStop(array $hardStop, array $completedActions): array
    {
        if (empty($completedActions)) {
            return [
                'status' => $hardStop['status'],
                'summary' => $hardStop['summary'],
                'task_type' => $hardStop['task_type'],
            ];
        }

        $priorSummary = collect($completedActions)->pluck('summary')->implode(' ');

        return [
            'status' => $hardStop['status'],
            'summary' => trim($priorSummary . ' ' . $hardStop['summary']),
            'task_type' => $completedActions[0]['task_type'],
            'undo_data' => collect($completedActions)->pluck('undo_data')->filter()->values()->all() ?: null,
        ];
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

        $dashboardUrl = route('admin.agent.tasks.show', $task);

        $agentMailbox = env('AGENT_INBOUND_MAILBOX', 'agent@rmflooring.ca');

        $body = match ($result['status']) {
            'completed' => "Done — {$result['summary']}\n\n"
                . "If anything needs fixing — wrong details, a correction, additional info — just reply to this "
                . "email (or send a new one to {$agentMailbox}) mentioning the job number or client name and what "
                . "should change.\n\n"
                . "View details: {$dashboardUrl}",
            'pending_clarification' => "Got your request — we need a bit more info before we can proceed:\n\n{$result['summary']}\n\nReply to this email, or respond here: {$dashboardUrl}",
            default => "We couldn't determine what you'd like us to do with that email.\n\nIf this was a mistake, reply with more detail or check: {$dashboardUrl}",
        };

        $sent = $mailer->send($task->requester_email, $subject, $body, 'agent_task_' . $result['status'], $agentMailbox, replyTo: $agentMailbox);
        if ($sent) {
            AgentNotification::create(['task_id' => $task->id, 'sent_to' => $task->requester_email, 'type' => 'requester_reply']);
        }

        $settings = AgentSetting::current();
        if ($settings->admin_notification_email
            && AgentNotificationSetting::bccEnabledFor($task->task_type)
        ) {
            $bccSent = $mailer->send($settings->admin_notification_email, '[Agent BCC] ' . $subject, $body, 'agent_task_bcc', replyTo: $agentMailbox);
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
