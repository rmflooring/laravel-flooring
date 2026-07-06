<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateFollowUp extends Model
{
    protected $fillable = [
        'estimate_id', 'user_id', 'stage', 'channel', 'notes', 'sent_to', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
