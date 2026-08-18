<?php

namespace App\Console\Commands;

use App\Services\GraphMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckAgentInboundMail extends Command
{
    protected $signature = 'agent:check-inbound-mail';
    protected $description = 'Poll the agent inbox via Microsoft Graph and forward new messages to the AI Agent inbound webhook.';

    public function handle(GraphMailService $mailer): void
    {
        $mailbox = env('AGENT_INBOUND_MAILBOX', 'agent@rmflooring.ca');
        $apiKey = env('AGENT_INBOUND_API_KEY');

        if (! $apiKey) {
            $this->error('AGENT_INBOUND_API_KEY is not configured — cannot forward to the webhook.');
            return;
        }

        try {
            $messages = $mailer->getUnreadMessages($mailbox, 50);
        } catch (\Throwable $e) {
            Log::error('[Agent Inbound Mail] Failed to fetch inbox', ['error' => $e->getMessage()]);
            $this->error('Could not read inbox: ' . $e->getMessage());
            return;
        }

        if (empty($messages)) {
            $this->info('No unread messages in agent inbox.');
            return;
        }

        $forwarded = 0;

        foreach ($messages as $message) {
            $messageId = $message['id'];

            try {
                $full = $mailer->getMessageWithAttachments($mailbox, $messageId);

                $from = $full['from']['emailAddress']['address'] ?? null;
                if (! $from) {
                    Log::warning('[Agent Inbound Mail] Message has no sender address, skipping', ['message_id' => $messageId]);
                    $mailer->markMessageRead($mailbox, $messageId);
                    continue;
                }

                $subject = $full['subject'] ?? '';
                $body = $this->extractPlainBody($full['body'] ?? []);
                $attachments = $this->realAttachments($full['attachments'] ?? []);

                $request = Http::withToken($apiKey)->asMultipart();
                foreach ($attachments as $attachment) {
                    $request->attach('attachments[]', $attachment['contents'], $attachment['name']);
                }

                $response = $request->post(config('app.url') . '/api/agent/inbound-email', [
                    'from' => $from,
                    'subject' => $subject,
                    'body' => $body,
                ]);

                if (! $response->successful()) {
                    Log::warning('[Agent Inbound Mail] Webhook rejected message, will retry next run', [
                        'message_id' => $messageId,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    $this->warn("Message {$messageId} not forwarded (status {$response->status()}) — left unread for retry.");
                    continue;
                }

                $mailer->markMessageRead($mailbox, $messageId);
                $forwarded++;

            } catch (\Throwable $e) {
                Log::error('[Agent Inbound Mail] Error processing message, will retry next run', [
                    'message_id' => $messageId,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("Skipped message {$messageId}: " . $e->getMessage());
            }
        }

        $this->info("Done — {$forwarded} of " . count($messages) . ' message(s) forwarded.');
    }

    /**
     * Graph gives HTML or plain text depending on the sender's client. The webhook
     * (and the raw_content Claude sees) expects plain text, so strip tags for HTML bodies.
     */
    private function extractPlainBody(array $body): string
    {
        $content = $body['content'] ?? '';

        if (($body['contentType'] ?? 'text') === 'html') {
            // Insert a separator at common block/cell boundaries before stripping tags —
            // otherwise adjacent table cells or block elements collapse into run-on text
            // with no space between them at all, e.g. a two-column "job information"
            // table's <td>PM Contact</td><td>Andrew Bou-Antoun</td> becomes
            // "PM ContactAndrew Bou-Antoun". Several real referral-partner emails use
            // exactly this kind of table for structured job data (PM contact, PO #,
            // schedule, etc.) — found live when a real PM Contact field got missed
            // because of this.
            $content = preg_replace('/<\/?(?:td|tr|div|p|br|th|li|h[1-6])[^>]*>/i', ' ', $content);
            $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5);
            $content = preg_replace('/[ \t]+/', ' ', $content);
            $content = preg_replace('/\n{3,}/', "\n\n", trim($content));
        }

        return trim($content);
    }

    /**
     * Only Graph fileAttachments with isInline=false are real attachments — inline
     * images (signature logos, etc.) are excluded, per the spec's guardrail that
     * inline body images must be handled separately from true attachments. Graph
     * flags this for us directly, so no MIME/cid parsing is needed.
     */
    private function realAttachments(array $attachments): array
    {
        return collect($attachments)
            ->filter(fn (array $a) => ($a['@odata.type'] ?? null) === '#microsoft.graph.fileAttachment'
                && ! ($a['isInline'] ?? false)
                && ! empty($a['contentBytes']))
            ->map(fn (array $a) => [
                'name' => $a['name'] ?? 'attachment',
                'contents' => base64_decode($a['contentBytes']),
            ])
            ->values()
            ->all();
    }
}
