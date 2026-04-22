<?php

namespace App\Models;

use App\Jobs\MirrorFileToOneDrive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_order_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::created(function (self $doc) {
            if ($doc->path) {
                MirrorFileToOneDrive::dispatch($doc->disk, $doc->path);
            }
        });
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
