<?php

namespace App\Mail;

use App\Models\DocumentSigningRequest;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;

class SignatureRequestMail
{
    public function __construct(protected DocumentSigningRequest $signingRequest) {}

    public function send(): bool
    {
        $mailer  = app(GraphMailService::class);
        $service = app(EmailTemplateService::class);

        $templateType = $this->signingRequest->document_type === 'flooring_selection'
            ? 'signature_request_flooring'
            : 'signature_request_work_auth';

        $documentLabel = $this->signingRequest->document_type === 'flooring_selection'
            ? 'Flooring Selection'
            : 'Work Authorization';

        $template   = $service->getTemplate(null, $templateType);
        $signingUrl = url('/sign/' . $this->signingRequest->uuid);

        $vars = [
            'client_name'    => $this->signingRequest->client_name,
            'document_label' => $documentLabel,
            'signing_link'   => $signingUrl,
            'expires_date'   => $this->signingRequest->expires_at
                ->timezone('America/Vancouver')
                ->format('F j, Y'),
        ];

        $subject = $service->render($template['subject'], $vars);
        $body    = $service->render($template['body'], $vars);

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

        $linked = str_replace('{{signing_link_button}}', $buttonHtml, $linked);

        $htmlBody = '<div style="font-family:sans-serif;font-size:15px;line-height:1.7;color:#222;max-width:600px;">'
            . $linked
            . '</div>';

        return $mailer->send(
            to:      $this->signingRequest->client_email,
            subject: $subject,
            body:    $htmlBody,
            type:    'signature_request',
        );
    }
}
