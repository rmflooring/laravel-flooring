<?php

namespace App\Mail;

use App\Models\Opportunity;
use App\Models\Rfm;
use App\Models\Setting;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;
use App\Services\ICalService;
use Illuminate\Support\Facades\Log;

class RfmUpdatedMail
{
    /**
     * @param  array  $changes  [ 'Field Label' => ['from' => '...', 'to' => '...'], ... ]
     */
    public function __construct(
        protected Rfm $rfm,
        protected Opportunity $opportunity,
        protected array $changes = [],
        protected bool $notifyEstimator = false,
        protected bool $notifyPm = false,
        protected bool $includeCalendarInvite = false,
    ) {}

    /**
     * Dispatch update notifications to the selected recipients.
     * Returns true if at least one send succeeded.
     */
    public function send(): bool
    {
        $mailer  = app(GraphMailService::class);
        $tpl     = app(EmailTemplateService::class);
        $vars    = $this->buildVars();
        $success = false;

        // --- Estimator ---
        if ($this->notifyEstimator) {
            $estimator = $this->rfm->estimator;
            if ($estimator && filled($estimator->email)) {
                $template = $tpl->getTemplate(null, 'rfm_updated_estimator');
                $subject  = $tpl->render($template['subject'], $vars);
                $body     = $tpl->render($template['body'], $vars);

                // Prepend the "what changed" diff block if present
                if (! empty($this->changes)) {
                    $diff  = "=== CHANGES ===\n";
                    foreach ($this->changes as $label => $change) {
                        $diff .= "{$label}:\n  Was:  {$change['from']}\n  Now:  {$change['to']}\n";
                    }
                    $diff .= "===============\n\n";
                    $body  = $diff . $body;
                }

                $icsContent = null;
                if ($this->includeCalendarInvite && $this->rfm->scheduled_at) {
                    $fromAddress   = Setting::get('mail_from_address', config('services.microsoft.mail_from_address', 'reception@rmflooring.ca'));
                    $fromName      = Setting::get('mail_from_name', 'RM Flooring Notifications');
                    $estimatorName = trim($estimator->first_name . ' ' . $estimator->last_name);
                    $icsContent    = app(ICalService::class)->generate(
                        uid:            "rfm-{$this->rfm->id}@rmflooring.ca",
                        title:          $subject,
                        start:          $this->rfm->scheduled_at->copy(),
                        end:            $this->rfm->scheduled_at->copy()->addHours(2),
                        organizerEmail: $fromAddress,
                        organizerName:  $fromName,
                        attendees:      [['email' => $estimator->email, 'name' => $estimatorName]],
                        location:       $vars['job_address'],
                    );
                }

                $sent = $mailer->send(
                    to:         $estimator->email,
                    subject:    $subject,
                    body:       $body,
                    type:       'rfm_notification',
                    icsContent: $icsContent,
                );
                if ($sent) $success = true;
            } else {
                Log::info('[RFM Mail] Estimator update notify requested but no email on record', [
                    'rfm_id' => $this->rfm->id,
                ]);
            }
        }

        // --- Project Manager ---
        if ($this->notifyPm) {
            $pm = $this->opportunity->projectManager;
            if ($pm && filled($pm->email)) {
                $template = $tpl->getTemplate(null, 'rfm_updated_pm');
                $subject  = $tpl->render($template['subject'], $vars);
                $body     = $tpl->render($template['body'], $vars);

                $sent = $mailer->send(
                    to:      $pm->email,
                    subject: $subject,
                    body:    $body,
                    type:    'rfm_notification',
                );
                if ($sent) $success = true;
            } else {
                Log::info('[RFM Mail] PM update notify requested but no email on record', [
                    'rfm_id'         => $this->rfm->id,
                    'opportunity_id' => $this->opportunity->id,
                ]);
            }
        }

        return $success;
    }

    // -------------------------------------------------------------------------

    protected function buildVars(): array
    {
        $rfm         = $this->rfm;
        $opportunity = $this->opportunity;

        $estimatorName = $rfm->estimator
            ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
            : '';

        $pm     = $opportunity->projectManager;
        $pmName = $pm?->name ?? '';

        $customer = $opportunity->parentCustomer?->company_name
            ?: $opportunity->parentCustomer?->name
            ?: '';

        $jobSite = $opportunity->jobSiteCustomer?->company_name
            ?: $opportunity->jobSiteCustomer?->name
            ?: '';

        $address = implode(', ', array_filter([
            $rfm->site_address,
            $rfm->site_address2,
            $rfm->site_city,
            $rfm->site_province,
            $rfm->site_postal_code,
        ]));

        $flooringTypes = $rfm->flooring_type
            ? implode(', ', (array) $rfm->flooring_type)
            : '';

        return [
            'customer_name'        => $customer,
            'job_no'               => $opportunity->job_no ?? '',
            'job_site'             => $jobSite,
            'estimator_name'       => $estimatorName,
            'estimator_first_name' => explode(' ', trim($estimatorName))[0] ?? '',
            'pm_name'              => $pmName,
            'pm_first_name'        => explode(' ', trim($pmName))[0] ?? '',
            'rfm_date'             => $rfm->scheduled_at->format('l, F j, Y'),
            'rfm_time'             => $rfm->scheduled_at->format('g:i A'),
            'job_address'          => $address,
            'job_phone'            => $opportunity->jobSiteCustomer?->phone ?? '',
            'job_mobile'           => $opportunity->jobSiteCustomer?->mobile ?? '',
            'flooring_type'        => $flooringTypes,
            'special_instructions' => $rfm->special_instructions ?? '',
            'rfm_link'             => route('mobile.rfms.show', $rfm->id),
        ];
    }
}
