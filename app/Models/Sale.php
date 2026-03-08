<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    // Protect sale_number from mass assignment (non-editable)
    protected $guarded = ['id', 'sale_number'];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            // If set internally, keep it
            if (!empty($sale->sale_number)) {
                return;
            }

         // Generate next sequential number in YYYY-0001 format
$yearPrefix = now()->format('Y') . '-'; // e.g. "2026-"
$prefixLen  = strlen($yearPrefix);

for ($attempt = 0; $attempt < 10; $attempt++) {

    $max = DB::table('sales')
        ->where('sale_number', 'like', $yearPrefix . '%')
        ->selectRaw("MAX(CAST(SUBSTRING(sale_number, ?) AS UNSIGNED)) as max_num", [$prefixLen + 1])
        ->value('max_num');

    $nextNum = ((int)$max) + 1 + $attempt;

    $candidate = $yearPrefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);

    if (!DB::table('sales')->where('sale_number', $candidate)->exists()) {
        $sale->sale_number = $candidate;
        return;
    }
}

throw new \RuntimeException('Could not generate a unique sale number.');
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(SaleRoom::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function sourceEstimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class, 'source_estimate_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }
	
	public function creator(): BelongsTo
	{
		return $this->belongsTo(\App\Models\User::class, 'created_by');
	}

	public function updater(): BelongsTo
	{
		return $this->belongsTo(\App\Models\User::class, 'updated_by');
	}
}