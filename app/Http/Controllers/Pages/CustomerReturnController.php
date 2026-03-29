<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\CustomerReturn;
use App\Models\PickTicket;
use App\Services\CustomerReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerReturnController extends Controller
{
    public function index(Request $request): View
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $rfcs = CustomerReturn::query()
            ->with(['pickTicket', 'sale', 'creator'])
            ->withCount('items')
            ->when($q, fn ($query) => $query->where('rfc_number', 'like', "%{$q}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $statuses = CustomerReturn::STATUS_LABELS;

        $trashedRfcs = collect();
        if (auth()->user()?->hasRole('admin')) {
            $trashedRfcs = CustomerReturn::onlyTrashed()
                ->with(['sale'])
                ->withCount('items')
                ->orderByDesc('deleted_at')
                ->get();
        }

        return view('pages.inventory.rfc.index', compact('rfcs', 'q', 'status', 'statuses', 'trashedRfcs'));
    }

    public function create(Request $request): View
    {
        // Optional: pre-load a PT if pt_id is passed (e.g. from PT show page)
        $pickTicket = null;
        if ($request->filled('pt_id')) {
            $pickTicket = PickTicket::with(['items.saleItem.room', 'sale'])
                ->findOrFail($request->pt_id);
        }

        // List of PTs that have delivered items (for the selector)
        $pickTickets = PickTicket::whereIn('status', ['delivered', 'partially_delivered', 'returned'])
            ->with('sale')
            ->orderByDesc('id')
            ->get();

        return view('pages.inventory.rfc.create', compact('pickTicket', 'pickTickets'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pick_ticket_id'                   => ['nullable', 'exists:pick_tickets,id'],
            'reason'                           => ['nullable', 'string', 'max:2000'],
            'notes'                            => ['nullable', 'string', 'max:2000'],
            'items'                            => ['required', 'array', 'min:1'],
            'items.*.item_name'                => ['required', 'string', 'max:500'],
            'items.*.unit'                     => ['nullable', 'string', 'max:50'],
            'items.*.quantity_returned'        => ['required', 'numeric', 'min:0.01'],
            'items.*.condition'                => ['nullable', 'string', 'in:good,damaged,partial'],
            'items.*.notes'                    => ['nullable', 'string', 'max:1000'],
            'items.*.pick_ticket_item_id'      => ['nullable', 'exists:pick_ticket_items,id'],
            'items.*.sale_item_id'             => ['nullable', 'exists:sale_items,id'],
        ]);

        // Resolve sale_id from pick ticket if provided
        $saleId = null;
        if ($request->filled('pick_ticket_id')) {
            $saleId = PickTicket::find($request->pick_ticket_id)?->sale_id;
        }

        $rfc = CustomerReturn::create([
            'pick_ticket_id' => $request->pick_ticket_id ?: null,
            'sale_id'        => $saleId,
            'status'         => 'draft',
            'reason'         => $request->reason ?: null,
            'notes'          => $request->notes ?: null,
        ]);

        foreach ($request->items as $itemData) {
            $rfc->items()->create([
                'pick_ticket_item_id' => $itemData['pick_ticket_item_id'] ?? null,
                'sale_item_id'        => $itemData['sale_item_id'] ?? null,
                'item_name'           => $itemData['item_name'],
                'unit'                => $itemData['unit'] ?? '',
                'quantity_returned'   => $itemData['quantity_returned'],
                'condition'           => $itemData['condition'] ?? null,
                'notes'               => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->route('pages.inventory.rfc.show', $rfc)
            ->with('success', "RFC {$rfc->rfc_number} created as draft.");
    }

    public function show(CustomerReturn $rfc): View
    {
        $rfc->load(['pickTicket.sale', 'items.pickTicketItem', 'items.saleItem.room', 'items.inventoryReceipt', 'creator', 'updater']);

        return view('pages.inventory.rfc.show', compact('rfc'));
    }

    public function edit(CustomerReturn $rfc): View
    {
        abort_unless($rfc->isDraft(), 403, 'Only draft RFCs can be edited.');

        $rfc->load(['items.pickTicketItem', 'items.saleItem.room']);

        $pickTickets = PickTicket::whereIn('status', ['delivered', 'partially_delivered', 'returned'])
            ->with('sale')
            ->orderByDesc('id')
            ->get();

        return view('pages.inventory.rfc.edit', compact('rfc', 'pickTickets'));
    }

    public function update(Request $request, CustomerReturn $rfc): RedirectResponse
    {
        abort_unless($rfc->isDraft(), 403, 'Only draft RFCs can be edited.');

        $request->validate([
            'reason'                           => ['nullable', 'string', 'max:2000'],
            'notes'                            => ['nullable', 'string', 'max:2000'],
            'items'                            => ['required', 'array', 'min:1'],
            'items.*.item_name'                => ['required', 'string', 'max:500'],
            'items.*.unit'                     => ['nullable', 'string', 'max:50'],
            'items.*.quantity_returned'        => ['required', 'numeric', 'min:0.01'],
            'items.*.condition'                => ['nullable', 'string', 'in:good,damaged,partial'],
            'items.*.notes'                    => ['nullable', 'string', 'max:1000'],
            'items.*.pick_ticket_item_id'      => ['nullable', 'exists:pick_ticket_items,id'],
            'items.*.sale_item_id'             => ['nullable', 'exists:sale_items,id'],
        ]);

        $rfc->update([
            'reason' => $request->reason ?: null,
            'notes'  => $request->notes ?: null,
        ]);

        // Replace all items
        $rfc->items()->delete();

        foreach ($request->items as $itemData) {
            $rfc->items()->create([
                'pick_ticket_item_id' => $itemData['pick_ticket_item_id'] ?? null,
                'sale_item_id'        => $itemData['sale_item_id'] ?? null,
                'item_name'           => $itemData['item_name'],
                'unit'                => $itemData['unit'] ?? '',
                'quantity_returned'   => $itemData['quantity_returned'],
                'condition'           => $itemData['condition'] ?? null,
                'notes'               => $itemData['notes'] ?? null,
            ]);
        }

        return redirect()->route('pages.inventory.rfc.show', $rfc)
            ->with('success', 'RFC updated.');
    }

    public function receive(Request $request, CustomerReturn $rfc, CustomerReturnService $service): RedirectResponse
    {
        abort_unless($rfc->isDraft(), 403, 'Only draft RFCs can be received.');

        $request->validate([
            'received_by'   => ['nullable', 'string', 'max:255'],
            'received_date' => ['nullable', 'date'],
        ]);

        $service->receive($rfc, $request->received_by, $request->received_date);

        return redirect()->route('pages.inventory.rfc.show', $rfc)
            ->with('success', "RFC {$rfc->rfc_number} marked as received. Inventory updated.");
    }

    public function destroy(CustomerReturn $rfc): RedirectResponse
    {
        abort_unless($rfc->isDraft(), 403, 'Only draft RFCs can be deleted.');

        $rfc->delete();

        return redirect()->route('pages.inventory.rfc.index')
            ->with('success', "RFC {$rfc->rfc_number} deleted.");
    }

    public function restore(CustomerReturn $rfc): RedirectResponse
    {
        $rfc->restore();

        return redirect()->route('pages.inventory.rfc.index')
            ->with('success', "RFC {$rfc->rfc_number} restored.");
    }

    public function forceDestroy(CustomerReturn $rfc): RedirectResponse
    {
        $hasReceipts = $rfc->items()->withTrashed()
            ->whereHas('inventoryReceipt')
            ->exists();

        abort_if($hasReceipts, 422, "RFC {$rfc->rfc_number} cannot be permanently deleted — it has inventory receipts linked to its items.");

        $rfc->items()->withTrashed()->forceDelete();
        $rfc->forceDelete();

        return redirect()->route('pages.inventory.rfc.index')
            ->with('success', "RFC {$rfc->rfc_number} permanently deleted.");
    }
}
