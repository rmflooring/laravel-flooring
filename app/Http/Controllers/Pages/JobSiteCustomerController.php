<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class JobSiteCustomerController extends Controller
{
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name'          => ['nullable', 'string', 'max:255'],
            'company_name'  => ['nullable', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'mobile'        => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:255'],
            'address2'      => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:100'],
            'province'      => ['nullable', 'string', 'max:100'],
            'postal_code'   => ['nullable', 'string', 'max:20'],
            'notes'         => ['nullable', 'string'],
            'redirect_to'   => ['nullable', 'string'],
        ]);

        $customer->update([
            'name'          => $data['name'] ?? null,
            'company_name'  => $data['company_name'] ?? null,
            'phone'         => $data['phone'] ?? null,
            'mobile'        => $data['mobile'] ?? null,
            'email'         => $data['email'] ?? null,
            'address'       => $data['address'] ?? null,
            'address2'      => $data['address2'] ?? null,
            'city'          => $data['city'] ?? null,
            'province'      => $data['province'] ?? null,
            'postal_code'   => $data['postal_code'] ?? null,
            'notes'         => $data['notes'] ?? null,
        ]);

        $redirectTo = $data['redirect_to'] ?? null;
        if ($redirectTo && str_starts_with($redirectTo, '/')) {
            return redirect($redirectTo)->with('success', 'Job site updated.');
        }

        return redirect()->back()->with('success', 'Job site updated.');
    }

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
