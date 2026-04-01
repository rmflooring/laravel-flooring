<?php

namespace App\Console\Commands;

use App\Models\Rfm;
use App\Models\Setting;
use App\Services\GraphMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckRfmCalendarConfirmations extends Command
{
    protected $signature   = 'rfm:check-confirmations';
    protected $description = 'Mark RFMs as confirmed when the estimator has accepted the calendar invite.';

    public function handle(): void
    {
        $pendingRfms = Rfm::where('status', 'pending')
            ->whereNotNull('calendar_event_id')
            ->get();

        if ($pendingRfms->isEmpty()) {
            $this->info('No pending RFMs with calendar events — nothing to check.');
            return;
        }

        $fromEmail = Setting::get('mail_from_address', 'reception@rmflooring.ca');
        $mailer    = app(GraphMailService::class);

        // Only look at messages received since the earliest pending RFM was created
        $since = $pendingRfms->min('created_at')?->subDay()->utc()->format('Y-m-d\TH:i:s\Z');

        try {
            $messages = $mailer->getUnreadMessages($fromEmail, 100, $since);
        } catch (\Throwable $e) {
            Log::error('[RFM Confirmations] Failed to fetch inbox', ['error' => $e->getMessage()]);
            $this->error('Could not read inbox: ' . $e->getMessage());
            return;
        }

        // Only bother fetching MIME for messages that look like calendar accepts
        $candidates = array_filter(
            $messages,
            fn($m) => stripos($m['subject'] ?? '', 'accepted') !== false
        );

        if (empty($candidates)) {
            $this->info('No accepted calendar replies in inbox.');
            return;
        }

        // Index pending RFMs by ID for quick lookup
        $rfmMap    = $pendingRfms->keyBy('id');
        $confirmed = 0;

        foreach ($candidates as $message) {
            try {
                $mime = $mailer->getMessageMime($fromEmail, $message['id']);

                // Find our RFM UID — e.g. "rfm-4@rmflooring.ca"
                if (! preg_match('/UID[;:][^\r\n]*rfm-(\d+)@rmflooring\.ca/i', $mime, $uidMatch)) {
                    continue;
                }

                $rfmId = (int) $uidMatch[1];
                $rfm   = $rfmMap->get($rfmId);

                if (! $rfm) {
                    continue; // Already confirmed/cancelled, or not a pending RFM we manage
                }

                // Verify the reply actually says ACCEPTED (not tentative/declined)
                if (! preg_match('/PARTSTAT=ACCEPTED/i', $mime)) {
                    continue;
                }

                $rfm->update(['status' => 'confirmed']);
                $mailer->markMessageRead($fromEmail, $message['id']);

                $confirmed++;

                Log::info('[RFM Confirmations] RFM confirmed via inbox reply', [
                    'rfm_id'  => $rfmId,
                    'subject' => $message['subject'],
                ]);

                $this->info("RFM #{$rfmId} confirmed (estimator accepted invite).");

            } catch (\Throwable $e) {
                Log::warning('[RFM Confirmations] Error processing message', [
                    'message_id' => $message['id'] ?? '?',
                    'error'      => $e->getMessage(),
                ]);
                $this->warn('Skipped message: ' . $e->getMessage());
            }
        }

        $this->info("Done — {$confirmed} RFM(s) confirmed.");
    }
}
