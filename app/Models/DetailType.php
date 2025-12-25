<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailType extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_type_id',
        'name',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Relationship: belongs to an account type
    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
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
}
