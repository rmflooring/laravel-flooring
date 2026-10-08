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
        'qbo_id',
        'qbo_sync_token',
        'qbo_synced_at',
        'returns_accepted',
        'return_days',
        'return_condition',
        'restocking_fee_percent',
        'special_orders_final_sale',
        'return_policy_notes',
    ];

    protected $casts = [
        'qbo_synced_at' => 'datetime',
        'returns_accepted' => 'boolean',
        'special_orders_final_sale' => 'boolean',
        'restocking_fee_percent' => 'decimal:2',
    ];

    /**
     * Return policy as shown to website customers, or null if it hasn't been set up for this vendor.
     * The vendor's name is never included (customers see the brand, not the supplier).
     */
    public function returnPolicy(): ?array
    {
        if ($this->returns_accepted === null) {
            return null;
        }

        if (! $this->returns_accepted) {
            $summary = 'Final sale — this product can’t be returned.';
        } else {
            $parts = [$this->return_days ? "Returns accepted within {$this->return_days} days" : 'Returns accepted'];
            if ($this->return_condition) {
                $parts[] = lcfirst(rtrim($this->return_condition, '.'));
            }
            $summary = implode(' — ', $parts) . '.';
            if ((float) $this->restocking_fee_percent > 0) {
                $summary .= ' A ' . rtrim(rtrim(number_format((float) $this->restocking_fee_percent, 2), '0'), '.') . '% restocking fee applies.';
            }
        }

        return [
            'returns_accepted' => (bool) $this->returns_accepted,
            'return_days' => $this->return_days,
            'condition' => $this->return_condition,
            'restocking_fee_percent' => $this->restocking_fee_percent !== null ? (float) $this->restocking_fee_percent : null,
            'special_orders_final_sale' => (bool) $this->special_orders_final_sale,
            'notes' => $this->return_policy_notes,
            'summary' => $summary,
        ];
    }

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

    // A subcontractor vendor may have a linked installer
    public function installers()
    {
        return $this->hasMany(Installer::class);
    }

}
