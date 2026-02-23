<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(SaleRoom::class, 'sale_room_id');
    }
}
