<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    use SoftDeletes;

    protected $guarded = ['id', 'invoice_number'];

    protected $casts = [
        'subtotal'     => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'grand_total'  => 'decimal:2',
        'amount_paid'  => 'decimal:2',
        'due_date'     => 'date',
        'sent_at'      => 'datetime',
        'voided_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (!empty($invoice->invoice_number)) {
                return;
            }

            $year = now()->year;

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $max = DB::table('invoices')
                    ->where('invoice_number', 'like', $year . '-%')
                    ->selectRaw("MAX(CAST(SUBSTRING(invoice_number, " . (strlen($year) + 2) . ") AS UNSIGNED)) as max_seq")
                    ->value('max_seq');

                $nextSeq   = ((int) $max) + 1 + $attempt;
                $candidate = $year . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

                if (! DB::table('invoices')->where('invoice_number', $candidate)->exists()) {
                    $invoice->invoice_number = $candidate;
                    return;
                }
            }

            throw new \RuntimeException('Could not generate a unique invoice number.');
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function paymentTerm(): BelongsTo
    {
        return $this->belongsTo(PaymentTerm::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(InvoiceRoom::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('payment_date');
    }

    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->grand_total - (float) $this->amount_paid, 2);
    }

    public function getIsOverpaidAttribute(): bool
    {
        return (float) $this->amount_paid > (float) $this->grand_total;
    }
}
