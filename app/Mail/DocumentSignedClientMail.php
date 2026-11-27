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
        $documentLabel = $this->signingRequest->documentLabel();

        $signedAt = $this->signingRequest->signed_at
            ->timezone('America/Vancouver')
            ->format('F j, Y \a\t g:i A T');

        // Documents sent together with this one that still need signing. Clients often
        // stop after the first (2026-11-27: Flooring Selection signed, Work Authorization
        // never opened), so the confirmation leads with what's left.
        $remaining = $this->remainingInBatch();

        $subject = $remaining->isEmpty()
            ? "Your signed {$documentLabel} document"
            : "Signed: {$documentLabel}. " . ($remaining->count() === 1 ? '1 more document' : $remaining->count() . ' more documents') . ' to sign';

        $html = '<p>Hello ' . e($this->signingRequest->client_name) . ',</p>';

        if ($remaining->isNotEmpty()) {
            $next  = $remaining->first();
            $items = $remaining->map(fn ($r) => '<li>' . e($r->documentLabel()) . '</li>')->implode('');

            $html .=
                '<div style="margin:16px 0 24px;padding:18px 20px;background:#eff6ff;border:2px solid #1d4ed8;border-radius:8px;">' .
                    '<p style="margin:0 0 6px;font-size:16px;font-weight:700;color:#1e3a8a;">' .
                        ($remaining->count() === 1 ? 'You have 1 more document to sign' : 'You have ' . $remaining->count() . ' more documents to sign') .
                    '</p>' .
                    '<ol style="margin:0 0 14px 20px;padding:0;">' . $items . '</ol>' .
                    '<a href="' . e(url('/sign/' . $next->uuid)) . '" ' .
                       'style="display:inline-block;background-color:#1d4ed8;color:#ffffff;font-size:15px;font-weight:600;' .
                              'text-decoration:none;padding:13px 28px;border-radius:6px;">' .
                        'Review &amp; Sign ' . e($next->documentLabel()) . ' &rarr;' .
                    '</a>' .
                '</div>';
        }

        $html .=
            '<p>Thank you, your <strong>' . e($documentLabel) . '</strong> document was successfully signed on ' . e($signedAt) . '.</p>' .
            '<p>A copy of your signed document is attached to this email for your records.</p>' .
            '<p>RM Flooring &amp; Cabinetry</p>';

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
            body:       '<div style="font-family:sans-serif;font-size:15px;line-height:1.7;color:#222;max-width:600px;">' . $html . '</div>',
            type:       'document_signed_client',
            attachment: $attachment,
            isHtml:     true,
        );
    }

    /** Still-open documents from the same batch, the one to sign next first. */
    private function remainingInBatch()
    {
        $request = $this->signingRequest;

        if (! $request->isBatched()) {
            return collect();
        }

        $open = DocumentSigningRequest::where('batch_uuid', $request->batch_uuid)
            ->where('id', '!=', $request->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('batch_position')
            ->orderBy('id')
            ->get();

        // Same order as nextInBatch(): documents after this one first, then any skipped earlier ones
        return $open->filter(fn ($r) => $r->batch_position > $request->batch_position)
            ->concat($open->filter(fn ($r) => $r->batch_position <= $request->batch_position))
            ->values();
    }
}
