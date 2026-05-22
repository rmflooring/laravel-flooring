<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Installer extends Model
{
    use HasFactory;

    // Maps MS Graph category color preset keys → display hex colors
    const CALENDAR_COLORS = [
        'preset0'  => ['label' => 'Red',          'hex' => '#E74856'],
        'preset1'  => ['label' => 'Orange',        'hex' => '#FF8C00'],
        'preset2'  => ['label' => 'Brown',         'hex' => '#A0522D'],
        'preset3'  => ['label' => 'Yellow',        'hex' => '#FFC300'],
        'preset4'  => ['label' => 'Green',         'hex' => '#107C10'],
        'preset5'  => ['label' => 'Teal',          'hex' => '#00B294'],
        'preset6'  => ['label' => 'Olive',         'hex' => '#6B7C00'],
        'preset7'  => ['label' => 'Blue',          'hex' => '#0078D4'],
        'preset8'  => ['label' => 'Purple',        'hex' => '#8764B8'],
        'preset9'  => ['label' => 'Cranberry',     'hex' => '#C239B3'],
        'preset10' => ['label' => 'Steel',         'hex' => '#7A7574'],
        'preset11' => ['label' => 'Dark Steel',    'hex' => '#393939'],
        'preset12' => ['label' => 'Gray',          'hex' => '#9E9E9E'],
        'preset13' => ['label' => 'Dark Gray',     'hex' => '#616161'],
        'preset15' => ['label' => 'Dark Red',      'hex' => '#A4262C'],
        'preset16' => ['label' => 'Dark Orange',   'hex' => '#CA5010'],
        'preset17' => ['label' => 'Dark Brown',    'hex' => '#6E3B1F'],
        'preset18' => ['label' => 'Dark Yellow',   'hex' => '#B7A300'],
        'preset19' => ['label' => 'Dark Green',    'hex' => '#215732'],
        'preset20' => ['label' => 'Dark Teal',     'hex' => '#00766A'],
        'preset21' => ['label' => 'Dark Olive',    'hex' => '#4B520D'],
        'preset22' => ['label' => 'Dark Blue',     'hex' => '#106EBE'],
        'preset23' => ['label' => 'Dark Purple',   'hex' => '#5C2D91'],
        'preset24' => ['label' => 'Dark Cranberry','hex' => '#750B1C'],
    ];

    protected $casts = [
        'qbo_synced_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'vendor_id',
        'qbo_id',
        'qbo_sync_token',
        'qbo_synced_at',
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
        'calendar_color',
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
