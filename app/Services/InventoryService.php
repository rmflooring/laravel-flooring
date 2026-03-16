<?php

namespace App\Services;

use App\Models\InventoryAllocation;
use App\Models\InventoryReceipt;
use App\Models\PurchaseOrderItem;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Create a receipt from a PO item (e.g. goods received at warehouse from a PO).
     */
    public function receiveFromPOItem(
        PurchaseOrderItem $poItem,
        float $quantity,
        string $receivedDate,
        ?string $notes = null
    ): InventoryReceipt {
        return InventoryReceipt::create([
            'purchase_order_id'      => $poItem->purchase_order_id,
            'purchase_order_item_id' => $poItem->id,
            'product_style_id'       => $poItem->product_style_id ?? $poItem->saleItem?->product_style_id ?? null,
            'item_name'              => $poItem->item_name,
            'unit'                   => $poItem->unit,
            'quantity_received'      => $quantity,
            'received_date'          => $receivedDate,
            'notes'                  => $notes,
        ]);
    }

    /**
     * Create a receipt manually (not tied to a PO).
     *
     * @param array{item_name: string, unit: string, quantity_received: float, received_date: string, notes?: string, product_style_id?: int} $data
     */
    public function receiveManual(array $data): InventoryReceipt
    {
        return InventoryReceipt::create([
            'purchase_order_id'      => null,
            'purchase_order_item_id' => null,
            'product_style_id'       => $data['product_style_id'] ?? null,
            'item_name'              => $data['item_name'],
            'unit'                   => $data['unit'] ?? '',
            'quantity_received'      => $data['quantity_received'],
            'received_date'          => $data['received_date'],
            'notes'                  => $data['notes'] ?? null,
        ]);
    }

    /**
     * Allocate stock from a receipt to a sale item.
     *
     * @throws \InvalidArgumentException if quantity exceeds available stock
     */
    public function allocate(
        InventoryReceipt $receipt,
        SaleItem $saleItem,
        float $quantity,
        ?string $notes = null
    ): InventoryAllocation {
        $receipt->load('allocations');
        $available = $receipt->available_qty;

        if ($quantity > $available) {
            throw new \InvalidArgumentException(
                "Cannot allocate {$quantity} — only {$available} available in receipt #{$receipt->id}."
            );
        }

        return DB::transaction(function () use ($receipt, $saleItem, $quantity, $notes) {
            return InventoryAllocation::create([
                'inventory_receipt_id' => $receipt->id,
                'sale_item_id'         => $saleItem->id,
                'sale_id'              => $saleItem->sale_id,
                'quantity'             => $quantity,
                'notes'                => $notes,
            ]);
        });
    }

    /**
     * Total quantity allocated from a receipt (across all sale items).
     */
    public function allocatedQty(InventoryReceipt $receipt): float
    {
        return (float) InventoryAllocation::where('inventory_receipt_id', $receipt->id)
            ->sum('quantity');
    }

    /**
     * Available qty for a receipt (received minus allocated).
     */
    public function availableQty(InventoryReceipt $receipt): float
    {
        return max(0, (float) $receipt->quantity_received - $this->allocatedQty($receipt));
    }

    /**
     * Total quantity allocated from inventory for a specific sale item.
     */
    public function allocatedQtyForSaleItem(SaleItem $saleItem): float
    {
        return (float) InventoryAllocation::where('sale_item_id', $saleItem->id)->sum('quantity');
    }

    /**
     * Return coverage info for a sale item from inventory.
     *
     * Returns:
     *   ['covered' => false]
     *   ['covered' => true, 'quantity' => float, 'allocations' => Collection]
     */
    public function coverageForSaleItem(SaleItem $saleItem): array
    {
        $allocations = InventoryAllocation::with('inventoryReceipt')
            ->where('sale_item_id', $saleItem->id)
            ->get();

        if ($allocations->isEmpty()) {
            return ['covered' => false];
        }

        $totalAllocated = $allocations->sum('quantity');

        return [
            'covered'     => true,
            'quantity'    => (float) $totalAllocated,
            'allocations' => $allocations,
        ];
    }
}
