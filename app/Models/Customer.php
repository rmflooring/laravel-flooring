<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
//use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'address',
        'address2',
        'city',
        'province',
        'postal_code',
        'customer_type',
        'customer_status',
        'notes',
        'created_by',
        'updated_by',
    ];

    // Relationship: this customer has many children
    public function children(): HasMany
    {
        return $this->hasMany(Customer::class, 'parent_id');
    }

    // Relationship: this customer belongs to a parent
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'parent_id');
    }

    // Created by user
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Updated by user
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($customer) {
            $customer->created_by = auth()->id();
            $customer->updated_by = auth()->id();
        });

        static::updating(function ($customer) {
            $customer->updated_by = auth()->id();
        });
    }
}
