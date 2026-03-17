<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItem extends Model
{
    protected $table = 'estimate_items';

    protected $fillable = [
        'estimate_id',
        'estimate_room_id',
        'item_type',

        'quantity',
        'order_qty',
        'unit',
        'sell_price',
        'line_total',

        // NEW COST FIELDS
        'cost_price',
        'cost_total',

        'notes',
        'sort_order',

        // Material-specific
        'product_type',
        'product_line_id',
        'product_style_id',
        'manufacturer',
        'style',
        'color_item_number',
        'po_notes',

        // Labour-specific
        'labour_type',
        'description',

        // Freight-specific
        'freight_description',
    ];

    protected $casts = [
        'quantity'    => 'decimal:2',
        'order_qty'   => 'decimal:2',
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
			$sell = (float) ($item->sell_price ?? 0);

            $item->cost_total = round($qty * $cost, 2);
			$item->line_total = round($qty * $sell, 2);
        });
    }

    public function productStyle(): BelongsTo
    {
        return $this->belongsTo(ProductStyle::class, 'product_style_id');
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(EstimateRoom::class, 'estimate_room_id');
    }
}