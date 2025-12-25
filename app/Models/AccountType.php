<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
    'name',
    'category',
    'description',
    'status',
    'created_by',
    'updated_by',
];

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($type) {
            $type->created_by = auth()->id();
            $type->updated_by = auth()->id();
        });

        static::updating(function ($type) {
            $type->updated_by = auth()->id();
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
