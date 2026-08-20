<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\QboSyncService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
public function index(Request $request)
{
    $query = Customer::with(['parent', 'creator'])
        ->withCount([
            'opportunitiesAsParent',
            'opportunitiesAsJobSite',
            'children',
        ]);

    // Search
if ($request->filled('search')) {
    $search = trim($request->search);

    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('company_name', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('phone', 'like', "%{$search}%")
          ->orWhere('mobile', 'like', "%{$search}%");
    });
}

	$perPage = (int) $request->get('perPage', 15);
$perPage = in_array($perPage, [15, 25, 50, 100], true) ? $perPage : 15;

    // Status filter
    if ($request->filled('status')) {
        $query->where('customer_status', $request->status);
    }

    // Type filter
    if ($request->filled('type')) {
        $query->where('customer_type', $request->type);
    }

    // Level filter — parent companies (standalone) vs. job-site customers (children)
    if ($request->get('level') === 'parent') {
        $query->whereNull('parent_id');
    } elseif ($request->get('level') === 'job_site') {
        $query->whereNotNull('parent_id');
    }
	
	// Safe sorting (whitelist)
$allowedSorts = [
    'name',
    'company_name',
    'email',
    'city',
    'province',
    'customer_type',
    'customer_status',
    'created_at',
];

$sort = $request->get('sort');
$dir  = strtolower($request->get('dir', 'asc')) === 'desc' ? 'desc' : 'asc';

if ($sort && in_array($sort, $allowedSorts, true)) {
    $query->orderBy($sort, $dir);
} else {
    // Default ordering
    $query->orderBy('company_name')->orderBy('name');
}


    $customers = $query->paginate($perPage)->withQueryString();

    $this->attachBalances($customers);

    $statusOptions = Customer::query()
        ->select('customer_status')
        ->whereNotNull('customer_status')
        ->distinct()
        ->orderBy('customer_status')
        ->pluck('customer_status');

    $typeOptions = Customer::query()
        ->select('customer_type')
        ->whereNotNull('customer_type')
        ->distinct()
        ->orderBy('customer_type')
        ->pluck('customer_type');

    return view('admin.customers.index', compact('customers', 'statusOptions', 'typeOptions'));
}

/**
 * Set a computed_balance attribute on each customer in the collection:
 * the sum of balance_due across all non-voided invoices on sales tied
 * to that customer (as either the parent or the job site customer).
 */
private function attachBalances(iterable $customers): void
{
    $customerIds = collect($customers->items())->pluck('id');

    if ($customerIds->isEmpty()) {
        return;
    }

    $opportunities = \App\Models\Opportunity::query()
        ->whereIn('parent_customer_id', $customerIds)
        ->orWhereIn('job_site_customer_id', $customerIds)
        ->get(['id', 'parent_customer_id', 'job_site_customer_id']);

    $balanceByOpportunity = \App\Models\Sale::whereIn('opportunity_id', $opportunities->pluck('id'))
        ->with(['invoices' => fn ($q) => $q->whereNotIn('status', ['voided'])])
        ->get()
        ->groupBy('opportunity_id')
        ->map(fn ($sales) => $sales->flatMap->invoices->sum(fn ($i) => $i->balance_due));

    $balancesByCustomer = [];
    foreach ($opportunities as $opp) {
        $balance = $balanceByOpportunity->get($opp->id, 0);
        if (! $balance) {
            continue;
        }

        foreach (array_unique(array_filter([$opp->parent_customer_id, $opp->job_site_customer_id])) as $cid) {
            $balancesByCustomer[$cid] = ($balancesByCustomer[$cid] ?? 0) + $balance;
        }
    }

    foreach ($customers as $customer) {
        $customer->computed_balance = $balancesByCustomer[$customer->id] ?? 0;
    }
}


    // We'll add create, store, edit, update, destroy in the next steps
//create method here
public function create()
{
    $parents = Customer::whereNull('parent_id')->pluck('name', 'id');
    $provinces = [
        '' => 'Select Province',
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NL' => 'Newfoundland and Labrador',
        'NS' => 'Nova Scotia',
        'NT' => 'Northwest Territories',
        'NU' => 'Nunavut',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
        'YT' => 'Yukon',
    ];

    return view('admin.customers.create', compact('parents', 'provinces'));
}
//end create method

//store method
public function store(Request $request)
{
    $request->validate([
        'name' => 'nullable|string|max:255',
        'company_name' => 'nullable|string|max:255',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'parent_id' => 'nullable|exists:customers,id',
        'province' => 'nullable|string',
        'postal_code' => 'nullable|string',
        'insurance_company' => 'nullable|string|max:255',
        'adjuster' => 'nullable|string|max:255',
        'policy_number' => 'nullable|string|max:255',
        'claim_number' => 'nullable|string|max:255',
        'dol' => 'nullable|date',
    ], [
        // Custom message: at least one of name or company_name required
        'required_without_all' => 'Either Name or Company Name must be provided.',
    ]);

    // Custom rule: at least one of name or company_name
    $request->validate([
        'name' => 'required_without:company_name',
        'company_name' => 'required_without:name',
    ]);

    $customer = Customer::create($request->only([
        'parent_id',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'address',
        'address2',
        'city',
        'province',
        'postal_code',
        'customer_type',
        'customer_status',
        'notes',
        'insurance_company',
        'adjuster',
        'policy_number',
        'claim_number',
        'dol',
    ]));

    // If a redirect_to URL was provided (ex: from the Job Site modal),
    // send the user there instead of the admin customers index.
    $redirectTo = $request->input('redirect_to');

    if ($redirectTo) {
        $sep = str_contains($redirectTo, '?') ? '&' : '?';
        return redirect($redirectTo . $sep . 'new_js_id=' . $customer->id)
            ->with('success', 'Customer created successfully.');
    }

    return redirect()->route('admin.customers.index')
        ->with('success', 'Customer created successfully.');
}
//end store method


public function show(Customer $customer)
{
    // Opportunities where this customer is the main customer
    $opportunitiesAsParent = $customer->opportunitiesAsParent()
        ->with(['rfms', 'estimates', 'purchaseOrders'])
        ->latest()
        ->get();

    // Opportunities where this customer is the job site
    $opportunitiesAsJobSite = $customer->opportunitiesAsJobSite()
        ->with(['rfms', 'estimates', 'purchaseOrders', 'parentCustomer'])
        ->latest()
        ->get();

    // Collect all opportunity IDs to load sales
    $opportunityIds = $opportunitiesAsParent->pluck('id')
        ->merge($opportunitiesAsJobSite->pluck('id'))
        ->unique();

    $sales = \App\Models\Sale::whereIn('opportunity_id', $opportunityIds)
        ->with(['invoices' => fn ($q) => $q->whereNotIn('status', ['voided'])])
        ->latest()
        ->get();

    $customerBalance = $sales->flatMap->invoices->sum(fn ($invoice) => $invoice->balance_due);

    $invoices = $sales->flatMap(fn ($sale) => $sale->invoices->map(function ($invoice) use ($sale) {
        $invoice->sale_number = $sale->sale_number;
        // sale_number isn't a real invoices column — sync it into "original" so
        // it's never treated as dirty and swept into a later ->update() call.
        $invoice->syncOriginal();
        return $invoice;
    }))->sortByDesc('created_at')->values();

    $openInvoices = $this->openInvoicesFor($customer);

    $customer->load('contacts');

    return view('admin.customers.show', compact(
        'customer',
        'opportunitiesAsParent',
        'opportunitiesAsJobSite',
        'sales',
        'customerBalance',
        'invoices',
        'openInvoices'
    ));
}

/**
 * All non-voided, unpaid invoices across every sale tied to this customer
 * (as either the parent or job site customer), oldest first.
 */
private function openInvoicesFor(Customer $customer): \Illuminate\Support\Collection
{
    $opportunityIds = $customer->opportunitiesAsParent()->pluck('id')
        ->merge($customer->opportunitiesAsJobSite()->pluck('id'))
        ->unique();

    $sales = \App\Models\Sale::whereIn('opportunity_id', $opportunityIds)
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
 * Record a single payment and split it across multiple open invoices
 * belonging to this customer.
 */
public function storeSplitPayment(Request $request, Customer $customer, QboSyncService $sync)
{
    $eligibleInvoices = $this->openInvoicesFor($customer)->keyBy('id');

    $data = $request->validate([
        'amount'           => ['required', 'numeric', 'min:0.01'],
        'payment_date'     => ['required', 'date'],
        'payment_method'   => ['required', 'in:' . implode(',', array_keys(\App\Models\InvoicePayment::PAYMENT_METHODS))],
        'reference_number' => ['nullable', 'string', 'max:100'],
        'notes'            => ['nullable', 'string', 'max:500'],
        'allocations'      => ['required', 'array', 'min:1'],
        'allocations.*'    => ['nullable', 'numeric', 'min:0'],
    ]);

    $allocations = collect($data['allocations'])
        ->map(fn ($v) => round((float) $v, 2))
        ->filter(fn ($v) => $v > 0);

    if ($allocations->isEmpty()) {
        return back()->with('error', 'Allocate the payment to at least one invoice.')->withInput();
    }

    $allocatedTotal = round($allocations->sum(), 2);
    $enteredTotal   = round((float) $data['amount'], 2);

    if (abs($allocatedTotal - $enteredTotal) > 0.01) {
        return back()
            ->with('error', 'Allocated total ($' . number_format($allocatedTotal, 2) . ') must equal the payment amount ($' . number_format($enteredTotal, 2) . ').')
            ->withInput();
    }

    foreach ($allocations as $invoiceId => $amt) {
        $invoice = $eligibleInvoices->get((int) $invoiceId);

        if (! $invoice) {
            return back()->with('error', 'One of the selected invoices is not valid for this customer.')->withInput();
        }

        if ($amt > (float) $invoice->balance_due + 0.01) {
            return back()
                ->with('error', 'Cannot allocate more than the balance due ($' . number_format($invoice->balance_due, 2) . ') on invoice ' . $invoice->invoice_number . '.')
                ->withInput();
        }
    }

    $invoiceService = app(\App\Services\InvoiceService::class);
    $qboConnected   = app(\App\Services\QuickBooksService::class)->isConnected();
    $splitAcrossMany = $allocations->count() > 1;
    $count = 0;

    \Illuminate\Support\Facades\DB::transaction(function () use ($allocations, $eligibleInvoices, $data, $invoiceService, $qboConnected, $sync, $splitAcrossMany, &$count) {
        foreach ($allocations as $invoiceId => $amt) {
            $invoice = $eligibleInvoices->get((int) $invoiceId);

            $notes = trim($data['notes'] ?? '');
            if ($splitAcrossMany) {
                $notes = trim($notes . ' (split payment across ' . $allocations->count() . ' invoices)');
            }

            $payment = \App\Models\InvoicePayment::create([
                'invoice_id'       => $invoice->id,
                'amount'           => $amt,
                'payment_date'     => $data['payment_date'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $notes ?: null,
                'recorded_by'      => auth()->id(),
            ]);

            $invoiceService->recalculateAfterPayment($invoice);

            if ($qboConnected && $invoice->qbo_id) {
                $sync->pushPayment($payment);
            }

            $count++;
        }
    });

    return redirect()->route('admin.customers.show', $customer)
        ->with('success', 'Payment of $' . number_format($enteredTotal, 2) . ' recorded across ' . $count . ' invoice(s).');
}

//add edit method
public function edit(Customer $customer)
{
    $parents = Customer::whereNull('parent_id')->orWhere('id', '!=', $customer->id)->pluck('name', 'id');
    $provinces = [
        '' => 'Select Province',
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NL' => 'Newfoundland and Labrador',
        'NS' => 'Nova Scotia',
        'NT' => 'Northwest Territories',
        'NU' => 'Nunavut',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
        'YT' => 'Yukon',
    ];

    $customer->load('contacts');

    return view('admin.customers.edit', compact('customer', 'parents', 'provinces'));
}

public function update(Request $request, Customer $customer)
{
    $request->validate([
        'name' => 'nullable|string|max:255',
        'company_name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:customers,email,' . $customer->id,
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'parent_id' => 'nullable|exists:customers,id',
        'province' => 'nullable|string',
        'postal_code' => 'nullable|string',
        'insurance_company' => 'nullable|string|max:255',
        'adjuster' => 'nullable|string|max:255',
        'policy_number' => 'nullable|string|max:255',
        'claim_number' => 'nullable|string|max:255',
        'dol' => 'nullable|date',
    ]);

    // At least one of name or company_name
    $request->validate([
        'name' => 'required_without:company_name',
        'company_name' => 'required_without:name',
    ]);

    $customer->update($request->only([
        'parent_id',
        'name',
        'company_name',
        'email',
        'phone',
        'mobile',
        'address',
        'address2',
        'city',
        'province',
        'postal_code',
        'customer_type',
        'customer_status',
        'notes',
        'insurance_company',
        'adjuster',
        'policy_number',
        'claim_number',
        'dol',
    ]));

    return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer updated successfully.');
}
//end edit method

public function destroy(Customer $customer)
{
    $hasOpportunities = $customer->opportunitiesAsParent()->exists()
        || $customer->opportunitiesAsJobSite()->exists();

    $hasChildren = $customer->children()->exists();

    if ($hasOpportunities || $hasChildren) {
        $reason = $hasOpportunities
            ? 'This customer has opportunities linked to them and cannot be deleted.'
            : 'This customer has child customers linked to them and cannot be deleted.';

        return redirect()->route('admin.customers.index')
            ->with('error', $reason . ' You can deactivate them instead.');
    }

    $customer->delete();

    return redirect()->route('admin.customers.index')
        ->with('success', 'Customer deleted successfully.');
}

public function deactivate(Customer $customer)
{
    $customer->update(['customer_status' => 'inactive']);

    return redirect()->route('admin.customers.index')
        ->with('success', 'Customer has been marked as inactive.');
}

public function toggleSmsOptOut(Customer $customer)
{
    if ($customer->sms_opted_out) {
        $customer->update(['sms_opted_out' => false, 'sms_opted_out_at' => null]);
        $message = 'SMS notifications re-enabled for this customer.';
    } else {
        $customer->update(['sms_opted_out' => true, 'sms_opted_out_at' => now()]);
        $message = 'Customer marked as SMS opted out.';
    }

    return back()->with('success', $message);
}

public function pushToQbo(Customer $customer, QboSyncService $sync)
{
    if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
        return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
    }

    $result = $sync->pushCustomer($customer);

    return back()->with(
        $result['success'] ? 'success' : 'error',
        $result['message']
    );
}

}
