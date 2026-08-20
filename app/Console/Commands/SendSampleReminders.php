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

        $checkouts = SampleCheckout::with([
                'sample.productStyle.productLine',
                'sampleSet.productLine',
            ])
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

        // Group into one reminder per checkout event, not one per sample — a batch
        // checkout (multiple samples sharing one checkout_number) should produce a
        // single consolidated email/SMS, not one per item. Rows with no checkout_number
        // (legacy data, or sample-set checkouts which are already one row each) fall
        // back to their own singleton group by row id.
        $groups = $checkouts->groupBy(fn (SampleCheckout $c) => $c->checkout_number ?: 'row-' . $c->id);

        $mailer = new GraphMailService();
        $sms    = new SmsService();
        $sent   = 0;

        foreach ($groups as $group) {
            $first = $group->first();

            $items = $group->map(function (SampleCheckout $c) {
                if ($c->sampleSet) {
                    return [
                        'id'   => $c->sampleSet->set_id,
                        'name' => $c->sampleSet->name ?? $c->sampleSet->productLine?->name,
                    ];
                }

                return [
                    'id'   => $c->sample->sample_id,
                    'name' => $c->sample->productStyle->name,
                ];
            })->all();

            $itemCount = count($items);
            $itemLines = array_map(fn ($i) => trim($i['id'] . ' — ' . ($i['name'] ?? '')), $items);

            $itemsSummary = $itemCount === 1
                ? "flooring sample ({$itemLines[0]})"
                : "{$itemCount} flooring samples" . ($first->checkout_number ? " (checkout {$first->checkout_number})" : '');

            $vars = [
                'checkout_number'  => $first->checkout_number,
                'item_count'       => $itemCount,
                'item_lines'       => $itemLines,
                'items_summary'    => $itemsSummary,
                'customer_name'    => $first->borrower_name,
                'checked_out_date' => $first->checked_out_at->format('M j, Y'),
                'due_back_date'    => $first->due_back_at->format('M j, Y'),
                'days_overdue'     => (string) $first->days_overdue,
                'showroom_phone'   => $companyPhone,
                'showroom_email'   => $companyEmail,
                'company_name'     => $companyName,
            ];

            $anySent = false;

            // ── Email reminder ─────────────────────────────────────
            if ($emailEnabled && $globalMail && $first->customer_email) {
                try {
                    $subject = $itemCount > 1
                        ? "[{$companyName}] {$itemCount} overdue samples" . ($first->checkout_number ? " — Checkout {$first->checkout_number}" : '')
                        : "[{$companyName}] Sample {$items[0]['id']} is overdue";

                    $body = view('emails.samples.overdue-reminder', $vars)->render();

                    $mailer->send(
                        $first->customer_email,
                        $subject,
                        $body,
                        'sample_overdue_reminder'
                    );

                    $anySent = true;
                    $this->line("  Email → {$first->customer_email} (" . implode(', ', $itemLines) . ')');
                } catch (\Throwable $e) {
                    Log::error('[Sample Reminder] Email failed', [
                        'checkout_number' => $first->checkout_number,
                        'checkout_ids'    => $group->pluck('id')->all(),
                        'email'           => $first->customer_email,
                        'error'           => $e->getMessage(),
                    ]);
                    $this->error("  Email FAILED for checkout {$first->checkout_number}: " . $e->getMessage());
                }
            }

            // ── SMS reminder ───────────────────────────────────────
            if ($smsEnabled && $globalSms && $first->customer_phone) {
                try {
                    $body = view('sms.samples.overdue-reminder', $vars)->render();
                    $body = trim(preg_replace('/\s+/', ' ', $body)); // collapse whitespace

                    $sms->send($first->customer_phone, $body, 'sample_overdue_reminder');

                    $anySent = true;
                    $this->line("  SMS → {$first->customer_phone} (" . implode(', ', $itemLines) . ')');
                } catch (\Throwable $e) {
                    Log::error('[Sample Reminder] SMS failed', [
                        'checkout_number' => $first->checkout_number,
                        'checkout_ids'    => $group->pluck('id')->all(),
                        'phone'           => $first->customer_phone,
                        'error'           => $e->getMessage(),
                    ]);
                    $this->error("  SMS FAILED for checkout {$first->checkout_number}: " . $e->getMessage());
                }
            }

            if ($anySent) {
                foreach ($group as $checkout) {
                    $checkout->increment('reminders_sent');
                    $checkout->update(['last_reminder_at' => now()]);
                }
                $sent++;
            }
        }

        $this->info("Sample reminders: {$sent} checkout(s) reminded out of {$groups->count()} overdue.");
    }
}
