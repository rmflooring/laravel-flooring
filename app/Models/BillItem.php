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

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function workOrderItem(): BelongsTo
    {
        return $this->belongsTo(WorkOrderItem::class);
    }
}
