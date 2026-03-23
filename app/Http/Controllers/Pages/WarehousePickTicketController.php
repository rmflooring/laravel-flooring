<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PickTicket;
use App\Services\PickTicketService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class WarehousePickTicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = PickTicket::query()
            ->with(['sale', 'workOrder', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('pt_number', 'like', '%' . $request->search . '%');
            });

        // Active statuses first (pending, ready, picked), then terminal (delivered, returned, cancelled)
        $query->orderByRaw("
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
        ")->orderBy('id');

        $pickTickets = $query->paginate(30)->withQueryString();

        $statuses = PickTicket::STATUS_LABELS;

        return view('pages.warehouse.pick-tickets.index', compact('pickTickets', 'statuses'));
    }

    public function show(PickTicket $pickTicket): View
    {
        $pickTicket->load(['sale', 'workOrder.installer', 'items.saleItem.room', 'creator', 'updater', 'unstagedBy']);

        $packingList = \App\Models\PackingList::where('pick_ticket_id', $pickTicket->id)->first();

        return view('pages.warehouse.pick-tickets.show', compact('pickTicket', 'packingList'));
    }

    public function pdf(PickTicket $pickTicket): Response
    {
        $pickTicket->load(['sale', 'workOrder.installer', 'items.saleItem.room', 'creator']);

        $pdf = Pdf::loadView('pdf.pick-ticket', compact('pickTicket'));

        return $pdf->stream('pick-ticket-' . $pickTicket->pt_number . '.pdf');
    }

    public function unstage(Request $request, PickTicket $pickTicket, PickTicketService $service): RedirectResponse
    {
        abort_unless($pickTicket->status === 'staged', 422);

        $request->validate([
            'unstage_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->unstage($pickTicket, $request->input('unstage_reason'));

        return back()->with('success', 'Pick ticket has been unstaged.');
    }

    public function updateStatus(Request $request, PickTicket $pickTicket, PickTicketService $service): RedirectResponse
    {
        $request->validate([
            'action'         => ['required', 'string', 'in:mark_ready,mark_picked,deliver,cancel,revert_status'],
            'received_by'    => ['nullable', 'string', 'max:255'],
            'delivery_notes' => ['nullable', 'string', 'max:2000'],
            'items'          => ['nullable', 'array'],
            'items.*'        => ['nullable', 'numeric', 'min:0'],
        ]);

        match ($request->action) {
            'mark_ready'     => $service->markReady($pickTicket),
            'mark_picked'    => $service->markPicked($pickTicket),
            'deliver'        => $service->deliver(
                                    $pickTicket,
                                    $request->input('items', []),
                                    $request->input('received_by'),
                                    $request->input('delivery_notes')
                                ),
            'cancel'         => $service->cancel($pickTicket),
            'revert_status'  => $service->revertStatus($pickTicket),
        };

        return back()->with('success', 'Pick ticket updated.');
    }
}
