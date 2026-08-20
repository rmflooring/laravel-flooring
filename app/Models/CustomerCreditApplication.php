<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class CustomerCreditApplication extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount'        => 'decimal:2',
        'applied_date'  => 'date',
        'qbo_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerCreditApplication $application) {
            if (Auth::check()) {
                $application->created_by = $application->created_by ?? Auth::id();
            }
        });
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(CustomerCredit::class, 'customer_credit_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return InvoicePayment::PAYMENT_METHODS[$this->refund_method]
            ?? ucwords(str_replace(['_', '-'], ' ', (string) $this->refund_method));
    }
}
