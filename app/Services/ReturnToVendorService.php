<?php

namespace App\Services;

use App\Models\InventoryAllocation;
use App\Models\InventoryReturn;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;

class ReturnToVendorService
{
    /**
     * Ship the RTV — triggers all three ripple effects in a single transaction:
     *
     *  1. INVENTORY  — negative InventoryTransaction per return item (inventory ↓)
     *                  + proportionally reduce/release InventoryAllocations on the receipt
     *                  (so sale coverage recalculates automatically)
     *  2. PO         — increment purchase_order_items.returned_quantity
     *  3. SALE COVERAGE — recalculates automatically because allocation totals decrease
     */
    public function ship(InventoryReturn $rtv): void
    {
        abort_unless($rtv->isDraft(), 422, 'Only draft RTVs can be shipped.');

        $rtv->loadMissing(['items.inventoryReceipt.allocations', 'items.purchaseOrderItem']);

        DB::transaction(function () use ($rtv) {
            foreach ($rtv->items as $rtvItem) {
                $receipt  = $rtvItem->inventoryReceipt;
                $qty      = (float) $rtvItem->quantity_returned;

                // 1a. Create negative inventory transaction (audit trail)
                InventoryTransaction::create([
                    'inventory_receipt_id' => $receipt->id,
                    'type'                 => 'return_to_vendor',
                    'quantity'             => -$qty,
                    'reference_type'       => InventoryReturn::class,
                    'reference_id'         => $rtv->id,
                    'note'                 => "RTV {$rtv->return_number}: {$rtv->reason_label}",
                    'created_by_user_id'   => auth()->id(),
                ]);

                // 1b. Reduce allocations on this receipt by the returned qty (largest first)
                //     This drops sale coverage for the affected sale items
                $remaining = $qty;
                $allocations = $receipt->allocations
                    ->where('released_at', null)   // only unreleased (still in warehouse)
                    ->sortByDesc('quantity');

                foreach ($allocations as $allocation) {
                    if ($remaining <= 0) break;

                    $allocationQty = (float) $allocation->quantity;

                    if ($remaining >= $allocationQty) {
                        // Remove the whole allocation
                        $remaining -= $allocationQty;
                        $allocation->delete();
                    } else {
                        // Partially reduce
                        $allocation->update(['quantity' => $allocationQty - $remaining]);
                        $remaining = 0;
                    }
                }

                // 2. Update PO item returned_quantity
                $rtvItem->purchaseOrderItem->increment('returned_quantity', $qty);
            }

            // Mark as shipped
            $rtv->update(['status' => 'shipped']);
        });
    }

    /**
     * Resolve the RTV — record the vendor's response (outcome + reference number).
     */
    public function resolve(InventoryReturn $rtv, string $outcome, ?string $vendorReference, ?string $notes): void
    {
        abort_unless($rtv->status === 'shipped', 422, 'Only shipped RTVs can be resolved.');

        $rtv->update([
            'status'           => 'resolved',
            'outcome'          => $outcome,
            'vendor_reference' => $vendorReference ?: null,
            'notes'            => $notes ?: $rtv->notes,
        ]);
    }
}
