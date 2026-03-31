<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlooringSignOffItem extends Model
{
    protected $fillable = [
        'sign_off_id', 'room_name', 'product_description', 'qty', 'unit', 'sort_order',
    ];

    public function signOff()
    {
        return $this->belongsTo(FlooringSignOff::class, 'sign_off_id');
    }
}
