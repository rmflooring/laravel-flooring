<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class PickTicket extends Model
{
    protected $guarded = ['id', 'pt_number'];

    protected $casts = [
        'delivery_date' => 'date',
        'ready_at'      => 'datetime',
        'picked_at'     => 'datetime',
        'delivered_at'  => 'datetime',
        'returned_at'   => 'datetime',
        'unstaged_at'   => 'datetime',
    ];

    const FULFILLMENT_TYPES = [
        'pickup'   => 'Pickup',
        'delivery' => 'Delivery',
    ];

    const ACTIVE_STATUSES = ['staged', 'pending', 'ready', 'picked', 'partially_delivered'];

    const STATUSES = ['pending', 'ready', 'picked', 'staged', 'partially_delivered', 'delivered', 'returned', 'cancelled'];

    const STATUS_LABELS = [
        'pending'             => 'Pending',
        'ready'               => 'Ready',
        'picked'              => 'Picked',
        'staged'              => 'Staged',
        'partially_delivered' => 'Partial',
        'delivered'           => 'Delivered',
        'returned'            => 'Returned',
        'cancelled'           => 'Cancelled',
    ];

    protected static function booted(): void
    {
        static::creating(function (PickTicket $pt) {
            // Generate pt_number: {seq}-{sale_number} or just {seq} for non-sale tickets
            $saleSuffix = '';
            if (! empty($pt->sale_id)) {
                $saleNumber = DB::table('sales')->where('id', $pt->sale_id)->value('sale_number');
                if ($saleNumber) {
                    $saleSuffix = '-' . $saleNumber;
                }
            }

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('pick_tickets')
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(pt_number, '-', 1) AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $nextNum   = ((int) $max) + 1 + $attempt;
                $candidate = $nextNum . $saleSuffix;

                if (! DB::table('pick_tickets')->where('pt_number', $candidate)->exists()) {
                    $pt->pt_number = $candidate;
                    break;
                }
            }

            if (empty($pt->pt_number)) {
                throw new \RuntimeException('Could not generate a unique PT number.');
            }

            if (auth()->check()) {
                $pt->created_by ??= auth()->id();
                $pt->updated_by  = auth()->id();
            }
        });

        static::updating(function (PickTicket $pt) {
            if (auth()->check()) {
                $pt->updated_by = auth()->id();
            }
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PickTicketItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function unstagedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unstaged_by');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CalendarEvent::class, 'calendar_event_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getFulfillmentTypeLabelAttribute(): ?string
    {
        return self::FULFILLMENT_TYPES[$this->fulfillment_type] ?? null;
    }
}
