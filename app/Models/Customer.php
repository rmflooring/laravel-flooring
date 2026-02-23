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
	
	public function setPhoneAttribute($value): void
{
    // Remove everything except digits
    $digits = preg_replace('/\D+/', '', (string) $value);

    // Handle leading country code 1 (North America)
    if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
        $this->attributes['phone'] =
            '1-' . substr($digits, 1, 3) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7, 4);
        return;
    }

    // Standard 10-digit
    if (strlen($digits) === 10) {
        $this->attributes['phone'] =
            substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6, 4);
        return;
    }

    // Fallback: store cleaned digits (so you don't lose data)
    $this->attributes['phone'] = $digits;
}

}
