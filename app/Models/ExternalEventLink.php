<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalEventLink extends Model
{
    protected $table = 'external_event_links';

    protected $fillable = [
        'calendar_event_id',
        'provider',
        'microsoft_account_id',
        'external_calendar_id',
        'external_event_id',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function microsoftAccount(): BelongsTo
    {
        return $this->belongsTo(MicrosoftAccount::class);
    }
}
