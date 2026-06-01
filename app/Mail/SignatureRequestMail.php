<?php

namespace App\Mail;

use App\Models\DocumentSigningRequest;
use App\Services\GraphMailService;

class SignatureRequestMail
{
    public function __construct(protected DocumentSigningRequest $signingRequest) {}

    public function send(): bool
    {
        $mailer        = app(GraphMailService::class);
        $documentLabel = $this->signingRequest->document_type === 'flooring_selection'
            ? 'Flooring Selection'
            : 'Work Authorization';

        $link    = url('/sign/' . $this->signingRequest->uuid);
        $expires = $this->signingRequest->expires_at
            ->timezone('America/Vancouver')
            ->format('F j, Y');

        $subject = "Please sign your {$documentLabel} document";

        $body = <<<TEXT
Hello {$this->signingRequest->client_name},

Please review and sign your {$documentLabel} document at the link below.

Sign here: {$link}

This link expires on {$expires}.

If you have any questions, please contact us.

RM Flooring & Cabinetry
TEXT;

        return $mailer->send(
            to:      $this->signingRequest->client_email,
            subject: $subject,
            body:    $body,
            type:    'signature_request',
        );
    }
}
