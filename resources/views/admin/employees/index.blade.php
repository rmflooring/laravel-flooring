<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
			@if (session('success'))
    <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between">
        <div>{{ session('success', 'Employee created successfully.') }}</div>
        <button onclick="this.parentElement.remove()"
                class="text-green-800 hover:text-green-900 font-bold">
            ×
        </button>
    </div>
@endif

            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Employees</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Manage employee records, status, and basic HR info.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.employees.create') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        + Add Employee
                    </a>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.employees.index') }}"
                  class="mb-5 p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                               placeholder="Employee ID, name, email, phone..."
                               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status"
                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <option value="">All</option>
                            @foreach (['active','inactive','on_leave','terminated','archived'] as $status)
                                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department_id"
                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <option value="">All</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" @selected((string)($filters['department_id'] ?? '') === (string)$dept->id)>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="employee_role_id"
                                class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <option value="">All</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string)($filters['employee_role_id'] ?? '') === (string)$role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

				<div class="mt-3 flex items-center gap-2">
    <input id="show_archived" type="checkbox" name="show_archived" value="1"
           @checked(!empty($filters['show_archived']))
           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
    <label for="show_archived" class="text-sm text-gray-700">
        Show Archived
    </label>
</div>
				
                <div class="mt-4 flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
                        Apply
                    </button>

                    <a href="{{ route('admin.employees.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        Clear
                    </a>
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs uppercase bg-gray-50 text-gray-700 border-b">
                            <tr>
                                <th class="px-4 py-3">Employee ID</th>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Phone</th>
                                <th class="px-4 py-3">Department</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($employees as $employee)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $employee->employee_number }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $employee->last_name }}, {{ $employee->first_name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $employee->email ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $employee->phone ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $employee->department->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $employee->role->name ?? '—' }}
                                        @if (($employee->role->name ?? null) === 'Other' && $employee->role_other)
                                            <span class="text-gray-500">({{ $employee->role_other }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $status = $employee->status;
                                            $badge = match($status) {
                                                'active' => 'bg-green-100 text-green-800 border-green-200',
                                                'inactive' => 'bg-gray-100 text-gray-800 border-gray-200',
                                                'on_leave' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'terminated' => 'bg-red-100 text-red-800 border-red-200',
                                                'archived' => 'bg-slate-100 text-slate-700 border-slate-200',
                                                default => 'bg-gray-100 text-gray-800 border-gray-200',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
    <a href="{{ route('admin.employees.edit', $employee) }}"
       class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
        Edit
    </a>

    <a href="{{ route('admin.employees.show', $employee) }}"
       class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 ml-2">
        View
    </a>

    @if ($employee->status !== 'archived')
        {{-- Archive --}}
        <form method="POST"
              action="{{ route('admin.employees.destroy', $employee) }}"
              class="inline ml-2"
              onsubmit="return confirm('Archive this employee? You can restore them later.');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                Archive
            </button>
        </form>
    @else
        {{-- Restore --}}
        <form method="POST"
              action="{{ route('admin.employees.restore', $employee) }}"
              class="inline ml-2"
              onsubmit="return confirm('Restore this employee to Active?');">
            @csrf
            @method('PATCH')
            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-green-700 bg-green-100 border border-green-300 rounded-lg hover:bg-green-200">
                Restore
            </button>
        </form>
    @endif
</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-10 text-center text-gray-500">
                                        No employees found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($employees->hasPages())
                    <div class="p-4 border-t bg-white">
                        {{ $employees->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
