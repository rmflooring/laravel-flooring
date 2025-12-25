<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxAgency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'registration_number',
        'next_period_month',
        'filing_frequency',
        'reporting_method',
        'collect_on_sales',
        'pay_on_purchases',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'collect_on_sales' => 'boolean',
        'pay_on_purchases' => 'boolean',
    ];

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($agency) {
            $agency->created_by = auth()->id();
            $agency->updated_by = auth()->id();
        });

        static::updating(function ($agency) {
            $agency->updated_by = auth()->id();
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
