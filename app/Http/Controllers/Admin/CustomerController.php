<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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

$customers = $query->paginate($perPage)->withQueryString();



    // Status filter
    if ($request->filled('status')) {
        $query->where('customer_status', $request->status);
    }

    // Type filter
    if ($request->filled('type')) {
        $query->where('customer_type', $request->type);
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


    $customers = $query->orderBy('company_name')->orderBy('name')
        ->paginate(15)
        ->withQueryString();

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
        'email' => 'nullable|email|unique:customers,email',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'parent_id' => 'nullable|exists:customers,id',
        'province' => 'nullable|string|size:2',
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
        ->latest()
        ->get();

    return view('admin.customers.show', compact(
        'customer',
        'opportunitiesAsParent',
        'opportunitiesAsJobSite',
        'sales'
    ));
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
        'province' => 'nullable|string|size:2',
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

    $customer->update($request->all());

    return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
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

}
