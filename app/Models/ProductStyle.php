<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ProductLine;

class ProductStyle extends Model
{
    use HasFactory;

    protected $casts = [
        'use_box_qty' => 'bool',
        'units_per'   => 'float',
        'cost_price'  => 'float',
        'sell_price'  => 'float',
    ];

    protected $fillable = [
        'product_line_id',
        'vendor_id',
        'name',
        'sku',
        'style_number',
        'color',
        'pattern',
        'description',
        'cost_price',
        'sell_price',
        'units_per',
        'use_box_qty',
        'thickness',
        'status',
        'created_by',
        'updated_by',
    ];

    public function productLine()
    {
        return $this->belongsTo(ProductLine::class, 'product_line_id');
    }

    public function vendor()
    {
        return $this->belongsTo(\App\Models\Vendor::class, 'vendor_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function estimateItems()
    {
        return $this->hasMany(\App\Models\EstimateItem::class, 'product_style_id');
    }

    public function saleItems()
    {
        return $this->hasMany(\App\Models\SaleItem::class, 'product_style_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductStylePhoto::class)->orderBy('sort_order');
    }

    public function primaryPhoto(): HasMany
    {
        return $this->hasMany(ProductStylePhoto::class)->where('is_primary', true)->limit(1);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function hasActivity(): bool
    {
        return $this->estimateItems()->exists() || $this->saleItems()->exists();
    }
}
