<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitMeasure extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'status',
        'created_by',
        'updated_by',
    ];

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($measure) {
            $measure->created_by = auth()->id();
            $measure->updated_by = auth()->id();
        });

        static::updating(function ($measure) {
            $measure->updated_by = auth()->id();
        });
    }

    // Relationship to creator user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to updater user
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
