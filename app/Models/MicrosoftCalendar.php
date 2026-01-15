<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrosoftCalendar extends Model
{
    protected $table = 'microsoft_calendars';

    protected $fillable = [
        'microsoft_account_id',
        'calendar_id',
        'name',
        'is_primary',
        'is_enabled',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function microsoftAccount(): BelongsTo
    {
        return $this->belongsTo(MicrosoftAccount::class);
    }
}
