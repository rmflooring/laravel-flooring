<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * One payment received from a customer, split across their sales (deposits),
 * invoices (invoice payments) and — for any remainder — a customer credit.
 * See CustomerPaymentService for how it's recorded and synced to QBO.
 */
class CustomerPayment extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $guarded = ['id', 'payment_number'];

    protected $casts = [
        'amount'        => 'decimal:2',
        'payment_date'  => 'date',
        'qbo_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (CustomerPayment $payment) {
            $payment->payment_number = 'PMT-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT);
            $payment->saveQuietly();
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Portions recorded as deposits on not-yet-invoiced sales. */
    public function deposits(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    /**
     * Portions sitting on invoices — both the ones allocated straight to an invoice and
     * the ones a deposit turned into once its sale was invoiced (those also carry
     * sale_payment_id).
     */
    public function invoicePayments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    /** The unallocated remainder, if any, issued as a customer credit. */
    public function credit(): HasOne
    {
        return $this->hasOne(CustomerCredit::class, 'source_customer_payment_id');
    }

    public function getMethodLabelAttribute(): string
    {
        return InvoicePayment::PAYMENT_METHODS[$this->payment_method]
            ?? SalePayment::PAYMENT_METHODS[$this->payment_method]
            ?? ucwords(str_replace(['_', '-'], ' ', $this->payment_method));
    }

    /**
     * True once anything downstream has happened that deleting this payment would
     * silently undo: a deposit already turned into an invoice payment, or the leftover
     * credit already used/refunded.
     */
    public function getCanBeDeletedAttribute(): bool
    {
        $this->loadMissing(['deposits', 'credit.applications']);

        $depositApplied = $this->deposits->contains(fn (SalePayment $d) => $d->is_applied);
        $creditUsed     = $this->credit && $this->credit->applications->isNotEmpty();

        return ! $depositApplied && ! $creditUsed;
    }
}
