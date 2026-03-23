<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleChangeOrderRoom extends Model
{
    protected $guarded = [];

    public function changeOrder(): BelongsTo
    {
        return $this->belongsTo(SaleChangeOrder::class, 'sale_change_order_id');
    }

    public function saleRoom(): BelongsTo
    {
        return $this->belongsTo(SaleRoom::class, 'sale_room_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleChangeOrderItem::class, 'sale_change_order_room_id')->orderBy('sort_order');
    }
}
