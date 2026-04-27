<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public const PAYMENT_METHODS = [
        'cash'         => 'Cash',
        'cheque'       => 'Cheque',
        'e-transfer'   => 'E-Transfer',
        'visa'         => 'Visa',
        'mastercard'   => 'Mastercard',
        'other'        => 'Other',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function salePayment(): BelongsTo
    {
        return $this->belongsTo(SalePayment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method]
            ?? ucwords(str_replace('_', ' ', $this->payment_method));
    }
}
