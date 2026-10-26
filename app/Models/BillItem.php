<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    protected $guarded = ['id'];

    const CHARGE_TYPES = [
        'fuel'          => 'Fuel Surcharge',
        'freight'       => 'Freight / Delivery',
        'other'         => 'Other Charge',
        'early_payment' => 'Early Payment Credit',
        'other_credit'  => 'Other Credit',
    ];

    const CREDIT_TYPES = ['early_payment', 'other_credit'];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'line_total' => 'decimal:2',
        'gst_rate'   => 'decimal:4',
        'pst_rate'   => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function (BillItem $item) {
            // line_total is NOT force-recomputed here — the caller passes the intended
            // value explicitly (either qty * unit_cost, or an exact total the user typed
            // that back-calculated unit_cost). Only fill it in as a fallback for a new
            // item created without one at all.
            if (! $item->exists && $item->line_total === null) {
                $item->line_total = round($item->quantity * $item->unit_cost, 2);
            }
        });
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    // Helpers

    /**
     * GST rate (decimal) applied to this line: its own override, else the bill's.
     * Early payment credits are zero-rated.
     */
    public function effectiveGstRate(Bill $bill): float
    {
        if ($this->charge_type === 'early_payment') {
            return 0.0;
        }
        return (float) ($this->gst_rate ?? $bill->gst_rate);
    }

    public function effectivePstRate(Bill $bill): float
    {
        if ($this->charge_type === 'early_payment') {
            return 0.0;
        }
        return (float) ($this->pst_rate ?? $bill->pst_rate);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }
}
