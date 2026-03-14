<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'sell_price'  => 'decimal:2',
        'line_total'  => 'decimal:2',
        'cost_price'  => 'decimal:2',
        'cost_total'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function ($item) {
            $qty  = (float) ($item->quantity ?? 0);
            $cost = (float) ($item->cost_price ?? 0);

            $item->cost_total = round($qty * $cost, 2);
        });
    }

    public function productStyle(): BelongsTo
    {
        return $this->belongsTo(ProductStyle::class, 'product_style_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(SaleRoom::class, 'sale_room_id');
    }
}