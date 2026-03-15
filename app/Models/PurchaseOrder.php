<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'po_number'];

    protected $casts = [
        'expected_delivery_date' => 'date',
        'sent_at'                => 'datetime',
        'pickup_at'              => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $po) {
            $saleSuffix = '';
            if (! empty($po->sale_id)) {
                $saleNumber = DB::table('sales')->where('id', $po->sale_id)->value('sale_number');
                if ($saleNumber) {
                    $saleSuffix = '-' . $saleNumber;
                }
            }

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('purchase_orders')
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(po_number, '-', 1) AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $nextNum   = ((int) $max) + 1 + $attempt;
                $candidate = $nextNum . $saleSuffix;

                if (! DB::table('purchase_orders')->where('po_number', $candidate)->exists()) {
                    $po->po_number = $candidate;
                    return;
                }
            }

            throw new \RuntimeException('Could not generate a unique PO number.');
        });

        static::creating(function (PurchaseOrder $po) {
            $userId = auth()->id();
            if ($userId) {
                if (empty($po->ordered_by)) {
                    $po->ordered_by = $userId;
                }
                if (empty($po->created_by)) {
                    $po->created_by = $userId;
                }
                if (empty($po->updated_by)) {
                    $po->updated_by = $userId;
                }
            }
        });

        static::updating(function (PurchaseOrder $po) {
            $userId = auth()->id();
            if ($userId) {
                $po->updated_by = $userId;
            }
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CalendarEvent::class, 'calendar_event_id');
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getFulfillmentLabelAttribute(): string
    {
        return match ($this->fulfillment_method) {
            'delivery_site'      => 'Delivery — Site Address',
            'delivery_warehouse' => 'Delivery — Warehouse',
            'delivery_custom'    => 'Delivery — Custom Address',
            'pickup'             => 'Pickup',
            default              => $this->fulfillment_method,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'   => 'Pending',
            'ordered'   => 'Ordered',
            'received'  => 'Received',
            'cancelled' => 'Cancelled',
            default     => ucfirst($this->status),
        };
    }

    public function getGrandTotalAttribute(): float
    {
        return $this->items->sum('cost_total');
    }
}
