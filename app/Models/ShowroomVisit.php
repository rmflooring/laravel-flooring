<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShowroomVisit extends Model
{
    use SoftDeletes;

    // scheduled        → booked, customer notified
    // change_requested → customer asked for a different time via their link; staff must reschedule
    // completed / no_show / cancelled → terminal
    const STATUSES = ['scheduled', 'change_requested', 'completed', 'no_show', 'cancelled'];

    const STATUS_LABELS = [
        'scheduled'        => 'Scheduled',
        'change_requested' => 'Change Requested',
        'completed'        => 'Completed',
        'no_show'          => 'No Show',
        'cancelled'        => 'Cancelled',
    ];

    const STATUS_COLORS = [
        'scheduled'        => 'bg-blue-100 text-blue-800',
        'change_requested' => 'bg-amber-100 text-amber-800',
        'completed'        => 'bg-green-100 text-green-800',
        'no_show'          => 'bg-gray-200 text-gray-800',
        'cancelled'        => 'bg-red-100 text-red-800',
    ];

    // Statuses that still represent an upcoming appointment (reminders, calendar, customer link active)
    const ACTIVE_STATUSES = ['scheduled', 'change_requested'];

    const DURATIONS = [30, 45, 60, 90, 120];

    protected $fillable = [
        'opportunity_id',
        'customer_id',
        'host_employee_id',
        'scheduled_at',
        'duration_minutes',
        'notes',
        'status',
        'contact_name',
        'contact_email',
        'contact_phone',
        'notify_email',
        'notify_sms',
        'token',
        'confirmation_sent_at',
        'reminder_day_before_sent_at',
        'reminder_morning_sent_at',
        'change_requested_at',
        'change_request_preferred',
        'change_request_note',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'microsoft_calendar_id',
        'calendar_event_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at'                => 'datetime',
        'notify_email'                => 'boolean',
        'notify_sms'                  => 'boolean',
        'confirmation_sent_at'        => 'datetime',
        'reminder_day_before_sent_at' => 'datetime',
        'reminder_morning_sent_at'    => 'datetime',
        'change_requested_at'         => 'datetime',
        'cancelled_at'                => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ShowroomVisit $visit) {
            $visit->token      = $visit->token ?: Str::random(48);
            $visit->created_by = $visit->created_by ?? auth()->id();
            $visit->updated_by = auth()->id();
        });

        static::updating(function (ShowroomVisit $visit) {
            if (auth()->check()) {
                $visit->updated_by = auth()->id();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'host_employee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    /** Customer can still cancel / request a change from their link. */
    public function customerCanModify(): bool
    {
        return $this->isActive() && $this->scheduled_at->isFuture();
    }

    public function endsAt(): \Carbon\Carbon
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes ?: 60);
    }

    public function publicUrl(): string
    {
        return route('showroom-visit.public.show', $this->token);
    }

    public function hostName(): string
    {
        return $this->host ? trim($this->host->first_name . ' ' . $this->host->last_name) : '';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
