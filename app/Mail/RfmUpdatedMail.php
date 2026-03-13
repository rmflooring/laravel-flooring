<?php

namespace App\Mail;

use App\Models\Opportunity;
use App\Models\Rfm;
use App\Services\GraphMailService;
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
    ) {}

    /**
     * Dispatch update notifications to the selected recipients.
     * Returns true if at least one send succeeded.
     */
    public function send(): bool
    {
        $mailer  = app(GraphMailService::class);
        $success = false;

        // --- Estimator (detailed internal email) ---
        if ($this->notifyEstimator) {
            $estimator = $this->rfm->estimator;
            if ($estimator && filled($estimator->email)) {
                $sent = $mailer->send(
                    to:      $estimator->email,
                    subject: $this->buildEstimatorSubject(),
                    body:    $this->buildEstimatorBody(),
                    type:    'rfm_notification',
                );
                if ($sent) $success = true;
            } else {
                Log::info('[RFM Mail] Estimator update notify requested but no email on record', [
                    'rfm_id' => $this->rfm->id,
                ]);
            }
        }

        // --- Project Manager (clean customer-facing email) ---
        if ($this->notifyPm) {
            $pm = $this->opportunity->projectManager;
            if ($pm && filled($pm->email)) {
                $sent = $mailer->send(
                    to:      $pm->email,
                    subject: $this->buildPmSubject(),
                    body:    $this->buildPmBody(),
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

    protected function buildEstimatorSubject(): string
    {
        $jobNo    = $this->opportunity->job_no ? '#' . $this->opportunity->job_no . ' ' : '';
        $customer = $this->opportunity->parentCustomer?->company_name
            ?: $this->opportunity->parentCustomer?->name
            ?: 'Unknown Customer';

        return "RFM Updated: {$jobNo}{$customer}";
    }

    protected function buildPmSubject(): string
    {
        $jobNo    = $this->opportunity->job_no ? '#' . $this->opportunity->job_no . ' ' : '';
        $customer = $this->opportunity->parentCustomer?->company_name
            ?: $this->opportunity->parentCustomer?->name
            ?: 'Unknown Customer';

        return "Measurement Update: {$jobNo}{$customer}";
    }

    /**
     * Detailed internal body for the estimator — shows what changed + full current details.
     */
    protected function buildEstimatorBody(): string
    {
        $rfm         = $this->rfm;
        $opportunity = $this->opportunity;

        $jobNo    = $opportunity->job_no ? '#' . $opportunity->job_no : '—';
        $customer = $opportunity->parentCustomer?->company_name
            ?: $opportunity->parentCustomer?->name
            ?: '—';
        $jobSite  = $opportunity->jobSiteCustomer?->company_name
            ?: $opportunity->jobSiteCustomer?->name
            ?: '—';

        $estimatorName = $rfm->estimator
            ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
            : '—';

        $scheduled = $rfm->scheduled_at->format('l, F j, Y \a\t g:i A');

        $address = implode(', ', array_filter([
            $rfm->site_address,
            $rfm->site_city,
            $rfm->site_postal_code,
        ])) ?: '—';

        $flooringTypes = $rfm->flooring_type
            ? implode(', ', (array) $rfm->flooring_type)
            : '—';

        $showUrl = route('pages.opportunities.rfms.show', [
            $opportunity->id,
            $rfm->id,
        ]);

        $lines = [
            'An RFM has been updated. Details below.',
            '',
        ];

        // What changed block
        if (! empty($this->changes)) {
            $lines[] = '=== CHANGES ===';
            foreach ($this->changes as $label => $change) {
                $lines[] = "{$label}:";
                $lines[] = "  Was:  {$change['from']}";
                $lines[] = "  Now:  {$change['to']}";
            }
            $lines[] = '===============';
            $lines[] = '';
        }

        $lines = array_merge($lines, [
            '--- Current RFM Details ---',
            "Job:          {$jobNo} — {$customer}",
            "Job Site:     {$jobSite}",
            "Estimator:    {$estimatorName}",
            "Scheduled:    {$scheduled}",
            "Address:      {$address}",
            "Flooring:     {$flooringTypes}",
        ]);

        if (filled($rfm->special_instructions)) {
            $lines[] = '';
            $lines[] = 'Special Instructions:';
            $lines[] = $rfm->special_instructions;
        }

        $lines[] = '';
        $lines[] = '----------------------------------------';
        $lines[] = "View RFM: {$showUrl}";

        return implode("\n", $lines);
    }

    /**
     * Clean customer-facing body for the Project Manager — current details only, no diff.
     */
    protected function buildPmBody(): string
    {
        $rfm         = $this->rfm;
        $opportunity = $this->opportunity;

        $customer = $opportunity->parentCustomer?->company_name
            ?: $opportunity->parentCustomer?->name
            ?: '—';
        $jobSite  = $opportunity->jobSiteCustomer?->company_name
            ?: $opportunity->jobSiteCustomer?->name
            ?: '—';

        $estimatorName = $rfm->estimator
            ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
            : '—';

        $scheduled = $rfm->scheduled_at->format('l, F j, Y \a\t g:i A');

        $address = implode(', ', array_filter([
            $rfm->site_address,
            $rfm->site_city,
            $rfm->site_postal_code,
        ])) ?: '—';

        $pmName = $opportunity->projectManager?->name ?? 'there';

        $lines = [
            "Hi {$pmName},",
            '',
            "We have updated the flooring measurement details for {$customer} at {$jobSite}.",
            '',
            '----------------------------------------',
            "Date & Time:  {$scheduled}",
            "Location:     {$address}",
            "Estimator:    {$estimatorName}",
            '----------------------------------------',
            '',
            'Please ensure site access is available at the updated scheduled time.',
            '',
            'Thank you,',
            'RM Flooring',
        ];

        return implode("\n", $lines);
    }
}
