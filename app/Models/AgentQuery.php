<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentQuery extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'tools_used',
        'answer',
        'source_refs',
        'feedback',
    ];

    protected $casts = [
        'tools_used' => 'array',
        'source_refs' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
