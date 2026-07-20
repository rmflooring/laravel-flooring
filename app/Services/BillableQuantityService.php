<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\PurchaseOrder;
use App\Models\WorkOrder;

/**
 * Computes ordered vs. already-billed vs. remaining quantity per item on a Purchase
 * Order or Work Order, so a second (or third) bill can be recorded against the same
 * PO/WO for whatever quantity wasn't covered by an earlier bill — a vendor/installer
 * commonly invoices in more than one batch for the same order.
 *
 * "Billed so far" excludes voided bills only (draft/pending/approved/paid/overdue all
 * still count as billed) — matches the active-bills convention used elsewhere in the AP
 * module (e.g. BillController::index()'s stat-card queries).
 */
class BillableQuantityService
{
    /**
     * @return array<int, array{item: \App\Models\PurchaseOrderItem, ordered: float, billed: float, remaining: float}>
     *         keyed by purchase_order_item_id
     */
    public function forPurchaseOrder(PurchaseOrder $purchaseOrder): array
    {
        $purchaseOrder->loadMissing('items');

        $billedByItemId = BillItem::query()
            ->whereNotNull('purchase_order_item_id')
            ->whereHas('bill', fn ($q) => $q->where('purchase_order_id', $purchaseOrder->id)->whereNotIn('status', ['voided']))
            ->selectRaw('purchase_order_item_id, SUM(quantity) as billed')
            ->groupBy('purchase_order_item_id')
            ->pluck('billed', 'purchase_order_item_id');

        $result = [];
        foreach ($purchaseOrder->items as $item) {
            $ordered = (float) $item->quantity;
            $billed  = (float) ($billedByItemId[$item->id] ?? 0);

            $result[$item->id] = [
                'item'      => $item,
                'ordered'   => $ordered,
                'billed'    => $billed,
                'remaining' => max(0, round($ordered - $billed, 2)),
            ];
        }

        return $result;
    }

    /**
     * WO items sharing the same item_name + cost_price + unit are merged into a single
     * row on the bill-create form (e.g. same labour type across multiple rooms), and
     * only the first underlying work_order_item_id gets tagged on the resulting
     * BillItem — the rest of the merged quantity has no direct FK. So "already billed"
     * has to be grouped by that same merge key, not by raw work_order_item_id, or a
     * second bill would only see billed-so-far for one of several merged source rows.
     *
     * @return array<string, array{key: string, item_name: string, unit: string, unit_cost: float,
     *         work_order_item_id: int, ordered: float, billed: float, remaining: float}>
     *         keyed by "{item_name}|||{cost_price}|||{unit}", same key shape used in
     *         admin/bills/create.blade.php's WO merge logic
     */
    public function forWorkOrder(WorkOrder $workOrder): array
    {
        $workOrder->loadMissing('items');

        $ordered = [];
        foreach ($workOrder->items as $item) {
            $key = $this->mergeKey($item->item_name, (float) $item->cost_price, $item->unit ?? '');

            if (! isset($ordered[$key])) {
                $ordered[$key] = [
                    'key'                => $key,
                    'item_name'          => $item->item_name,
                    'unit'               => $item->unit ?? '',
                    'unit_cost'          => (float) $item->cost_price,
                    'work_order_item_id' => $item->id,
                    'ordered'            => 0.0,
                ];
            }
            $ordered[$key]['ordered'] += (float) $item->quantity;
        }

        $billedItems = BillItem::query()
            ->whereNotNull('work_order_item_id')
            ->whereHas('bill', fn ($q) => $q->where('work_order_id', $workOrder->id)->whereNotIn('status', ['voided']))
            ->get(['item_name', 'unit_cost', 'unit', 'quantity']);

        $billedByKey = [];
        foreach ($billedItems as $billItem) {
            $key = $this->mergeKey($billItem->item_name, (float) $billItem->unit_cost, $billItem->unit ?? '');
            $billedByKey[$key] = ($billedByKey[$key] ?? 0) + (float) $billItem->quantity;
        }

        $result = [];
        foreach ($ordered as $key => $row) {
            $billed = (float) ($billedByKey[$key] ?? 0);
            $result[$key] = $row + [
                'billed'    => $billed,
                'remaining' => max(0, round($row['ordered'] - $billed, 2)),
            ];
        }

        return $result;
    }

    public function hasRemainingBillableQty(PurchaseOrder|WorkOrder $source): bool
    {
        $map = $source instanceof PurchaseOrder ? $this->forPurchaseOrder($source) : $this->forWorkOrder($source);

        foreach ($map as $row) {
            if ($row['remaining'] > 0) {
                return true;
            }
        }

        return empty($map); // no items at all — don't hide the action on an empty PO/WO
    }

    private function mergeKey(string $itemName, float $costPrice, string $unit): string
    {
        return $itemName . '|||' . $costPrice . '|||' . $unit;
    }
}
