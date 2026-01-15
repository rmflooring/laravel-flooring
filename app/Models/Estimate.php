<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estimate extends Model
{
    protected $fillable = [
        'estimate_number',
        'revision_no',
        'status',

        'customer_name',
        'job_name',
        'job_no',
        'job_address',
        'pm_name',

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

}
