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
        'unit',
        'sell_price',
        'line_total',

        'notes',
        'sort_order',

        // Material-specific
        'product_type',
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

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(EstimateRoom::class, 'estimate_room_id');
    }
}