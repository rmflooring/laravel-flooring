<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeAgentToolAccess extends Model
{
    protected $table = 'knowledge_agent_tool_access';

    protected $fillable = [
        'role',
        'tool_name',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];
}
