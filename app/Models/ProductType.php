<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'ordered_by_unit_id',
        'sold_by_unit_id',
        'default_cost_gl_account_id',
        'default_sell_gl_account_id',
    ];

    /**
     * Get the unit used for ordering this product type.
     */
    public function orderedByUnit()
    {
        return $this->belongsTo(UnitMeasure::class, 'ordered_by_unit_id');
    }

    /**
     * Get the unit used for selling this product type.
     */
    public function soldByUnit()
    {
        return $this->belongsTo(UnitMeasure::class, 'sold_by_unit_id');
    }

    /**
     * Get the default GL account used for costing/purchasing.
     */
    public function defaultCostGlAccount()
    {
        return $this->belongsTo(GLAccount::class, 'default_cost_gl_account_id');
    }

    /**
     * Get the default GL account used for selling/revenue.
     */
    public function defaultSellGlAccount()
    {
        return $this->belongsTo(GLAccount::class, 'default_sell_gl_account_id');
    }
}
