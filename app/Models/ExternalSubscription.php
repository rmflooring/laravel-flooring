<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalSubscription extends Model
{
    protected $table = 'external_subscriptions';

    protected $fillable = [
        'provider',
        'microsoft_account_id',
        'external_calendar_id',
        'subscription_id',
        'expires_at',
        'last_notified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    public function microsoftAccount(): BelongsTo
    {
        return $this->belongsTo(MicrosoftAccount::class);
    }
}
