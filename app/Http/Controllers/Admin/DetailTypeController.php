<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailType;
use Illuminate\Http\Request;

class DetailTypeController extends Controller
{
    public function index()
    {
        $detailTypes = DetailType::with(['accountType', 'creator'])->paginate(15);

        return view('admin.detail_types.index', compact('detailTypes'));
    }

	//start create function
	public function create()
{
    $accountTypes = \App\Models\AccountType::where('status', 'active')->pluck('name', 'id');

    return view('admin.detail_types.create', compact('accountTypes'));
}

public function store(Request $request)
{
    $request->validate([
        'account_type_id' => 'required|exists:account_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);

    DetailType::create($request->all());

    return redirect()->route('admin.detail_types.index')->with('success', 'Detail Type created successfully.');
}
	//end create function
	//Start edit function
	public function edit(DetailType $detailType)
{
    $accountTypes = \App\Models\AccountType::where('status', 'active')->pluck('name', 'id');

    return view('admin.detail_types.edit', compact('detailType', 'accountTypes'));
}
	//End edit function
	//Start update function
public function update(Request $request, DetailType $detailType)
{
    $request->validate([
        'account_type_id' => 'required|exists:account_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);

    $detailType->update($request->all());

    return redirect()->route('admin.detail_types.index')
        ->with('success', 'Detail Type updated successfully.');
}
	//End update function
    	//Start destroy function
	public function destroy(DetailType $detailType)
{
    $detailType->delete();

    return redirect()->route('admin.detail_types.index')
        ->with('success', 'Detail Type deleted successfully.');
}
	//End destroy function
}
