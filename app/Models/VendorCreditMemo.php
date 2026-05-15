<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorCreditMemo extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'credit_memo_number'];

    protected $casts = [
        'date'          => 'date',
        'voided_at'     => 'datetime',
        'subtotal'      => 'decimal:2',
        'gst_rate'      => 'decimal:6',
        'pst_rate'      => 'decimal:6',
        'tax_manual'    => 'boolean',
        'gst_amount'    => 'decimal:2',
        'pst_amount'    => 'decimal:2',
        'tax_amount'    => 'decimal:2',
        'grand_total'   => 'decimal:2',
        'qbo_synced_at' => 'datetime',
    ];

    const STATUSES = [
        'open'   => 'Open',
        'voided' => 'Voided',
    ];

    const STATUS_COLORS = [
        'open'   => 'green',
        'voided' => 'gray',
    ];

    protected static function booted(): void
    {
        static::creating(function (VendorCreditMemo $vcm) {
            $base = DB::table('vendor_credit_memos')->count();
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $seq       = $base + 1 + $attempt;
                $candidate = 'VCM-' . $seq;
                if (! DB::table('vendor_credit_memos')->where('credit_memo_number', $candidate)->exists()) {
                    $vcm->credit_memo_number = $candidate;
                    break;
                }
            }

            if (Auth::check()) {
                $vcm->created_by = Auth::id();
                $vcm->updated_by = Auth::id();
            }
        });

        static::updating(function (VendorCreditMemo $vcm) {
            if (Auth::check()) {
                $vcm->updated_by = Auth::id();
            }
        });
    }

    // Relationships

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function inventoryReturn(): BelongsTo
    {
        return $this->belongsTo(InventoryReturn::class);
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
}
