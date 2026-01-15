<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunityDocumentLabel extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function documents()
    {
        return $this->hasMany(OpportunityDocument::class, 'label_id');
    }
}
