<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\InventoryReturn;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
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
        // Optional: pre-select an inventory receipt
        $receipt = null;
        if ($request->filled('receipt_id')) {
            $receipt = InventoryReceipt::with([
                'purchaseOrder.vendor',
                'purchaseOrder.items',
                'customerReturnItem.customerReturn',
                'allocations',
            ])->findOrFail($request->receipt_id);
        }

        // ALL receipts with available qty > 0 — both PO-linked and RFC-sourced
        $receipts = InventoryReceipt::query()
            ->with([
                'purchaseOrder.vendor',
                'purchaseOrder.items',
                'customerReturnItem.customerReturn',
                'allocations',
                'transactions',
            ])
            ->orderByDesc('received_date')
            ->get()
            ->filter(fn ($r) => $r->available_qty > 0);

        // Batch-infer vendor for RFC receipts via: sale_item → PO item → PO → vendor
        $rfcSaleItemIds = $receipts
            ->filter(fn ($r) => ! $r->purchase_order_id && $r->customerReturnItem?->sale_item_id)
            ->map(fn ($r) => $r->customerReturnItem->sale_item_id)
            ->filter()
            ->unique();

        $saleItemVendorMap = [];
        if ($rfcSaleItemIds->isNotEmpty()) {
            PurchaseOrderItem::whereIn('sale_item_id', $rfcSaleItemIds)
                ->with('purchaseOrder.vendor')
                ->get()
                ->each(function ($poItem) use (&$saleItemVendorMap) {
                    $sid = $poItem->sale_item_id;
                    if ($sid && $poItem->purchaseOrder?->vendor_id && ! isset($saleItemVendorMap[$sid])) {
                        $saleItemVendorMap[$sid] = [
                            'vendor_id'   => $poItem->purchaseOrder->vendor_id,
                            'vendor_name' => $poItem->purchaseOrder->vendor->company_name ?? '',
                        ];
                    }
                });
        }

        $vendors = Vendor::where('status', 'active')->orderBy('company_name')->get();

        return view('pages.inventory.rtv.create', compact('receipt', 'receipts', 'vendors', 'saleItemVendorMap'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'inventory_receipt_id'           => ['required', 'exists:inventory_receipts,id'],
            'vendor_id'                      => ['nullable', 'exists:vendors,id'],
            'reason'                         => ['required', 'string', 'in:wrong_item,damaged,overstock,cancelled_job'],
            'notes'                          => ['nullable', 'string', 'max:2000'],
            'items'                          => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.item_name'              => ['nullable', 'string', 'max:500'],
            'items.*.unit'                   => ['nullable', 'string', 'max:50'],
            'items.*.quantity_returned'      => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost'              => ['nullable', 'numeric', 'min:0'],
        ]);

        $receipt = InventoryReceipt::with([
            'purchaseOrder.vendor',
            'purchaseOrder.items',
            'customerReturnItem',
        ])->findOrFail($request->inventory_receipt_id);

        // Vendor: from PO if PO-linked, otherwise from form input
        $vendorId = $receipt->purchaseOrder?->vendor_id ?? $request->vendor_id;
        abort_unless($vendorId, 422, 'A vendor is required. Select one or link this receipt to a PO.');

        // sale_item_id from RFC chain (used as fallback for non-PO receipts)
        $rfcSaleItemId = $receipt->customerReturnItem?->sale_item_id;

        $rtv = InventoryReturn::create([
            'purchase_order_id' => $receipt->purchase_order_id,
            'vendor_id'         => $vendorId,
            'reason'            => $request->reason,
            'notes'             => $request->notes ?: null,
            'status'            => 'draft',
            'outcome'           => 'pending',
        ]);

        foreach ($request->items as $itemData) {
            $poItemId    = $itemData['purchase_order_item_id'] ?? null;
            $poItem      = $poItemId ? \App\Models\PurchaseOrderItem::find($poItemId) : null;
            $qty         = (float) $itemData['quantity_returned'];
            $unitCost    = $poItem ? (float) $poItem->cost_price : (float) ($itemData['unit_cost'] ?? 0);
            $itemName    = $poItem ? $poItem->item_name : ($itemData['item_name'] ?? $receipt->item_name);
            $unit        = $poItem ? $poItem->unit : ($itemData['unit'] ?? $receipt->unit);

            // sale_item_id: from PO item if PO-linked, otherwise from the RFC chain
            $saleItemId  = $poItem?->sale_item_id ?? $rfcSaleItemId;

            $rtv->items()->create([
                'inventory_receipt_id'   => $receipt->id,
                'purchase_order_item_id' => $poItem?->id,
                'sale_item_id'           => $saleItemId,
                'item_name'              => $itemName,
                'unit'                   => $unit,
                'quantity_returned'      => $qty,
                'unit_cost'              => $unitCost,
                'line_total'             => round($qty * $unitCost, 2),
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
            'items.saleItem',
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
            'outcome'                              => ['required', 'string', 'in:pending,credit_note,replacement,refund'],
            'vendor_reference'                     => ['nullable', 'string', 'max:255'],
            'notes'                                => ['nullable', 'string', 'max:2000'],
            'items'                                => ['nullable', 'array'],
            'items.*.credit_received'              => ['nullable', 'numeric', 'min:0'],
            'items.*.apply_to_sale_cost'           => ['nullable', 'boolean'],
        ]);

        // Build per-item credit map keyed by inventory_return_item_id
        $itemCredits = [];
        foreach ($request->input('items', []) as $itemId => $data) {
            $itemCredits[(int) $itemId] = [
                'credit_received'   => $data['credit_received'] ?? 0,
                'apply_to_sale_cost' => ! empty($data['apply_to_sale_cost']),
            ];
        }

        $service->resolve($rtv, $request->outcome, $request->vendor_reference, $request->notes, $itemCredits);

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
