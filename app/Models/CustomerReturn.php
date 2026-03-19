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
            // Generate RFC-YYYY-0001 sequential per year
            $year = now()->year;
            $max  = DB::table('customer_returns')
                ->whereYear('created_at', $year)
                ->max(DB::raw("CAST(SUBSTRING_INDEX(rfc_number, '-', -1) AS UNSIGNED)"));

            $next           = str_pad(((int) $max) + 1, 4, '0', STR_PAD_LEFT);
            $rfc->rfc_number = "RFC-{$year}-{$next}";

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
