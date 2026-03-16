<?php

namespace App\Services;

use App\Models\InventoryAllocation;
use App\Models\PickTicket;
use App\Models\PickTicketItem;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class PickTicketService
{
    /**
     * Create a pick ticket from a single inventory allocation.
     * Optionally associates it with a work order.
     */
    public function createFromAllocation(
        InventoryAllocation $allocation,
        ?WorkOrder $workOrder = null
    ): PickTicket {
        $allocation->loadMissing('inventoryReceipt');

        return DB::transaction(function () use ($allocation, $workOrder) {
            $pt = PickTicket::create([
                'sale_id'       => $allocation->sale_id,
                'work_order_id' => $workOrder?->id,
                'status'        => 'pending',
            ]);

            PickTicketItem::create([
                'pick_ticket_id'          => $pt->id,
                'inventory_allocation_id' => $allocation->id,
                'sale_item_id'            => $allocation->sale_item_id,
                'item_name'               => $allocation->inventoryReceipt->item_name,
                'unit'                    => $allocation->inventoryReceipt->unit,
                'quantity'                => $allocation->quantity,
                'sort_order'              => 0,
            ]);

            return $pt;
        });
    }

    /**
     * Create a staging pick ticket from a Work Order.
     * Items are the material sale items linked to the WO via work_order_item_materials.
     * No inventory allocation is required — this is a staging/preparation ticket.
     */
    public function createFromWorkOrder(WorkOrder $workOrder, ?string $stagingNotes = null): PickTicket
    {
        $workOrder->loadMissing(['items.relatedMaterials.saleItem']);

        // Collect unique material sale items across all WO labour items
        $materialSaleItems = $workOrder->items
            ->flatMap(fn ($woItem) => $woItem->relatedMaterials)
            ->map(fn ($mat) => $mat->saleItem)
            ->filter()
            ->unique('id')
            ->values();

        return DB::transaction(function () use ($workOrder, $materialSaleItems, $stagingNotes) {
            $pt = PickTicket::create([
                'sale_id'        => $workOrder->sale_id,
                'work_order_id'  => $workOrder->id,
                'status'         => 'staged',
                'staging_notes'  => $stagingNotes ?: null,
            ]);

            foreach ($materialSaleItems as $index => $saleItem) {
                $itemName = implode(' — ', array_filter([
                    $saleItem->product_type,
                    $saleItem->manufacturer,
                    $saleItem->style,
                    $saleItem->color_item_number,
                ])) ?: 'Material';

                PickTicketItem::create([
                    'pick_ticket_id'          => $pt->id,
                    'inventory_allocation_id' => null,
                    'sale_item_id'            => $saleItem->id,
                    'item_name'               => $itemName,
                    'unit'                    => $saleItem->unit ?? '',
                    'quantity'                => $saleItem->quantity,
                    'sort_order'              => $index,
                ]);
            }

            return $pt;
        });
    }

    /**
     * Mark the pick ticket as delivered.
     * Stamps delivered_at on the ticket and released_at on every linked allocation —
     * signifying the inventory has physically left the warehouse.
     */
    /**
     * Record a delivery (full or partial) against the pick ticket.
     *
     * $itemQtys is a map of [pick_ticket_item_id => qty_delivered_this_time].
     * Items omitted from the map (or with qty 0) are untouched.
     *
     * If every item reaches its full quantity the ticket moves to `delivered`
     * and inventory allocations are released. Otherwise it becomes
     * `partially_delivered` and can be delivered again later.
     */
    public function deliver(
        PickTicket $pickTicket,
        array $itemQtys,
        ?string $receivedBy = null,
        ?string $deliveryNotes = null
    ): void {
        $pickTicket->loadMissing('items');

        DB::transaction(function () use ($pickTicket, $itemQtys, $receivedBy, $deliveryNotes) {
            $now              = now();
            $allFullyDelivered = true;

            foreach ($pickTicket->items as $item) {
                $thisDelivery    = max(0, (float) ($itemQtys[$item->id] ?? 0));
                $alreadyDelivered = (float) $item->delivered_qty;
                $ordered          = (float) $item->quantity;

                if ($thisDelivery > 0) {
                    // Cap so we never exceed the ordered qty
                    $newTotal = min($alreadyDelivered + $thisDelivery, $ordered);
                    $item->update(['delivered_qty' => $newTotal]);
                    $alreadyDelivered = $newTotal;
                }

                if ($alreadyDelivered < $ordered) {
                    $allFullyDelivered = false;
                }
            }

            $noteParts = [];
            if ($receivedBy)    $noteParts[] = 'Received by: ' . $receivedBy;
            if ($deliveryNotes) $noteParts[] = $deliveryNotes;

            if ($allFullyDelivered) {
                // Release all linked inventory allocations
                $allocationIds = $pickTicket->items
                    ->pluck('inventory_allocation_id')
                    ->filter()
                    ->values()
                    ->all();

                InventoryAllocation::whereIn('id', $allocationIds)
                    ->update(['released_at' => $now]);

                $pickTicket->update([
                    'status'       => 'delivered',
                    'delivered_at' => $pickTicket->delivered_at ?? $now,
                    'returned_at'  => null,
                    'notes'        => $noteParts ? implode("\n", $noteParts) : $pickTicket->notes,
                ]);
            } else {
                $pickTicket->update([
                    'status'       => 'partially_delivered',
                    'delivered_at' => $pickTicket->delivered_at ?? $now, // stamp first partial delivery
                    'notes'        => $noteParts ? implode("\n", $noteParts) : $pickTicket->notes,
                ]);
            }
        });
    }

    /**
     * Record a return (full or partial) against the pick ticket.
     *
     * $itemQtys is a map of [pick_ticket_item_id => qty_returned_this_time].
     * Returned qty is added to each item's returned_qty (capped at delivered_qty).
     *
     * If net-at-site (delivered - returned) reaches 0 for all items → status = returned,
     * allocations released_at cleared. Otherwise → partially_delivered.
     */
    public function returnTicket(PickTicket $pickTicket, array $itemQtys = [], ?string $returnNotes = null): void
    {
        $pickTicket->loadMissing('items');

        DB::transaction(function () use ($pickTicket, $itemQtys, $returnNotes) {
            $now        = now();
            $anyStillOut = false;

            foreach ($pickTicket->items as $item) {
                $returning       = max(0, (float) ($itemQtys[$item->id] ?? 0));
                $alreadyReturned = (float) $item->returned_qty;
                $totalDelivered  = (float) $item->delivered_qty;

                if ($returning > 0) {
                    $newReturned = min($alreadyReturned + $returning, $totalDelivered);
                    $item->update(['returned_qty' => $newReturned]);
                    $alreadyReturned = $newReturned;
                }

                $netOut = $totalDelivered - $alreadyReturned;
                if ($netOut > 0) {
                    $anyStillOut = true;
                }
            }

            $noteParts = [];
            if ($returnNotes) {
                $noteParts[] = 'Return notes: ' . $returnNotes;
            }

            if ($anyStillOut) {
                $pickTicket->update([
                    'status'      => 'partially_delivered',
                    'returned_at' => $pickTicket->returned_at ?? $now,
                    'notes'       => $noteParts ? implode("\n", $noteParts) : $pickTicket->notes,
                ]);
            } else {
                // All items fully returned — clear released_at on allocations
                $allocationIds = $pickTicket->items
                    ->pluck('inventory_allocation_id')
                    ->filter()
                    ->values()
                    ->all();

                InventoryAllocation::whereIn('id', $allocationIds)
                    ->update(['released_at' => null]);

                $pickTicket->update([
                    'status'      => 'returned',
                    'returned_at' => $pickTicket->returned_at ?? $now,
                    'notes'       => $noteParts ? implode("\n", $noteParts) : $pickTicket->notes,
                ]);
            }
        });
    }

    /**
     * Mark the pick ticket as ready for pickup.
     */
    public function markReady(PickTicket $pickTicket): void
    {
        $pickTicket->update(['status' => 'ready', 'ready_at' => now()]);
    }

    /**
     * Mark the pick ticket as picked (collected from warehouse shelf).
     */
    public function markPicked(PickTicket $pickTicket): void
    {
        $pickTicket->update(['status' => 'picked', 'picked_at' => now()]);
    }

    /**
     * Cancel the pick ticket.
     * Does not affect allocation released_at — cancelling a pending ticket
     * leaves the inventory allocation intact (stock remains reserved).
     */
    public function cancel(PickTicket $pickTicket): void
    {
        $pickTicket->update(['status' => 'cancelled']);
    }

    /**
     * Unstage a staged pick ticket.
     * Records who unstaged it, when, and the reason. Sets status to cancelled
     * so the work order can be re-staged if needed.
     */
    public function unstage(PickTicket $pickTicket, ?string $reason = null): void
    {
        $pickTicket->update([
            'status'         => 'cancelled',
            'unstaged_by'    => auth()->id(),
            'unstaged_at'    => now(),
            'unstage_reason' => $reason ?: null,
        ]);
    }
}
