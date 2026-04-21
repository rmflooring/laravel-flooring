<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Bill extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'bill_date'     => 'date',
        'due_date'      => 'date',
        'voided_at'     => 'datetime',
        'subtotal'      => 'decimal:2',
        'gst_rate'      => 'decimal:4',
        'pst_rate'      => 'decimal:4',
        'tax_manual'    => 'boolean',
        'gst_amount'    => 'decimal:2',
        'pst_amount'    => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'grand_total'   => 'decimal:2',
        'qbo_synced_at' => 'datetime',
        'qbo_paid_at'   => 'datetime',
    ];

    const STATUSES = [
        'draft'    => 'Draft',
        'pending'  => 'Pending',
        'approved' => 'Approved',
        'overdue'  => 'Overdue',
        'voided'   => 'Voided',
    ];

    const STATUS_COLORS = [
        'draft'    => 'gray',
        'pending'  => 'blue',
        'approved' => 'green',
        'overdue'  => 'red',
        'voided'   => 'gray',
    ];

    protected static function booted(): void
    {
        static::creating(function (Bill $bill) {
            if (Auth::check()) {
                $bill->created_by = Auth::id();
                $bill->updated_by = Auth::id();
            }
        });

        static::updating(function (Bill $bill) {
            if (Auth::check()) {
                $bill->updated_by = Auth::id();
            }
        });
    }

    // Relationships

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function installer(): BelongsTo
    {
        return $this->belongsTo(Installer::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getPayeeNameAttribute(): string
    {
        if ($this->bill_type === 'vendor') {
            return $this->vendor?->company_name ?? '—';
        }
        return $this->installer?->company_name ?? '—';
    }

    /**
     * Days overdue (positive = overdue, 0 = current/no due date).
     */
    public function getDaysOverdueAttribute(): int
    {
        if (! $this->due_date || in_array($this->status, ['voided', 'approved'])) {
            return 0;
        }
        $days = now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
        return $days < 0 ? abs((int) $days) : 0;
    }

    /**
     * Aging bucket label for this bill.
     */
    public function getAgingBucketAttribute(): string
    {
        $days = $this->days_overdue;
        if ($days === 0) return 'current';
        if ($days <= 30)  return '1_30';
        if ($days <= 60)  return '31_60';
        if ($days <= 90)  return '61_90';
        return '90_plus';
    }

    // Helpers

    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('line_total');

        if ($this->tax_manual) {
            // Use the already-stored manual amounts (don't recalculate from rates)
            $gst = round((float) $this->gst_amount, 2);
            $pst = round((float) $this->pst_amount, 2);
        } else {
            $gst = round($subtotal * $this->gst_rate, 2);
            $pst = round($subtotal * $this->pst_rate, 2);
        }

        $this->subtotal    = $subtotal;
        $this->gst_amount  = $gst;
        $this->pst_amount  = $pst;
        $this->tax_amount  = $gst + $pst;
        $this->grand_total = $subtotal + $gst + $pst;
        $this->saveQuietly();
    }
}
