<?php

namespace App\Services;

use App\Mail\SignatureRequestMail;
use App\Models\DocumentSigningRequest;
use App\Models\DocumentTemplate;
use App\Models\FlooringSignOff;
use App\Models\OpportunityDocument;
use App\Models\Setting;
use App\Models\WorkOrder;
use App\Services\DocumentTemplateService;
use App\Services\EmailTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class DocumentSigningRequestService
{
    public function createSigningRequest(
        string $documentType,
        int $documentId,
        string $clientName,
        string $clientEmail,
        ?string $customSubject = null,
        ?string $customBody = null
    ): DocumentSigningRequest {
        $signingRequest = $this->makeSigningRequest($documentType, $documentId, $clientName, $clientEmail);

        (new SignatureRequestMail($signingRequest, $customSubject, $customBody))->send();

        return $signingRequest;
    }

    /**
     * Send several documents to the client in ONE email. Each document gets its
     * own signing request (sharing a batch_uuid); the email links to the first,
     * and after signing each one the client is taken on to the next.
     *
     * @param  array<int, array{0: string, 1: int}>  $documents  [[document_type, document_id], ...] in signing order
     * @return DocumentSigningRequest  the first request in the batch
     */
    public function createSigningBatch(
        array $documents,
        string $clientName,
        string $clientEmail,
        ?string $customSubject = null,
        ?string $customBody = null
    ): DocumentSigningRequest {
        $documents = array_values($documents);

        if (count($documents) === 1) {
            return $this->createSigningRequest($documents[0][0], (int) $documents[0][1], $clientName, $clientEmail, $customSubject, $customBody);
        }

        $batchUuid = Str::uuid()->toString();
        $requests  = [];
        foreach ($documents as $position => [$type, $id]) {
            $requests[] = $this->makeSigningRequest($type, (int) $id, $clientName, $clientEmail, $batchUuid, $position + 1);
        }

        (new SignatureRequestMail($requests[0], $customSubject, $customBody))->send();

        return $requests[0];
    }

    private function makeSigningRequest(
        string $documentType,
        int $documentId,
        string $clientName,
        string $clientEmail,
        ?string $batchUuid = null,
        ?int $batchPosition = null
    ): DocumentSigningRequest {
        $signingRequest = DocumentSigningRequest::create([
            'uuid'           => Str::uuid()->toString(),
            'batch_uuid'     => $batchUuid,
            'batch_position' => $batchPosition,
            'document_type'  => $documentType,
            'document_id'    => $documentId,
            'client_name'    => $clientName,
            'client_email'   => $clientEmail,
            'status'         => 'pending',
            'sent_at'        => now(),
            'expires_at'     => now()->addDays(10),
            'audit_log'      => ['sent_at' => now()->toIso8601String()],
        ]);

        $pdfPath = $this->storePendingPdf($signingRequest);
        $signingRequest->update(['pending_pdf_path' => $pdfPath]);

        return $signingRequest;
    }

    /**
     * Documents on an opportunity that can be sent for e-signature: flooring
     * sign-offs, and generated documents whose template allows it (e.g. the
     * Work Authorization Form).
     *
     * Each item has a `status`: null = can be sent now, 'pending' = already
     * awaiting a signature, 'signed' = already signed. Unavailable ones are left
     * out unless $includeUnavailable (the modal shows them greyed out).
     *
     * @return \Illuminate\Support\Collection<int, array{key: string, type: string, id: int, label: string, status: ?string}>
     */
    public function signableDocumentsForOpportunity(int $opportunityId, ?string $exceptKey = null, bool $includeUnavailable = false)
    {
        $docs = collect();

        foreach (FlooringSignOff::where('opportunity_id', $opportunityId)->orderBy('id')->get() as $signOff) {
            $docs->push([
                'key'   => 'flooring_selection:' . $signOff->id,
                'type'  => 'flooring_selection',
                'id'    => $signOff->id,
                'label' => 'Flooring Selection' . ($signOff->date ? ' — ' . $signOff->date->format('M j, Y') : ' #' . $signOff->id),
            ]);
        }

        $signableTemplateIds = DocumentTemplate::where('allows_esignature', true)->pluck('id');
        $generated = OpportunityDocument::where('opportunity_id', $opportunityId)
            ->where('category', 'generated_document')
            ->whereNotNull('rendered_body')
            ->whereIn('template_id', $signableTemplateIds)
            ->orderBy('id')
            ->get();
        foreach ($generated as $doc) {
            $docs->push([
                'key'   => 'opportunity_document:' . $doc->id,
                'type'  => 'opportunity_document',
                'id'    => $doc->id,
                'label' => $doc->original_name . ' — ' . $doc->created_at->format('M j, Y'),
            ]);
        }

        $docs = $docs->reject(fn ($d) => $d['key'] === $exceptKey)->values();
        if ($docs->isEmpty()) {
            return $docs;
        }

        // Status of anything already out for signature or signed ('signed' wins)
        $taken = [];
        DocumentSigningRequest::whereIn('status', ['pending', 'signed'])
            ->where(function ($q) use ($docs) {
                foreach ($docs->groupBy('type') as $type => $group) {
                    $q->orWhere(fn ($q) => $q->where('document_type', $type)->whereIn('document_id', $group->pluck('id')));
                }
            })
            ->get(['document_type', 'document_id', 'status'])
            ->each(function ($r) use (&$taken) {
                $key = $r->document_type . ':' . $r->document_id;
                if (($taken[$key] ?? null) !== 'signed') {
                    $taken[$key] = $r->status;
                }
            });

        $docs = $docs->map(fn ($d) => $d + ['status' => $taken[$d['key']] ?? null]);

        return $includeUnavailable ? $docs : $docs->whereNull('status')->values();
    }

    /**
     * Pre-filled subject/body for the Request E-Signature modal, from the same
     * System Email Template the email itself uses. {{signing_link_button}},
     * {{signing_link}} and {{expires_date}} are left in — they're resolved when
     * the request is actually sent.
     *
     * @return array{subject: string, body: string}
     */
    public function emailPreview(string $documentType, string $clientName, string $documentLabel, string $jobSiteName = ''): array
    {
        $templates = app(EmailTemplateService::class);
        $template  = $templates->getTemplate(null, SignatureRequestMail::templateTypeFor($documentType));
        $vars      = [
            'client_name'    => $clientName,
            'job_site_name'  => $jobSiteName,
            'document_label' => $documentLabel,
        ];

        return [
            'subject' => $templates->render($template['subject'], $vars),
            'body'    => $templates->render($template['body'], $vars),
        ];
    }

    public function storePendingPdf(DocumentSigningRequest $request): string
    {
        $docTemplate = $this->getDocumentTemplateWithTag($request);

        $pdfContent = $docTemplate
            ? $this->generateFromDocumentTemplate($request, $docTemplate, null)
            : match ($request->document_type) {
                'flooring_selection'   => $this->generateFlooringSignOffPdf($request->document_id),
                'work_auth'            => $this->generateWorkOrderPdf($request->document_id),
                'opportunity_document' => $this->generateOpportunityDocumentPdf($request->document_id),
            };

        $path = 'signed-documents/pending/' . $request->uuid . '.pdf';
        Storage::disk('local')->put($path, $pdfContent);

        return $path;
    }

    public function stampSignature(
        DocumentSigningRequest $request,
        string $signatureData,
        string $signatureType
    ): string {
        if ($this->hasInlineSignatureTag($request)) {
            return $this->stampSignatureInline($request, $signatureData, $signatureType);
        }

        $pendingPath = Storage::disk('local')->path($request->pending_pdf_path);

        $pdf       = new Fpdi();
        $pageCount = $pdf->setSourceFile($pendingPath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size  = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            if ($i === $pageCount) {
                $this->addSignatureBlock($pdf, $request, $signatureData, $signatureType, $size);
            }
        }

        $signedContent = $pdf->Output('S');
        $signedPath    = 'signed-documents/signed/' . $request->uuid . '-signed.pdf';
        Storage::disk('local')->put($signedPath, $signedContent);
        Storage::disk('local')->delete($request->pending_pdf_path);

        return $signedPath;
    }

    private function addSignatureBlock(
        Fpdi $pdf,
        DocumentSigningRequest $request,
        string $signatureData,
        string $signatureType,
        array $size
    ): void {
        $signedAt = now()->timezone('America/Vancouver')->format('F j, Y \a\t g:i A T');

        $blockH = 58;
        $blockX = 10;
        $blockY = $size['height'] - $blockH - 10;
        $blockW = $size['width'] - 20;

        // Box
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetFillColor(249, 250, 251);
        $pdf->Rect($blockX, $blockY, $blockW, $blockH, 'DF');

        // Header label
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY($blockX + 3, $blockY + 3);
        $pdf->Cell($blockW - 6, 5, 'ELECTRONIC SIGNATURE', 0, 1);

        $pdf->SetDrawColor(220, 220, 220);
        $pdf->Line($blockX + 3, $blockY + 9, $blockX + $blockW - 3, $blockY + 9);

        // Signature image
        $imgData = base64_decode(preg_replace('#^data:image/[^;]+;base64,#i', '', $signatureData));
        $tmpImg  = tempnam(sys_get_temp_dir(), 'sig_') . '.png';
        file_put_contents($tmpImg, $imgData);
        $pdf->Image($tmpImg, $blockX + 3, $blockY + 11, 90, 22);
        unlink($tmpImg);

        // Details
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(31, 41, 55);
        $pdf->SetXY($blockX + 3, $blockY + 35);
        $pdf->Cell($blockW - 6, 4, 'Digitally signed by: ' . $request->client_name, 0, 1);
        $pdf->SetX($blockX + 3);
        $pdf->Cell($blockW - 6, 4, 'Signed on: ' . $signedAt, 0, 1);
        $pdf->SetX($blockX + 3);
        $pdf->Cell($blockW - 6, 4, 'Signature method: ' . ucfirst($signatureType), 0, 1);

        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor(156, 163, 175);
        $pdf->SetX($blockX + 3);
        $pdf->Cell($blockW - 6, 4, 'Document ID: ' . $request->uuid, 0, 1);
    }

    public function expireStaleRequests(): int
    {
        // Phase 4
    }

    public function sendReminders(): int
    {
        // Phase 4
    }

    // ── Private PDF generators ────────────────────────────────────────────────

    private function generateFlooringSignOffPdf(int $id, ?string $signatureHtml = null): string
    {
        $signOff     = FlooringSignOff::with(['items', 'condition'])->findOrFail($id);
        $branding    = $this->branding();
        $logoDataUri = $this->logoDataUri($branding['logo_path']);

        $hasTag = $signOff->condition_text && str_contains($signOff->condition_text, '{{customer_signature}}');

        $conditionText = $signOff->condition_text
            ? $this->processPlainTextWithTag($signOff->condition_text, $signatureHtml)
            : null;

        $showCustomerSigLine = ! $hasTag;

        return Pdf::loadView('pdf.flooring-sign-off', compact(
            'signOff', 'branding', 'logoDataUri', 'conditionText', 'showCustomerSigLine'
        ))->setPaper('letter', 'portrait')->output();
    }

    private function generateWorkOrderPdf(int $id, ?string $signatureHtml = null): string
    {
        $workOrder = WorkOrder::with([
            'installer',
            'items.relatedMaterials.saleItem',
            'items.saleItem.room',
            'sale',
            'creator',
        ])->findOrFail($id);

        $processedNotes = $workOrder->notes
            ? $this->processHtmlWithTag($workOrder->notes, $signatureHtml)
            : null;

        return Pdf::loadView('pdf.work-order', compact('workOrder', 'processedNotes'))->output();
    }

    private function generateOpportunityDocumentPdf(int $id, ?string $signatureHtml = null): string
    {
        $doc      = OpportunityDocument::with(['opportunity', 'sale'])->findOrFail($id);
        $template = $doc->template_id ? DocumentTemplate::find($doc->template_id) : null;

        $body = $doc->rendered_body ?? '';
        if ($signatureHtml !== null) {
            $body = str_replace('{{customer_signature}}', $signatureHtml, $body);
        }

        return Pdf::loadView('pdf.document-template', [
            'body'        => $body,
            'template'    => $template,
            'opportunity' => $doc->opportunity,
            'sale'        => $doc->sale,
        ])->setPaper('letter', 'portrait')->output();
    }

    // ── Inline signature tag helpers ─────────────────────────────────────────

    private function hasInlineSignatureTag(DocumentSigningRequest $request): bool
    {
        if ($this->getDocumentTemplateWithTag($request) !== null) {
            return true;
        }

        return match ($request->document_type) {
            'flooring_selection' => str_contains(
                FlooringSignOff::find($request->document_id)?->condition_text ?? '',
                '{{customer_signature}}'
            ),
            'work_auth' => str_contains(
                WorkOrder::find($request->document_id)?->notes ?? '',
                '{{customer_signature}}'
            ),
            'opportunity_document' => str_contains(
                OpportunityDocument::find($request->document_id)?->rendered_body ?? '',
                '{{customer_signature}}'
            ),
            default => false,
        };
    }

    private function stampSignatureInline(
        DocumentSigningRequest $request,
        string $signatureData,
        string $signatureType
    ): string {
        $signatureHtml = $this->buildSignatureBlockHtml($request, $signatureData, $signatureType);
        $docTemplate   = $this->getDocumentTemplateWithTag($request);

        $pdfContent = $docTemplate
            ? $this->generateFromDocumentTemplate($request, $docTemplate, $signatureHtml)
            : match ($request->document_type) {
                'flooring_selection'   => $this->generateFlooringSignOffPdf($request->document_id, $signatureHtml),
                'work_auth'            => $this->generateWorkOrderPdf($request->document_id, $signatureHtml),
                'opportunity_document' => $this->generateOpportunityDocumentPdf($request->document_id, $signatureHtml),
            };

        $signedPath = 'signed-documents/signed/' . $request->uuid . '-signed.pdf';
        Storage::disk('local')->put($signedPath, $pdfContent);
        Storage::disk('local')->delete($request->pending_pdf_path);

        return $signedPath;
    }

    private function getDocumentTemplateWithTag(DocumentSigningRequest $request): ?DocumentTemplate
    {
        $templateId = match ($request->document_type) {
            'flooring_selection' => 2,
            'work_auth'          => 3,
            default              => null,
        };

        if (! $templateId) return null;

        $template = DocumentTemplate::find($templateId);

        return ($template && str_contains($template->body, '{{customer_signature}}')) ? $template : null;
    }

    private function generateFromDocumentTemplate(
        DocumentSigningRequest $request,
        DocumentTemplate $docTemplate,
        ?string $signatureHtml
    ): string {
        $templateService = new DocumentTemplateService();
        [$opportunity, $sale] = $this->getOpportunityAndSale($request);

        // For flooring sign-offs, pass the sign-off so only its curated items appear
        $signOff = $request->document_type === 'flooring_selection'
            ? FlooringSignOff::with('items')->find($request->document_id)
            : null;

        $fields       = $templateService->getDefaultFields($docTemplate, $opportunity, $sale);
        $renderedBody = $templateService->renderFromFields($docTemplate, $fields, $sale, $opportunity, $signOff);

        $injection    = $signatureHtml ?? $this->buildSignaturePlaceholderHtml();
        $renderedBody = str_replace('{{customer_signature}}', $injection, $renderedBody);

        return Pdf::loadView('pdf.document-template', [
            'body'        => $renderedBody,
            'template'    => $docTemplate,
            'opportunity' => $opportunity,
            'sale'        => $sale,
        ])->setPaper('letter', 'portrait')->output();
    }

    private function getOpportunityAndSale(DocumentSigningRequest $request): array
    {
        if ($request->document_type === 'flooring_selection') {
            $signOff = FlooringSignOff::with(['opportunity', 'sale'])->findOrFail($request->document_id);
            return [$signOff->opportunity, $signOff->sale];
        }

        if ($request->document_type === 'work_auth') {
            $workOrder = WorkOrder::with(['sale.opportunity'])->findOrFail($request->document_id);
            return [$workOrder->sale->opportunity, $workOrder->sale];
        }

        if ($request->document_type === 'opportunity_document') {
            $doc = OpportunityDocument::with(['opportunity', 'sale.opportunity'])->findOrFail($request->document_id);
            return [$doc->opportunity, $doc->sale ?? null];
        }

        return [null, null];
    }

    private function buildSignatureBlockHtml(
        DocumentSigningRequest $request,
        string $signatureData,
        string $signatureType
    ): string {
        $signedAt = now()->timezone('America/Vancouver')->format('F j, Y \a\t g:i A T');

        return
            '<div style="border:1px solid #d1d5db; border-radius:4px; padding:10px 14px; margin:10px 0; background:#f9fafb;">'
            . '<div style="font-size:9px; font-weight:bold; color:#374151; text-transform:uppercase; border-bottom:1px solid #e5e7eb; padding-bottom:4px; margin-bottom:8px;">ELECTRONIC SIGNATURE</div>'
            . '<table style="width:100%; border-collapse:collapse; margin:0;">'
            . '<tr>'
            . '<td style="width:55%; border:none; padding:0; vertical-align:middle;">'
            . '<img src="' . e($signatureData) . '" style="max-width:180px; max-height:50px; display:block; border-bottom:1px solid #555;">'
            . '</td>'
            . '<td style="border:none; padding:0 0 0 12px; vertical-align:middle; font-size:9px; color:#555; line-height:1.7; text-align:left; font-weight:normal;">'
            . 'Signed by: ' . e($request->client_name) . '<br>'
            . 'Date: ' . e($signedAt) . '<br>'
            . 'Method: ' . e(ucfirst($signatureType)) . '<br>'
            . 'Document ID: ' . e($request->uuid)
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</div>';
    }

    private function buildSignaturePlaceholderHtml(): string
    {
        return
            '<div style="border:2px dashed #d1d5db; border-radius:4px; padding:14px; margin:10px 0; background:#f9fafb; text-align:center;">'
            . '<div style="font-size:9px; font-weight:bold; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:10px;">CUSTOMER SIGNATURE REQUIRED</div>'
            . '<div style="border-bottom:1px solid #9ca3af; width:65%; margin:0 auto; height:32px;"></div>'
            . '<div style="margin-top:5px; font-size:8px; color:#9ca3af;">Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</div>'
            . '</div>';
    }

    private function processPlainTextWithTag(string $text, ?string $signatureHtml): string
    {
        if (! str_contains($text, '{{customer_signature}}')) {
            return nl2br(e($text));
        }

        [$before, $after] = explode('{{customer_signature}}', $text, 2);
        $injection = $signatureHtml ?? $this->buildSignaturePlaceholderHtml();

        return nl2br(e($before)) . $injection . nl2br(e($after));
    }

    private function processHtmlWithTag(string $html, ?string $signatureHtml): string
    {
        if (! str_contains($html, '{{customer_signature}}')) {
            return $html;
        }

        $injection = $signatureHtml ?? $this->buildSignaturePlaceholderHtml();

        return str_replace('{{customer_signature}}', $injection, $html);
    }

    private function branding(): array
    {
        return [
            'company_name' => Setting::get('branding_company_name', 'RM Flooring'),
            'tagline'      => Setting::get('branding_tagline', ''),
            'street'       => Setting::get('branding_address', ''),
            'city'         => Setting::get('branding_city', ''),
            'province'     => Setting::get('branding_province', ''),
            'postal'       => Setting::get('branding_postal', ''),
            'phone'        => Setting::get('branding_phone', ''),
            'email'        => Setting::get('branding_email', ''),
            'website'      => Setting::get('branding_website', ''),
            'logo_path'    => Setting::get('branding_logo_path', ''),
        ];
    }

    private function logoDataUri(?string $logoPath): ?string
    {
        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $raw  = Storage::disk('public')->get($logoPath);
        $mime = Storage::disk('public')->mimeType($logoPath);

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }
}
