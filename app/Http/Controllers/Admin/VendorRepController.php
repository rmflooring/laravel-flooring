<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorRep;
use Illuminate\Http\Request;

class VendorRepController extends Controller
{
    public function index()
    {
        $reps = VendorRep::with('creator')->paginate(15);

        return view('admin.vendor_reps.index', compact('reps'));
    }

    // start create functions

	public function create()
	{
    return view('admin.vendor_reps.create');
	}
     //end create function

	//start store function
	public function store(Request $request)
	{
    	$request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'email' => 'nullable|email',
        'notes' => 'nullable|string',
   	 ]);

    	VendorRep::create($request->all());

    	return redirect()->route('admin.vendor_reps.index')->with('success', 'Vendor Rep created successfully.');
	}

	// end store function
	
	//start edit function
	
	public function edit(VendorRep $vendorRep)
	{
    	return view('admin.vendor_reps.edit', compact('vendorRep'));
	}

	//end edit function
	
	//start update function
	public function update(Request $request, VendorRep $vendorRep)
	{
    	$request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'email' => 'nullable|email',
        'notes' => 'nullable|string',
   	 ]);

    	$vendorRep->update($request->all());

    	return redirect()->route('admin.vendor_reps.index')->with('success', 'Vendor Rep updated successfully.');
	}
	//end update function

	//start destroy function
	public function destroy(VendorRep $vendorRep)
	{
    	$vendorRep->delete();

    	return redirect()->route('admin.vendor_reps.index')->with('success', 'Vendor Rep deleted successfully.');
	}

	//end destroy function

}
