<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSigningRequest extends Model
{
    protected $fillable = [
        'uuid',
        'document_type',
        'document_id',
        'client_name',
        'client_email',
        'status',
        'expires_at',
        'sent_at',
        'viewed_at',
        'signed_at',
        'pending_pdf_path',
        'signed_pdf_path',
        'signature_type',
        'audit_log',
        'reminder_sent_at',
        'reminder_count',
    ];

    protected $casts = [
        'audit_log'        => 'array',
        'expires_at'       => 'datetime',
        'sent_at'          => 'datetime',
        'viewed_at'        => 'datetime',
        'signed_at'        => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isViewable(): bool
    {
        return $this->isPending() && $this->expires_at->isFuture();
    }
}
