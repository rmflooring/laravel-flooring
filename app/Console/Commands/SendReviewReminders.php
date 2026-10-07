<?php

namespace App\Console\Commands;

use App\Models\ReviewRequest;
use App\Services\ReviewRequestService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendReviewReminders extends Command
{
    protected $signature   = 'reviews:send-reminders';
    protected $description = 'Send a one-time reminder for review requests still unrated 7 days after being sent.';

    const REMINDER_AFTER_DAYS = 7;

    public function handle(ReviewRequestService $reviewService, SmsService $sms): void
    {
        $cutoff = now()->subDays(self::REMINDER_AFTER_DAYS);

        $pending = ReviewRequest::whereNull('submitted_at')
            ->whereNull('reminder_sent_at')
            ->where('created_at', '<=', $cutoff)
            ->with('opportunity')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No review requests need a reminder.');
            return;
        }

        $sent = 0;

        foreach ($pending as $review) {
            $url = $review->publicUrl();

            try {
                if ($review->sent_via === 'sms' && $review->customer_phone) {
                    $message = $reviewService->smsMessage($review->customer_name, $url);
                    $sms->send($review->customer_phone, $message, 'review_request_reminder', $review->opportunity);
                } elseif ($review->sent_via === 'email' && $review->customer_email) {
                    $reviewService->sendEmail($review->customer_email, $review->customer_name, $url);
                } else {
                    // No contact info on file for the channel it was sent via — nothing to remind through.
                    continue;
                }

                $review->update(['reminder_sent_at' => now()]);
                $sent++;
                $this->line("  Reminder → {$review->customer_name} ({$review->sent_via})");
            } catch (\Throwable $e) {
                Log::error('[ReviewReminder] send failed', [
                    'review_request_id' => $review->id,
                    'error'              => $e->getMessage(),
                ]);
                $this->error("  FAILED for {$review->customer_name}: " . $e->getMessage());
            }
        }

        $this->info("Review reminders: {$sent} sent out of {$pending->count()} pending.");
    }
}
