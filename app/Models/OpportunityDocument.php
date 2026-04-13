<?php

namespace App\Models;

use App\Jobs\MirrorFileToOneDrive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class OpportunityDocument extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        // After a new document is created, queue a background OneDrive mirror
        static::created(function (self $doc) {
            if ($doc->path) {
                MirrorFileToOneDrive::dispatch($doc->disk, $doc->path);
            }
            if ($doc->thumbnail_path) {
                MirrorFileToOneDrive::dispatch($doc->disk, $doc->thumbnail_path);
            }
        });
    }

    protected $fillable = [
        'opportunity_id',
        'disk',
        'path',
        'thumbnail_path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'size_bytes',
        'category',
        'category_override',
        'template_id',
        'sale_id',
        'document_fields',
        'rendered_body',
        'label_id',
        'label_text',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'document_fields' => 'array',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }
        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function label()
    {
        return $this->belongsTo(OpportunityDocumentLabel::class, 'label_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
