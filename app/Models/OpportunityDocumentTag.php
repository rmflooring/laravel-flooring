<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunityDocumentTag extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function documents()
    {
        return $this->belongsToMany(OpportunityDocument::class, 'opportunity_document_tag_assignments', 'tag_id', 'document_id');
    }
}
