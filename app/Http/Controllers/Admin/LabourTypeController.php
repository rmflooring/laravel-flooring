<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabourType;
use Illuminate\Http\Request;
use App\Models\User;

class LabourTypeController extends Controller
{
    public function index(Request $request)
		{
			$query = LabourType::query()->with(['creator']);

			// Search
			if ($request->filled('search')) {
				$search = trim((string) $request->search);

				$query->where(function ($q) use ($search) {
					$q->where('name', 'like', "%{$search}%")
					  ->orWhere('notes', 'like', "%{$search}%")
					  ->orWhereHas('creator', function ($u) use ($search) {
						  $u->where('name', 'like', "%{$search}%")
							->orWhere('email', 'like', "%{$search}%");
					  });
				});
			}

			// Status filter (optional — only if your labour_types table has a status column)
			if ($request->filled('status')) {
				$query->where('status', $request->status);
			}

			// Created by filter (optional)
			if ($request->filled('created_by')) {
				$query->where('created_by', $request->created_by);
			}

			// Per page
			$perPage = (int) $request->get('per_page', 15);
			if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
				$perPage = 15;
			}

			$types = $query
				->orderBy('name')
				->paginate($perPage)
				->withQueryString();

			// Only needed if you enable the "Created By" dropdown
			$creators = User::orderBy('name')->get(['id', 'name']);

			return view('admin.labour_types.index', compact('types', 'creators', 'perPage'));
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
