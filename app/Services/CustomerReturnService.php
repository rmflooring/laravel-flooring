<?php

namespace App\Services;

use App\Models\CustomerReturn;
use App\Models\InventoryReceipt;
use App\Models\PickTicket;
use Illuminate\Support\Facades\DB;

class CustomerReturnService
{
    /**
     * Mark an RFC as received.
     *
     * For each RFC item:
     *   1. Create an InventoryReceipt (inventory ↑)
     *   2. Stamp inventory_receipt_id back onto the CustomerReturnItem
     *   3. If linked to a PickTicketItem, add to its returned_qty
     *
     * Then recalculate the linked PT status based on updated returned_qty values.
     *
     * @param  CustomerReturn  $rfc
     * @param  string|null     $receivedBy   Name of the person who received the goods
     * @param  string|null     $receivedDate ISO date string (defaults to today)
     */
    public function receive(
        CustomerReturn $rfc,
        ?string $receivedBy = null,
        ?string $receivedDate = null
    ): void {
        abort_unless($rfc->status === 'draft', 422, 'Only draft RFCs can be received.');

        $rfc->loadMissing(['items.pickTicketItem', 'pickTicket.items']);

        DB::transaction(function () use ($rfc, $receivedBy, $receivedDate) {
            $date = $receivedDate ? \Carbon\Carbon::parse($receivedDate)->toDateString() : now()->toDateString();

            foreach ($rfc->items as $rfcItem) {
                // 1. Create a new InventoryReceipt — inventory goes UP
                $receipt = InventoryReceipt::create([
                    'customer_return_item_id' => $rfcItem->id,
                    'item_name'               => $rfcItem->item_name,
                    'unit'                    => $rfcItem->unit,
                    'quantity_received'       => $rfcItem->quantity_returned,
                    'received_date'           => $date,
                    'notes'                   => $rfcItem->notes
                        ? 'RFC ' . $rfc->rfc_number . ': ' . $rfcItem->notes
                        : 'RFC ' . $rfc->rfc_number,
                ]);

                // 2. Stamp the receipt back onto the RFC item
                $rfcItem->update(['inventory_receipt_id' => $receipt->id]);

                // 3. Update PT item returned_qty if linked
                if ($rfcItem->pickTicketItem) {
                    $ptItem       = $rfcItem->pickTicketItem;
                    $newReturned  = min(
                        (float) $ptItem->returned_qty + (float) $rfcItem->quantity_returned,
                        (float) $ptItem->delivered_qty
                    );
                    $ptItem->update(['returned_qty' => $newReturned]);
                }
            }

            // 4. Recalculate linked PT status
            if ($rfc->pickTicket) {
                $this->recalculatePickTicketStatus($rfc->pickTicket);
            }

            // 5. Mark RFC as received
            $rfc->update([
                'status'        => 'received',
                'received_date' => $date,
                'received_by'   => $receivedBy ?: $rfc->received_by,
            ]);
        });
    }

    /**
     * Recalculate the pick ticket status after returned_qty changes.
     * Re-loads items fresh to get updated values.
     */
    private function recalculatePickTicketStatus(PickTicket $pt): void
    {
        $pt->load('items');

        $anyStillOut  = false;
        $anyDelivered = false;

        foreach ($pt->items as $item) {
            $delivered = (float) $item->delivered_qty;
            $returned  = (float) $item->returned_qty;
            $netOut    = $delivered - $returned;

            if ($delivered > 0) {
                $anyDelivered = true;
            }
            if ($netOut > 0) {
                $anyStillOut = true;
            }
        }

        if (! $anyDelivered) {
            // Nothing was ever delivered — leave status alone
            return;
        }

        $newStatus = $anyStillOut ? 'partially_delivered' : 'returned';

        $pt->update([
            'status'      => $newStatus,
            'returned_at' => $pt->returned_at ?? now(),
        ]);
    }
}
