<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $table = 'mail_log';

    protected $fillable = [
        'to',
        'subject',
        'status',
        'type',
        'track',
        'sent_from',
        'error',
        'body',
        'cc',
        'attachment_name',
        'pdf_url',
        'related_id',
        'related_type',
    ];
}
