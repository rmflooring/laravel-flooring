<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Edit Role: {{ $role->name }}</h1>

                    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <h2 class="text-2xl font-semibold mb-6">Assign Permissions</h2>

                        @php
                        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get()->keyBy('name');

                        $groups = [
                            'Reports' => [
                                'view reports',
                                'view sales report',
                                'view invoices report',
                                'view revenue report',
                                'view purchase orders report',
                                'view aging estimates report',
                            ],
                            'Estimates' => [
                                'view estimates',
                                'create estimates',
                                'edit estimates',
                                'delete estimates',
                            ],
                            'Sales' => [
                                'view sale status',
                                'delete sales',
                            ],
                            'Invoices' => [
                                'view invoices',
                                'create invoices',
                                'edit invoices',
                                'delete invoices',
                            ],
                            'Purchase Orders' => [
                                'view purchase orders',
                                'create purchase orders',
                                'edit purchase orders',
                                'delete purchase orders',
                            ],
                            'Work Orders' => [
                                'view work orders',
                                'create work orders',
                                'edit work orders',
                                'delete work orders',
                            ],
                            'RFMs' => [
                                'view rfms',
                                'create rfms',
                                'edit rfms',
                                'delete rfms',
                            ],
                            'Pick Tickets' => [
                                'view pick tickets',
                            ],
                            'RFC / RTV' => [
                                'view rfcs',
                                'create rfcs',
                                'edit rfcs',
                                'view rtvs',
                                'create rtvs',
                                'edit rtvs',
                            ],
                            'Customers' => [
                                'view customers',
                                'create customers',
                                'edit customers',
                                'delete customers',
                            ],
                            'Vendors' => [
                                'view vendors',
                                'create vendors',
                                'edit vendors',
                                'delete vendors',
                            ],
                            'Vendor Reps' => [
                                'view vendor reps',
                                'create vendor reps',
                                'edit vendor reps',
                                'delete vendor reps',
                            ],
                            'Installers' => [
                                'view installers',
                                'create installers',
                                'edit installers',
                                'delete installers',
                            ],
                            'Project Managers' => [
                                'view project managers',
                                'create project managers',
                                'edit project managers',
                                'delete project managers',
                            ],
                            'Products & Catalogue' => [
                                'view product types',
                                'create product types',
                                'edit product types',
                                'delete product types',
                                'view product lines',
                                'create product lines',
                                'edit product lines',
                                'delete product lines',
                                'view product styles',
                                'create product styles',
                                'edit product styles',
                                'delete product styles',
                                'view labour types',
                                'create labour types',
                                'edit labour types',
                                'delete labour types',
                                'view labour items',
                                'create labour items',
                                'edit labour items',
                                'delete labour items',
                                'view unit measures',
                                'create unit measures',
                                'edit unit measures',
                                'delete unit measures',
                            ],
                            'E-Signatures' => [
                                'manage signing requests',
                            ],
                            'Administration' => [
                                'view dashboard',
                                'edit settings',
                                'manage users',
                                'manage roles',
                            ],
                        ];

                        // Track which permissions have been grouped so we can show any ungrouped ones at the end
                        $grouped = collect($groups)->flatten()->toArray();
                        $ungrouped = $allPermissions->keys()->diff($grouped)->values();
                        @endphp

                        <div class="space-y-6 mb-12">
                            @foreach($groups as $groupName => $groupPermissions)
                                @php
                                    // Only show the group if at least one permission in it exists in DB
                                    $existing = collect($groupPermissions)->filter(fn($p) => $allPermissions->has($p));
                                @endphp
                                @if($existing->isNotEmpty())
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-2 border-b border-gray-200 flex items-center justify-between">
                                        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">{{ $groupName }}</h3>
                                        <button type="button"
                                                onclick="toggleGroup(this)"
                                                data-group="{{ Str::slug($groupName) }}"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                            Select All
                                        </button>
                                    </div>
                                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3" data-group-body="{{ Str::slug($groupName) }}">
                                        @foreach($existing as $permName)
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox"
                                                       name="permissions[]"
                                                       value="{{ $permName }}"
                                                       {{ $role->permissions->contains('name', $permName) ? 'checked' : '' }}
                                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 group-checkbox">
                                                <span class="text-sm text-gray-800">{{ $permName }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            @endforeach

                            {{-- Any permissions not in a group --}}
                            @if($ungrouped->isNotEmpty())
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Other</h3>
                                </div>
                                <div class="p-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    @foreach($ungrouped as $permName)
                                        @if($allPermissions->has($permName))
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox"
                                                   name="permissions[]"
                                                   value="{{ $permName }}"
                                                   {{ $role->permissions->contains('name', $permName) ? 'checked' : '' }}
                                                   class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm text-gray-800">{{ $permName }}</span>
                                        </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-start gap-8">
                            <a href="{{ route('admin.roles.index') }}" class="px-10 py-5 text-lg font-bold text-white bg-gray-800 rounded-lg shadow-lg hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300">
                                Cancel
                            </a>
                            <button type="submit" class="px-10 py-5 text-lg font-bold text-white bg-green-600 rounded-lg shadow-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                                Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleGroup(btn) {
            const groupSlug = btn.dataset.group;
            const body = document.querySelector('[data-group-body="' + groupSlug + '"]');
            const checkboxes = body.querySelectorAll('input[type="checkbox"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            btn.textContent = allChecked ? 'Select All' : 'Deselect All';
        }
    </script>
</x-app-layout>
