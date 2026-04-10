<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $query = Opportunity::with(['parentCustomer', 'jobSiteCustomer', 'projectManager']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('job_no', 'like', "%{$search}%")
                  ->orWhereHas('parentCustomer', fn ($q) =>
                      $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                  )
                  ->orWhereHas('jobSiteCustomer', fn ($q) =>
                      $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                  );
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $opportunities = $query->orderByDesc('updated_at')->paginate(25)->withQueryString();

        return view('mobile.opportunities.index', compact('opportunities', 'search', 'status'));
    }

    public function show(Opportunity $opportunity)
    {
        $opportunity->load([
            'parentCustomer',
            'jobSiteCustomer',
            'projectManager',
            'rfms.estimator',
            'estimates',
            'sales',
            'purchaseOrders.vendor',
        ]);

        return view('mobile.opportunities.show', compact('opportunity'));
    }
}
