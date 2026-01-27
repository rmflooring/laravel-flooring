<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreightItem extends Model
{
    protected $fillable = [
        'description',
        'cost_price',
        'sell_price',
        'notes',
        'created_by',
        'updated_by',
    ];
}
