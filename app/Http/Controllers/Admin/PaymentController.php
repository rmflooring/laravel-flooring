<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->input('q', '');
        $method   = $request->input('method', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');
        $sort     = in_array($request->input('sort'), ['payment_date', 'amount', 'payment_method', 'created_at']) ? $request->input('sort') : 'payment_date';
        $dir      = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $query = InvoicePayment::query()
            ->with(['invoice.sale', 'recordedBy']);

        if ($q) {
            $search = $q;
            $query->where(function ($query) use ($search) {
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
            $query->where('payment_method', $method);
        }

        if ($dateFrom) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        $payments = $query->orderBy($sort, $dir)->paginate(30)->withQueryString();

        $paymentMethods = InvoicePayment::PAYMENT_METHODS;

        // Summary totals for filtered result set (without pagination)
        $totalAmount = $query->sum('amount');

        return view('admin.payments.index', compact(
            'payments', 'paymentMethods', 'q', 'method', 'dateFrom', 'dateTo', 'sort', 'dir', 'totalAmount'
        ));
    }

    public function show(InvoicePayment $payment)
    {
        $payment->load(['invoice.sale.opportunity.customer', 'invoice.paymentTerm', 'recordedBy']);

        return view('admin.payments.show', compact('payment'));
    }
}
