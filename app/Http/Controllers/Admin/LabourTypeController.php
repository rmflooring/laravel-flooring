<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourType;
use Illuminate\Http\Request;

class LabourTypeController extends Controller
{
    public function index()
    {
        $types = LabourType::with('creator')->paginate(15);

        return view('admin.labour_types.index', compact('types'));
    }

    // Start Create function 
	public function create()
{
    return view('admin.labour_types.create');
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'notes' => 'nullable|string',
    ]);

    LabourType::create($request->all());

    return redirect()->route('admin.labour_types.index')->with('success', 'Labour Type created successfully.');
}
	//end create function
	//start edit function 
	public function edit(LabourType $labourType)
{
    return view('admin.labour_types.edit', compact('labourType'));
}
	//end edit funciton
	//start update funciton
public function update(Request $request, LabourType $labourType)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'notes' => 'nullable|string',
    ]);

    $labourType->update($request->all());

    return redirect()->route('admin.labour_types.index')
        ->with('success', 'Labour Type updated successfully.');
}
	//end update function
	//start destroy functioin
	public function destroy(LabourType $labourType)
{
    $labourType->delete();

    return redirect()->route('admin.labour_types.index')
        ->with('success', 'Labour Type deleted successfully.');
}
	//end destroy function

}
