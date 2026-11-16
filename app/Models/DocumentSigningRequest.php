<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSigningRequest extends Model
{
    protected $fillable = [
        'uuid',
        'batch_uuid',
        'batch_position',
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

    // ── Labels ──────────────────────────────────────────────────────────────

    /** Client-facing name of the document being signed. */
    public function documentLabel(): string
    {
        return match ($this->document_type) {
            'flooring_selection'   => 'Flooring Selection',
            'work_auth'            => 'Work Authorization',
            'opportunity_document' => OpportunityDocument::withTrashed()->find($this->document_id)?->original_name ?? 'Document',
            default                => 'Document',
        };
    }

    // ── Batches (several documents sent together) ──────────────────────────

    public function isBatched(): bool
    {
        return $this->batch_uuid !== null;
    }

    /** Every request in this request's batch (including itself), in signing order. */
    public function batchMembers()
    {
        if (! $this->isBatched()) {
            return collect([$this]);
        }

        return static::where('batch_uuid', $this->batch_uuid)
            ->orderBy('batch_position')
            ->orderBy('id')
            ->get();
    }

    /**
     * The next document in the batch still waiting for a signature — preferring
     * ones after this one, then any earlier one that was skipped.
     */
    public function nextInBatch(): ?self
    {
        if (! $this->isBatched()) {
            return null;
        }

        $open = static::where('batch_uuid', $this->batch_uuid)
            ->where('id', '!=', $this->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->orderBy('batch_position')
            ->orderBy('id')
            ->get();

        return $open->first(fn ($r) => $r->batch_position > $this->batch_position) ?? $open->first();
    }
}
