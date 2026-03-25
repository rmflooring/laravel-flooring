<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $table = 'sms_log';

    protected $fillable = [
        'to',
        'from',
        'body',
        'type',
        'status',
        'error',
        'related_type',
        'related_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
