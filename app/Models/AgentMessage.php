<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentMessage extends Model
{
    protected $fillable = [
        'task_id',
        'sender',
        'body',
    ];

    public function task()
    {
        return $this->belongsTo(AgentTask::class, 'task_id');
    }
}
