<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentTask extends Model
{
    protected $fillable = [
        'source',
        'requester_email',
        'requester_user_id',
        'raw_content',
        'attachments',
        'extracted_intent',
        'task_type',
        'status',
        'confidence_score',
        'opportunity_id',
    ];

    protected $casts = [
        'attachments' => 'array',
        'confidence_score' => 'float',
    ];

    public function messages()
    {
        return $this->hasMany(AgentMessage::class, 'task_id')->orderBy('created_at');
    }

    public function notifications()
    {
        return $this->hasMany(AgentNotification::class, 'task_id');
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }
}
