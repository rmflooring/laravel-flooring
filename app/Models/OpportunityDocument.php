<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size_bytes',
        'category',
        'category_override',
        'label_id',
        'label_text',
        'description',
        'created_by',
        'updated_by',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function label()
    {
        return $this->belongsTo(OpportunityDocumentLabel::class, 'label_id');
    }
}
