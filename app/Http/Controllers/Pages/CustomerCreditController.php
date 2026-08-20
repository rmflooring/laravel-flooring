<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCredit;
use App\Models\CustomerCreditApplication;
use App\Models\InvoicePayment;
use App\Models\Setting;
use App\Services\QboSyncService;
use Illuminate\Http\Request;

class CustomerCreditController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerCredit::with('customer');

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $credits = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('pages.customer-credits.index', [
            'credits' => $credits,
            'filters' => $request->only('customer_id', 'status'),
        ]);
    }

    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        $credit = CustomerCredit::create([
            'customer_id' => $customer->id,
            'amount'      => round($data['amount'], 2),
            'notes'       => $data['notes'] ?? null,
            'status'      => 'open',
        ]);

        $customerName = $customer->company_name ?? $customer->name;

        return back()->with('success', "Credit {$credit->credit_number} for \${$data['amount']} issued to {$customerName}.");
    }

    public function void(Request $request, CustomerCredit $customerCredit)
    {
        if ($customerCredit->status === 'voided') {
            return back()->with('error', 'This credit is already voided.');
        }

        $request->validate([
            'void_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $customerCredit->update([
            'status'      => 'voided',
            'voided_at'   => now(),
            'void_reason' => $request->input('void_reason'),
        ]);

        return back()->with('success', "Credit {$customerCredit->credit_number} voided.");
    }

    public function pushToQbo(CustomerCredit $customerCredit, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        if ($customerCredit->status === 'voided') {
            return back()->with('error', 'Voided credits cannot be pushed to QuickBooks.');
        }

        $itemId = Setting::get('qbo_income_credit_item_id') ?: Setting::get('qbo_income_material_item_id');

        if (! $itemId) {
            return back()->with('error', 'Missing QBO income item for credits. Visit Settings → QuickBooks Online to set one up.');
        }

        $result = $sync->pushCustomerCredit($customerCredit, $itemId);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    // -------------------------------------------------------------------------
    // Refund
    // -------------------------------------------------------------------------

    public function refund(Request $request, CustomerCredit $customerCredit)
    {
        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'refund_method'    => ['required', 'in:' . implode(',', array_keys(InvoicePayment::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $customerCredit->refund(
                (float) $data['amount'],
                $data['refund_method'],
                $data['reference_number'] ?? null,
                $data['notes'] ?? null
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Refunded \${$data['amount']} from {$customerCredit->credit_number}.");
    }

    public function pushRefundToQbo(CustomerCreditApplication $application, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        if ($application->type !== 'refund') {
            return back()->with('error', 'This is not a refund.');
        }

        $itemId          = Setting::get('qbo_income_credit_item_id') ?: Setting::get('qbo_income_material_item_id');
        $refundAccountId = Setting::get('qbo_refund_account_id');

        if (! $itemId) {
            return back()->with('error', 'Missing QBO income item for credits. Visit Settings → QuickBooks Online to set one up.');
        }
        if (! $refundAccountId) {
            return back()->with('error', 'Missing Refund Account QBO ID. Visit Settings → QuickBooks Online.');
        }

        $result = $sync->pushCustomerCreditRefund($application, $itemId, $refundAccountId);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }
}
