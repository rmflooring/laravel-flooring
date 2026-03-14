<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'wo_number'];

    protected $casts = [
        'scheduled_date' => 'date',
        'sent_at'        => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    const STATUSES = ['created', 'scheduled', 'in_progress', 'completed', 'cancelled'];

    const STATUS_LABELS = [
        'created'     => 'Created',
        'scheduled'   => 'Scheduled',
        'in_progress' => 'In Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $wo) {
            // Auto-generate WO-YYYY-NNNN
            $yearPrefix = now()->format('Y') . '-';
            $prefixLen  = strlen($yearPrefix);

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('work_orders')
                    ->where('wo_number', 'like', 'WO-' . $yearPrefix . '%')
                    ->selectRaw("MAX(CAST(SUBSTRING(wo_number, ?) AS UNSIGNED)) as max_num", [strlen('WO-') + $prefixLen + 1])
                    ->value('max_num');

                $nextNum   = ((int) $max) + 1 + $attempt;
                $candidate = 'WO-' . $yearPrefix . str_pad((string) $nextNum, 4, '0', STR_PAD_LEFT);

                if (! DB::table('work_orders')->where('wo_number', $candidate)->exists()) {
                    $wo->wo_number = $candidate;
                    break;
                }
            }

            if (empty($wo->wo_number)) {
                throw new \RuntimeException('Could not generate a unique WO number.');
            }

            if (auth()->check()) {
                $wo->created_by = $wo->created_by ?? auth()->id();
                $wo->updated_by = auth()->id();
            }
        });

        static::updating(function (WorkOrder $wo) {
            if (auth()->check()) {
                $wo->updated_by = auth()->id();
            }
        });
    }

    // ── Accessors ─────────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getCalendarSyncedAttribute(): bool
    {
        return ! empty($this->calendar_event_id);
    }

    public function getGrandTotalAttribute(): float
    {
        return (float) $this->items->sum('cost_total');
    }

    // ── Relationships ─────────────────────────────────────────────

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(Installer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class)->orderBy('sort_order');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
