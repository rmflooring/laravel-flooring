<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
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
        'account_number',
        'gst_number',
        'terms',
        'gl_cost_account_id',
        'gl_sale_account_id',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function ($installer) {
            $installer->created_by = auth()->id();
            $installer->updated_by = auth()->id();
        });

        static::updating(function ($installer) {
            $installer->updated_by = auth()->id();
        });
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function glCostAccount()
    {
        return $this->belongsTo(GLAccount::class, 'gl_cost_account_id');
    }

    public function glSaleAccount()
    {
        return $this->belongsTo(GLAccount::class, 'gl_sale_account_id');
    }

    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class)->orderByDesc('created_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
