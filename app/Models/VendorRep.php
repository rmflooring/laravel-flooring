<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorRep extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'mobile',
        'email',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($rep) {
            $rep->created_by = auth()->id();
            $rep->updated_by = auth()->id();
        });

        static::updating(function ($rep) {
            $rep->updated_by = auth()->id();
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
