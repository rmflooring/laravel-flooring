<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalePayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public const PAYMENT_METHODS = [
        'cash'        => 'Cash',
        'cheque'      => 'Cheque',
        'e-transfer'  => 'E-Transfer',
        'credit_card' => 'Credit Card',
        'other'       => 'Other',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'payer_customer_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function getPayerLabelAttribute(): string
    {
        return match ($this->payer_type) {
            'parent'   => 'Parent Customer',
            'job_site' => 'Job Site',
            default    => 'Customer',
        };
    }

    /**
     * Whether this deposit has been applied to a non-voided invoice.
     */
    public function getIsAppliedAttribute(): bool
    {
        return $this->invoicePayments()
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->whereNotIn('invoices.status', ['voided'])
            ->exists();
    }

    /**
     * The invoice this deposit was applied to (first non-voided).
     */
    public function getAppliedInvoiceAttribute(): ?Invoice
    {
        $payment = $this->invoicePayments()
            ->with('invoice')
            ->whereHas('invoice', fn ($q) => $q->whereNotIn('status', ['voided']))
            ->first();

        return $payment?->invoice;
    }
}
