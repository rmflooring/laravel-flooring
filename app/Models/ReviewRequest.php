<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReviewRequest extends Model
{
    protected $fillable = [
        'token', 'opportunity_id', 'sent_by', 'customer_name',
        'customer_phone', 'customer_email', 'sent_via',
        'rating', 'feedback', 'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'rating'       => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $r) {
            $r->token = $r->token ?? Str::random(48);
        });
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    public function isPositive(): bool
    {
        return $this->rating !== null && $this->rating >= 4;
    }

    public function publicUrl(): string
    {
        return url('/review/' . $this->token);
    }
}
