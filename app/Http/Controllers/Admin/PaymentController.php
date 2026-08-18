<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use App\Models\SalePayment;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->input('q', '');
        $method   = $request->input('method', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $type     = $request->input('type', '');
        $sort     = in_array($request->input('sort'), ['payment_date', 'amount', 'payment_method', 'created_at']) ? $request->input('sort') : 'payment_date';
        $dir      = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $invoicePayments = collect();
        $deposits        = collect();

        if ($type !== 'deposit') {
            $invoiceQuery = InvoicePayment::query()->with(['invoice.sale', 'recordedBy']);

            if ($q) {
                $search = $q;
                $invoiceQuery->where(function ($query) use ($search) {
                    $query->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"))
                        ->orWhereHas('invoice.sale', fn ($q) =>
                            $q->where('sale_number', 'like', "%{$search}%")
                              ->orWhere('job_name', 'like', "%{$search}%")
                              ->orWhere('homeowner_name', 'like', "%{$search}%")
                        );
                });
            }

            if ($method) {
                $invoiceQuery->where('payment_method', $method);
            }

            if ($dateFrom) {
                $invoiceQuery->whereDate('payment_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $invoiceQuery->whereDate('payment_date', '<=', $dateTo);
            }

            $invoicePayments = $invoiceQuery->get()->each(function ($payment) {
                $payment->payment_type = 'invoice_payment';
            });
        }

        if ($type !== 'invoice_payment') {
            $depositQuery = SalePayment::query()->with(['sale', 'payerCustomer', 'recordedBy']);

            if ($q) {
                $search = $q;
                $depositQuery->where(function ($query) use ($search) {
                    $query->where('reference_number', 'like', "%{$search}%")
                        ->orWhereHas('sale', fn ($q) =>
                            $q->where('sale_number', 'like', "%{$search}%")
                              ->orWhere('job_name', 'like', "%{$search}%")
                              ->orWhere('homeowner_name', 'like', "%{$search}%")
                        )
                        ->orWhereHas('payerCustomer', fn ($q) =>
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('company_name', 'like', "%{$search}%")
                        );
                });
            }

            if ($method) {
                $depositQuery->where('payment_method', $method);
            }

            if ($dateFrom) {
                $depositQuery->whereDate('payment_date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $depositQuery->whereDate('payment_date', '<=', $dateTo);
            }

            $deposits = $depositQuery->get()->each(function ($payment) {
                $payment->payment_type = 'deposit';
            });
        }

        $sortKey = $sort === 'amount'
            ? fn ($payment) => (float) $payment->amount
            : fn ($payment) => $payment->{$sort};

        $merged = $invoicePayments->concat($deposits);
        $merged = $dir === 'asc' ? $merged->sortBy($sortKey)->values() : $merged->sortByDesc($sortKey)->values();

        $totalAmount = $merged->sum('amount');

        $perPage  = 30;
        $page     = (int) $request->input('page', 1);
        $payments = new LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $paymentMethods = InvoicePayment::PAYMENT_METHODS;

        return view('admin.payments.index', compact(
            'payments', 'paymentMethods', 'q', 'method', 'dateFrom', 'dateTo', 'type', 'sort', 'dir', 'totalAmount'
        ));
    }

    public function show(InvoicePayment $payment)
    {
        $payment->load(['invoice.sale.opportunity.customer', 'invoice.paymentTerm', 'recordedBy']);

        return view('admin.payments.show', compact('payment'));
    }

    public function edit(InvoicePayment $payment)
    {
        if ($payment->qbo_id) {
            return redirect()->route('admin.payments.show', $payment)
                ->with('error', 'This payment has been pushed to QuickBooks Online and can no longer be edited.');
        }

        $payment->load(['invoice.sale', 'recordedBy']);

        $paymentMethods = InvoicePayment::PAYMENT_METHODS;

        return view('admin.payments.edit', compact('payment', 'paymentMethods'));
    }

    public function update(Request $request, InvoicePayment $payment, InvoiceService $service)
    {
        if ($payment->qbo_id) {
            return redirect()->route('admin.payments.show', $payment)
                ->with('error', 'This payment has been pushed to QuickBooks Online and can no longer be edited.');
        }

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'payment_method'   => ['required', 'in:' . implode(',', array_keys(InvoicePayment::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $payment->update($data);

        $service->recalculateAfterPayment($payment->invoice);

        return redirect()->route('admin.payments.show', $payment)
            ->with('success', 'Payment updated.');
    }

    public function depositShow(SalePayment $deposit)
    {
        $deposit->load(['sale.opportunity.customer', 'payerCustomer', 'recordedBy']);

        return view('admin.payments.deposits.show', compact('deposit'));
    }

    public function depositEdit(SalePayment $deposit)
    {
        if ($deposit->is_applied) {
            return redirect()->route('admin.payments.deposits.show', $deposit)
                ->with('error', 'This deposit has been applied to an invoice and can no longer be edited.');
        }

        $deposit->load(['sale', 'recordedBy']);

        $paymentMethods = SalePayment::PAYMENT_METHODS;

        return view('admin.payments.deposits.edit', compact('deposit', 'paymentMethods'));
    }

    public function depositUpdate(Request $request, SalePayment $deposit)
    {
        if ($deposit->is_applied) {
            return redirect()->route('admin.payments.deposits.show', $deposit)
                ->with('error', 'This deposit has been applied to an invoice and can no longer be edited.');
        }

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'payment_method'   => ['required', 'in:' . implode(',', array_keys(SalePayment::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $deposit->update($data);

        return redirect()->route('admin.payments.deposits.show', $deposit)
            ->with('success', 'Deposit updated.');
    }
}
