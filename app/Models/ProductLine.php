<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'name',
        'vendor',
        'manufacturer',
        'model',
        'collection',
        'status',
        'created_by',
        'updated_by',
    ];

    // Relationship to ProductType
    public function productType()
    {
        return $this->belongsTo(ProductType::class);
    }
}

