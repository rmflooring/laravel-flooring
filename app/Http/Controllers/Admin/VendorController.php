<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Models\VendorRep;

class VendorController extends Controller
{
    public function index()
{
    $vendors = Vendor::with(['creator', 'reps'])->paginate(15);

    return view('admin.vendors.index', compact('vendors'));
}

    // create function starts
	public function create()
{
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

    $vendorTypes = [
        '' => 'Select Type',
        'Flooring Supplier' => 'Flooring Supplier',
        'Tile Distributor' => 'Tile Distributor',
        'Tools' => 'Tools',
        'Subcontractor' => 'Subcontractor',
        'Other' => 'Other',
    ];
	$reps = VendorRep::pluck('name', 'id');

    return view('admin.vendors.create', compact('provinces', 'vendorTypes', 'reps'));
}

public function store(Request $request)
{
    $request->validate([
        'company_name' => 'required|string|max:255',
        'contact_name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:vendors,email',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'province' => 'nullable|string|size:2',
        'website' => 'nullable|url',
        'reps' => 'array', // optional array of rep IDs
        'reps.*' => 'exists:vendor_reps,id',
    ]);

    $vendor = Vendor::create($request->all());

    // Sync the selected reps (empty array if none selected)
    $vendor->reps()->sync($request->reps ?? []);

    return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
}
	///start edit function 
public function edit(Vendor $vendor)
{
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

    $vendorTypes = [
        '' => 'Select Type',
        'Flooring Supplier' => 'Flooring Supplier',
        'Tile Distributor' => 'Tile Distributor',
        'Tools' => 'Tools',
        'Subcontractor' => 'Subcontractor',
        'Other' => 'Other',
    ];

    $reps = VendorRep::pluck('name', 'id');
    $selectedReps = $vendor->reps->pluck('id')->toArray();

    return view('admin.vendors.edit', compact('vendor', 'provinces', 'vendorTypes', 'reps', 'selectedReps'));
}

//end edit function

//start update funtion 

public function update(Request $request, Vendor $vendor)
{
    $request->validate([
        'company_name' => 'required|string|max:255',
        'contact_name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:vendors,email,' . $vendor->id,
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'province' => 'nullable|string|size:2',
        'website' => 'nullable|url',
        'reps' => 'array',
        'reps.*' => 'exists:vendor_reps,id',
    ]);

    $vendor->update($request->all());

    // Sync the selected reps
    $vendor->reps()->sync($request->reps ?? []);

    return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
}

///end update function
//start destroy function 

public function destroy(Vendor $vendor)
{
    $vendor->delete();

    return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
}

//end destroy function

}
