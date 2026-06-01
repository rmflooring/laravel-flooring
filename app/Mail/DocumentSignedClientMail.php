<?php

namespace App\Mail;

use App\Models\DocumentSigningRequest;
use App\Services\GraphMailService;
use Illuminate\Support\Facades\Storage;

class DocumentSignedClientMail
{
    public function __construct(protected DocumentSigningRequest $signingRequest) {}

    public function send(): bool
    {
        $mailer        = app(GraphMailService::class);
        $documentLabel = $this->signingRequest->document_type === 'flooring_selection'
            ? 'Flooring Selection'
            : 'Work Authorization';

        $signedAt = $this->signingRequest->signed_at
            ->timezone('America/Vancouver')
            ->format('F j, Y \a\t g:i A T');

        $subject = "Your signed {$documentLabel} document";

        $body = <<<TEXT
Hello {$this->signingRequest->client_name},

Thank you — your {$documentLabel} document has been successfully signed on {$signedAt}.

A copy of your signed document is attached to this email for your records.

RM Flooring & Cabinetry
TEXT;

        $attachment = null;
        if ($this->signingRequest->signed_pdf_path &&
            Storage::disk('local')->exists($this->signingRequest->signed_pdf_path)) {
            $attachment = [
                'filename' => 'signed-document.pdf',
                'content'  => base64_encode(Storage::disk('local')->get($this->signingRequest->signed_pdf_path)),
            ];
        }

        return $mailer->send(
            to:         $this->signingRequest->client_email,
            subject:    $subject,
            body:       $body,
            type:       'document_signed_client',
            attachment: $attachment,
        );
    }
}
