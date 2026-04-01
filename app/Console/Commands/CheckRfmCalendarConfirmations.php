<?php

namespace App\Console\Commands;

use App\Models\MicrosoftAccount;
use App\Models\Rfm;
use App\Services\GraphCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckRfmCalendarConfirmations extends Command
{
    protected $signature   = 'rfm:check-confirmations';
    protected $description = 'Mark RFMs as confirmed when the estimator has accepted the calendar invite.';

    public function handle(): void
    {
        // Pending RFMs that have a calendar event synced to MS365
        $rfms = Rfm::with(['estimator', 'calendarEvent.externalLink'])
            ->where('status', 'pending')
            ->whereNotNull('calendar_event_id')
            ->get();

        if ($rfms->isEmpty()) {
            $this->info('No pending RFMs with calendar events — nothing to check.');
            return;
        }

        $service   = new GraphCalendarService();
        $confirmed = 0;

        foreach ($rfms as $rfm) {
            $link = $rfm->calendarEvent?->externalLink;

            if (! $link) {
                continue;
            }

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if (! $account) {
                continue;
            }

            // Estimator email — we only care about their response
            $estimatorEmail = strtolower(trim($rfm->estimator?->email ?? ''));

            try {
                $attendees = $service->getEventAttendees($account, $link);

                $response = $attendees[$estimatorEmail] ?? null;

                if ($response === 'accepted') {
                    $rfm->update(['status' => 'confirmed']);
                    $confirmed++;

                    Log::info('[RFM Confirmations] RFM confirmed via calendar accept', [
                        'rfm_id'          => $rfm->id,
                        'estimator_email' => $estimatorEmail,
                    ]);

                    $this->info("RFM #{$rfm->id} confirmed (estimator accepted).");
                }
            } catch (\Throwable $e) {
                Log::warning('[RFM Confirmations] Failed to check attendees', [
                    'rfm_id' => $rfm->id,
                    'error'  => $e->getMessage(),
                ]);
                $this->warn("RFM #{$rfm->id} skipped: " . $e->getMessage());
            }
        }

        $this->info("Done — {$confirmed} RFM(s) confirmed out of {$rfms->count()} checked.");
    }
}
