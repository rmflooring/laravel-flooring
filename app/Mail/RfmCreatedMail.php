<?php

namespace App\Mail;

use App\Models\Opportunity;
use App\Models\Rfm;
use App\Services\GraphMailService;
use Illuminate\Support\Facades\Log;

class RfmCreatedMail
{
    public function __construct(
        protected Rfm $rfm,
        protected Opportunity $opportunity,
    ) {}

    /**
     * Build the plain-text email body and dispatch to all recipients.
     * Returns true if at least one send succeeded.
     */
    public function send(): bool
    {
        $recipients = $this->buildRecipients();

        if (empty($recipients)) {
            Log::info('[RFM Mail] No recipients with email addresses — skipping notification', [
                'rfm_id' => $this->rfm->id,
            ]);
            return false;
        }

        $subject = $this->buildSubject();
        $body    = $this->buildBody();
        $mailer  = app(GraphMailService::class);
        $success = false;

        foreach ($recipients as $address => $name) {
            $sent = $mailer->send(
                to:      $address,
                subject: $subject,
                body:    $body,
                type:    'rfm_notification',
            );
            if ($sent) {
                $success = true;
            }
        }

        return $success;
    }

    // -------------------------------------------------------------------------

    protected function buildRecipients(): array
    {
        $recipients = [];

        // Estimator
        $estimator = $this->rfm->estimator;
        if ($estimator && filled($estimator->email)) {
            $recipients[$estimator->email] = trim($estimator->first_name . ' ' . $estimator->last_name);
        }

        // Project Manager (if assigned to the opportunity)
        $pm = $this->opportunity->projectManager;
        if ($pm && filled($pm->email)) {
            $recipients[$pm->email] = $pm->name;
        }

        return $recipients;
    }

    protected function buildSubject(): string
    {
        $jobNo    = $this->opportunity->job_no ? '#' . $this->opportunity->job_no . ' ' : '';
        $customer = $this->opportunity->parentCustomer?->company_name
            ?: $this->opportunity->parentCustomer?->name
            ?: 'Unknown Customer';

        return "RFM Scheduled: {$jobNo}{$customer}";
    }

    protected function buildBody(): string
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

        $addressParts = array_filter([
            $rfm->site_address,
            $rfm->site_city,
            $rfm->site_postal_code,
        ]);
        $address = $addressParts ? implode(', ', $addressParts) : '—';

        $flooringTypes = $rfm->flooring_type
            ? implode(', ', (array) $rfm->flooring_type)
            : '—';

        $showUrl = route('pages.opportunities.rfms.show', [
            $opportunity->id,
            $rfm->id,
        ]);

        $lines = [
            'A new Request for Measure has been scheduled.',
            '',
            '----------------------------------------',
            "Job:          {$jobNo} — {$customer}",
            "Job Site:     {$jobSite}",
            "Estimator:    {$estimatorName}",
            "Scheduled:    {$scheduled}",
            "Address:      {$address}",
            "Flooring:     {$flooringTypes}",
        ];

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
}
