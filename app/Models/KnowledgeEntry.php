<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeEntry extends Model
{
    // Fixed allowlist, not a DB enum — matches AttachImagesService::CATEGORIES /
    // LogCommunicationService::CATEGORIES elsewhere in this agent module.
    public const CATEGORIES = [
        'pricing',
        'protocol',
        'policy',
        'sop',
        'faq',
    ];

    protected $fillable = [
        'title',
        'category',
        'content',
        'structured_data',
        'visible_to_roles',
        'created_by',
    ];

    protected $casts = [
        'structured_data' => 'array',
        'visible_to_roles' => 'array',
    ];

    public function embeddings(): HasMany
    {
        return $this->hasMany(KnowledgeEmbedding::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
