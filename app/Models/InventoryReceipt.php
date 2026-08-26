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
        'cost_price'        => 'decimal:4',
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

    public function customerReturnItem(): BelongsTo
    {
        return $this->belongsTo(CustomerReturnItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(InventoryReturnItem::class);
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
     * Quantity still available:
     *   received − allocated − outbound transactions (return_to_vendor, fulfilled)
     *   + adjustments (signed — manual stock corrections, positive or negative)
     */
    public function getAvailableQtyAttribute(): float
    {
        $allocated = $this->allocations->sum('quantity');
        $outbound  = $this->transactions
            ->whereIn('type', ['return_to_vendor', 'fulfilled'])
            ->sum(fn ($t) => abs((float) $t->quantity));
        $adjustments = $this->transactions
            ->where('type', 'adjustment')
            ->sum(fn ($t) => (float) $t->quantity);

        return max(0, (float) $this->quantity_received - (float) $allocated - (float) $outbound + (float) $adjustments);
    }

    /**
     * Quantity eligible for a new Return to Vendor: unlike available_qty (which treats
     * any allocation as unavailable, correctly gating new allocations against
     * double-claiming stock), an allocation whose released_at is still null means the
     * stock hasn't been picked/delivered out of the warehouse yet (see
     * PickTicketService::deliver()) — it's physically still sitting there and
     * genuinely returnable. ReturnToVendorService::ship() already reduces/deletes
     * exactly these unreleased allocations when a return ships; this accessor just
     * makes that same stock selectable on the RTV create page in the first place
     * (2026-08-26 — a fully-allocated-but-undelivered receipt was invisible there).
     * Released allocations (physically gone) still count against this like before.
     */
    public function getReturnableQtyAttribute(): float
    {
        $releasedAllocated = $this->allocations->whereNotNull('released_at')->sum('quantity');
        $outbound  = $this->transactions
            ->whereIn('type', ['return_to_vendor', 'fulfilled'])
            ->sum(fn ($t) => abs((float) $t->quantity));
        $adjustments = $this->transactions
            ->where('type', 'adjustment')
            ->sum(fn ($t) => (float) $t->quantity);

        return max(0, (float) $this->quantity_received - (float) $releasedAllocated - (float) $outbound + (float) $adjustments);
    }
}
