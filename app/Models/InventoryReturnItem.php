<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReturnItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity_returned'  => 'decimal:2',
        'unit_cost'          => 'decimal:4',
        'line_total'         => 'decimal:2',
        'apply_to_sale_cost' => 'boolean',
        'credit_received'    => 'decimal:2',
        'cost_applied_at'    => 'datetime',
    ];

    /**
     * Resolved item name — from snapshot, or falls back to PO item name.
     */
    public function getItemNameResolvedAttribute(): string
    {
        return $this->item_name
            ?? $this->purchaseOrderItem?->item_name
            ?? '—';
    }

    /**
     * Resolved unit — from snapshot, or falls back to PO item unit.
     */
    public function getUnitResolvedAttribute(): string
    {
        return $this->unit
            ?? $this->purchaseOrderItem?->unit
            ?? '';
    }

    public function inventoryReturn(): BelongsTo
    {
        return $this->belongsTo(InventoryReturn::class);
    }

    public function inventoryReceipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
