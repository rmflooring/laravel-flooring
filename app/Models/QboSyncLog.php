<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QboSyncLog extends Model
{
    public $timestamps = false;

    protected $table = 'qbo_sync_log';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'direction',
        'qbo_id',
        'status',
        'message',
        'payload',
        'response',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'response'   => 'array',
        'created_at' => 'datetime',
    ];
}
