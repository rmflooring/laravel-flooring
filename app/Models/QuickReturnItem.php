<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickReturnItem extends Model
{
    protected $guarded = ['id'];

    public function quickReturn(): BelongsTo
    {
        return $this->belongsTo(QuickReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function productStyle(): BelongsTo
    {
        return $this->belongsTo(ProductStyle::class);
    }
}
