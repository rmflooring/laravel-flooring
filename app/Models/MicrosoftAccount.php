<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MicrosoftAccount extends Model
{
    protected $table = 'microsoft_accounts';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'microsoft_user_id',
        'email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_connected',
        'connected_at',
        'disconnected_at',
    ];

    protected $casts = [
        // Encrypt tokens at rest
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',

        // Dates
        'token_expires_at' => 'datetime',
        'connected_at'     => 'datetime',
        'disconnected_at'  => 'datetime',

        // Boolean
        'is_connected' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
	
	public function calendars()
	{
		return $this->hasMany(\App\Models\MicrosoftCalendar::class);
	}

}
