<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductLine;  

class ProductLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type_id',
        'name',
		'vendor_id',
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
	public function vendorRelation()
{
    return $this->belongsTo(Vendor::class, 'vendor_id');
}
		public function productStyles()
{
    return $this->hasMany(ProductStyle::class, 'product_line_id');
}
	
}


