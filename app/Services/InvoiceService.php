<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceRoom;
use App\Models\InvoiceItem;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\SaleRoom;
use Illuminate\Support\Collection;

class InvoiceService
{
    /**
     * Create a new invoice from a sale with a selected subset of sale items.
     *
     * $selectedItems = [
     *   sale_item_id => quantity_to_invoice,
     *   ...
     * ]
     */
    public function createFromSale(Sale $sale, array $invoiceData, array $selectedItems): Invoice
    {
        // Build the invoice header
        $invoice = Invoice::create([
            'sale_id'            => $sale->id,
            'payment_term_id'    => $invoiceData['payment_term_id'] ?? null,
            'status'             => 'draft',
            'due_date'           => $invoiceData['due_date'] ?? null,
            'customer_po_number' => $invoiceData['customer_po_number'] ?? null,
            'notes'              => $invoiceData['notes'] ?? null,
            'subtotal'           => 0,
            'tax_amount'         => 0,
            'grand_total'        => 0,
            'amount_paid'        => 0,
        ]);

        $taxRate = (float) ($sale->tax_rate_percent ?? 0) / 100;

        // Load sale rooms with items, maintaining sort order
        $saleRooms = $sale->rooms()->with(['items' => fn ($q) => $q->orderBy('sort_order')])->get();

        $subtotal = 0;
        $taxTotal = 0;
        $sort     = 0;

        foreach ($saleRooms as $saleRoom) {
            $roomItems = $saleRoom->items->filter(
                fn ($item) => isset($selectedItems[$item->id]) && $selectedItems[$item->id] > 0
            );

            if ($roomItems->isEmpty()) {
                continue;
            }

            $invoiceRoom = InvoiceRoom::create([
                'invoice_id'   => $invoice->id,
                'sale_room_id' => $saleRoom->id,
                'name'         => $saleRoom->room_name ?? '',
                'sort_order'   => $sort++,
            ]);

            $itemSort = 0;
            foreach ($roomItems as $saleItem) {
                $invoiceQty = (float) $selectedItems[$saleItem->id];
                $lineTotal  = round($invoiceQty * (float) $saleItem->sell_price, 2);
                $taxAmount  = round($lineTotal * $taxRate, 2);

                $label = $this->buildLabel($saleItem);

                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'invoice_room_id' => $invoiceRoom->id,
                    'sale_item_id'    => $saleItem->id,
                    'item_type'       => $saleItem->item_type,
                    'label'           => $label,
                    'quantity'        => $invoiceQty,
                    'unit'            => $saleItem->unit ?? null,
                    'sell_price'      => $saleItem->sell_price,
                    'line_total'      => $lineTotal,
                    'tax_rate'        => $sale->tax_rate_percent ?? 0,
                    'tax_amount'      => $taxAmount,
                    'tax_group_id'    => $sale->tax_group_id,
                    'sort_order'      => $itemSort++,
                ]);

                $subtotal += $lineTotal;
            }
        }

        // Compute tax on total subtotal (not sum-of-rounded per-item amounts) to match sale total
        $taxTotal   = round($subtotal * $taxRate, 2);
        $grandTotal = round($subtotal + $taxTotal, 2);

        $invoice->update([
            'subtotal'    => round($subtotal, 2),
            'tax_amount'  => round($taxTotal, 2),
            'grand_total' => $grandTotal,
        ]);

        $this->syncSaleInvoiceStatus($sale);

        // Apply any unallocated sale deposits to this invoice
        $this->applyDepositsToInvoice($invoice, $sale);

        return $invoice->fresh();
    }

    /**
     * Apply any unallocated sale deposits to the given invoice as invoice payments.
     * A deposit is "unallocated" if it has no invoice_payment linked to a non-voided invoice.
     */
    public function applyDepositsToInvoice(Invoice $invoice, Sale $sale): void
    {
        // IDs of deposits already applied to a non-voided invoice for this sale
        $appliedIds = \DB::table('invoice_payments')
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->where('invoices.sale_id', $sale->id)
            ->whereNotIn('invoices.status', ['voided'])
            ->whereNotNull('invoice_payments.sale_payment_id')
            ->pluck('invoice_payments.sale_payment_id')
            ->toArray();

        $pendingDeposits = SalePayment::where('sale_id', $sale->id)
            ->when(! empty($appliedIds), fn ($q) => $q->whereNotIn('id', $appliedIds))
            ->orderBy('payment_date')
            ->get();

        foreach ($pendingDeposits as $deposit) {
            InvoicePayment::create([
                'invoice_id'       => $invoice->id,
                'sale_payment_id'  => $deposit->id,
                'amount'           => $deposit->amount,
                'payment_date'     => $deposit->payment_date,
                'payment_method'   => $deposit->payment_method,
                'reference_number' => $deposit->reference_number,
                'notes'            => $deposit->notes ?: 'Deposit',
                'recorded_by'      => $deposit->recorded_by,
            ]);
        }

        if ($pendingDeposits->isNotEmpty()) {
            $this->recalculateAfterPayment($invoice);
        }
    }

    /**
     * Recalculate invoice totals from its items and update amount_paid from payments.
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $invoice->load(['items', 'payments']);

        $subtotal  = (float) $invoice->items->sum('line_total');
        $taxTotal  = (float) $invoice->items->sum('tax_amount');
        $amountPaid = (float) $invoice->payments->sum('amount');

        $invoice->update([
            'subtotal'    => round($subtotal, 2),
            'tax_amount'  => round($taxTotal, 2),
            'grand_total' => round($subtotal + $taxTotal, 2),
            'amount_paid' => round($amountPaid, 2),
        ]);

        // Derive status from payment state (don't override voided/draft)
        if (! in_array($invoice->status, ['voided', 'draft'])) {
            $this->derivePaymentStatus($invoice);
        }

        $this->syncSaleInvoiceStatus($invoice->sale);
    }

    /**
     * After adding/removing a payment, update amount_paid and derive status.
     */
    public function recalculateAfterPayment(Invoice $invoice): void
    {
        $invoice->load('payments');
        $amountPaid = round((float) $invoice->payments->sum('amount'), 2);

        $invoice->update(['amount_paid' => $amountPaid]);
        $invoice->refresh();

        $this->derivePaymentStatus($invoice);
        $this->syncSaleInvoiceStatus($invoice->sale);
    }

    /**
     * Sync the parent sale's invoiced_total and is_fully_invoiced fields.
     * Also updates sale status to partially_invoiced or invoiced.
     */
    public function syncSaleInvoiceStatus(Sale $sale): void
    {
        $sale->load('invoices');

        $activeInvoices = $sale->invoices->whereNotIn('status', ['voided']);

        $invoicedTotal = round($activeInvoices->sum('grand_total'), 2);
        $lockedTotal   = (float) $sale->locked_grand_total;
        $revisedTotal  = (float) $sale->revised_contract_total;
        $saleTotal     = $lockedTotal > 0 ? $lockedTotal : ($revisedTotal > 0 ? $revisedTotal : (float) $sale->grand_total);

        $isFullyInvoiced = $saleTotal > 0 && $invoicedTotal >= $saleTotal;

        $sale->update([
            'invoiced_total'    => $invoicedTotal,
            'is_fully_invoiced' => $isFullyInvoiced,
        ]);

        // Update sale status — only if currently in an invoiceable state
        $invoiceableStatuses = ['approved', 'scheduled', 'in_progress', 'completed', 'partially_invoiced', 'invoiced'];

        if (in_array($sale->status, $invoiceableStatuses)) {
            if ($invoicedTotal <= 0) {
                // No active invoices — revert to approved if was invoiced/partially invoiced
                if (in_array($sale->status, ['partially_invoiced', 'invoiced'])) {
                    $sale->update(['status' => 'approved']);
                }
            } elseif ($isFullyInvoiced) {
                $sale->update(['status' => 'invoiced']);
            } else {
                $sale->update(['status' => 'partially_invoiced']);
            }
        }
    }

    /**
     * Set invoice status based on payment amount vs total.
     */
    private function derivePaymentStatus(Invoice $invoice): void
    {
        $grand      = (float) $invoice->grand_total;
        $paid       = (float) $invoice->amount_paid;

        if ($paid <= 0) {
            // No payment — check if overdue
            $status = ($invoice->due_date && $invoice->due_date->isPast()) ? 'overdue' : 'sent';
        } elseif ($paid >= $grand) {
            $status = 'paid';
        } else {
            $status = 'partially_paid';
        }

        $invoice->update(['status' => $status]);
    }

    /**
     * Returns how much quantity of a given sale_item_id has already been invoiced
     * across all non-voided invoices for the sale.
     */
    public function getInvoicedQtyBySaleItem(Sale $sale): array
    {
        $results = \DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.sale_id', $sale->id)
            ->whereNotIn('invoices.status', ['voided'])
            ->whereNull('invoices.deleted_at')
            ->whereNotNull('invoice_items.sale_item_id')
            ->selectRaw('invoice_items.sale_item_id, SUM(invoice_items.quantity) as invoiced_qty')
            ->groupBy('invoice_items.sale_item_id')
            ->pluck('invoiced_qty', 'sale_item_id')
            ->toArray();

        return array_map('floatval', $results);
    }

    /**
     * Build a human-readable label for an invoice item from a sale item.
     */
    private function buildLabel($saleItem): string
    {
        if ($saleItem->item_type === 'material') {
            $parts = array_filter([
                $saleItem->product_type,
                $saleItem->manufacturer,
                $saleItem->style,
                $saleItem->color_item_number,
            ]);
            return implode(' — ', $parts) ?: 'Material';
        }

        if ($saleItem->item_type === 'labour') {
            return $saleItem->labour_type ?: 'Labour';
        }

        if ($saleItem->item_type === 'freight') {
            return $saleItem->freight_description ?: 'Freight';
        }

        return $saleItem->label ?? 'Item';
    }
}
