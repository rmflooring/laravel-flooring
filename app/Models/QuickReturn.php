<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class QuickReturn extends Model
{
    protected $guarded = ['id', 'return_number'];

    protected static function booted(): void
    {
        static::creating(function (QuickReturn $return) {
            if (!empty($return->return_number)) {
                return;
            }

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('quick_returns')
                    ->selectRaw("MAX(CAST(SUBSTRING(return_number, 4) AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $nextNum   = ((int) $max) + 1 + $attempt;
                $candidate = 'QR-' . $nextNum;

                if (! DB::table('quick_returns')->where('return_number', $candidate)->exists()) {
                    $return->return_number = $candidate;
                    return;
                }
            }

            throw new \RuntimeException('Could not generate a unique return number.');
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function taxGroup(): BelongsTo
    {
        return $this->belongsTo(TaxRateGroup::class, 'tax_group_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuickReturnItem::class)->orderBy('sort_order');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
