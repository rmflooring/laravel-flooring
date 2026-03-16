<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use App\Services\InventoryService;
use App\Services\PickTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryAllocationController extends Controller
{
    public function store(
        Request $request,
        Sale $sale,
        SaleItem $saleItem,
        InventoryService $inventory,
        PickTicketService $pickTickets
    ): RedirectResponse {
        $request->validate([
            'inventory_receipt_id' => ['required', 'integer', 'exists:inventory_receipts,id'],
            'quantity'             => ['required', 'numeric', 'min:0.01'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ]);

        // Ensure the sale item belongs to this sale
        abort_unless($saleItem->sale_id === $sale->id, 404);

        $receipt = InventoryReceipt::with('allocations')->findOrFail($request->inventory_receipt_id);

        $available = max(0, (float) $receipt->quantity_received - $receipt->allocations->sum('quantity'));

        if ((float) $request->quantity > $available) {
            return back()->withErrors([
                'quantity' => "Only {$available} {$receipt->unit} available in that receipt.",
            ])->withInput();
        }

        $allocation = $inventory->allocate($receipt, $saleItem, (float) $request->quantity, $request->notes ?: null);

        // Find the best non-cancelled WO for this sale that covers this material item
        // (via work_order_item_materials linking the labour item to this material sale item)
        $workOrder = WorkOrder::where('sale_id', $sale->id)
            ->where('status', '<>', 'cancelled')
            ->whereHas('items.relatedMaterials', fn ($q) => $q->where('sale_item_id', $saleItem->id))
            ->first();

        $pickTickets->createFromAllocation($allocation, $workOrder);

        return redirect()
            ->route('pages.sales.status', $sale)
            ->with('success', 'Inventory allocated and pick ticket created.');
    }
}
