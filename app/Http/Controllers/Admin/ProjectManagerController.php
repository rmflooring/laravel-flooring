<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectManager;
use Illuminate\Http\Request;

class ProjectManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectManager::with(['customer', 'creator']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $sortable = ['name', 'email'];
        $sort = in_array($request->input('sort'), $sortable) ? $request->input('sort') : 'name';
        $dir  = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $dir);

        $perPage = in_array((int) $request->input('perPage'), [15, 25, 50, 100])
            ? (int) $request->input('perPage')
            : 15;

        $pms = $query->paginate($perPage)->withQueryString();

        return view('admin.project_managers.index', compact('pms'));
    }

    // createfunction start 
	public function create()
{
    // Only top-level customers (no parent) for the dropdown
    $customers = \App\Models\Customer::whereNull('parent_id')->pluck('name', 'id');

    return view('admin.project_managers.create', compact('customers'));
}
	//end create funtion
	//store function start
	public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'customer_id' => 'required|exists:customers,id',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'email' => 'nullable|email',
        'notes' => 'nullable|string',
    ]);

    ProjectManager::create($request->all());

    return redirect()->route('admin.project_managers.index')->with('success', 'Project Manager created successfully.');
}

     //end store function

	//edit function start
	public function edit(ProjectManager $projectManager)
{
    $customers = \App\Models\Customer::whereNull('parent_id')->pluck('name', 'id');

    return view('admin.project_managers.edit', compact('projectManager', 'customers'));
}
	//end edit function

	//start update function
public function update(Request $request, ProjectManager $projectManager)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'customer_id' => 'required|exists:customers,id',
        'phone' => 'nullable|string',
        'mobile' => 'nullable|string',
        'email' => 'nullable|email',
        'notes' => 'nullable|string',
    ]);

    $projectManager->update($request->all());

    return redirect()->route('admin.project_managers.index')
        ->with('success', 'Project Manager updated successfully.');
}

	//end update function
	//start destroy function
	public function destroy(ProjectManager $projectManager)
{
    $projectManager->delete();

    return redirect()->route('admin.project_managers.index')
        ->with('success', 'Project Manager deleted successfully.');
}
	//end destroy function
}
