<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;

class OpportunityController extends Controller
{
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
