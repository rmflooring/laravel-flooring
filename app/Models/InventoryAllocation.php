<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAllocation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'released_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryAllocation $allocation) {
            $userId = auth()->id();
            if ($userId) {
                $allocation->allocated_by ??= $userId;
            }
        });
    }

    public function inventoryReceipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function pickTicketItems(): HasMany
    {
        return $this->hasMany(PickTicketItem::class);
    }
}
