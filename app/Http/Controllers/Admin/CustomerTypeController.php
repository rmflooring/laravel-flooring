<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerType;
use Illuminate\Http\Request;

class CustomerTypeController extends Controller
{
    public function index()
    {
        $types = CustomerType::with('creator')->paginate(15);

        return view('admin.customer_types.index', compact('types'));
    }

    // start create function
public function create()
{
    return view('admin.customer_types.create');
}
	//end create function
	//start store function
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);

    CustomerType::create($request->all());

    return redirect()->route('admin.customer_types.index')->with('success', 'Customer Type created successfully.');
}
	//end store function
	//start edit function
public function edit(CustomerType $customerType)
{
    return view('admin.customer_types.edit', compact('customerType'));
}
	//end edit function
	//start update function
public function update(Request $request, CustomerType $customerType)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);

    $customerType->update($request->all());

    return redirect()->route('admin.customer_types.index')
        ->with('success', 'Customer Type updated successfully.');
}
	//end update function
	//start destroy function
public function destroy(CustomerType $customerType)
{
    $customerType->delete();

    return redirect()->route('admin.customer_types.index')
        ->with('success', 'Customer Type deleted successfully.');
}
	//end destroy function

}
