<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Product Types</h1>
                        <a href="{{ route('admin.product_types.create') }}" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                            Add New Product Type
                        </a>
                    </div>

                    @if ($productTypes->isEmpty())
                        <p class="text-gray-500">No product types found. <a href="{{ route('admin.product_types.create') }}" class="text-indigo-600 hover:underline">Create one now</a>.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordered By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sold By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Cost GL</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Sell GL</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($productTypes as $type)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $type->name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $type->orderedByUnit->label ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $type->soldByUnit->label ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $type->defaultCostGlAccount ? $type->defaultCostGlAccount->account_number . ' - ' . $type->defaultCostGlAccount->name : 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $type->defaultSellGlAccount ? $type->defaultSellGlAccount->account_number . ' - ' . $type->defaultSellGlAccount->name : 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
    <a href="{{ route('admin.product_types.edit', $type) }}" 
       class="text-indigo-600 hover:text-indigo-900 mr-4">
        Edit
    </a>

    <form action="{{ route('admin.product_types.destroy', $type) }}" method="POST" class="inline-block">
        @csrf
        @method('DELETE')
        <button type="submit"
                onclick="return confirm('Are you sure you want to delete this Product Type?\nThis action cannot be undone and may affect related product lines.')"
                class="text-red-600 hover:text-red-900">
            Delete
        </button>
    </form>
</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

