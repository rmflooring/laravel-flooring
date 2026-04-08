<?php

namespace App\Console\Commands;

use App\Models\SampleCheckout;
use App\Models\Setting;
use App\Services\GraphMailService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSampleReminders extends Command
{
    protected $signature   = 'samples:send-reminders';
    protected $description = 'Send overdue sample reminder emails and SMS to customers.';

    public function handle(): void
    {
        $emailEnabled = Setting::get('sample_email_reminders_enabled', '1') === '1';
        $smsEnabled   = Setting::get('sample_sms_reminders_enabled', '1') === '1';
        $globalMail   = Setting::get('mail_notifications_enabled', '1') === '1';
        $globalSms    = Setting::get('sms_enabled', '0') === '1';
        $reminderDays = (int) Setting::get('sample_reminder_days', 3);

        if (! $emailEnabled && ! $smsEnabled) {
            $this->info('Sample reminders are disabled — skipping.');
            return;
        }

        $companyName  = Setting::get('branding_company_name', 'RM Flooring');
        $companyPhone = Setting::get('branding_phone', '');
        $companyEmail = Setting::get('mail_from_address', '');

        // Find overdue customer checkouts that need a reminder:
        //   - customer type only (staff checkouts don't get reminders)
        //   - due_back_at has passed
        //   - not yet returned
        //   - never reminded OR last reminder was >= $reminderDays ago
        $overdueDate    = now()->toDateString();
        $reminderCutoff = now()->subDays($reminderDays);

        $checkouts = SampleCheckout::with(['sample.productStyle', 'sample.productStyle.productLine'])
            ->where('checkout_type', 'customer')
            ->whereNotNull('due_back_at')
            ->where('due_back_at', '<', $overdueDate)
            ->whereNull('returned_at')
            ->where(function ($q) use ($reminderCutoff) {
                $q->where('reminders_sent', 0)
                  ->orWhere('last_reminder_at', '<=', $reminderCutoff);
            })
            ->get();

        if ($checkouts->isEmpty()) {
            $this->info('No overdue checkouts to remind.');
            return;
        }

        $mailer = new GraphMailService();
        $sms    = new SmsService();
        $sent   = 0;

        foreach ($checkouts as $checkout) {
            $sample  = $checkout->sample;
            $product = $sample->productStyle;
            $line    = $product->productLine;

            $vars = [
                'sample_id'        => $sample->sample_id,
                'product_name'     => $product->name,
                'customer_name'    => $checkout->borrower_name,
                'checked_out_date' => $checkout->checked_out_at->format('M j, Y'),
                'due_back_date'    => $checkout->due_back_at->format('M j, Y'),
                'days_overdue'     => (string) $checkout->days_overdue,
                'showroom_phone'   => $companyPhone,
                'showroom_email'   => $companyEmail,
                'company_name'     => $companyName,
            ];

            $anySent = false;

            // ── Email reminder ─────────────────────────────────────
            if ($emailEnabled && $globalMail && $checkout->customer_email) {
                try {
                    $subject  = "[{$companyName}] Sample {$sample->sample_id} is overdue";
                    $body     = view('emails.samples.overdue-reminder', $vars)->render();

                    $mailer->send(
                        $checkout->customer_email,
                        $subject,
                        $body,
                        'sample_overdue_reminder'
                    );

                    $anySent = true;
                    $this->line("  Email → {$checkout->customer_email} ({$sample->sample_id})");
                } catch (\Throwable $e) {
                    Log::error('[Sample Reminder] Email failed', [
                        'checkout_id' => $checkout->id,
                        'email'       => $checkout->customer_email,
                        'error'       => $e->getMessage(),
                    ]);
                    $this->error("  Email FAILED for checkout #{$checkout->id}: " . $e->getMessage());
                }
            }

            // ── SMS reminder ───────────────────────────────────────
            if ($smsEnabled && $globalSms && $checkout->customer_phone) {
                try {
                    $body = view('sms.samples.overdue-reminder', $vars)->render();
                    $body = trim(preg_replace('/\s+/', ' ', $body)); // collapse whitespace

                    $sms->send($checkout->customer_phone, $body, 'sample_overdue_reminder');

                    $anySent = true;
                    $this->line("  SMS → {$checkout->customer_phone} ({$sample->sample_id})");
                } catch (\Throwable $e) {
                    Log::error('[Sample Reminder] SMS failed', [
                        'checkout_id' => $checkout->id,
                        'phone'       => $checkout->customer_phone,
                        'error'       => $e->getMessage(),
                    ]);
                    $this->error("  SMS FAILED for checkout #{$checkout->id}: " . $e->getMessage());
                }
            }

            if ($anySent) {
                $checkout->increment('reminders_sent');
                $checkout->update(['last_reminder_at' => now()]);
                $sent++;
            }
        }

        $this->info("Sample reminders: {$sent} sent out of {$checkouts->count()} overdue.");
    }
}
