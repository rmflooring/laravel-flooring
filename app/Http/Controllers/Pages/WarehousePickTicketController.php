<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PickTicket;
use App\Models\PickTicketItem;
use App\Models\SaleItem;
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

        // Items from the sale that can still be added to this PT
        $availableSaleItems = collect();
        if (in_array($pickTicket->status, ['staged', 'pending']) && $pickTicket->sale_id) {
            $existingSaleItemIds = $pickTicket->items->pluck('sale_item_id')->filter()->toArray();
            $availableSaleItems = SaleItem::whereHas('room', fn ($q) => $q->where('sale_id', $pickTicket->sale_id))
                ->where('item_type', 'material')
                ->when(!empty($existingSaleItemIds), fn ($q) => $q->whereNotIn('id', $existingSaleItemIds))
                ->with('room')
                ->orderBy('id')
                ->get();
        }

        return view('pages.warehouse.pick-tickets.show', compact('pickTicket', 'packingList', 'availableSaleItems'));
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

    public function addItems(Request $request, PickTicket $pickTicket): RedirectResponse
    {
        abort_unless(in_array($pickTicket->status, ['staged', 'pending']), 422, 'Items can only be added when the pick ticket is staged or pending.');

        $request->validate([
            'sale_item_ids'   => ['required', 'array', 'min:1'],
            'sale_item_ids.*' => ['required', 'integer'],
        ]);

        $existingSaleItemIds = $pickTicket->items()->pluck('sale_item_id')->filter()->toArray();

        // Validate each requested sale item belongs to the PT's sale, is material, and isn't already on the PT
        $saleItems = SaleItem::whereIn('id', $request->sale_item_ids)
            ->whereHas('room', fn ($q) => $q->where('sale_id', $pickTicket->sale_id))
            ->where('item_type', 'material')
            ->whereNotIn('id', $existingSaleItemIds)
            ->with('room')
            ->get();

        if ($saleItems->isEmpty()) {
            return back()->withErrors(['sale_item_ids' => 'No valid items selected.']);
        }

        $nextSortOrder = $pickTicket->items()->max('sort_order') + 1;

        foreach ($saleItems as $index => $saleItem) {
            $itemName = implode(' — ', array_filter([
                $saleItem->product_type,
                $saleItem->manufacturer,
                $saleItem->style,
                $saleItem->color_item_number,
            ])) ?: 'Material';

            PickTicketItem::create([
                'pick_ticket_id'          => $pickTicket->id,
                'inventory_allocation_id' => null,
                'sale_item_id'            => $saleItem->id,
                'item_name'               => $itemName,
                'unit'                    => $saleItem->unit ?? '',
                'quantity'                => $saleItem->quantity,
                'sort_order'              => $nextSortOrder + $index,
            ]);
        }

        return back()->with('success', $saleItems->count() . ' ' . \Str::plural('item', $saleItems->count()) . ' added to pick ticket.');
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
