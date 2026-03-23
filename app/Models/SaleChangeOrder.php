<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SaleChangeOrder extends Model
{
    protected $guarded = ['id', 'co_number'];

    protected $casts = [
        'approved_at'           => 'datetime',
        'sent_at'               => 'datetime',
        'locked_at'             => 'datetime',
        'original_pretax_total' => 'decimal:2',
        'original_tax_amount'   => 'decimal:2',
        'original_grand_total'  => 'decimal:2',
        'pretax_total'          => 'decimal:2',
        'tax_amount'            => 'decimal:2',
        'grand_total'           => 'decimal:2',
        'locked_pretax_total'   => 'decimal:2',
        'locked_tax_amount'     => 'decimal:2',
        'locked_grand_total'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SaleChangeOrder $co) {
            if (!empty($co->co_number)) {
                return;
            }

            $sale = Sale::find($co->sale_id);
            $saleNumber = $sale?->sale_number ?? '0';

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('sale_change_orders')
                    ->where('sale_id', $co->sale_id)
                    ->selectRaw("MAX(CAST(SUBSTRING_INDEX(co_number, '-', 1) AS UNSIGNED)) as max_seq")
                    ->value('max_seq');

                $nextSeq   = ((int) $max) + 1 + $attempt;
                $candidate = "CO-{$nextSeq}-{$saleNumber}";

                if (! DB::table('sale_change_orders')->where('co_number', $candidate)->exists()) {
                    $co->co_number = $candidate;
                    return;
                }
            }

            throw new \RuntimeException('Could not generate a unique CO number.');
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(SaleChangeOrderRoom::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleChangeOrderItem::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft']);
    }
}
