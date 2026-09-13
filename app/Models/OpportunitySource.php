<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunitySource extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'opportunity_source_id');
    }
}
