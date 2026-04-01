<?php

namespace App\Console\Commands;

use App\Models\Rfm;
use App\Models\Setting;
use App\Models\WorkOrder;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSmsReminders extends Command
{
    protected $signature = 'sms:send-reminders';
    protected $description = 'Send day-before SMS reminders for scheduled Work Orders and RFMs.';

    public function handle(): void
    {
        if (! Setting::get('sms_enabled')) {
            $this->info('SMS is disabled — skipping reminders.');
            return;
        }

        $tomorrow = Carbon::tomorrow()->toDateString();
        $sms      = new SmsService();
        $tpl      = new SmsTemplateService();

        // ── Work Order reminders ───────────────────────────────────
        if (Setting::get('sms_notify_wo_reminder')) {
            $recipients = array_filter(explode(',', Setting::get('sms_wo_reminder_to', 'pm,installer')));

            $workOrders = WorkOrder::with(['installer', 'sale.opportunity.projectManager', 'sale.opportunity.parentCustomer'])
                ->whereDate('scheduled_date', $tomorrow)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->whereNull('sms_reminder_sent_at')
                ->get();

            foreach ($workOrders as $wo) {
                try {
                    $sale      = $wo->sale;
                    $installer = $wo->installer;
                    $pm        = $sale?->opportunity?->projectManager;

                    $vars = [
                        'wo_number'            => $wo->wo_number ?? '',
                        'sale_number'          => $sale?->sale_number ?? '',
                        'customer_name'        => $sale?->homeowner_name ?? $sale?->job_name ?? '',
                        'job_address'          => $sale?->job_address ?? '',
                        'scheduled_date'       => Carbon::parse($wo->scheduled_date)->format('M j, Y'),
                        'scheduled_time'       => $wo->scheduled_time
                            ? Carbon::createFromFormat('H:i', $wo->scheduled_time)->format('g:ia')
                            : '',
                        'installer_name'       => $installer?->company_name ?? '',
                        'installer_first_name' => explode(' ', trim($installer?->company_name ?? 'Installer'))[0],
                        'pm_name'              => $pm?->name ?? '',
                        'pm_first_name'        => explode(' ', trim($pm?->name ?? ''))[0],
                    ];

                    $body         = $tpl->renderTemplate('wo_reminder', $vars);
                    $bodyCustomer = $tpl->renderTemplate('wo_reminder_customer', $vars);
                    $sent = false;

                    if (in_array('pm', $recipients) && $pm?->mobile) {
                        $sms->send($pm->mobile, $body, 'wo_reminder', $wo);
                        $sent = true;
                    }

                    if (in_array('installer', $recipients) && $installer?->mobile) {
                        $sms->send($installer->mobile, $body, 'wo_reminder', $wo);
                        $sent = true;
                    }

                    if (in_array('homeowner', $recipients) && !$sale?->opportunity?->parentCustomer?->sms_opted_out) {
                        $phone = $sale?->job_phone ?? $sale?->sourceEstimate?->homeowner_phone ?? null;
                        if ($phone) {
                            $sms->send($phone, $bodyCustomer, 'wo_reminder_customer', $wo);
                            $sent = true;
                        }
                    }

                    if ($sent) {
                        $wo->update(['sms_reminder_sent_at' => now()]);
                        $this->info("WO reminder sent: {$wo->wo_number}");
                    }
                } catch (\Throwable $e) {
                    Log::error('[SMS Reminder] WO reminder failed', [
                        'wo_id' => $wo->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("WO {$wo->wo_number} failed: " . $e->getMessage());
                }
            }

            $this->info("WO reminders: processed {$workOrders->count()} work orders.");
        }

        // ── RFM reminders ─────────────────────────────────────────
        if (Setting::get('sms_notify_rfm_reminder')) {
            $recipients = array_filter(explode(',', Setting::get('sms_rfm_reminder_to', 'estimator,pm')));

            $rfms = Rfm::with(['estimator', 'parentCustomer', 'jobSiteCustomer', 'opportunity.projectManager'])
                ->whereDate('scheduled_at', $tomorrow)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNull('sms_reminder_sent_at')
                ->get();

            foreach ($rfms as $rfm) {
                try {
                    $estimator       = $rfm->estimator;
                    $pm              = $rfm->opportunity?->projectManager;
                    $jobSiteCustomer = $rfm->jobSiteCustomer;
                    $customerName    = $rfm->parentCustomer?->company_name
                        ?: $rfm->parentCustomer?->name
                        ?: 'Customer';
                    $fullAddress  = implode(', ', array_filter([
                        $rfm->site_address, $rfm->site_city, $rfm->site_postal_code,
                    ]));
                    $estimatorName = $estimator
                        ? trim($estimator->first_name . ' ' . $estimator->last_name)
                        : '';
                    $scheduled = Carbon::parse($rfm->scheduled_at);

                    $vars = [
                        'customer_name'        => $customerName,
                        'rfm_date'             => $scheduled->format('M j, Y'),
                        'rfm_time'             => $scheduled->format('g:ia'),
                        'site_address'         => $fullAddress,
                        'special_instructions' => $rfm->special_instructions ?? '',
                        'estimator_name'       => $estimatorName,
                        'estimator_first_name' => explode(' ', trim($estimatorName))[0],
                        'pm_name'              => $pm?->name ?? '',
                        'pm_first_name'        => explode(' ', trim($pm?->name ?? ''))[0],
                        'rfm_link'             => route('mobile.rfms.show', $rfm->id),
                    ];

                    $body         = $tpl->renderTemplate('rfm_reminder', $vars);
                    $bodyCustomer = $tpl->renderTemplate('rfm_reminder_customer', $vars);
                    $sent = false;

                    if (in_array('estimator', $recipients) && $estimator?->phone) {
                        $sms->send($estimator->phone, $body, 'rfm_reminder', $rfm);
                        $sent = true;
                    }

                    if (in_array('pm', $recipients) && $pm?->mobile) {
                        $sms->send($pm->mobile, $body, 'rfm_reminder', $rfm);
                        $sent = true;
                    }

                    if (in_array('customer', $recipients) && !$jobSiteCustomer?->sms_opted_out) {
                        $phone = $jobSiteCustomer?->mobile ?? $jobSiteCustomer?->phone ?? null;
                        if ($phone) {
                            $sms->send($phone, $bodyCustomer, 'rfm_reminder_customer', $rfm);
                            $sent = true;
                        }
                    }

                    if ($sent) {
                        $rfm->update(['sms_reminder_sent_at' => now()]);
                        $this->info("RFM reminder sent: RFM #{$rfm->id}");
                    }
                } catch (\Throwable $e) {
                    Log::error('[SMS Reminder] RFM reminder failed', [
                        'rfm_id' => $rfm->id,
                        'error'  => $e->getMessage(),
                    ]);
                    $this->error("RFM #{$rfm->id} failed: " . $e->getMessage());
                }
            }

            $this->info("RFM reminders: processed {$rfms->count()} RFMs.");
        }
    }
}
