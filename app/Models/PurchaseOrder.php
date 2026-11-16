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

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class)->whereNotIn('status', ['voided'])->latest();
    }

    // ── Receiving ───────────────────────────────────────────────────────────
    // "Needs receiving" is based on quantities (inventory receipts per line),
    // not on the status alone — setting a received PO back to Ordered by hand
    // must never make it look unreceived again.

    public function receipts(): HasMany
    {
        return $this->hasMany(InventoryReceipt::class);
    }

    /** Total quantity still owed across all lines. */
    public function remainingToReceive(): float
    {
        return round((float) $this->items->sum(fn (PurchaseOrderItem $i) => $i->remainingQty()), 2);
    }

    public function hasReceipts(): bool
    {
        return $this->relationLoaded('receipts') ? $this->receipts->isNotEmpty() : $this->receipts()->exists();
    }

    public function isFullyReceived(): bool
    {
        return $this->items->isNotEmpty() && $this->remainingToReceive() <= 0;
    }

    /** Some stock received, some still owed. */
    public function isPartiallyReceived(): bool
    {
        return $this->hasReceipts() && ! $this->isFullyReceived();
    }

    /** Whether the Receive screens should be offered for this PO. */
    public function canReceive(): bool
    {
        return $this->status === 'ordered' && $this->remainingToReceive() > 0;
    }

    /**
     * Why this PO can't be received right now, in plain words — or null if it can.
     * Used to send staff back to the PO with a message instead of an error page.
     */
    public function receiveBlockedReason(): ?string
    {
        if ($this->items->isNotEmpty() && $this->remainingToReceive() <= 0 && $this->hasReceipts()) {
            return 'Nothing left to receive — this PO was already fully received on ' . $this->receivedSummary() . '.';
        }

        return match ($this->status) {
            'ordered'   => $this->remainingToReceive() > 0 ? null : 'Nothing left to receive on this PO.',
            'pending'   => "This PO hasn't been ordered yet — set it to Ordered (with the vendor order #) before receiving.",
            'cancelled' => 'This PO is cancelled, so it can\'t be received.',
            default     => 'This PO is already marked ' . $this->status_label . ', so there is nothing to receive.',
        };
    }

    /** e.g. "Nov 16, 2026 (inventory #173)" — for "already received" messages. */
    public function receivedSummary(): ?string
    {
        $receipts = $this->relationLoaded('receipts') ? $this->receipts : $this->receipts()->get();
        if ($receipts->isEmpty()) {
            return null;
        }

        $date = $receipts->max('received_date');
        $ids  = $receipts->sortBy('id')->pluck('id')->map(fn ($id) => '#' . $id)->implode(', ');

        return \Illuminate\Support\Carbon::parse($date)->format('M j, Y') . ' (inventory ' . $ids . ')';
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

    public function documents(): HasMany
    {
        return $this->hasMany(PurchaseOrderDocument::class)->orderByDesc('created_at');
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
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            default     => ucfirst($this->status),
        };
    }

    public function getGrandTotalAttribute(): float
    {
        return $this->items->sum('cost_total');
    }

    // ── Combined lines (same material across rooms) ─────────────────────────

    /**
     * Lines as the VENDOR sees them: PO items sharing a vendor_group are merged
     * into one line with the summed quantity and total. Each line carries its
     * `members` (the underlying per-room PO items) for staff-facing breakdowns.
     *
     * @return \Illuminate\Support\Collection<int, object{item_name: string, quantity: float, unit: ?string, cost_price: float, cost_total: float, po_notes: ?string, members: \Illuminate\Support\Collection, is_combined: bool, vendor_group: ?string}>
     */
    public function vendorLines()
    {
        $groups = [];
        foreach ($this->items->sortBy([['sort_order', 'asc'], ['id', 'asc']]) as $item) {
            $key = $item->vendor_group ? 'g:' . $item->vendor_group : 'i:' . $item->id;
            $groups[$key][] = $item;
        }

        return collect($groups)->values()->map(function (array $members) {
            $members = collect($members);
            $first   = $members->first();
            $notes   = $members->pluck('po_notes')->map(fn ($n) => trim((string) $n))->filter()->unique()->implode("\n");

            return (object) [
                'item_name'    => $first->item_name,
                'quantity'     => round((float) $members->sum('quantity'), 2),
                'unit'         => $first->unit,
                'cost_price'   => (float) $first->cost_price,
                'cost_total'   => round((float) $members->sum('cost_total'), 2),
                'po_notes'     => $notes !== '' ? $notes : null,
                'members'      => $members,
                'remaining'    => round((float) $members->sum(fn ($m) => $m->remainingQty()), 2),
                'is_combined'  => $members->count() > 1,
                'vendor_group' => $members->count() > 1 ? $first->vendor_group : null,
            ];
        });
    }
}
