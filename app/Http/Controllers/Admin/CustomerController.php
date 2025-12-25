<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        // Load customers with parent and creator info
        $customers = Customer::with(['parent', 'creator'])->paginate(15);

        return view('admin.customers.index', compact('customers'));
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
    ], [
        // Custom message: at least one of name or company_name required
        'required_without_all' => 'Either Name or Company Name must be provided.',
    ]);

    // Custom rule: at least one of name or company_name
    $request->validate([
        'name' => 'required_without:company_name',
        'company_name' => 'required_without:name',
    ]);

    Customer::create($request->all());

    return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
}
//end store method

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

}
