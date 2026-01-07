<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductLine;

class ProductStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_line_id',
        'name',
        'sku',
        'style_number',
        'color',
        'pattern',
        'description',
        'cost_price',
        'sell_price',
        'status',
        'created_by',
        'updated_by',
    ];

    public function productLine()
    {
        return $this->belongsTo(ProductLine::class, 'product_line_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
