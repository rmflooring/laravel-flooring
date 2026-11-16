<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'returned_quantity' => 'decimal:2',
        'cost_price'        => 'decimal:3',
        'cost_total'        => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (PurchaseOrderItem $item) {
            $item->cost_total = round($item->quantity * $item->cost_price, 2);
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    // ── Receiving ───────────────────────────────────────────────────────────
    // What's been received is derived from the inventory receipts created from
    // this line — never from the PO status, which staff can change by hand.

    public function receipts(): HasMany
    {
        return $this->hasMany(InventoryReceipt::class, 'purchase_order_item_id');
    }

    /** Total quantity received into inventory from this line so far. */
    public function receivedQty(): float
    {
        $total = $this->relationLoaded('receipts')
            ? $this->receipts->sum('quantity_received')
            : $this->receipts()->sum('quantity_received');

        return round((float) $total, 2);
    }

    /** Quantity still owed by the vendor on this line. */
    public function remainingQty(): float
    {
        return max(0, round((float) $this->quantity - $this->receivedQty(), 2));
    }

    public function isFullyReceived(): bool
    {
        return $this->remainingQty() <= 0;
    }
}
