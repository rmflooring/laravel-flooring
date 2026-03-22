<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CustomerReturn extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'rfc_number'];

    protected $casts = [
        'received_date' => 'date',
    ];

    const STATUS_LABELS = [
        'draft'     => 'Draft',
        'received'  => 'Received',
        'cancelled' => 'Cancelled',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerReturn $rfc) {
            // Build sale suffix: -sale_number if a sale is linked
            $saleSuffix = '';
            if (! empty($rfc->sale_id)) {
                $saleNumber = DB::table('sales')->where('id', $rfc->sale_id)->value('sale_number');
                if ($saleNumber) {
                    $saleSuffix = '-' . $saleNumber;
                }
            }

            // Sequential number — start from total count to avoid collisions with old-format records
            $base = DB::table('customer_returns')->count();
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $seq       = $base + 1 + $attempt;
                $candidate = 'RFC-' . $seq . $saleSuffix;
                if (! DB::table('customer_returns')->where('rfc_number', $candidate)->exists()) {
                    $rfc->rfc_number = $candidate;
                    break;
                }
            }

            if (auth()->check()) {
                $rfc->created_by ??= auth()->id();
                $rfc->updated_by  = auth()->id();
            }
        });

        static::updating(function (CustomerReturn $rfc) {
            if (auth()->check()) {
                $rfc->updated_by = auth()->id();
            }
        });
    }

    public function pickTicket(): BelongsTo
    {
        return $this->belongsTo(PickTicket::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
