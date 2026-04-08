<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleSetItem extends Model
{
    protected $guarded = ['id'];

    public function sampleSet(): BelongsTo
    {
        return $this->belongsTo(SampleSet::class);
    }

    public function productStyle(): BelongsTo
    {
        return $this->belongsTo(ProductStyle::class);
    }
}
