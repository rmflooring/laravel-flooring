<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Sale;
use App\Models\SalePayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records one payment from a customer split across any mix of:
 *   - their open invoices            → an InvoicePayment on each
 *   - their not-yet-invoiced sales   → a SalePayment (deposit) on each
 *   - whatever's left over           → a CustomerCredit (apply later or refund)
 * all linked back to a single CustomerPayment, which goes to QBO as ONE Payment.
 */
class CustomerPaymentService
{
    public function __construct(
        protected InvoiceService $invoiceService,
    ) {}

    // ── What can be paid ────────────────────────────────────────────────────

    /** Opportunity ids where this customer is the parent or the job site. */
    protected function opportunityIdsFor(Customer $customer): Collection
    {
        return $customer->opportunitiesAsParent()->pluck('id')
            ->merge($customer->opportunitiesAsJobSite()->pluck('id'))
            ->unique();
    }

    /** Non-voided invoices with a balance owing, oldest first. */
    public function openInvoicesFor(Customer $customer): Collection
    {
        $sales = Sale::whereIn('opportunity_id', $this->opportunityIdsFor($customer))
            ->with(['invoices' => fn ($q) => $q->whereNotIn('status', ['voided'])])
            ->get();

        return $sales->flatMap(fn ($sale) => $sale->invoices->map(function ($invoice) use ($sale) {
                $invoice->sale_number = $sale->sale_number;
                // sale_number isn't a real invoices column — sync it into "original"
                // so it's never treated as dirty and swept into a later ->update() call.
                $invoice->syncOriginal();
                return $invoice;
            }))
            ->filter(fn ($invoice) => $invoice->balance_due > 0.004)
            ->sortBy('created_at')
            ->values();
    }

    /**
     * Sales that can take a deposit: not cancelled, not invoiced at all yet (once a sale
     * has an invoice, money goes on the invoice instead — that keeps a payment from being
     * counted on both), and with a balance owing.
     */
    public function depositableSalesFor(Customer $customer): Collection
    {
        return Sale::whereIn('opportunity_id', $this->opportunityIdsFor($customer))
            ->whereNotIn('status', ['cancelled', 'invoiced'])
            ->whereDoesntHave('invoices', fn ($q) => $q->whereNotIn('status', ['voided']))
            ->with(['deposits', 'creditApplications', 'opportunity'])
            ->orderBy('created_at')
            ->get()
            ->filter(fn (Sale $sale) => $sale->balance_due > 0.004)
            ->values();
    }

    // ── Recording ───────────────────────────────────────────────────────────

    /**
     * @param  array  $data              amount, payment_date, payment_method, reference_number?, notes?
     * @param  array  $invoiceAllocations  [invoice_id => amount]
     * @param  array  $saleAllocations     [sale_id => amount]
     *
     * @throws \InvalidArgumentException with a user-facing message
     */
    public function record(Customer $customer, array $data, array $invoiceAllocations, array $saleAllocations): CustomerPayment
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Enter the payment amount.');
        }

        $clean = fn (array $allocs) => collect($allocs)
            ->map(fn ($v) => round((float) $v, 2))
            ->filter(fn ($v) => $v > 0);

        $invoiceAllocs = $clean($invoiceAllocations);
        $saleAllocs    = $clean($saleAllocations);

        $invoices = $this->openInvoicesFor($customer)->keyBy('id');
        $sales    = $this->depositableSalesFor($customer)->keyBy('id');

        foreach ($invoiceAllocs as $id => $amt) {
            $invoice = $invoices->get((int) $id);
            if (! $invoice) {
                throw new \InvalidArgumentException('One of the selected invoices is no longer open for this customer.');
            }
            if ($amt > $invoice->balance_due + 0.005) {
                throw new \InvalidArgumentException('Cannot allocate more than the balance due ($' . number_format($invoice->balance_due, 2) . ") on invoice {$invoice->invoice_number}.");
            }
        }

        foreach ($saleAllocs as $id => $amt) {
            $sale = $sales->get((int) $id);
            if (! $sale) {
                throw new \InvalidArgumentException('One of the selected sales can no longer take a deposit (it may have been invoiced).');
            }
            if ($amt > $sale->balance_due + 0.005) {
                throw new \InvalidArgumentException('Cannot allocate more than the balance owing ($' . number_format($sale->balance_due, 2) . ") on sale #{$sale->sale_number}.");
            }
        }

        $allocated = round($invoiceAllocs->sum() + $saleAllocs->sum(), 2);
        if ($allocated > $amount + 0.005) {
            throw new \InvalidArgumentException('You\'ve allocated $' . number_format($allocated, 2) . ', which is more than the payment of $' . number_format($amount, 2) . '.');
        }
        $leftover = round($amount - $allocated, 2);

        $payment = DB::transaction(function () use ($customer, $data, $amount, $invoiceAllocs, $saleAllocs, $invoices, $sales, $leftover) {
            $payment = CustomerPayment::create([
                'customer_id'      => $customer->id,
                'amount'           => $amount,
                'payment_date'     => $data['payment_date'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
                'recorded_by'      => auth()->id(),
            ]);

            $pieceNote = trim("Part of payment {$payment->payment_number}. " . ($data['notes'] ?? ''));

            foreach ($invoiceAllocs as $id => $amt) {
                $invoice = $invoices->get((int) $id);
                InvoicePayment::create([
                    'invoice_id'          => $invoice->id,
                    'customer_payment_id' => $payment->id,
                    'amount'              => $amt,
                    'payment_date'        => $data['payment_date'],
                    'payment_method'      => $data['payment_method'],
                    'reference_number'    => $data['reference_number'] ?? null,
                    'notes'               => $pieceNote,
                    'recorded_by'         => auth()->id(),
                ]);
                $this->invoiceService->recalculateAfterPayment($invoice);
            }

            foreach ($saleAllocs as $id => $amt) {
                $sale = $sales->get((int) $id);
                SalePayment::create([
                    'sale_id'             => $sale->id,
                    'customer_payment_id' => $payment->id,
                    'payer_customer_id'   => $customer->id,
                    'payer_type'          => match (true) {
                        $sale->opportunity?->job_site_customer_id === $customer->id => 'job_site',
                        $sale->opportunity?->parent_customer_id === $customer->id   => 'parent',
                        default                                                    => null,
                    },
                    'amount'              => $amt,
                    'payment_date'        => $data['payment_date'],
                    'payment_method'      => $data['payment_method'],
                    'reference_number'    => $data['reference_number'] ?? null,
                    'notes'               => $pieceNote,
                    'recorded_by'         => auth()->id(),
                ]);
            }

            if ($leftover > 0.005) {
                CustomerCredit::create([
                    'customer_id'                => $customer->id,
                    'source_customer_payment_id' => $payment->id,
                    'amount'                     => $leftover,
                    'status'                     => 'open',
                    'notes'                      => "Unallocated remainder of payment {$payment->payment_number}"
                        . ($payment->reference_number ? " (ref {$payment->reference_number})" : '') . '.',
                ]);
            }

            return $payment;
        });

        $this->syncToQbo($payment);

        return $payment->fresh();
    }

    /**
     * Delete a payment and every piece of it. Refused once a piece has moved on (a
     * deposit was invoiced, or the leftover credit was used) — undo that first.
     *
     * @throws \InvalidArgumentException
     */
    public function delete(CustomerPayment $payment): void
    {
        if (! $payment->can_be_deleted) {
            throw new \InvalidArgumentException('Part of this payment has already been used — a deposit was invoiced or the leftover credit was applied/refunded — so it can\'t be deleted. Void that invoice or reverse that credit use first.');
        }

        if ($payment->qbo_id) {
            $result = app(QboSyncService::class)->deleteCustomerPaymentInQbo($payment);
            if (! $result['success']) {
                throw new \InvalidArgumentException('Could not delete the payment in QuickBooks: ' . $result['message']);
            }
        }

        DB::transaction(function () use ($payment) {
            $payment->load(['invoicePayments.invoice', 'deposits', 'credit']);

            foreach ($payment->invoicePayments as $ip) {
                $invoice = $ip->invoice;
                $ip->delete();
                if ($invoice) {
                    $this->invoiceService->recalculateAfterPayment($invoice);
                }
            }

            $payment->deposits->each->delete();
            $payment->credit?->delete();
            $payment->delete();
        });
    }

    // ── Keeping QBO in step ─────────────────────────────────────────────────

    /** Push (create or update) to QBO if connected. Never throws. */
    public function syncToQbo(CustomerPayment $payment): ?array
    {
        try {
            if (! app(QuickBooksService::class)->isConnected()) {
                return null;
            }

            return app(QboSyncService::class)->pushCustomerPayment($payment);
        } catch (\Throwable $e) {
            Log::error('[CustomerPayment] QBO sync failed', ['customer_payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** Re-push the given payments (by id) — e.g. after one of their pieces changed. */
    public function resync(iterable $customerPaymentIds): void
    {
        $ids = collect($customerPaymentIds)->filter()->unique();
        if ($ids->isEmpty()) {
            return;
        }

        CustomerPayment::whereIn('id', $ids)->get()->each(fn ($p) => $this->syncToQbo($p));
    }

    /**
     * Re-push every split payment that touches this invoice — its own pieces, plus any
     * leftover credit from a split payment redeemed on it. Called when the invoice is
     * pushed to QBO, voided, or gets deposits/credit applied.
     */
    public function resyncForInvoice(Invoice $invoice): void
    {
        $fromPayments = InvoicePayment::where('invoice_id', $invoice->id)
            ->whereNotNull('customer_payment_id')
            ->pluck('customer_payment_id');

        $fromCredits = DB::table('customer_credit_applications')
            ->join('customer_credits', 'customer_credits.id', '=', 'customer_credit_applications.customer_credit_id')
            ->where('customer_credit_applications.invoice_id', $invoice->id)
            ->whereNotNull('customer_credits.source_customer_payment_id')
            ->pluck('customer_credits.source_customer_payment_id');

        $this->resync($fromPayments->merge($fromCredits));
    }
}
