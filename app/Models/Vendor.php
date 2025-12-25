<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_name',
        'phone',
        'mobile',
        'email',
        'address',
        'address2',
        'city',
        'province',
        'postal_code',
        'website',
        'notes',
        'vendor_type',
        'status',
        'account_number',
        'terms',
        'created_by',
        'updated_by',
    ];

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($vendor) {
            $vendor->created_by = auth()->id();
            $vendor->updated_by = auth()->id();
        });

        static::updating(function ($vendor) {
            $vendor->updated_by = auth()->id();
        });
    }

    //RElationship to vendor rep
//    public function reps(): BelongsToMany
//	{
//    return $this->belongsToMany(VendorRep::class, 'vendor_vendor_rep');
//	}

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

    // A vendor can have many reps (many-to-many)
    public function reps()
	{
    return $this->belongsToMany(VendorRep::class, 'vendor_vendor_rep');
	}

}
