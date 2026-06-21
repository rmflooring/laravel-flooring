<?php

namespace App\Jobs;

use App\Models\IncomingLead;
use App\Services\GraphMailService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNewLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public readonly int $leadId) {}

    public function handle(GraphMailService $mailer, SmsService $sms): void
    {
        $lead = IncomingLead::find($this->leadId);

        if (! $lead) {
            return;
        }

        $this->sendAdminEmail($lead, $mailer);
        $this->sendAdminSms($lead, $sms);

        if ($lead->sms_consent) {
            $this->sendLeadAcknowledgment($lead, $sms);
        }
    }

    protected function sendAdminEmail(IncomingLead $lead, GraphMailService $mailer): void
    {
        $to = env('ADMIN_NOTIFICATION_EMAIL');

        if (! $to) {
            Log::warning('[Leads] ADMIN_NOTIFICATION_EMAIL not set — skipping admin email.');
            return;
        }

        $subject = 'New Flooring Lead — ' . $lead->name . ($lead->service_type ? ' (' . $lead->service_type . ')' : '');

        $reviewUrl = url('/leads/' . $lead->id);

        $rows = [
            'Name'            => e($lead->name),
            'Phone'           => e($lead->phone),
            'Email'           => e($lead->email ?? '—'),
            'SMS Consent'     => $lead->sms_consent ? 'Yes' : 'No',
            'Service Type'    => e($lead->service_type ?? '—'),
            'Project Type'    => e($lead->project_type ?? '—'),
            'Area'            => e($lead->area ?? '—'),
            'City'            => e($lead->city ?? '—'),
            'Timeline'        => e($lead->timeline ?? '—'),
            'Referral Source' => e($lead->referral_source ?? '—'),
            'Source'          => e($lead->source),
            'Received'        => $lead->created_at->format('M j, Y g:i A'),
        ];

        $tableRows = '';
        foreach ($rows as $label => $value) {
            $tableRows .= "<tr><td style='padding:6px 12px;font-weight:600;white-space:nowrap;'>{$label}</td><td style='padding:6px 12px;'>{$value}</td></tr>";
        }

        $message = $lead->message ? '<p style="margin:16px 0 4px;font-weight:600;">Message:</p><p style="margin:0;white-space:pre-wrap;">' . e($lead->message) . '</p>' : '';

        $body = <<<HTML
        <div style="font-family:sans-serif;max-width:600px;">
            <h2 style="margin-bottom:16px;">New Flooring Lead</h2>
            <table style="border-collapse:collapse;width:100%;">
                {$tableRows}
            </table>
            {$message}
            <p style="margin-top:24px;">
                <a href="{$reviewUrl}" style="background:#1d4ed8;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;display:inline-block;">
                    Review This Lead
                </a>
            </p>
        </div>
        HTML;

        $mailer->send(
            to:      $to,
            subject: $subject,
            body:    $body,
            type:    'lead_notification',
        );
    }

    protected function sendAdminSms(IncomingLead $lead, SmsService $sms): void
    {
        $to = env('ADMIN_SMS_NUMBER');

        if (! $to) {
            Log::warning('[Leads] ADMIN_SMS_NUMBER not set — skipping admin SMS.');
            return;
        }

        $timeline = $lead->timeline ? ', ' . $lead->timeline : '';
        $service  = $lead->service_type ?? 'General Inquiry';
        $url      = url('/leads/' . $lead->id);

        $message = "New flooring lead from {$lead->name} — {$service}{$timeline}. Review in FM: {$url}";

        $sms->send($to, $message, 'lead_notification', $lead);
    }

    protected function sendLeadAcknowledgment(IncomingLead $lead, SmsService $sms): void
    {
        $firstName = explode(' ', trim($lead->name))[0];
        $message   = "Hi {$firstName}, thanks for reaching out to RM Flooring! We've received your flooring estimate request and will be in touch within 1 business day. — The RM Flooring Team";

        $sms->send($lead->phone, $message, 'lead_acknowledgment', $lead);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Leads] ProcessNewLead job failed permanently', [
            'lead_id' => $this->leadId,
            'message' => $e->getMessage(),
        ]);
    }
}
