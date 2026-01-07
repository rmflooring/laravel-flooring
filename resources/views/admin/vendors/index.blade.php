<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Manage Vendors</h1>

                        <a href="{{ route('admin.vendors.create') }}"
                           class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5">
                            Add New Vendor
                        </a>
                    </div>

                    @include('admin.partials.filter-bar', [
                        'action' => route('admin.vendors.index'),
                        'searchPlaceholder' => 'Company, contact, email, phone, city...',
                        'searchValue' => request('search'),
                        'perPageValue' => request('perPage', 15),
                        'perPageOptions' => [15, 25, 50, 100],
                        'selects' => [
                            [
                                'name' => 'status',
                                'label' => 'Status',
                                'options' => (isset($statusOptions) ? $statusOptions : collect())
                                    ->map(fn($v) => ['value' => $v, 'label' => ucfirst($v)])
                                    ->values()
                                    ->all(),
                                'selected' => request('status'),
                            ],
                            [
                                'name' => 'type',
                                'label' => 'Type',
                                'options' => (isset($typeOptions) ? $typeOptions : collect())
                                    ->map(fn($v) => ['value' => $v, 'label' => ucfirst($v)])
                                    ->values()
                                    ->all(),
                                'selected' => request('type'),
                            ],
                        ],
                    ])

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        @include('admin.partials.sort-link', ['label' => 'Company Name', 'field' => 'company_name'])
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        @include('admin.partials.sort-link', ['label' => 'Contact', 'field' => 'contact_name'])
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Phone / Mobile
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        @include('admin.partials.sort-link', ['label' => 'Email', 'field' => 'email'])
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        @include('admin.partials.sort-link', ['label' => 'City / Province', 'field' => 'city'])
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Type / Status
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vendor Reps
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Created By
                                    </th>

                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($vendors as $vendor)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $vendor->company_name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $vendor->contact_name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $vendor->phone ?? $vendor->mobile ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $vendor->email ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $vendor->city ?? '-' }} {{ $vendor->province ?? '' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst($vendor->vendor_type ?? '-') }} / {{ ucfirst($vendor->status ?? '-') }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @forelse($vendor->reps as $rep)
                                                <span class="inline-flex px-2 py-1 text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800 mr-1">
                                                    {{ $rep->name }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400 text-xs">-</span>
                                            @endforelse
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $vendor->creator?->name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.vendors.edit', $vendor) }}"
                                               class="text-indigo-600 hover:text-indigo-900 mr-4">
                                                Edit
                                            </a>

                                            <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-600 hover:text-red-900"
                                                        onclick="return confirm('Are you sure?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                            No vendors found.
                                            <a href="{{ route('admin.vendors.create') }}" class="text-indigo-600 hover:text-indigo-900">
                                                Add the first one
                                            </a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $vendors->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
