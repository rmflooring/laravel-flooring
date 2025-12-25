<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitMeasure;
use Illuminate\Http\Request;

class UnitMeasureController extends Controller
{
    public function index()
    {
        $measures = UnitMeasure::with('creator')->paginate(15);

        return view('admin.unit_measures.index', compact('measures'));
    }

    // Start create fuunction

	public function create()
{
    return view('admin.unit_measures.create');
}
	//End  create function
	//start store function
public function store(Request $request)
{
    $request->validate([
        'code' => 'required|string|max:10|unique:unit_measures,code',
        'label' => 'nullable|string|max:255',
        'status' => 'required|in:active,inactive',
    ]);

    UnitMeasure::create($request->all());

    return redirect()->route('admin.unit_measures.index')->with('success', 'Unit Measure created successfully.');
}
	//end store function
	//start edit/update/destroy in the next steps
	public function edit(UnitMeasure $unitMeasure)
{
    return view('admin.unit_measures.edit', compact('unitMeasure'));
}
	//end edit function
	//start update function
public function update(Request $request, UnitMeasure $unitMeasure)
{
    $request->validate([
        'code' => 'required|string|max:10|unique:unit_measures,code,' . $unitMeasure->id,
        'label' => 'nullable|string|max:255',
        'status' => 'required|in:active,inactive',
    ]);

    $unitMeasure->update($request->all());

    return redirect()->route('admin.unit_measures.index')
        ->with('success', 'Unit Measure updated successfully.');
}
	//end update function
	//start destroy function
	public function destroy(UnitMeasure $unitMeasure)
{
    $unitMeasure->delete();

    return redirect()->route('admin.unit_measures.index')
        ->with('success', 'Unit Measure deleted successfully.');
}
	//end destroy function
}
