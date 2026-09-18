<?php

namespace App\Services;

use App\Models\InventoryAllocation;
use App\Models\InventoryReceipt;
use App\Models\PickTicket;
use App\Models\PickTicketItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class PickTicketService
{
    public function __construct(private InventoryService $inventory) {}

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
            // Consolidate into an existing open PT for the same sale + WO rather than
            // creating a new ticket for every allocation (which causes duplicate PTs per
            // job). This must also catch 'staged' tickets — not just 'pending' — since a
            // material can be staged from the sale/WO page before any stock is allocated
            // to it, and allocating afterward should land on that same ticket.
            $pt = PickTicket::where('sale_id', $allocation->sale_id)
                ->whereIn('status', ['pending', 'staged'])
                ->where('work_order_id', $workOrder?->id)
                ->first();

            if (! $pt) {
                $pt = PickTicket::create([
                    'sale_id'       => $allocation->sale_id,
                    'work_order_id' => $workOrder?->id,
                    'status'        => 'pending',
                ]);
            }

            // If this sale item is already on the ticket as an unallocated placeholder
            // (added when staged, before any stock was allocated), claim that line
            // instead of adding a duplicate row for the same material. A line that
            // already has its own allocation is left alone — that's a genuine second
            // batch (e.g. stock pulled from two different receipts).
            $existingItem = $pt->items()
                ->where('sale_item_id', $allocation->sale_item_id)
                ->whereNull('inventory_allocation_id')
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'inventory_allocation_id' => $allocation->id,
                    'quantity'                => $allocation->quantity,
                ]);

                return $pt;
            }

            $nextSort = (int) $pt->items()->max('sort_order') + 1;

            PickTicketItem::create([
                'pick_ticket_id'          => $pt->id,
                'inventory_allocation_id' => $allocation->id,
                'sale_item_id'            => $allocation->sale_item_id,
                'item_name'               => $allocation->inventoryReceipt->item_name,
                'unit'                    => $allocation->inventoryReceipt->unit,
                'quantity'                => $allocation->quantity,
                'sort_order'              => $nextSort,
            ]);

            return $pt;
        });
    }

    /**
     * Create a staging pick ticket directly from a sale (no Work Order).
     * Used for material-only orders where the customer is picking up or we are delivering.
     * Items are selected by the user from the sale's material items.
     */
    public function createFromSale(
        Sale $sale,
        array $saleItemIds,
        string $fulfillmentType,
        ?string $stagingNotes = null,
        ?string $deliveryDate = null,
        ?string $deliveryTime = null
    ): PickTicket {
        $saleItems = SaleItem::whereIn('id', $saleItemIds)
            ->whereHas('room', fn ($q) => $q->where('sale_id', $sale->id))
            ->where('item_type', 'material')
            ->orderBy('id')
            ->get();

        return DB::transaction(function () use ($sale, $saleItems, $fulfillmentType, $stagingNotes, $deliveryDate, $deliveryTime) {
            $pt = PickTicket::create([
                'sale_id'          => $sale->id,
                'work_order_id'    => null,
                'status'           => 'staged',
                'fulfillment_type' => $fulfillmentType,
                'staging_notes'    => $stagingNotes ?: null,
                'delivery_date'    => $deliveryDate ?: null,
                'delivery_time'    => $deliveryTime ?: null,
            ]);

            foreach ($saleItems as $index => $saleItem) {
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
     * Create a staging pick ticket from a Work Order.
     * Items are the material sale items linked to the WO via work_order_item_materials.
     * No inventory allocation is required — this is a staging/preparation ticket.
     */
    public function createFromWorkOrder(
        WorkOrder $workOrder,
        ?string $stagingNotes = null,
        ?string $fulfillmentType = null,
        ?string $deliveryDate = null,
        ?string $deliveryTime = null,
    ): PickTicket {
        $workOrder->loadMissing(['items.relatedMaterials.saleItem']);

        // Collect unique material sale items across all WO labour items
        $materialSaleItems = $workOrder->items
            ->flatMap(fn ($woItem) => $woItem->relatedMaterials)
            ->map(fn ($mat) => $mat->saleItem)
            ->filter()
            ->unique('id')
            ->values();

        return DB::transaction(function () use ($workOrder, $materialSaleItems, $stagingNotes, $fulfillmentType, $deliveryDate, $deliveryTime) {
            $pt = PickTicket::create([
                'sale_id'          => $workOrder->sale_id,
                'work_order_id'    => $workOrder->id,
                'status'           => 'staged',
                'fulfillment_type' => $fulfillmentType ?: null,
                'staging_notes'    => $stagingNotes ?: null,
                'delivery_date'    => $deliveryDate ?: null,
                'delivery_time'    => $deliveryTime ?: null,
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
     * $receiptSelections is a map of [pick_ticket_item_id => inventory_receipt_id],
     * required for any item being delivered that has no inventory_allocation_id yet
     * (see the "must link a receipt to deliver" guardrail below).
     *
     * If every item reaches its full quantity the ticket moves to `delivered`
     * and inventory allocations are released. Otherwise it becomes
     * `partially_delivered` and can be delivered again later.
     *
     * @throws \InvalidArgumentException if a sale item has already been fully
     *     delivered on a different (non-cancelled) pick ticket — added
     *     2026-09-17 after a real incident where the same 555 SF underlayment
     *     line got "delivered" twice across two separate pick tickets, since
     *     nothing previously checked across tickets for the same sale item.
     * @throws \InvalidArgumentException if an item has no inventory_allocation_id
     *     and no matching entry in $receiptSelections — added the same day,
     *     closing the gap where createFromSale()/createFromWorkOrder() stage
     *     items with no inventory link at all (deliberately, for warehouse
     *     staging flexibility), which meant "delivered" material could sit
     *     with zero effect on stock and be invisible to Sale Status coverage.
     */
    public function deliver(
        PickTicket $pickTicket,
        array $itemQtys,
        ?string $receivedBy = null,
        ?string $deliveryNotes = null,
        array $receiptSelections = [],
    ): void {
        $pickTicket->loadMissing('items.saleItem');

        DB::transaction(function () use ($pickTicket, $itemQtys, $receivedBy, $deliveryNotes, $receiptSelections) {
            $now              = now();
            $allFullyDelivered = true;

            foreach ($pickTicket->items as $item) {
                $thisDelivery    = max(0, (float) ($itemQtys[$item->id] ?? 0));
                $alreadyDelivered = (float) $item->delivered_qty;
                $ordered          = (float) $item->quantity;

                if ($thisDelivery > 0 && $item->sale_item_id) {
                    $saleItem = $item->saleItem;
                    $needed   = $saleItem && $saleItem->order_qty !== null
                        ? (float) $saleItem->order_qty
                        : (float) ($saleItem->quantity ?? $ordered);

                    // Guardrail 1: block delivering more than this sale item actually
                    // needs once every non-cancelled pick ticket is accounted for —
                    // catches a duplicate/second pick ticket for material already
                    // fully delivered elsewhere.
                    $deliveredElsewhere = PickTicketItem::where('sale_item_id', $item->sale_item_id)
                        ->where('id', '<>', $item->id)
                        ->whereHas('pickTicket', fn ($q) => $q->where('status', '<>', 'cancelled'))
                        ->sum('delivered_qty');

                    $projectedTotal = (float) $deliveredElsewhere + $alreadyDelivered + $thisDelivery;

                    if ($projectedTotal > $needed + 0.01) {
                        $stillNeeded = max(0, $needed - $deliveredElsewhere - $alreadyDelivered);
                        throw new \InvalidArgumentException(
                            "\"{$item->item_name}\" — cannot deliver {$thisDelivery} {$item->unit}: "
                            . "{$deliveredElsewhere} {$item->unit} already delivered on another pick ticket "
                            . "for this line, only {$stillNeeded} {$item->unit} still needed. If this is a "
                            . "genuine second batch, increase the sale item's ordered quantity first."
                        );
                    }

                    // Guardrail 2: require this delivery to be backed by a real
                    // inventory allocation — either the one already on the item, or
                    // one selected right now — so stock and Sale Status coverage
                    // never again silently drift from what was actually delivered.
                    if ($item->inventory_allocation_id) {
                        $allocation = InventoryAllocation::with('inventoryReceipt')->find($item->inventory_allocation_id);
                        $receiptAvailable = $allocation->inventoryReceipt->available_qty;

                        if ($thisDelivery > $receiptAvailable + 0.001) {
                            throw new \InvalidArgumentException(
                                "\"{$item->item_name}\" — cannot deliver an additional {$thisDelivery} {$item->unit}: "
                                . "only {$receiptAvailable} {$item->unit} left in the linked receipt (#{$allocation->inventory_receipt_id})."
                            );
                        }

                        $allocation->increment('quantity', $thisDelivery);
                    } else {
                        $receiptId = $receiptSelections[$item->id] ?? null;

                        if (! $receiptId) {
                            throw new \InvalidArgumentException(
                                "\"{$item->item_name}\" has no linked inventory — select which receipt this is "
                                . "coming from before it can be delivered."
                            );
                        }

                        if (! $saleItem) {
                            throw new \InvalidArgumentException(
                                "\"{$item->item_name}\" has no linked sale item — cannot allocate inventory to it."
                            );
                        }

                        $receipt = InventoryReceipt::with('allocations')->findOrFail($receiptId);

                        $newAllocation = $this->inventory->allocate(
                            $receipt,
                            $saleItem,
                            $thisDelivery,
                            "Linked at delivery time (Pick Ticket {$pickTicket->pt_number})",
                        );

                        $item->inventory_allocation_id = $newAllocation->id;
                    }
                }

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

            if ($allFullyDelivered) {
                $this->transitionPOsToDelivered($pickTicket);
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
     *
     * Releases any linked inventory allocations that were never delivered
     * (released_at still null) so that reserved stock goes back into the
     * warehouse's available pool. Allocations already released via delivery
     * are left alone, since that stock has physically left the warehouse.
     * Pick ticket item rows are kept (with their allocation link cleared)
     * so the cancelled ticket's history stays visible.
     */
    public function cancel(PickTicket $pickTicket): void
    {
        DB::transaction(function () use ($pickTicket) {
            $pickTicket->loadMissing('items');

            $allocationIds = $pickTicket->items
                ->pluck('inventory_allocation_id')
                ->filter()
                ->values()
                ->all();

            if ($allocationIds) {
                $releasableIds = InventoryAllocation::whereIn('id', $allocationIds)
                    ->whereNull('released_at')
                    ->pluck('id')
                    ->all();

                if ($releasableIds) {
                    PickTicketItem::where('pick_ticket_id', $pickTicket->id)
                        ->whereIn('inventory_allocation_id', $releasableIds)
                        ->update(['inventory_allocation_id' => null]);

                    InventoryAllocation::whereIn('id', $releasableIds)->delete();
                }
            }

            $pickTicket->update(['status' => 'cancelled']);
        });
    }

    /**
     * After a pick ticket is fully delivered, transition any linked POs from
     * 'received' → 'delivered' if every sale item on the PO now has a fully
     * delivered pick ticket item.
     */
    private function transitionPOsToDelivered(PickTicket $pickTicket): void
    {
        $saleItemIds = $pickTicket->items->pluck('sale_item_id')->filter()->unique()->values()->all();
        if (empty($saleItemIds)) {
            return;
        }

        $poIds = PurchaseOrderItem::whereIn('sale_item_id', $saleItemIds)
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.status', 'received')
            ->pluck('purchase_orders.id')
            ->unique()
            ->all();

        foreach ($poIds as $poId) {
            $po = PurchaseOrder::with('items')->find($poId);
            if (! $po) {
                continue;
            }

            $allDelivered = $po->items->every(function ($poItem) {
                if (! $poItem->sale_item_id) {
                    return true; // items without a sale link don't block
                }

                return PickTicketItem::where('sale_item_id', $poItem->sale_item_id)
                    ->join('pick_tickets', 'pick_tickets.id', '=', 'pick_ticket_items.pick_ticket_id')
                    ->where('pick_tickets.status', 'delivered')
                    ->whereColumn('pick_ticket_items.delivered_qty', '>=', 'pick_ticket_items.quantity')
                    ->exists();
            });

            if ($allDelivered) {
                $po->update(['status' => 'delivered']);
            }
        }
    }

    /**
     * When a pick ticket delivery is reverted, move any 'delivered' POs that
     * have sale items on this ticket back to 'received'.
     */
    private function revertPOsFromDelivered(PickTicket $pickTicket): void
    {
        $saleItemIds = $pickTicket->items->pluck('sale_item_id')->filter()->unique()->values()->all();
        if (empty($saleItemIds)) {
            return;
        }

        $poIds = PurchaseOrderItem::whereIn('sale_item_id', $saleItemIds)
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.status', 'delivered')
            ->pluck('purchase_orders.id')
            ->unique()
            ->all();

        if (! empty($poIds)) {
            PurchaseOrder::whereIn('id', $poIds)->update(['status' => 'received']);
        }
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

    /**
     * Revert the pick ticket one step backwards in the status flow.
     *
     * ready             → pending  (clears ready_at)
     * picked            → ready    (clears picked_at)
     * delivered         → picked | staged  (resets delivered_qty, clears delivered_at, un-releases allocations)
     * partially_delivered → picked | staged  (resets delivered_qty, clears delivered_at)
     * returned          → delivered (resets returned_qty, clears returned_at, re-releases allocations)
     *
     * For delivered / partially_delivered, the previous status is determined by
     * whether the ticket ever passed through the picked state (picked_at is set).
     * If not, it was staged — revert to staged.
     */
    public function revertStatus(PickTicket $pickTicket): void
    {
        DB::transaction(function () use ($pickTicket) {
            switch ($pickTicket->status) {

                case 'ready':
                    $pickTicket->update(['status' => 'pending', 'ready_at' => null]);
                    break;

                case 'picked':
                    $pickTicket->update(['status' => 'ready', 'picked_at' => null]);
                    break;

                case 'delivered':
                case 'partially_delivered':
                    $pickTicket->loadMissing('items');

                    foreach ($pickTicket->items as $item) {
                        $item->update(['delivered_qty' => 0]);
                    }

                    // Un-release allocations so stock is reserved again
                    $allocationIds = $pickTicket->items
                        ->pluck('inventory_allocation_id')
                        ->filter()->values()->all();

                    if ($allocationIds) {
                        InventoryAllocation::whereIn('id', $allocationIds)
                            ->update(['released_at' => null]);
                    }

                    $previousStatus = $pickTicket->picked_at ? 'picked' : 'staged';
                    $pickTicket->update(['status' => $previousStatus, 'delivered_at' => null]);

                    // Revert any POs that were auto-transitioned to delivered
                    $this->revertPOsFromDelivered($pickTicket);
                    break;

                case 'returned':
                    $pickTicket->loadMissing('items');

                    foreach ($pickTicket->items as $item) {
                        $item->update(['delivered_qty' => 0, 'returned_qty' => 0]);
                    }

                    // Un-release allocations — stock is back in warehouse
                    $allocationIds = $pickTicket->items
                        ->pluck('inventory_allocation_id')
                        ->filter()->values()->all();

                    if ($allocationIds) {
                        InventoryAllocation::whereIn('id', $allocationIds)
                            ->update(['released_at' => null]);
                    }

                    $pickTicket->update([
                        'status'       => 'ready',
                        'delivered_at' => null,
                        'returned_at'  => null,
                    ]);
                    break;
            }
        });
    }
}
