<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'direction',
        'body',
        'media',
        'sent_by_id',
        'twilio_sid',
        'voipms_id',
        'unconfirmed',
        'read_at',
    ];

    protected $casts = [
        'read_at'     => 'datetime',
        'unconfirmed' => 'boolean',
        'media'       => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SmsConversation::class, 'conversation_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_id');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'outbound';
    }

    public function hasMedia(): bool
    {
        return ! empty($this->media);
    }

    /**
     * Body for one-line previews (conversation list, alerts) — a picture-only MMS has
     * no text of its own.
     */
    public function previewText(): string
    {
        if (trim((string) $this->body) !== '') {
            return $this->body;
        }

        $count = count($this->media ?? []);

        return $count > 1 ? "📷 {$count} pictures" : ($count ? '📷 Picture' : '');
    }
}
