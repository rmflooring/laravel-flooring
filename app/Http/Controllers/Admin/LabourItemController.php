<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitMeasure;
use App\Models\LabourType;
use App\Models\LabourItem;
use Illuminate\Http\Request;

class LabourItemController extends Controller
{
   
    public function create()
    {
    $unitMeasures = UnitMeasure::all(['id', 'label']); // from previous fix
    $labourTypes = LabourType::all(['id', 'name']); // Add this line (adjust 'name' to your actual column if different)

    return view('admin.labour_items.create', compact('unitMeasures', 'labourTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'sell' => 'required|numeric|min:0',
            'unit_measure_id' => 'required|exists:unit_measures,id',
            'status' => 'required|in:Active,Inactive,Needs Update',
        ]);

        LabourItem::create($validated);

        return redirect()->route('admin.labour_items.index')->with('success', 'Labour Item created!');
    }

    //Start index function
    public function index()
{
    $labourItems = LabourItem::with(['unitMeasure', 'labourType'])->get(); // Load related unit and type

    return view('admin.labour_items.index', compact('labourItems'));
}
    //End Index function
    //Start edit function
public function edit(LabourItem $labourItem)
{
    $unitMeasures = UnitMeasure::all(['id', 'label']);
    $labourTypes = LabourType::all(['id', 'name']);

    return view('admin.labour_items.edit', compact('labourItem', 'unitMeasures', 'labourTypes'));
}

public function update(Request $request, LabourItem $labourItem)
{
    $validated = $request->validate([
        'description' => 'required|string|max:255',
        'notes' => 'nullable|string',
        'cost' => 'required|numeric|min:0',
        'sell' => 'required|numeric|min:0',
        'unit_measure_id' => 'required|exists:unit_measures,id',
        'status' => 'required|in:Active,Inactive,Needs Update',
        'labour_type_id' => 'required|exists:labour_types,id',
    ]);

    $labourItem->update($validated);

    return redirect()->route('admin.labour_items.index')->with('success', 'Labour Item updated successfully!');
}
    //End edit function

}
