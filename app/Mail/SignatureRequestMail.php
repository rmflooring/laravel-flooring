<?php

namespace App\Mail;

use App\Models\DocumentSigningRequest;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;

class SignatureRequestMail
{
    public function __construct(
        protected DocumentSigningRequest $signingRequest,
        protected ?string $customSubject = null,
        protected ?string $customBody = null,
    ) {}

    public function send(): bool
    {
        $mailer  = app(GraphMailService::class);
        $service = app(EmailTemplateService::class);

        $templateType = match ($this->signingRequest->document_type) {
            'flooring_selection'   => 'signature_request_flooring',
            'work_auth'            => 'signature_request_work_auth',
            'opportunity_document' => 'signature_request_document',
            default                => 'signature_request_document',
        };

        // A batch (several documents in one email) is described as a whole
        $members       = $this->signingRequest->batchMembers();
        $labels        = $members->map(fn ($r) => $r->documentLabel())->values();
        $documentLabel = $labels->count() > 1
            ? $labels->slice(0, -1)->implode(', ') . ' and ' . $labels->last()
            : $labels->first();

        $template   = $service->getTemplate(null, $templateType);
        $signingUrl = url('/sign/' . $this->signingRequest->uuid);

        // Job site name comes from a flooring sign-off (any one in the batch)
        $signOffRequest = $members->firstWhere('document_type', 'flooring_selection');
        $jobSiteName = $signOffRequest
            ? \App\Models\FlooringSignOff::find($signOffRequest->document_id)?->job_site_name ?? ''
            : '';

        $vars = [
            'client_name'    => $this->signingRequest->client_name,
            'job_site_name'  => $jobSiteName,
            'document_label' => $documentLabel,
            'signing_link'   => $signingUrl,
            'expires_date'   => $this->signingRequest->expires_at
                ->timezone('America/Vancouver')
                ->format('F j, Y'),
        ];

        $subject = $service->render($this->customSubject ?: $template['subject'], $vars);
        $body    = $service->render($this->customBody ?: $template['body'], $vars);

        // Convert plain-text body to HTML; auto-link any bare URLs.
        // {{signing_link_button}} is injected after escaping so its HTML is preserved.
        $escaped = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $linked  = preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" style="color:#2563eb;word-break:break-all;">$1</a>',
            $escaped
        );

        $buttonHtml =
            '<div style="margin:20px 0;">' .
            '<a href="' . $signingUrl . '" ' .
               'style="display:inline-block;background-color:#1d4ed8;color:#ffffff;font-family:sans-serif;' .
                      'font-size:15px;font-weight:600;text-decoration:none;padding:13px 28px;border-radius:6px;">' .
                'Review &amp; Sign Document' .
            '</a>' .
            '</div>';

        // Batch: list the documents so the client knows there's more than one to sign
        if ($labels->count() > 1) {
            $items = $labels->map(fn ($l) => '<li>' . e($l) . '</li>')->implode('');
            $buttonHtml .=
                '<div style="margin:0 0 20px;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:14px;">' .
                    '<strong>' . $labels->count() . ' documents to sign:</strong>' .
                    '<ol style="margin:6px 0 6px 20px;padding:0;">' . $items . '</ol>' .
                    '<span style="color:#64748b;">After you sign each one, you\'ll be taken straight to the next.</span>' .
                '</div>';
        }

        if (str_contains($linked, '{{signing_link_button}}')) {
            $linked = str_replace('{{signing_link_button}}', $buttonHtml, $linked);
        } elseif ($labels->count() > 1) {
            $linked .= $buttonHtml; // custom body without the button tag — still show the list
        }

        $htmlBody = '<div style="font-family:sans-serif;font-size:15px;line-height:1.7;color:#222;max-width:600px;">'
            . $linked
            . '</div>';

        return $mailer->send(
            to:      $this->signingRequest->client_email,
            subject: $subject,
            body:    $htmlBody,
            type:    'signature_request',
            isHtml:  true,
        );
    }
}
