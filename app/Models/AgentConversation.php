<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function queries(): HasMany
    {
        return $this->hasMany(AgentQuery::class, 'conversation_id')->orderBy('id');
    }

    public function scopeArchived(Builder $query, bool $archived = true): Builder
    {
        return $archived ? $query->whereNotNull('archived_at') : $query->whereNull('archived_at');
    }
}
