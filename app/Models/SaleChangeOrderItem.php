<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleChangeOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'sell_price' => 'decimal:2',
        'line_total'  => 'decimal:2',
    ];

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(SaleChangeOrder::class, 'sale_change_order_id');
    }

    public function changeOrderRoom(): BelongsTo
    {
        return $this->belongsTo(SaleChangeOrderRoom::class, 'sale_change_order_room_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }
}
