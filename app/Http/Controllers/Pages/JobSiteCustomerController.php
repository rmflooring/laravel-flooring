<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class JobSiteCustomerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id'   => ['required', 'integer', 'exists:customers,id'],
            'name'        => ['nullable', 'string', 'max:255'],
            'company_name'=> ['nullable', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'string', 'max:255'],
            'address'     => ['nullable', 'string', 'max:255'],
        ]);

        // Job site = child customer
        $jobSite = Customer::create([
            'parent_id'    => $data['parent_id'],
            'name'         => $data['name'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'phone'        => $data['phone'] ?? null,
            'email'        => $data['email'] ?? null,
            'address'      => $data['address'] ?? null,
        ]);

		if (!$jobSite->exists) {
    return redirect()
        ->route('pages.opportunities.create')
        ->with('error', 'Job site was NOT saved (Customer model blocked the insert). Check Customer model events.');
}

        // Send them back to Create Opportunity with the new job site pre-selected
        return redirect()
            ->route('pages.opportunities.create')
            ->with('job_site_created_id', $jobSite->id)
            ->with('job_site_created_parent_id', $jobSite->parent_id)
            ->with('success', 'Job site created.');
    }
}
