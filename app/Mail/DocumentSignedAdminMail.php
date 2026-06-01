<?php

namespace App\Mail;

use App\Models\DocumentSigningRequest;
use App\Services\GraphMailService;
use Illuminate\Support\Facades\Storage;

class DocumentSignedAdminMail
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

        $subject = "{$documentLabel} signed by {$this->signingRequest->client_name}";

        $body = <<<TEXT
A document has been signed.

Document type: {$documentLabel}
Client: {$this->signingRequest->client_name} <{$this->signingRequest->client_email}>
Signed at: {$signedAt}
Signature method: {$this->signingRequest->signature_type}
Document UUID: {$this->signingRequest->uuid}

The signed PDF is attached.
TEXT;

        $attachment = null;
        if ($this->signingRequest->signed_pdf_path &&
            Storage::disk('local')->exists($this->signingRequest->signed_pdf_path)) {
            $attachment = [
                'filename' => 'signed-' . $this->signingRequest->uuid . '.pdf',
                'content'  => base64_encode(Storage::disk('local')->get($this->signingRequest->signed_pdf_path)),
            ];
        }

        return $mailer->send(
            to:         'richard@rmflooring.ca',
            subject:    $subject,
            body:       $body,
            type:       'document_signed_admin',
            attachment: $attachment,
        );
    }
}
