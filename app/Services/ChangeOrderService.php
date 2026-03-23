<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleChangeOrder;
use App\Models\SaleChangeOrderItem;
use App\Models\SaleChangeOrderRoom;
use App\Models\SaleRoom;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class ChangeOrderService
{
    /**
     * Create a new CO for a sale. Snapshots current rooms + items.
     * Sale status moves to change_in_progress.
     */
    public function create(Sale $sale, array $data, int $userId): SaleChangeOrder
    {
        return DB::transaction(function () use ($sale, $data, $userId) {
            $co = SaleChangeOrder::create([
                'sale_id'               => $sale->id,
                'status'                => 'draft',
                'title'                 => $data['title'] ?? null,
                'reason'                => $data['reason'] ?? null,
                'notes'                 => $data['notes'] ?? null,
                'original_pretax_total' => $sale->pretax_total,
                'original_tax_amount'   => $sale->tax_amount,
                'original_grand_total'  => $sale->grand_total,
                'created_by'            => $userId,
                'updated_by'            => $userId,
            ]);

            // Snapshot all current rooms + items
            foreach ($sale->rooms()->with('items')->get() as $room) {
                $coRoom = SaleChangeOrderRoom::create([
                    'sale_change_order_id' => $co->id,
                    'sale_room_id'         => $room->id,
                    'room_name'            => $room->room_name,
                    'sort_order'           => $room->sort_order,
                    'subtotal_materials'   => $room->subtotal_materials,
                    'subtotal_labour'      => $room->subtotal_labour,
                    'subtotal_freight'     => $room->subtotal_freight,
                    'room_total'           => $room->room_total,
                ]);

                foreach ($room->items as $item) {
                    SaleChangeOrderItem::create([
                        'sale_change_order_id'      => $co->id,
                        'sale_change_order_room_id' => $coRoom->id,
                        'sale_item_id'              => $item->id,
                        'item_type'                 => $item->item_type,
                        'quantity'                  => $item->quantity,
                        'unit'                      => $item->unit,
                        'sell_price'                => $item->sell_price,
                        'line_total'                => $item->line_total,
                        'notes'                     => $item->notes,
                        'sort_order'                => $item->sort_order,
                        'product_type'              => $item->product_type,
                        'manufacturer'              => $item->manufacturer,
                        'style'                     => $item->style,
                        'color_item_number'         => $item->color_item_number,
                        'po_notes'                  => $item->po_notes,
                        'labour_type'               => $item->labour_type,
                        'description'               => $item->description,
                        'freight_description'       => $item->freight_description,
                    ]);

                }
            }

            // Move sale to change_in_progress
            $sale->update([
                'status'     => 'change_in_progress',
                'has_changes' => true,
                'changed_at'  => now(),
                'changed_by'  => $userId,
            ]);

            return $co->fresh();
        });
    }

    /**
     * Calculate the delta between the CO snapshot and current sale items.
     * Returns a structured array of rooms with before/after/delta data.
     */
    public function calculateDelta(SaleChangeOrder $co): array
    {
        $co->load('rooms.items');
        $sale = $co->sale;
        $sale->load('rooms.items');

        // Index current sale rooms by ID for quick lookup
        $currentRoomsById = $sale->rooms->keyBy('id');

        $rooms = [];

        // --- Process snapshot rooms (original) ---
        foreach ($co->rooms as $snapshotRoom) {
            $currentRoom = $snapshotRoom->sale_room_id
                ? $currentRoomsById->get($snapshotRoom->sale_room_id)
                : null;

            $roomRows = [];

            // Match snapshot items to current items by sort_order (positional matching).
            // We cannot rely on sale_item_id because the sale update delete+recreates all
            // items on every save, which would break any ID-based reference.
            $snapItems    = $snapshotRoom->items->sortBy('sort_order')->values();
            $currentItems = $currentRoom
                ? $currentRoom->items->sortBy('sort_order')->values()
                : collect();

            $maxIndex = max($snapItems->count(), $currentItems->count());

            for ($i = 0; $i < $maxIndex; $i++) {
                $snapItem    = $snapItems->get($i);
                $currentItem = $currentItems->get($i);

                if ($snapItem && ! $currentItem) {
                    // Item was removed
                    $roomRows[] = [
                        'status'      => 'removed',
                        'label'       => $this->itemLabel($snapItem),
                        'item_type'   => $snapItem->item_type,
                        'orig_qty'    => $snapItem->quantity,
                        'orig_price'  => $snapItem->sell_price,
                        'orig_total'  => $snapItem->line_total,
                        'new_qty'     => null,
                        'new_price'   => null,
                        'new_total'   => null,
                        'delta'       => -$snapItem->line_total,
                    ];
                } elseif (! $snapItem && $currentItem) {
                    // Item was added
                    $roomRows[] = [
                        'status'      => 'added',
                        'label'       => $this->itemLabel($currentItem),
                        'item_type'   => $currentItem->item_type,
                        'orig_qty'    => null,
                        'orig_price'  => null,
                        'orig_total'  => null,
                        'new_qty'     => $currentItem->quantity,
                        'new_price'   => $currentItem->sell_price,
                        'new_total'   => $currentItem->line_total,
                        'delta'       => $currentItem->line_total,
                    ];
                } else {
                    // Both exist at this position — compare values
                    $itemDelta = $currentItem->line_total - $snapItem->line_total;
                    $labelChanged = $this->itemLabel($snapItem) !== $this->itemLabel($currentItem);
                    $status = (abs($itemDelta) < 0.01 && ! $labelChanged) ? 'unchanged' : 'changed';

                    $roomRows[] = [
                        'status'      => $status,
                        'label'       => $this->itemLabel($currentItem),
                        'orig_label'  => $this->itemLabel($snapItem),
                        'item_type'   => $currentItem->item_type,
                        'orig_qty'    => $snapItem->quantity,
                        'orig_price'  => $snapItem->sell_price,
                        'orig_total'  => $snapItem->line_total,
                        'new_qty'     => $currentItem->quantity,
                        'new_price'   => $currentItem->sell_price,
                        'new_total'   => $currentItem->line_total,
                        'delta'       => $itemDelta,
                    ];
                }
            }

            $origRoomTotal    = $snapshotRoom->room_total;
            $newRoomTotal     = $currentRoom?->room_total ?? 0;
            $roomDelta        = $newRoomTotal - $origRoomTotal;
            $roomStatus       = $currentRoom ? (abs($roomDelta) < 0.01 ? 'unchanged' : 'changed') : 'removed';

            $rooms[] = [
                'status'         => $roomStatus,
                'room_name'      => $snapshotRoom->room_name,
                'orig_total'     => $origRoomTotal,
                'new_total'      => $newRoomTotal,
                'delta'          => $roomDelta,
                'rows'           => $roomRows,
                'snapshot_room'  => $snapshotRoom,
                'current_room'   => $currentRoom,
            ];
        }

        // --- Newly added rooms (no snapshot counterpart) ---
        $snapshotRoomSaleIds = $co->rooms->pluck('sale_room_id')->filter()->all();

        foreach ($sale->rooms as $currentRoom) {
            if (! in_array($currentRoom->id, $snapshotRoomSaleIds)) {
                $roomRows = [];

                foreach ($currentRoom->items as $currentItem) {
                    $roomRows[] = [
                        'status'      => 'added',
                        'label'       => $this->itemLabel($currentItem),
                        'item_type'   => $currentItem->item_type,
                        'orig_qty'    => null,
                        'orig_price'  => null,
                        'orig_total'  => null,
                        'new_qty'     => $currentItem->quantity,
                        'new_price'   => $currentItem->sell_price,
                        'new_total'   => $currentItem->line_total,
                        'delta'       => $currentItem->line_total,
                    ];
                }

                $rooms[] = [
                    'status'        => 'added',
                    'room_name'     => $currentRoom->room_name,
                    'orig_total'    => 0,
                    'new_total'     => $currentRoom->room_total,
                    'delta'         => $currentRoom->room_total,
                    'rows'          => $roomRows,
                    'snapshot_room' => null,
                    'current_room'  => $currentRoom,
                ];
            }
        }

        $origGrandTotal    = $co->original_grand_total;
        $newGrandTotal     = $sale->grand_total;
        $grandDelta        = $newGrandTotal - $origGrandTotal;

        return [
            'rooms'             => $rooms,
            'orig_grand_total'  => $origGrandTotal,
            'new_grand_total'   => $newGrandTotal,
            'grand_delta'       => $grandDelta,
        ];
    }

    /**
     * Approve the CO: re-lock the sale at the new totals, update revised_contract_total.
     */
    public function approve(SaleChangeOrder $co, int $userId): void
    {
        DB::transaction(function () use ($co, $userId) {
            $sale = $co->sale;

            $co->update([
                'status'               => 'approved',
                'approved_at'          => now(),
                'approved_by'          => $userId,
                'locked_at'            => now(),
                'locked_by'            => $userId,
                'locked_pretax_total'  => $sale->pretax_total,
                'locked_tax_amount'    => $sale->tax_amount,
                'locked_grand_total'   => $sale->grand_total,
                'updated_by'           => $userId,
            ]);

            // Re-lock the sale at new totals
            $sale->update([
                'status'                 => 'approved',
                'locked_at'              => now(),
                'locked_by'              => $userId,
                'locked_pretax_total'    => $sale->pretax_total,
                'locked_tax_amount'      => $sale->tax_amount,
                'locked_grand_total'     => $sale->grand_total,
                'revised_contract_total' => $sale->grand_total,
                'approved_co_total'      => DB::raw("approved_co_total + ({$sale->grand_total} - {$co->original_grand_total})"),
            ]);
        });
    }

    /**
     * Revert a draft/sent CO: restore sale items from snapshot data, re-lock at original totals.
     * Rebuilds items directly from snapshot — does not rely on sale_item_id being set.
     */
    public function revert(SaleChangeOrder $co, int $userId): void
    {
        DB::transaction(function () use ($co, $userId) {
            $sale = $co->sale;
            $co->load('rooms.items');

            // Track which sale room IDs the snapshot covers
            $snapshotSaleRoomIds = $co->rooms->pluck('sale_room_id')->filter()->all();

            // Restore snapshot rooms
            foreach ($co->rooms as $snapshotRoom) {
                if (! $snapshotRoom->sale_room_id) {
                    continue;
                }

                $saleRoom = SaleRoom::find($snapshotRoom->sale_room_id);
                if (! $saleRoom) {
                    continue;
                }

                // Delete ALL current items in this room, then rebuild from snapshot.
                // This approach is safe regardless of whether sale_item_id is set,
                // avoiding issues with ON DELETE SET NULL not rolling back in MariaDB.
                SaleItem::where('sale_room_id', $saleRoom->id)->delete();

                foreach ($snapshotRoom->items as $snapItem) {
                    SaleItem::create([
                        'sale_id'            => $sale->id,
                        'sale_room_id'       => $saleRoom->id,
                        'item_type'          => $snapItem->item_type,
                        'quantity'           => $snapItem->quantity,
                        'unit'               => $snapItem->unit,
                        'sell_price'         => $snapItem->sell_price,
                        'line_total'         => $snapItem->line_total,
                        'notes'              => $snapItem->notes,
                        'sort_order'         => $snapItem->sort_order,
                        'product_type'       => $snapItem->product_type,
                        'manufacturer'       => $snapItem->manufacturer,
                        'style'              => $snapItem->style,
                        'color_item_number'  => $snapItem->color_item_number,
                        'po_notes'           => $snapItem->po_notes,
                        'labour_type'        => $snapItem->labour_type,
                        'description'        => $snapItem->description,
                        'freight_description' => $snapItem->freight_description,
                        'is_changed'         => false,
                        'is_removed'         => false,
                    ]);
                }

                // Restore room totals
                $saleRoom->update([
                    'room_name'          => $snapshotRoom->room_name,
                    'subtotal_materials' => $snapshotRoom->subtotal_materials,
                    'subtotal_labour'    => $snapshotRoom->subtotal_labour,
                    'subtotal_freight'   => $snapshotRoom->subtotal_freight,
                    'room_total'         => $snapshotRoom->room_total,
                    'is_changed'         => false,
                ]);
            }

            // Delete rooms that were added during the CO (no snapshot counterpart)
            if (! empty($snapshotSaleRoomIds)) {
                SaleRoom::where('sale_id', $sale->id)
                    ->whereNotIn('id', $snapshotSaleRoomIds)
                    ->delete();
            }

            // Cancel the CO
            $co->update([
                'status'     => 'cancelled',
                'updated_by' => $userId,
            ]);

            // Re-lock sale at original totals
            $sale->update([
                'status'                 => 'approved',
                'pretax_total'           => $co->original_pretax_total,
                'tax_amount'             => $co->original_tax_amount,
                'grand_total'            => $co->original_grand_total,
                'locked_at'              => now(),
                'locked_by'              => $userId,
                'locked_pretax_total'    => $co->original_pretax_total,
                'locked_tax_amount'      => $co->original_tax_amount,
                'locked_grand_total'     => $co->original_grand_total,
                'revised_contract_total' => $co->original_grand_total,
                'has_changes'            => false,
            ]);
        });
    }

    /**
     * Mark a CO as sent.
     */
    public function markSent(SaleChangeOrder $co, int $userId): void
    {
        $co->update([
            'status'     => 'sent',
            'sent_at'    => now(),
            'updated_by' => $userId,
        ]);
    }

    private function itemLabel(SaleChangeOrderItem|SaleItem $item): string
    {
        return match ($item->item_type) {
            'material' => trim(implode(' — ', array_filter([
                $item->product_type,
                $item->manufacturer,
                $item->style,
                $item->color_item_number,
            ]))),
            'labour'   => trim(implode(' — ', array_filter([$item->labour_type, $item->description]))),
            'freight'  => $item->freight_description ?? 'Freight',
            default    => 'Item',
        };
    }
}
