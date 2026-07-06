<?php

namespace App\Console\Commands;

use App\Models\Estimate;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckEstimateFollowUps extends Command
{
    protected $signature = 'estimates:check-follow-ups';
    protected $description = 'Flag sent estimates that are due for a follow-up and notify the estimator via internal message.';

    // Days after first_sent_at that each stage becomes due
    private const STAGE_DAYS = [
        1 => 7,
        2 => 14,
        3 => 30,
    ];

    public function handle(): void
    {
        $systemUser = User::role('admin')->orderBy('id')->first();

        if (! $systemUser) {
            $this->error('No admin user found — cannot send internal notifications.');
            return;
        }

        $flagged = 0;

        foreach (self::STAGE_DAYS as $stage => $days) {
            // Estimates that:
            // - have been sent (first_sent_at set)
            // - have not been closed
            // - have not yet reached this stage
            // - have no linked sale (not converted)
            // - first_sent_at is old enough for this stage to be due
            $estimates = Estimate::whereNotNull('first_sent_at')
                ->where('follow_up_closed', false)
                ->where('follow_up_stage', $stage - 1)
                ->whereDoesntHave('sale')
                ->where('first_sent_at', '<=', Carbon::now()->subDays($days))
                ->with(['creator', 'opportunity.jobSiteCustomer'])
                ->get();

            foreach ($estimates as $estimate) {
                try {
                    $this->flagEstimate($estimate, $stage, $systemUser);
                    $flagged++;
                    $this->info("Stage {$stage} flagged: Estimate #{$estimate->estimate_number}");
                } catch (\Throwable $e) {
                    Log::error('[EstimateFollowUp] Failed to flag estimate', [
                        'estimate_id' => $estimate->id,
                        'stage'       => $stage,
                        'error'       => $e->getMessage(),
                    ]);
                    $this->error("Estimate #{$estimate->estimate_number} failed: " . $e->getMessage());
                }
            }
        }

        $this->info("Done — {$flagged} estimate(s) flagged for follow-up.");
    }

    private function flagEstimate(Estimate $estimate, int $stage, User $systemUser): void
    {
        $days         = self::STAGE_DAYS[$stage];
        $customerName = $estimate->homeowner_name ?: $estimate->customer_name ?: 'Customer';
        $recipient    = $estimate->creator;

        // Advance the stage on the estimate
        $estimate->update(['follow_up_stage' => $stage]);

        // Send internal message to the creator (estimator)
        if (! $recipient) {
            return;
        }

        $subject = "Follow-up due: Estimate #{$estimate->estimate_number} — {$customerName}";
        $body    = "This estimate was sent {$days} days ago and has not been converted to a sale.\n\n"
            . "Customer: {$customerName}\n"
            . "Estimate #: {$estimate->estimate_number}\n"
            . "Total: $" . number_format($estimate->grand_total, 2) . "\n\n"
            . "Head to the Aging Estimates report to send a follow-up email or SMS.";

        DB::transaction(function () use ($subject, $body, $estimate, $systemUser, $recipient) {
            $thread = MessageThread::create([
                'subject'         => $subject,
                'created_by'      => $systemUser->id,
                'threadable_type' => Estimate::class,
                'threadable_id'   => $estimate->id,
            ]);

            // Attach both the system user (as sender/read) and the recipient
            $thread->participants()->attach([
                $systemUser->id => ['last_read_at' => now()],
                $recipient->id  => ['last_read_at' => null],
            ]);

            Message::create([
                'message_thread_id' => $thread->id,
                'sender_id'         => $systemUser->id,
                'body'              => $body,
            ]);
        });
    }
}
