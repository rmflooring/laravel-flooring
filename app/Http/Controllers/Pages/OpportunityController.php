<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProjectManager;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use App\Models\Employee;


class OpportunityController extends Controller
{
    public function index(Request $request)
{
    $statuses = [
        'New',
        'In Progress',
        'Awaiting Site Measure',
        'Estimate Sent',
        'Approved',
        'Lost',
        'Closed',
    ];

    $query = Opportunity::query()
    ->with(['parentCustomer', 'jobSiteCustomer', 'projectManager']);

// Sorting
$sort = $request->input('sort', 'updated_desc');

switch ($sort) {
    case 'updated_asc':
        $query->orderBy('updated_at', 'asc');
        break;

    case 'job_no_asc':
        $query->orderBy('job_no', 'asc');
        break;

    case 'job_no_desc':
        $query->orderBy('job_no', 'desc');
        break;

    case 'updated_desc':
    default:
        $query->orderBy('updated_at', 'desc');
        break;
}

		// Project Manager filter
if ($request->filled('project_manager_id')) {
    $query->where('project_manager_id', $request->input('project_manager_id'));
}

    // Search (q)
    if ($request->filled('q')) {
        $q = trim($request->input('q'));

        $query->where(function ($sub) use ($q) {
            $sub->where('job_no', 'like', "%{$q}%")
                ->orWhere('sales_person_1', 'like', "%{$q}%")
                ->orWhere('sales_person_2', 'like', "%{$q}%")
                ->orWhereHas('parentCustomer', function ($c) use ($q) {
                    $c->where('company_name', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
                })
                ->orWhereHas('jobSiteCustomer', function ($c) use ($q) {
                    $c->where('company_name', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
                })
                ->orWhereHas('projectManager', function ($pm) use ($q) {
                    $pm->where('name', 'like', "%{$q}%");
                });
        });
    }

    // Status filter
    if ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    // Parent customer filter
    if ($request->filled('parent_customer_id')) {
        $query->where('parent_customer_id', $request->input('parent_customer_id'));
    }

    $opportunities = $query
        ->paginate(15)
        ->withQueryString();

		$projectManagers = ProjectManager::orderBy('name')
    ->get(['id', 'name']);

    // For the parent filter dropdown
    $parentCustomers = Customer::whereNull('parent_id')
        ->orderBy('company_name')
        ->orderBy('name')
        ->get(['id', 'company_name', 'name']);

    return view('pages.opportunities.index', compact('opportunities', 'statuses', 'parentCustomers', 'projectManagers'));
}


    public function create()
{
    // Parent customers (top-level)
    $parentCustomers = Customer::whereNull('parent_id')
        ->orderBy('company_name')
        ->orderBy('name')
        ->get(['id', 'company_name', 'name']);

    // Job sites = child customers
    $jobSiteCustomers = Customer::whereNotNull('parent_id')
        ->orderBy('name')
        ->get(['id', 'parent_id', 'company_name', 'name']);

    // Project Managers
    $projectManagers = ProjectManager::orderBy('name')
        ->get(['id', 'customer_id', 'name']);

    $statuses = [
        'New',
        'In Progress',
        'Awaiting Site Measure',
        'Estimate Sent',
        'Approved',
        'Lost',
        'Closed',
    ];

		// Employees (for Sales Person dropdowns)
$employees = Employee::query()
    ->orderBy('first_name')
    ->get(['id', 'first_name']);

    return view('pages.opportunities.create', compact(
        'parentCustomers',
        'jobSiteCustomers',
        'projectManagers',
        'statuses',
		'employees'
    ));
}

	public function projectManagersForCustomer(Customer $customer)
{
    $pms = ProjectManager::where('customer_id', $customer->id)
        ->orderBy('name')
        ->get(['id', 'name']);

    return response()->json($pms);
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_customer_id'   => ['required', 'exists:customers,id'],
            'job_site_customer_id' => ['nullable', 'exists:customers,id'],
            'project_manager_id'   => ['nullable', 'exists:project_managers,id'],
            'job_no'               => ['nullable', 'string', 'max:255'],
            'status'               => ['required', 'string', 'max:50'],
            'sales_person_1'       => ['nullable', 'string', 'max:255'],
            'sales_person_2'       => ['nullable', 'string', 'max:255'],
        ]);

        $opportunity = Opportunity::create($data);

        return redirect()
            ->route('pages.opportunities.show', $opportunity->id)
            ->with('success', 'Opportunity created.');
    }

	public function show(string $id)
	{
		$opportunity = Opportunity::with([
			'parentCustomer',
			'jobSiteCustomer',
			'projectManager',
			'estimates',
		])->findOrFail($id);

		$salesPeople = Employee::whereIn('id', array_filter([
			$opportunity->sales_person_1,
			$opportunity->sales_person_2,
		]))->get()->keyBy('id');

		return view('pages.opportunities.show', compact('opportunity', 'salesPeople'));
	}

    public function edit(string $id)
{
    $opportunity = Opportunity::findOrFail($id);

    // Parent customers (top-level)
    $parentCustomers = Customer::whereNull('parent_id')
        ->orderBy('company_name')
        ->orderBy('name')
        ->get(['id', 'company_name', 'name']);

    // Job sites = child customers
    $jobSiteCustomers = Customer::whereNotNull('parent_id')
        ->orderBy('name')
        ->get(['id', 'parent_id', 'company_name', 'name']);

    // Project Managers (same as create; JS will filter by parent)
    $projectManagers = ProjectManager::orderBy('name')
        ->get(['id', 'customer_id', 'name']);

    $statuses = [
        'New',
        'In Progress',
        'Awaiting Site Measure',
        'Estimate Sent',
        'Approved',
        'Lost',
        'Closed',
    ];

		$employees = Employee::query()
    ->orderBy('first_name')
    ->get(['id', 'first_name']);

    return view('pages.opportunities.edit', compact(
        'opportunity',
        'parentCustomers',
        'jobSiteCustomers',
        'projectManagers',
         'statuses',
		 'employees'
    ));
}

public function update(Request $request, string $id)
{
    $opportunity = Opportunity::findOrFail($id);

    $data = $request->validate([
        'parent_customer_id'   => ['required', 'exists:customers,id'],
        'job_site_customer_id' => ['nullable', 'exists:customers,id'],
        'project_manager_id'   => ['nullable', 'exists:project_managers,id'],
        'job_no'               => ['nullable', 'string', 'max:255'],
        'status'               => ['required', 'string', 'max:50'],
        'sales_person_1'       => ['nullable', 'string', 'max:255'],
        'sales_person_2'       => ['nullable', 'string', 'max:255'],
    ]);

    $opportunity->update($data);

    return redirect()
        ->route('pages.opportunities.show', $opportunity->id)
        ->with('success', 'Opportunity updated.');
}

    public function destroy(string $id)
    {
        // (Later)
        return redirect()->route('pages.opportunities.index');
    }
}
