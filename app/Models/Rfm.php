<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rfm extends Model
{
    protected $fillable = [
        'opportunity_id',
        'estimator_id',
        'parent_customer_id',
        'job_site_customer_id',
        'site_address',
        'site_city',
        'site_postal_code',
        'flooring_type',
        'scheduled_at',
        'special_instructions',
        'status',
        'microsoft_calendar_id',
        'calendar_event_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at'  => 'datetime',
        'flooring_type' => 'array',
    ];

    // Valid statuses in lifecycle order
    const STATUSES = ['pending', 'confirmed', 'completed', 'cancelled'];

    // Flooring types available in the create form
    const FLOORING_TYPES = [
        'Carpet',
        'Hardwood',
        'Vinyl / LVP',
        'Tile',
        'Laminate',
        'Other',
    ];

    protected static function booted(): void
    {
        static::creating(function (Rfm $rfm) {
            $rfm->created_by = $rfm->created_by ?? auth()->id();
            $rfm->updated_by = auth()->id();
        });

        static::updating(function (Rfm $rfm) {
            $rfm->updated_by = auth()->id();
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function estimator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'estimator_id');
    }

    public function parentCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'parent_customer_id');
    }

    public function jobSiteCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'job_site_customer_id');
    }

    public function microsoftCalendar(): BelongsTo
    {
        return $this->belongsTo(MicrosoftCalendar::class, 'microsoft_calendar_id');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }
}
