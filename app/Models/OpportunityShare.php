<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OpportunityShare extends Model
{
    protected $fillable = ['token', 'opportunity_id', 'label', 'created_by', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $share) {
            $share->token = $share->token ?? Str::random(48);
        });
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents()
    {
        return $this->belongsToMany(OpportunityDocument::class, 'opportunity_share_documents', 'share_id', 'document_id')
                    ->withTrashed();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function publicUrl(): string
    {
        return url('/share/' . $this->token);
    }
}
