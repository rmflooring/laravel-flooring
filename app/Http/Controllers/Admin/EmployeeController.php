<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeRole;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
{
    $q = Employee::query()->with(['department', 'role']);

    // Default: hide archived unless explicitly shown (or unless status filter is used)
    if (!$request->boolean('show_archived') && !$request->filled('status')) {
        $q->where('status', '!=', 'archived');
    }

    if ($request->filled('search')) {
        $s = $request->string('search')->toString();
        $q->where(function ($sub) use ($s) {
            $sub->where('employee_number', 'like', "%{$s}%")
                ->orWhere('first_name', 'like', "%{$s}%")
                ->orWhere('last_name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%");
        });
    }

    if ($request->filled('status')) {
        $q->where('status', $request->string('status')->toString());
    }

    if ($request->filled('department_id')) {
        $q->where('department_id', (int) $request->input('department_id'));
    }

    if ($request->filled('employee_role_id')) {
        $q->where('employee_role_id', (int) $request->input('employee_role_id'));
    }

    $employees = $q->orderBy('last_name')
        ->orderBy('first_name')
        ->paginate(20)
        ->withQueryString();

    return view('admin.employees.index', [
        'employees'   => $employees,
        'departments' => Department::where('is_active', true)->orderBy('name')->get(),
        'roles'       => EmployeeRole::where('is_active', true)->orderBy('name')->get(),
        'filters'     => $request->only(['search', 'status', 'department_id', 'employee_role_id', 'show_archived']),
    ]);
}


    public function create()
    {
        return view('admin.employees.create', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'roles'       => EmployeeRole::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEmployee($request);

        $employee = new Employee($data);

        // Encrypt SIN via virtual attribute in the Employee model
        if ($request->filled('sin')) {
            $employee->sin_plain = $request->input('sin');
        }

        $employee->created_by = auth()->id();
        $employee->updated_by = auth()->id();
        $employee->save();

        return redirect()
    		->route('admin.employees.index')
			->with('success', 'Employee created successfully.');
			}

    public function show(Employee $employee)
    {
        $employee->load(['department', 'role']);
        return view('admin.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $employee->load(['department', 'role']);

        return view('admin.employees.edit', [
            'employee'    => $employee,
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'roles'       => EmployeeRole::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateEmployee($request, $employee->id);

        $employee->fill($data);

        if ($request->filled('sin')) {
            $employee->sin_plain = $request->input('sin');
        }

        $employee->updated_by = auth()->id();
        $employee->save();

        return back()->with('success', 'Employee updated.');
    }

    // No hard delete — archive via status for now
    public function destroy(Employee $employee)
    {
        $employee->status = 'archived';
        $employee->updated_by = auth()->id();
        $employee->save();

        return back()->with('success', 'Employee archived.');
    }

	public function restore(Employee $employee)
	{
    $employee->status = 'active';
    $employee->updated_by = auth()->id();
    $employee->save();

    return back()->with('success', 'Employee restored successfully.');
	}

	
    private function validateEmployee(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'employee_number' => ['required', 'string', 'max:255', 'unique:employees,employee_number,' . ($ignoreId ?? 'NULL') . ',id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],

            'date_of_birth' => ['nullable', 'date'],

            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:30'],

            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:255'],

            'hire_date' => ['nullable', 'date'],
            'job_title' => ['nullable', 'string', 'max:255'],

            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'employee_role_id' => ['nullable', 'integer', 'exists:employee_roles,id'],
            'role_other' => ['nullable', 'string', 'max:255'],

            'status' => ['required', 'in:active,inactive,terminated,on_leave,archived'],
            'notes' => ['nullable', 'string'],

            // SIN handled separately via $employee->sin_plain
        ]);
    }
}
