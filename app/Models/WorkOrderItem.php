<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrderItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'cost_price' => 'decimal:2',
        'cost_total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $item->cost_total = round((float) $item->quantity * (float) $item->cost_price, 2);
        });
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function relatedMaterials(): HasMany
    {
        return $this->hasMany(WorkOrderItemMaterial::class);
    }
}
