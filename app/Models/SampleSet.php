<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SampleSet extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    const STATUSES = ['active', 'checked_out', 'discontinued', 'retired', 'lost'];

    const STATUS_COLORS = [
        'active'       => 'bg-green-100 text-green-800',
        'checked_out'  => 'bg-blue-100 text-blue-800',
        'discontinued' => 'bg-gray-100 text-gray-600',
        'retired'      => 'bg-yellow-100 text-yellow-800',
        'lost'         => 'bg-red-100 text-red-800',
    ];

    protected static function booted(): void
    {
        static::creating(function (SampleSet $set) {
            if (empty($set->set_id)) {
                $last = static::withTrashed()
                    ->where('set_id', 'like', 'SET-%')
                    ->orderByDesc('id')
                    ->value('set_id');

                $next = $last ? (int) substr($last, 4) + 1 : 1;
                $set->set_id = 'SET-' . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────────

    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SampleSetItem::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(SampleCheckout::class, 'sample_set_id');
    }

    public function activeCheckout()
    {
        return $this->hasOne(SampleCheckout::class, 'sample_set_id')
            ->whereNull('returned_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Accessors ──────────────────────────────────────────────

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeOverdue($query)
    {
        return $query->whereHas('checkouts', function ($q) {
            $q->whereNull('returned_at')
              ->where('due_back_at', '<', now()->startOfDay());
        });
    }
}
