<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sample extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'sample_id'];

    protected $casts = [
        'received_at'      => 'date',
        'discontinued_at'  => 'datetime',
        'display_price'    => 'float',
    ];

    const STATUSES = [
        'active'       => 'Active',
        'checked_out'  => 'Checked Out',
        'discontinued' => 'Discontinued',
        'retired'      => 'Retired',
        'lost'         => 'Lost',
    ];

    const STATUS_COLORS = [
        'active'       => 'green',
        'checked_out'  => 'blue',
        'discontinued' => 'gray',
        'retired'      => 'yellow',
        'lost'         => 'red',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sample $sample) {
            // Auto-generate sample_id: SMP-0001
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('samples')
                    ->selectRaw("MAX(CAST(SUBSTRING(sample_id, 5) AS UNSIGNED)) as max_num")
                    ->value('max_num');

                $nextNum   = ((int) $max) + 1 + $attempt;
                $candidate = 'SMP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

                if (! DB::table('samples')->where('sample_id', $candidate)->exists()) {
                    $sample->sample_id = $candidate;
                    break;
                }
            }

            $userId = auth()->id();
            if ($userId) {
                $sample->created_by = $sample->created_by ?? $userId;
                $sample->updated_by = $sample->updated_by ?? $userId;
            }
        });

        static::updating(function (Sample $sample) {
            $userId = auth()->id();
            if ($userId) {
                $sample->updated_by = $userId;
            }
        });
    }

    // --- Relationships ---

    public function productStyle(): BelongsTo
    {
        return $this->belongsTo(ProductStyle::class);
    }

    public function checkouts(): HasMany
    {
        return $this->hasMany(SampleCheckout::class)->orderByDesc('checked_out_at');
    }

    public function activeCheckouts(): HasMany
    {
        return $this->hasMany(SampleCheckout::class)->whereNull('returned_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // --- Accessors ---

    public function getAvailableQtyAttribute(): int
    {
        $checkedOut = $this->activeCheckouts()->sum('qty_checked_out');
        return max(0, $this->quantity - (int) $checkedOut);
    }

    public function getEffectivePriceAttribute(): ?float
    {
        return $this->display_price ?? $this->productStyle?->sell_price;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    // --- Scopes ---

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->whereHas('activeCheckouts', function ($q) {
            $q->whereNotNull('due_back_at')->where('due_back_at', '<', now()->toDateString());
        });
    }
}
