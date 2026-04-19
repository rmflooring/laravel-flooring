<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CustomerReturn;
use App\Models\InventoryReturn;
use App\Models\PickTicket;
use App\Services\PickTicketService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    // -----------------------------------------------------------------------
    // HUB
    // -----------------------------------------------------------------------

    public function index()
    {
        $pendingPts  = PickTicket::whereIn('status', ['pending', 'ready', 'picked', 'staged', 'partially_delivered'])->count();
        $draftRfcs   = CustomerReturn::where('status', 'draft')->count();
        $draftRtvs   = InventoryReturn::where('status', 'draft')->count();

        return view('mobile.warehouse.index', compact('pendingPts', 'draftRfcs', 'draftRtvs'));
    }

    // -----------------------------------------------------------------------
    // PICK TICKETS
    // -----------------------------------------------------------------------

    public function pickTickets(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $query = PickTicket::with(['sale', 'workOrder'])
            ->when($search, fn ($q) => $q->where('pt_number', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($q) => $q->whereIn('status', ['pending', 'ready', 'picked', 'staged', 'partially_delivered']))
            ->when($status !== 'active' && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw("
                CASE status
                    WHEN 'pending'             THEN 0
                    WHEN 'ready'               THEN 1
                    WHEN 'picked'              THEN 2
                    WHEN 'staged'              THEN 3
                    WHEN 'partially_delivered' THEN 4
                    WHEN 'delivered'           THEN 5
                    WHEN 'returned'            THEN 6
                    WHEN 'cancelled'           THEN 7
                    ELSE 8
                END
            ")
            ->orderBy('id');

        $pickTickets = $query->paginate(25)->withQueryString();

        return view('mobile.warehouse.pick-tickets.index', compact('pickTickets', 'search', 'status'));
    }

    public function showPickTicket(PickTicket $pickTicket)
    {
        $pickTicket->load(['sale', 'workOrder.installer', 'items.saleItem.room', 'creator']);

        return view('mobile.warehouse.pick-tickets.show', compact('pickTicket'));
    }

    public function updatePickTicketStatus(Request $request, PickTicket $pickTicket, PickTicketService $service)
    {
        $request->validate([
            'action'         => ['required', 'in:mark_ready,mark_picked,deliver,cancel,revert_status'],
            'received_by'    => ['nullable', 'string', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:2000'],
            'items'          => ['nullable', 'array'],
            'items.*'        => ['nullable', 'numeric', 'min:0'],
        ]);

        match ($request->action) {
            'mark_ready'    => $service->markReady($pickTicket),
            'mark_picked'   => $service->markPicked($pickTicket),
            'deliver'       => $service->deliver(
                                   $pickTicket,
                                   $request->input('items', []),
                                   $request->input('received_by'),
                                   $request->input('delivery_notes')
                               ),
            'cancel'        => $service->cancel($pickTicket),
            'revert_status' => $service->revertStatus($pickTicket),
        };

        return redirect()->route('mobile.warehouse.pick-tickets.show', $pickTicket)
            ->with('success', 'Pick ticket updated.');
    }

    // -----------------------------------------------------------------------
    // RFCs
    // -----------------------------------------------------------------------

    public function rfcs(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $query = CustomerReturn::with(['sale', 'creator'])
            ->withCount('items')
            ->when($search, fn ($q) => $q->where('rfc_number', 'like', "%{$search}%"))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at');

        $rfcs = $query->paginate(25)->withQueryString();

        return view('mobile.warehouse.rfc.index', compact('rfcs', 'search', 'status'));
    }

    public function showRfc(CustomerReturn $customerReturn)
    {
        $customerReturn->load(['sale', 'pickTicket', 'items', 'creator']);

        return view('mobile.warehouse.rfc.show', compact('customerReturn'));
    }

    // -----------------------------------------------------------------------
    // RTVs
    // -----------------------------------------------------------------------

    public function rtvs(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $query = InventoryReturn::with(['vendor', 'purchaseOrder'])
            ->withCount('items')
            ->when($search, fn ($q) => $q->where('return_number', 'like', "%{$search}%"))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('created_at');

        $rtvs = $query->paginate(25)->withQueryString();

        return view('mobile.warehouse.rtv.index', compact('rtvs', 'search', 'status'));
    }

    public function showRtv(InventoryReturn $inventoryReturn)
    {
        $inventoryReturn->load(['vendor', 'purchaseOrder.sale', 'items', 'returnedBy']);

        return view('mobile.warehouse.rtv.show', compact('inventoryReturn'));
    }
}
