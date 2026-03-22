<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class InventoryReturn extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'return_number'];

    const REASON_LABELS = [
        'wrong_item'    => 'Wrong item',
        'damaged'       => 'Damaged',
        'overstock'     => 'Overstock',
        'cancelled_job' => 'Cancelled job',
    ];

    const OUTCOME_LABELS = [
        'pending'     => 'Pending',
        'credit_note' => 'Credit note',
        'replacement' => 'Replacement',
        'refund'      => 'Refund',
    ];

    const STATUS_LABELS = [
        'draft'    => 'Draft',
        'shipped'  => 'Shipped',
        'resolved' => 'Resolved',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryReturn $rtv) {
            // Build sale suffix: look up sale via the linked PO
            $saleSuffix = '';
            if (! empty($rtv->purchase_order_id)) {
                $saleId = DB::table('purchase_orders')->where('id', $rtv->purchase_order_id)->value('sale_id');
                if ($saleId) {
                    $saleNumber = DB::table('sales')->where('id', $saleId)->value('sale_number');
                    if ($saleNumber) {
                        $saleSuffix = '-' . $saleNumber;
                    }
                }
            }

            // Sequential number — start from total count to avoid collisions with old-format records
            $base = DB::table('inventory_returns')->count();
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $seq       = $base + 1 + $attempt;
                $candidate = 'RTV-' . $seq . $saleSuffix;
                if (! DB::table('inventory_returns')->where('return_number', $candidate)->exists()) {
                    $rtv->return_number = $candidate;
                    break;
                }
            }

            if (auth()->check()) {
                $rtv->returned_by_user_id ??= auth()->id();
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryReturnItem::class);
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASON_LABELS[$this->reason] ?? ucfirst(str_replace('_', ' ', $this->reason ?? ''));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getOutcomeLabelAttribute(): string
    {
        return self::OUTCOME_LABELS[$this->outcome] ?? ucfirst($this->outcome);
    }
}
