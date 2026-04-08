<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleCheckout extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'checked_out_at'  => 'datetime',
        'due_back_at'     => 'date',
        'returned_at'     => 'datetime',
        'last_reminder_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (SampleCheckout $checkout) {
            if (empty($checkout->checked_out_at)) {
                $checkout->checked_out_at = now();
            }
            if (empty($checkout->checked_out_by)) {
                $checkout->checked_out_by = auth()->id();
            }

            // Default due_back_at from app setting
            if (empty($checkout->due_back_at)) {
                $days = (int) Setting::get('sample_checkout_days', 5);
                $checkout->due_back_at = now()->addDays($days)->toDateString();
            }
        });
    }

    // --- Relationships ---

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function sampleSet(): BelongsTo
    {
        return $this->belongsTo(SampleSet::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    // --- Accessors ---

    public function getIsReturnedAttribute(): bool
    {
        return $this->returned_at !== null;
    }

    public function getIsOverdueAttribute(): bool
    {
        return ! $this->is_returned
            && $this->due_back_at !== null
            && $this->due_back_at->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }
        return (int) $this->due_back_at->diffInDays(now());
    }

    /**
     * Display label for the checked-out item (sample ID or set ID).
     */
    public function getSubjectLabelAttribute(): string
    {
        if ($this->sampleSet) {
            return $this->sampleSet->set_id;
        }
        return $this->sample?->sample_id ?? '—';
    }

    /**
     * Resolved display name for the borrower (customer or staff).
     */
    public function getBorrowerNameAttribute(): string
    {
        if ($this->checkout_type === 'staff') {
            return $this->user?->name ?? 'Staff';
        }

        return $this->customer?->company_name
            ?? $this->customer_name
            ?? 'Unknown';
    }
}
