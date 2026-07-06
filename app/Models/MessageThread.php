<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageThread extends Model
{
    protected $fillable = ['subject', 'threadable_type', 'threadable_id', 'created_by'];

    public function threadable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'message_thread_users')
            ->withPivot('last_read_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCountFor(int $userId): int
    {
        $participant = $this->participants()->where('user_id', $userId)->first();
        if (! $participant) {
            return 0;
        }
        $lastRead = $participant->pivot->last_read_at;
        $query = $this->messages()->where('sender_id', '<>', $userId);
        if ($lastRead) {
            $query->where('created_at', '>', $lastRead);
        }
        return $query->count();
    }

    public function getContextLabelAttribute(): ?string
    {
        if (! $this->threadable_type || ! $this->threadable) {
            return null;
        }
        $type = class_basename($this->threadable_type);
        $record = $this->threadable;

        if ($type === 'Opportunity') {
            $name  = $record->jobSiteCustomer?->name ?? ('Opportunity #' . $record->id);
            $jobNo = $record->job_no ? ' (' . $record->job_no . ')' : '';
            return 'Opportunity: ' . $name . $jobNo;
        }

        if ($type === 'Sale') {
            $num  = $record->sale_number ?? $record->id;
            $name = $record->opportunity?->jobSiteCustomer?->name;
            return 'Sale #' . $num . ($name ? ' — ' . $name : '');
        }

        if ($type === 'Estimate') {
            $num  = $record->estimate_number ?? $record->id;
            $name = $record->homeowner_name ?: $record->customer_name;
            return 'Estimate #' . $num . ($name ? ' — ' . $name : '');
        }

        return $type . ' #' . $this->threadable_id;
    }

    public function getContextUrlAttribute(): ?string
    {
        if (! $this->threadable_type || ! $this->threadable) {
            return null;
        }
        $type = class_basename($this->threadable_type);
        try {
            if ($type === 'Opportunity') {
                return route('pages.opportunities.show', $this->threadable_id);
            }
            if ($type === 'Sale') {
                return route('pages.sales.show', $this->threadable_id);
            }
            if ($type === 'Estimate') {
                return route('pages.estimates.show', $this->threadable_id);
            }
        } catch (\Exception $e) {}
        return null;
    }
}
