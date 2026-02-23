<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $guarded = [];

    public function rooms(): HasMany
    {
        return $this->hasMany(SaleRoom::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function sourceEstimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'source_estimate_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
}
