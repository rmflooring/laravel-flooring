<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentNotification extends Model
{
    protected $fillable = [
        'task_id',
        'sent_to',
        'type',
    ];

    public function task()
    {
        return $this->belongsTo(AgentTask::class, 'task_id');
    }
}
