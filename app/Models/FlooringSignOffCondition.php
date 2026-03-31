<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlooringSignOffCondition extends Model
{
    protected $fillable = ['title', 'body', 'sort_order', 'is_active'];

    public function signOffs()
    {
        return $this->hasMany(FlooringSignOff::class, 'condition_id');
    }
}
