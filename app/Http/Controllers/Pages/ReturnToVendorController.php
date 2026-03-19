<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\InventoryReturn;
use App\Services\ReturnToVendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnToVendorController extends Controller
{
    public function index(Request $request): View
    {
        $q      = trim($request->input('q', ''));
        $status = $request->input('status', '');

        $rtvs = InventoryReturn::query()
            ->with(['purchaseOrder', 'vendor'])
            ->withCount('items')
            ->when($q, fn ($query) => $query->where('return_number', 'like', "%{$q}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $statuses = InventoryReturn::STATUS_LABELS;

        return view('pages.inventory.rtv.index', compact('rtvs', 'q', 'status', 'statuses'));
    }

    public function create(Request $request): View
    {
        // Optional: pre-select an inventory receipt to return
        $receipt = null;
        if ($request->filled('receipt_id')) {
            $receipt = InventoryReceipt::with([
                'purchaseOrder.vendor',
                'purchaseOrder.items',
                'allocations',
            ])->findOrFail($request->receipt_id);
        }

        // Receipts that have something left to potentially return
        // (received from a PO, not fully returned yet)
        $receipts = InventoryReceipt::query()
            ->whereNotNull('purchase_order_id')
            ->with(['purchaseOrder.vendor', 'purchaseOrder.items', 'returnItems'])
            ->orderByDesc('received_date')
            ->get()
            ->filter(fn ($r) => (float) $r->quantity_received > $r->returnItems->sum('quantity_returned'));

        return view('pages.inventory.rtv.create', compact('receipt', 'receipts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'inventory_receipt_id'      => ['required', 'exists:inventory_receipts,id'],
            'reason'                    => ['required', 'string', 'in:wrong_item,damaged,overstock,cancelled_job'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_returned' => ['required', 'numeric', 'min:0.01'],
        ]);

        $receipt = InventoryReceipt::with('purchaseOrder.vendor')->findOrFail($request->inventory_receipt_id);

        abort_unless($receipt->purchase_order_id, 422, 'Selected receipt is not linked to a PO.');

        $rtv = InventoryReturn::create([
            'purchase_order_id'    => $receipt->purchase_order_id,
            'vendor_id'            => $receipt->purchaseOrder->vendor_id,
            'reason'               => $request->reason,
            'notes'                => $request->notes ?: null,
            'status'               => 'draft',
            'outcome'              => 'pending',
        ]);

        foreach ($request->items as $itemData) {
            $poItem = \App\Models\PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);
            $qty    = (float) $itemData['quantity_returned'];

            $rtv->items()->create([
                'inventory_receipt_id'    => $receipt->id,
                'purchase_order_item_id'  => $poItem->id,
                'quantity_returned'       => $qty,
                'unit_cost'               => $poItem->cost_price,
                'line_total'              => round($qty * (float) $poItem->cost_price, 2),
            ]);
        }

        return redirect()->route('pages.inventory.rtv.show', $rtv)
            ->with('success', "RTV {$rtv->return_number} created as draft.");
    }

    public function show(InventoryReturn $rtv): View
    {
        $rtv->load([
            'purchaseOrder.vendor',
            'items.inventoryReceipt',
            'items.purchaseOrderItem',
            'returnedBy',
        ]);

        return view('pages.inventory.rtv.show', compact('rtv'));
    }

    public function edit(InventoryReturn $rtv): View
    {
        abort_unless($rtv->isDraft(), 403, 'Only draft RTVs can be edited.');

        $rtv->load(['items.inventoryReceipt.purchaseOrder.vendor', 'items.purchaseOrderItem']);

        return view('pages.inventory.rtv.edit', compact('rtv'));
    }

    public function update(Request $request, InventoryReturn $rtv): RedirectResponse
    {
        abort_unless($rtv->isDraft(), 403, 'Only draft RTVs can be edited.');

        $request->validate([
            'reason'                    => ['required', 'string', 'in:wrong_item,damaged,overstock,cancelled_job'],
            'notes'                     => ['nullable', 'string', 'max:2000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_returned' => ['required', 'numeric', 'min:0.01'],
        ]);

        $rtv->update([
            'reason' => $request->reason,
            'notes'  => $request->notes ?: null,
        ]);

        $rtv->items()->delete();

        $receiptId = $rtv->items()->withTrashed()->value('inventory_receipt_id')
            ?? $request->input('inventory_receipt_id');

        foreach ($request->items as $itemData) {
            $poItem = \App\Models\PurchaseOrderItem::findOrFail($itemData['purchase_order_item_id']);
            $qty    = (float) $itemData['quantity_returned'];

            $rtv->items()->create([
                'inventory_receipt_id'   => $itemData['inventory_receipt_id'],
                'purchase_order_item_id' => $poItem->id,
                'quantity_returned'      => $qty,
                'unit_cost'              => $poItem->cost_price,
                'line_total'             => round($qty * (float) $poItem->cost_price, 2),
            ]);
        }

        return redirect()->route('pages.inventory.rtv.show', $rtv)
            ->with('success', 'RTV updated.');
    }

    public function ship(InventoryReturn $rtv, ReturnToVendorService $service): RedirectResponse
    {
        $service->ship($rtv);

        return redirect()->route('pages.inventory.rtv.show', $rtv)
            ->with('success', "RTV {$rtv->return_number} marked as shipped. Inventory adjusted.");
    }

    public function resolve(Request $request, InventoryReturn $rtv, ReturnToVendorService $service): RedirectResponse
    {
        $request->validate([
            'outcome'          => ['required', 'string', 'in:pending,credit_note,replacement,refund'],
            'vendor_reference' => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $service->resolve($rtv, $request->outcome, $request->vendor_reference, $request->notes);

        return redirect()->route('pages.inventory.rtv.show', $rtv)
            ->with('success', "RTV {$rtv->return_number} resolved.");
    }

    public function destroy(InventoryReturn $rtv): RedirectResponse
    {
        abort_unless($rtv->isDraft(), 403, 'Only draft RTVs can be deleted.');

        $rtv->delete();

        return redirect()->route('pages.inventory.rtv.index')
            ->with('success', "RTV {$rtv->return_number} deleted.");
    }
}
