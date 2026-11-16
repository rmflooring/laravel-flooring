<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\FlooringSignOff;
use App\Models\DocumentTemplate;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\Sale;
use App\Models\WorkOrder;
use App\Services\DocumentSigningRequestService;
use Illuminate\Http\Request;

class SigningRequestController extends Controller
{
    public function __construct(
        private DocumentSigningRequestService $service
    ) {}

    public function storeFromSignOff(Opportunity $opportunity, FlooringSignOff $signOff, Request $request)
    {
        abort_if((int) $signOff->opportunity_id !== (int) $opportunity->id, 404);

        $request->validate([
            'client_name'  => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'subject'      => ['nullable', 'string', 'max:255'],
            'body'         => ['nullable', 'string'],
            'also'         => ['nullable', 'array'],
            'also.*'       => ['string'],
        ]);

        $documents = $this->withExtras($opportunity, ['flooring_selection', $signOff->id], $request);

        $this->service->createSigningBatch(
            documents:     $documents,
            clientName:    $request->client_name,
            clientEmail:   $request->client_email,
            customSubject: $request->subject,
            customBody:    $request->body,
        );

        return back()->with('success', $this->sentMessage($documents, $request->client_email));
    }

    public function storeFromWorkOrder(Sale $sale, WorkOrder $workOrder, Request $request)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $request->validate([
            'client_name'  => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
        ]);

        $this->service->createSigningRequest(
            documentType: 'work_auth',
            documentId:   $workOrder->id,
            clientName:   $request->client_name,
            clientEmail:  $request->client_email,
        );

        return back()->with('success', 'Signature request sent to ' . $request->client_email . '.');
    }

    public function storeFromOpportunityDocument(Opportunity $opportunity, OpportunityDocument $document, Request $request)
    {
        abort_if((int) $document->opportunity_id !== (int) $opportunity->id, 404);
        abort_if($document->category !== 'generated_document', 404);

        // Only templates flagged for e-signature (e.g. Work Authorization Form)
        abort_unless(DocumentTemplate::find($document->template_id)?->allows_esignature, 403,
            'This document type is not set up for e-signature.');

        $request->validate([
            'client_name'  => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'also'         => ['nullable', 'array'],
            'also.*'       => ['string'],
        ]);

        $documents = $this->withExtras($opportunity, ['opportunity_document', $document->id], $request);

        $this->service->createSigningBatch(
            documents:   $documents,
            clientName:  $request->client_name,
            clientEmail: $request->client_email,
        );

        return back()->with('success', $this->sentMessage($documents, $request->client_email));
    }

    /**
     * The document being sent first, followed by any other documents ticked under
     * "Also include" — each re-checked against what's actually signable on this
     * opportunity right now (so a stale page or tampered form can't add others).
     *
     * @return array<int, array{0: string, 1: int}>
     */
    private function withExtras(Opportunity $opportunity, array $primary, Request $request): array
    {
        $wanted = array_unique((array) $request->input('also', []));
        if (! $wanted) {
            return [$primary];
        }

        $extras = $this->service
            ->signableDocumentsForOpportunity($opportunity->id, $primary[0] . ':' . $primary[1])
            ->filter(fn ($d) => in_array($d['key'], $wanted, true))
            ->map(fn ($d) => [$d['type'], $d['id']])
            ->values()
            ->all();

        return array_merge([$primary], $extras);
    }

    private function sentMessage(array $documents, string $email): string
    {
        return count($documents) > 1
            ? count($documents) . ' documents sent for signature to ' . $email . ' in one email.'
            : 'Signature request sent to ' . $email . '.';
    }
}
