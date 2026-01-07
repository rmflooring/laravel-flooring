<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Manage Customers</h1>

                        <a href="{{ route('admin.customers.create') }}"
                           class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5">
                            Add New Customer
                        </a>
                    </div>

                    <!-- Filters / Search (Flowbite style) -->
                    @include('admin.partials.filter-bar', [
    'action' => route('admin.customers.index'),
    'searchPlaceholder' => 'Name, company, email, phone...',
    'searchValue' => request('search'),
	'perPageValue' => request('perPage', 15),
    'perPageOptions' => [15, 25, 50, 100],
    'selects' => [
        [
            'name' => 'status',
            'label' => 'Status',
            'options' => $statusOptions->map(fn($v) => ['value' => $v, 'label' => ucfirst($v)])->values()->all(),
            'selected' => request('status'),
        ],
        [
            'name' => 'type',
            'label' => 'Type',
            'options' => $typeOptions->map(fn($v) => ['value' => $v, 'label' => ucfirst($v)])->values()->all(),
            'selected' => request('type'),
        ],
    ],
])


                    <!-- Table (Flowbite style) -->
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">
									    @include('admin.partials.sort-link', ['label' => 'Name', 'field' => 'name'])
									</th>
									<th scope="col" class="px-6 py-3">
									    @include('admin.partials.sort-link', ['label' => 'Company', 'field' => 'company_name'])
									</th>
                                    <th scope="col" class="px-6 py-3">Parent Customer</th>
                                    <th scope="col" class="px-6 py-3">Phone / Mobile</th>
                                    <th scope="col" class="px-6 py-3">Email</th>
                                    <th scope="col" class="px-6 py-3">City / Province</th>
                                    <th scope="col" class="px-6 py-3">Type / Status</th>
                                    <th scope="col" class="px-6 py-3">Created By</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($customers as $customer)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                            {{ $customer->name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $customer->company_name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $customer->parent?->name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $customer->phone ?? $customer->mobile ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $customer->email ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $customer->city ?? '-' }} {{ $customer->province ?? '' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ ucfirst($customer->customer_type ?? '') }} / {{ ucfirst($customer->customer_status ?? '') }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $customer->creator?->name ?? '-' }}
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.customers.edit', $customer) }}"
                                               class="font-medium text-blue-600 hover:underline mr-4">
                                                Edit
                                            </a>

                                            <form action="{{ route('admin.customers.destroy', $customer) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="font-medium text-red-600 hover:underline"
                                                        onclick="return confirm('Are you sure?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b">
                                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                            No customers found.
                                            <a href="{{ route('admin.customers.create') }}" class="text-blue-600 hover:underline">
                                                Add the first one
                                            </a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $customers->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
