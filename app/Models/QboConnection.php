<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QboConnection extends Model
{
    protected $fillable = [
        'realm_id',
        'environment',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'connected_at',
        'connected_by',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'connected_at'     => 'datetime',
    ];

    public function connectedBy()
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }
}
