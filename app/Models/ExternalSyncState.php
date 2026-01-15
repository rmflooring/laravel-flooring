<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalSyncState extends Model
{
    protected $table = 'external_sync_states';

    protected $fillable = [
        'provider',
        'microsoft_account_id',
        'external_calendar_id',
        'delta_token',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function microsoftAccount(): BelongsTo
    {
        return $this->belongsTo(MicrosoftAccount::class);
    }
}
