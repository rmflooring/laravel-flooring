<x-admin-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Employee</h1>
                    <p class="text-sm text-gray-600 mt-1">Update employee record.</p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.employees.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        ← Back
                    </a>
                </div>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <div class="font-semibold mb-2">Please fix the following:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.employees.update', $employee) }}"
                  class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-8">
                @csrf
                @method('PUT')

                {{-- Basic --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Info</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                            <input type="text" name="employee_number"
                                   value="{{ old('employee_number', $employee->employee_number) }}" required
                                   placeholder="You enter (e.g. RM-1001)"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="first_name"
                                   value="{{ old('first_name', $employee->first_name) }}" required
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="last_name"
                                   value="{{ old('last_name', $employee->last_name) }}" required
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email"
                                   value="{{ old('email', $employee->email) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="phone"
                                   value="{{ old('phone', $employee->phone) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="date_of_birth"
                                   value="{{ old('date_of_birth', optional($employee->date_of_birth)->format('Y-m-d')) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <p class="text-xs text-gray-500 mt-1">Format: yyyy-mm-dd</p>
                        </div>
                    </div>
                </div>

                {{-- Address --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Address</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                            <input type="text" name="address_line1"
                                   value="{{ old('address_line1', $employee->address_line1) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                            <input type="text" name="address_line2"
                                   value="{{ old('address_line2', $employee->address_line2) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="city"
                                   value="{{ old('city', $employee->city) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <input type="text" name="province"
                                   value="{{ old('province', $employee->province) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                            <input type="text" name="postal_code"
                                   value="{{ old('postal_code', $employee->postal_code) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                    </div>
                </div>

                {{-- Employment --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Employment</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date</label>
                            <input type="date" name="hire_date"
                                   value="{{ old('hire_date', optional($employee->hire_date)->format('Y-m-d')) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <p class="text-xs text-gray-500 mt-1">Format: yyyy-mm-dd</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
                            <input type="text" name="job_title"
                                   value="{{ old('job_title', $employee->job_title) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                                @foreach (['active','inactive','on_leave','terminated','archived'] as $status)
                                    <option value="{{ $status }}" @selected(old('status', $employee->status ?? 'active') === $status)>
                                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select name="department_id"
                                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                                <option value="">—</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        @selected((string)old('department_id', $employee->department_id) === (string)$dept->id)>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select name="employee_role_id"
                                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                                <option value="">—</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        @selected((string)old('employee_role_id', $employee->employee_role_id) === (string)$role->id)>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">If Role is “Other”, fill in the field below.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role (Other)</label>
                            <input type="text" name="role_other"
                                   value="{{ old('role_other', $employee->role_other) }}"
                                   placeholder="Only if Role is Other"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                    </div>
                </div>

                {{-- Emergency Contact --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Emergency Contact</h2>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <input type="text" name="emergency_contact_name"
                                   value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="emergency_contact_phone"
                                   value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Relation</label>
                            <input type="text" name="emergency_contact_relation"
                                   value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation) }}"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                        </div>
                    </div>
                </div>

                {{-- Sensitive --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Sensitive Info</h2>
                    <p class="text-sm text-gray-600 mb-4">
                        SIN is stored encrypted. Leave blank to keep the current value.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Social Insurance Number (SIN)</label>
                            <input type="password" name="sin" value=""
                                   placeholder="Enter SIN (digits only)"
                                   autocomplete="off"
                                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                            <p class="text-xs text-gray-500 mt-1">
                                Leave blank to keep existing.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes</h2>
                    <textarea name="notes" rows="4"
                              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5"
                              placeholder="Optional notes...">{{ old('notes', $employee->notes) }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('admin.employees.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        Update Employee
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-admin-layout>
