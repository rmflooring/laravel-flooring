<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstimateRoom extends Model
{
    protected $table = 'estimate_rooms';

    protected $fillable = [
        'estimate_id',
        'room_name',
        'sort_order',
        'subtotal_materials',
        'subtotal_labour',
        'subtotal_freight',
        'room_total',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class, 'estimate_room_id')->orderBy('sort_order');
    }
}
