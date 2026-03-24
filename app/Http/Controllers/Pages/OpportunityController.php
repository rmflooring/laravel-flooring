<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProjectManager;
use App\Models\Opportunity;
use App\Models\Sale;
use Illuminate\Http\Request;
use App\Models\Employee;


class OpportunityController extends Controller
{
    private function applyOpportunityFilters($query, Request $request): void
    {
        $sort = $request->input('sort', 'updated_desc');
        switch ($sort) {
            case 'updated_asc': $query->orderBy('updated_at', 'asc'); break;
            case 'job_no_asc':  $query->orderBy('job_no', 'asc'); break;
            case 'job_no_desc': $query->orderBy('job_no', 'desc'); break;
            default:            $query->orderBy('updated_at', 'desc'); break;
        }

        if ($request->filled('project_manager_id')) {
            $query->where('project_manager_id', $request->input('project_manager_id'));
        }

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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('parent_customer_id')) {
            $query->where('parent_customer_id', $request->input('parent_customer_id'));
        }
    }

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
        ->with(['parentCustomer', 'jobSiteCustomer', 'projectManager'])
        ->withCount(['rfms', 'estimates', 'sales', 'purchaseOrders']);

    $this->applyOpportunityFilters($query, $request);

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
        ->get(['id', 'company_name', 'name', 'email', 'phone', 'mobile', 'address', 'address2', 'city', 'province', 'postal_code', 'customer_type']);

    // Job sites = child customers
    $jobSiteCustomers = Customer::whereNotNull('parent_id')
        ->orderBy('name')
        ->get(['id', 'parent_id', 'company_name', 'name', 'email', 'phone', 'mobile', 'address', 'address2', 'city', 'province', 'postal_code', 'notes', 'insurance_company', 'adjuster', 'policy_number', 'claim_number', 'dol']);

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

		public function show(Request $request, string $id)
		{
			$opportunity = Opportunity::with([
				'parentCustomer',
				'jobSiteCustomer',
				'projectManager',
				'estimates',
				'rfms.estimator',
			])->findOrFail($id);

			$salesPeople = Employee::whereIn('id', array_filter([
				$opportunity->sales_person_1,
				$opportunity->sales_person_2,
			]))->get()->keyBy('id');

			// Sales for this opportunity
			$sales = Sale::where('opportunity_id', $opportunity->id)
				->latest('updated_at')
				->get();

			// POs for this opportunity
			$purchaseOrders = \App\Models\PurchaseOrder::where('opportunity_id', $opportunity->id)
				->with(['vendor', 'sale'])
				->orderByDesc('created_at')
				->get();

			// Prev / next navigation within the current filter context
			$filterParams = $request->only(['q', 'status', 'parent_customer_id', 'project_manager_id', 'sort']);
			$navQuery = Opportunity::query()->select('id');
			$this->applyOpportunityFilters($navQuery, $request);
			$ids = $navQuery->pluck('id')->toArray();
			$pos = array_search((int) $id, $ids);

			$prev = ($pos !== false && $pos > 0)
				? Opportunity::find($ids[$pos - 1], ['id', 'job_no'])
				: null;
			$next = ($pos !== false && $pos < count($ids) - 1)
				? Opportunity::find($ids[$pos + 1], ['id', 'job_no'])
				: null;

			$navPosition = $pos !== false ? $pos + 1 : null;
			$navTotal    = count($ids);

			$backUrl = route('pages.opportunities.index', array_filter($filterParams));

			return view('pages.opportunities.show', compact('opportunity', 'salesPeople', 'sales', 'purchaseOrders', 'prev', 'next', 'backUrl', 'filterParams', 'navPosition', 'navTotal'));
		}


    public function edit(string $id)
{
    $opportunity = Opportunity::findOrFail($id);

    // Parent customers (top-level)
    $parentCustomers = Customer::whereNull('parent_id')
        ->orderBy('company_name')
        ->orderBy('name')
        ->get(['id', 'company_name', 'name', 'email', 'phone', 'mobile', 'address', 'address2', 'city', 'province', 'postal_code', 'customer_type']);

    // Job sites = child customers
    $jobSiteCustomers = Customer::whereNotNull('parent_id')
        ->orderBy('name')
        ->get(['id', 'parent_id', 'company_name', 'name', 'email', 'phone', 'mobile', 'address', 'address2', 'city', 'province', 'postal_code', 'notes', 'insurance_company', 'adjuster', 'policy_number', 'claim_number', 'dol']);

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
        $opportunity = Opportunity::findOrFail($id);

        $hasActivity = $opportunity->rfms()->exists()
            || $opportunity->estimates()->exists()
            || $opportunity->sales()->exists()
            || $opportunity->purchaseOrders()->exists();

        if ($hasActivity) {
            return redirect()->route('pages.opportunities.index')
                ->with('error', 'This opportunity has linked activity (RFMs, estimates, sales, or POs) and cannot be deleted. You can deactivate it instead.');
        }

        $opportunity->delete();

        return redirect()->route('pages.opportunities.index')
            ->with('success', 'Opportunity deleted successfully.');
    }

    public function deactivate(Opportunity $opportunity)
    {
        $opportunity->update(['is_active' => false]);

        return redirect()->route('pages.opportunities.index')
            ->with('success', 'Opportunity has been deactivated.');
    }
}
