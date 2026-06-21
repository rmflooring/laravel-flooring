<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsConversation extends Model
{
    protected $fillable = [
        'phone',
        'customer_id',
        'opportunity_id',
        'last_message_at',
        'unread_count',
        'status',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_count'    => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'conversation_id')->latest()->limit(1);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('unread_count', '>', 0);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function displayName(): string
    {
        return $this->customer?->name ?? $this->phone;
    }

    public function isUnknown(): bool
    {
        return $this->customer_id === null;
    }
}
