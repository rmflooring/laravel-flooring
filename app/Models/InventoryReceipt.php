<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryReceipt extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'received_date'     => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryReceipt $receipt) {
            $userId = auth()->id();
            if ($userId) {
                $receipt->created_by ??= $userId;
                $receipt->updated_by ??= $userId;
            }
        });

        static::updating(function (InventoryReceipt $receipt) {
            $userId = auth()->id();
            if ($userId) {
                $receipt->updated_by = $userId;
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function productStyle(): BelongsTo
    {
        return $this->belongsTo(ProductStyle::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InventoryAllocation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Quantity still available (received minus all allocations).
     */
    public function getAvailableQtyAttribute(): float
    {
        $allocated = $this->allocations->sum('quantity');

        return max(0, (float) $this->quantity_received - (float) $allocated);
    }
}
