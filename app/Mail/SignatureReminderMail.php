<?php

namespace App\Mail;

use App\Models\DocumentSigningRequest;
use App\Services\GraphMailService;

class SignatureReminderMail
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

        $subject = "Reminder: Please sign your {$documentLabel} document";

        $body = <<<TEXT
Hello {$this->signingRequest->client_name},

This is a friendly reminder that your {$documentLabel} document is still waiting for your signature.

Sign here: {$link}

This link expires on {$expires}.

RM Flooring & Cabinetry
TEXT;

        return $mailer->send(
            to:      $this->signingRequest->client_email,
            subject: $subject,
            body:    $body,
            type:    'signature_reminder',
        );
    }
}
