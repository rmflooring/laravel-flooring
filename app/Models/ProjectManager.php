<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'phone',
        'mobile',
        'email',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Relationship: belongs to a customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
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
        static::creating(function ($pm) {
            $pm->created_by = auth()->id();
            $pm->updated_by = auth()->id();
        });

        static::updating(function ($pm) {
            $pm->updated_by = auth()->id();
        });
    }
}
