<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estimate extends Model
{
    protected $fillable = [
		'opportunity_id',
        'estimate_number',
        'revision_no',
        'status',

        'customer_name',
        'job_name',
        'job_no',
        'job_address',
        'pm_name',
		'salesperson_1_employee_id',
		'salesperson_2_employee_id',
		
		'homeowner_name',
		'homeowner_phone',
		'homeowner_email',

        'notes',

        'subtotal_materials',
        'subtotal_labour',
        'subtotal_freight',
        'pretax_total',

        'tax_group_id',
        'tax_rate_percent',
        'tax_amount',

        'grand_total',

        'created_by',
        'updated_by',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(EstimateRoom::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }
	
	public function opportunity()
{
    return $this->belongsTo(\App\Models\Opportunity::class);
}

	public function salesperson1()
{
    return $this->belongsTo(\App\Models\User::class, 'salesperson_1_id');
}
		public function salesperson2()
{
    return $this->belongsTo(\App\Models\User::class, 'salesperson_2_id');
}

	public function salesperson1Employee(): BelongsTo
{
    return $this->belongsTo(\App\Models\Employee::class, 'salesperson_1_employee_id');
}

public function salesperson2Employee(): BelongsTo
{
    return $this->belongsTo(\App\Models\Employee::class, 'salesperson_2_employee_id');
}
	public function creator()
{
    return $this->belongsTo(\App\Models\User::class, 'created_by');
}

public function updater()
{
    return $this->belongsTo(\App\Models\User::class, 'updated_by');
}
	
}
