<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryAllocation;
use App\Models\InventoryReceipt;
use App\Models\PickTicketItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use App\Services\InventoryService;
use App\Services\PickTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAllocationController extends Controller
{
    /**
     * Allocate stock from one or more receipts in a single submission — e.g. the
     * full need splits across two partial receipts, neither of which alone covers
     * it. Previously this only accepted a single inventory_receipt_id/quantity
     * pair, forcing the user to repeat "Assign from stock" once per receipt.
     * Each receipt gets its own InventoryAllocation + pick ticket consolidation
     * (via PickTicketService::createFromAllocation(), which already merges onto
     * the same pick ticket for the sale), all inside one transaction so a
     * mid-way failure (e.g. someone else claimed a receipt's stock first)
     * doesn't leave a partial split applied.
     */
    public function store(
        Request $request,
        Sale $sale,
        SaleItem $saleItem,
        InventoryService $inventory,
        PickTicketService $pickTickets
    ): RedirectResponse {
        abort_unless($saleItem->sale_id === $sale->id, 404);

        $data = $request->validate([
            'quantities'   => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        // Drop receipts that were ticked but left at 0/blank — a checkbox with no
        // real quantity shouldn't silently create a zero-quantity allocation.
        $selections = array_filter(
            $data['quantities'],
            fn ($qty) => $qty !== null && (float) $qty > 0
        );

        if (empty($selections)) {
            return back()
                ->withErrors(['quantities' => 'Select at least one receipt and enter a quantity.'])
                ->withInput();
        }

        // Find the best non-cancelled WO for this sale that covers this material item
        // (via work_order_item_materials linking the labour item to this material sale item)
        $workOrder = WorkOrder::where('sale_id', $sale->id)
            ->where('status', '<>', 'cancelled')
            ->whereHas('items.relatedMaterials', fn ($q) => $q->where('sale_item_id', $saleItem->id))
            ->first();

        $notes = $data['notes'] ?? null;

        try {
            DB::transaction(function () use ($selections, $inventory, $saleItem, $pickTickets, $workOrder, $notes) {
                foreach ($selections as $receiptId => $quantity) {
                    $receipt    = InventoryReceipt::with('allocations')->findOrFail((int) $receiptId);
                    $allocation = $inventory->allocate($receipt, $saleItem, (float) $quantity, $notes);
                    $pickTickets->createFromAllocation($allocation, $workOrder);
                }
            });
        } catch (\InvalidArgumentException $e) {
            // Thrown by InventoryService::allocate() if a receipt's available
            // quantity was exceeded (e.g. a race with another allocation) — the
            // whole transaction above rolls back automatically on exception.
            return back()->withErrors(['quantities' => $e->getMessage()])->withInput();
        }

        $count = count($selections);

        return redirect()
            ->route('pages.sales.status', $sale)
            ->with('success', $count > 1
                ? "Inventory allocated from {$count} receipts and pick ticket updated."
                : 'Inventory allocated and pick ticket created.');
    }

    /**
     * Correct an allocation's quantity (e.g. "Assign from stock" was given the wrong
     * amount). Blocked once the owning pick ticket has progressed past pending/staged —
     * same gate used for every other pick-ticket-item edit (see
     * WarehousePickTicketController::update/addItems/removeItem) — since at that point
     * the quantity may already be reflected in picking/delivery and should go through
     * that flow instead of being silently rewritten here.
     */
    public function update(Request $request, Sale $sale, SaleItem $saleItem, InventoryAllocation $allocation): RedirectResponse
    {
        abort_unless($saleItem->sale_id === $sale->id, 404);
        abort_unless($allocation->sale_item_id === $saleItem->id, 404);

        $ptItem   = PickTicketItem::where('inventory_allocation_id', $allocation->id)->with('pickTicket')->first();
        $ptStatus = $ptItem?->pickTicket?->status;
        abort_unless(
            $ptStatus === null || in_array($ptStatus, ['pending', 'staged']),
            422,
            'This allocation can no longer be edited — its pick ticket has already progressed.'
        );

        $request->validate([
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        $allocation->loadMissing('inventoryReceipt');
        // The ceiling is what's available on the receipt PLUS this allocation's own
        // current quantity — available_qty already subtracts this allocation along
        // with every other one, so adding it back reconstitutes "how much could this
        // one allocation be set to at most" without double-counting itself.
        $ceiling = $allocation->inventoryReceipt->available_qty + (float) $allocation->quantity;

        if ((float) $request->quantity > $ceiling) {
            return back()->withErrors([
                'quantity' => "Only {$ceiling} {$allocation->inventoryReceipt->unit} available in that receipt.",
            ])->withInput();
        }

        $allocation->update(['quantity' => $request->quantity]);
        $ptItem?->update(['quantity' => $request->quantity]);

        return redirect()
            ->route('pages.sales.status', $sale)
            ->with('success', 'Allocation updated.');
    }

    /**
     * Fully revert an allocation (e.g. "Assign from stock" was done against the wrong
     * receipt entirely). Nulls the owning pick ticket item's allocation link rather than
     * deleting the item itself — restoring it to the same "staged, not yet linked to
     * inventory" placeholder state it would be in if stock had never been assigned —
     * then deletes the allocation row, which is what actually frees the quantity back
     * to available_qty (unlike the released_at flag, which available_qty always counts
     * regardless — that flag means "physically shipped," not "undo this reservation").
     */
    public function destroy(Sale $sale, SaleItem $saleItem, InventoryAllocation $allocation): RedirectResponse
    {
        abort_unless($saleItem->sale_id === $sale->id, 404);
        abort_unless($allocation->sale_item_id === $saleItem->id, 404);

        $ptItem   = PickTicketItem::where('inventory_allocation_id', $allocation->id)->with('pickTicket')->first();
        $ptStatus = $ptItem?->pickTicket?->status;
        abort_unless(
            $ptStatus === null || in_array($ptStatus, ['pending', 'staged']),
            422,
            'This allocation can no longer be removed — its pick ticket has already progressed.'
        );

        $ptItem?->update(['inventory_allocation_id' => null]);
        $allocation->delete();

        return redirect()
            ->route('pages.sales.status', $sale)
            ->with('success', 'Allocation removed — the stock is available again.');
    }
}
