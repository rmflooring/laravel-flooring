<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerPayment;
use App\Services\CustomerPaymentService;
use App\Services\QuickBooksService;

class CustomerPaymentController extends Controller
{
    public function show(CustomerPayment $customerPayment)
    {
        $customerPayment->load([
            'customer',
            'recordedBy',
            'invoicePayments.invoice.sale',
            'deposits.sale',
            'deposits.invoicePayments.invoice',
            'credit.applications.invoice',
            'credit.applications.sale',
        ]);

        return view('admin.customer-payments.show', [
            'payment'      => $customerPayment,
            'qboConnected' => app(QuickBooksService::class)->isConnected(),
        ]);
    }

    public function pushToQbo(CustomerPayment $customerPayment, CustomerPaymentService $service)
    {
        $result = $service->syncToQbo($customerPayment);

        if ($result === null) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroy(CustomerPayment $customerPayment, CustomerPaymentService $service)
    {
        $customer = $customerPayment->customer;
        $number   = $customerPayment->payment_number;

        try {
            $service->delete($customerPayment);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', "Payment {$number} and all of its allocations were deleted.");
    }
}
