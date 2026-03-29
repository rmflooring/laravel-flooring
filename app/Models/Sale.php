<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sale extends Model
{
    use SoftDeletes;

    // Protect sale_number from mass assignment (non-editable)
    protected $guarded = ['id', 'sale_number'];

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            // If set internally, keep it
            if (!empty($sale->sale_number)) {
                return;
            }

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('sales')
                    ->selectRaw("MAX(CAST(sale_number AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $nextNum   = ((int) $max) + 1 + $attempt;
                $candidate = (string) $nextNum;

                if (! DB::table('sales')->where('sale_number', $candidate)->exists()) {
                    $sale->sale_number = $candidate;
                    return;
                }
            }

            throw new \RuntimeException('Could not generate a unique sale number.');
        });
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderByDesc('created_at');
    }

    public function activeInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->whereNotIn('status', ['voided']);
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(SaleChangeOrder::class)->orderByDesc('created_at');
    }

    public function activeChangeOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SaleChangeOrder::class)->whereIn('status', ['draft', 'sent'])->latest();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->orderByDesc('created_at');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class)->orderByDesc('created_at');
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
	
	public function salesperson1Employee(): BelongsTo
	{
		return $this->belongsTo(\App\Models\Employee::class, 'salesperson_1_employee_id');
	}

	public function salesperson2Employee(): BelongsTo
	{
		return $this->belongsTo(\App\Models\Employee::class, 'salesperson_2_employee_id');
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