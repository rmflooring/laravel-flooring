<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PackingList extends Model
{
    protected $guarded = ['id', 'pl_number'];

    protected static function booted(): void
    {
        static::creating(function (PackingList $pl) {
            // Generate pl_number: PL-{pt_number}
            $ptNumber = PickTicket::find($pl->pick_ticket_id)?->pt_number ?? 'X';
            $base     = 'PL-' . $ptNumber;
            $candidate = $base;
            $attempt   = 0;

            while (DB::table('packing_lists')->where('pl_number', $candidate)->exists()) {
                $attempt++;
                $candidate = $base . '-' . $attempt;
            }

            $pl->pl_number = $candidate;
        });

        static::creating(function (PackingList $pl) {
            if (auth()->check()) {
                $pl->created_by = $pl->created_by ?? auth()->id();
                $pl->updated_by = auth()->id();
            }
        });

        static::updating(function (PackingList $pl) {
            if (auth()->check()) {
                $pl->updated_by = auth()->id();
            }
        });
    }

    public function pickTicket(): BelongsTo
    {
        return $this->belongsTo(PickTicket::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
