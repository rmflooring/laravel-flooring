<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerCredit extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'credit_number'];

    protected $casts = [
        'amount'        => 'decimal:2',
        'voided_at'     => 'datetime',
        'qbo_synced_at' => 'datetime',
    ];

    const STATUSES = [
        'open'   => 'Open',
        'voided' => 'Voided',
    ];

    const STATUS_COLORS = [
        'open'   => 'green',
        'voided' => 'gray',
    ];

    protected static function booted(): void
    {
        static::creating(function (CustomerCredit $credit) {
            $base = DB::table('customer_credits')->count();
            for ($attempt = 0; $attempt < 20; $attempt++) {
                $seq       = $base + 1 + $attempt;
                $candidate = 'CR-' . $seq;
                if (! DB::table('customer_credits')->where('credit_number', $candidate)->exists()) {
                    $credit->credit_number = $candidate;
                    break;
                }
            }

            if (Auth::check()) {
                $credit->created_by = Auth::id();
                $credit->updated_by = Auth::id();
            }
        });

        static::updating(function (CustomerCredit $credit) {
            if (Auth::check()) {
                $credit->updated_by = Auth::id();
            }
        });
    }

    // Relationships

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sourceSale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'source_sale_id');
    }

    public function sourceInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'source_invoice_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CustomerCreditApplication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Accessors

    public function getRemainingBalanceAttribute(): float
    {
        if ($this->status === 'voided') {
            return 0.0;
        }

        return round((float) $this->amount - (float) $this->applications->sum('amount'), 2);
    }

    public function getIsFullyAppliedAttribute(): bool
    {
        return $this->status !== 'voided' && $this->remaining_balance <= 0.005;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Apply (some or all of) this credit's remaining balance to a Sale or Invoice.
     */
    public function applyTo(Sale|Invoice $target, float $amount, ?string $notes = null): CustomerCreditApplication
    {
        $amount = round($amount, 2);

        if ($this->status !== 'open') {
            throw new \InvalidArgumentException('This credit is not open.');
        }

        if ($amount <= 0 || $amount > $this->remaining_balance + 0.005) {
            throw new \InvalidArgumentException('Amount exceeds the credit\'s remaining balance.');
        }

        if ($amount > $target->balance_due + 0.005) {
            throw new \InvalidArgumentException('Amount exceeds the outstanding balance due.');
        }

        $application = $this->applications()->create([
            'type'         => 'redemption',
            'sale_id'      => $target instanceof Sale ? $target->id : null,
            'invoice_id'   => $target instanceof Invoice ? $target->id : null,
            'amount'       => $amount,
            'applied_date' => now(),
            'notes'        => $notes,
        ]);

        if ($target instanceof Invoice) {
            app(\App\Services\InvoiceService::class)->derivePaymentStatus($target);
        }

        return $application;
    }

    /**
     * Pay (some or all of) this credit's remaining balance back to the customer in cash —
     * reduces the remaining balance the same way applyTo() does, just with no Sale/Invoice
     * target. Unlike void(), this records how and how much was actually paid out.
     */
    public function refund(float $amount, string $refundMethod, ?string $referenceNumber = null, ?string $notes = null): CustomerCreditApplication
    {
        $amount = round($amount, 2);

        if ($this->status !== 'open') {
            throw new \InvalidArgumentException('This credit is not open.');
        }

        if ($amount <= 0 || $amount > $this->remaining_balance + 0.005) {
            throw new \InvalidArgumentException('Amount exceeds the credit\'s remaining balance.');
        }

        return $this->applications()->create([
            'type'             => 'refund',
            'amount'           => $amount,
            'applied_date'     => now(),
            'refund_method'    => $refundMethod,
            'reference_number' => $referenceNumber,
            'notes'            => $notes,
        ]);
    }
}
