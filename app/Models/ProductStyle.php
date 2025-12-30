<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStyle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_line_id',
        'name',
        'style_number',
        'color',
        'pattern',
        'description',
        'status',
    ];

    /**
     * Get the product line that owns the style.
     */
    public function productLine()
    {
        return $this->belongsTo(ProductLine::class, 'product_line_id');
    }

    /**
     * Scope a query to only include active styles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
	
}