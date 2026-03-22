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

                // 2. Update PO item returned_quantity (only if linked — RFC-sourced items have no PO item)
                $rtvItem->purchaseOrderItem?->increment('returned_quantity', $qty);
            }

            // Mark as shipped
            $rtv->update(['status' => 'shipped']);
        });
    }

    /**
     * Resolve the RTV — record the vendor's response (outcome + reference number).
     *
     * When outcome = credit_note, per-item credit_received amounts can be passed
     * and optionally applied to the linked sale item's cost_total.
     *
     * @param array $itemCredits  [inventory_return_item_id => ['credit_received' => float, 'apply_to_sale_cost' => bool]]
     */
    public function resolve(
        InventoryReturn $rtv,
        string $outcome,
        ?string $vendorReference,
        ?string $notes,
        array $itemCredits = []
    ): void {
        abort_unless($rtv->status === 'shipped', 422, 'Only shipped RTVs can be resolved.');

        DB::transaction(function () use ($rtv, $outcome, $vendorReference, $notes, $itemCredits) {
            // Apply per-item credits if outcome is credit_note
            if ($outcome === 'credit_note' && ! empty($itemCredits)) {
                $rtv->loadMissing('items.saleItem');

                foreach ($rtv->items as $rtvItem) {
                    $creditData = $itemCredits[$rtvItem->id] ?? null;
                    if (! $creditData) continue;

                    $creditReceived  = (float) ($creditData['credit_received'] ?? 0);
                    $applyToSaleCost = ! empty($creditData['apply_to_sale_cost']);

                    $itemUpdates = [
                        'credit_received'    => $creditReceived > 0 ? $creditReceived : null,
                        'apply_to_sale_cost' => $applyToSaleCost,
                    ];

                    // Reduce order_qty by the returned quantity, leaving the original design qty
                    // untouched. The profits page uses order_qty (when set) for cost calculations,
                    // so this automatically reduces displayed cost and improves profit margin.
                    if ($applyToSaleCost && $rtvItem->saleItem) {
                        $saleItem    = $rtvItem->saleItem;
                        $qtyReturned = (float) $rtvItem->quantity_returned;

                        $currentOrderQty = $saleItem->order_qty !== null
                            ? (float) $saleItem->order_qty
                            : (float) $saleItem->quantity;
                        $newOrderQty = max(0, $currentOrderQty - $qtyReturned);

                        // Bypass the saving hook (which recalculates cost_total from quantity, not order_qty)
                        DB::table('sale_items')->where('id', $saleItem->id)->update([
                            'order_qty' => $newOrderQty,
                        ]);

                        $itemUpdates['cost_applied_at'] = now();
                    }

                    $rtvItem->update($itemUpdates);
                }
            }

            $rtv->update([
                'status'           => 'resolved',
                'outcome'          => $outcome,
                'vendor_reference' => $vendorReference ?: null,
                'notes'            => $notes ?: $rtv->notes,
            ]);
        });
    }
}
