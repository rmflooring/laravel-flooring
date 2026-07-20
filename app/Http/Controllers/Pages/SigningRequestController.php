<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\FlooringSignOff;
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
        ]);

        $this->service->createSigningRequest(
            documentType:  'flooring_selection',
            documentId:    $signOff->id,
            clientName:    $request->client_name,
            clientEmail:   $request->client_email,
            customSubject: $request->subject,
            customBody:    $request->body,
        );

        return back()->with('success', 'Signature request sent to ' . $request->client_email . '.');
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

        $request->validate([
            'client_name'  => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
        ]);

        $this->service->createSigningRequest(
            documentType: 'opportunity_document',
            documentId:   $document->id,
            clientName:   $request->client_name,
            clientEmail:  $request->client_email,
        );

        return back()->with('success', 'Signature request sent to ' . $request->client_email . '.');
    }
}
