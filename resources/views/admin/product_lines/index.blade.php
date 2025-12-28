<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Product Lines
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 text-green-600 font-medium">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4">
                <a href="{{ route('admin.product-lines.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Add New Product Line
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Type</th>
                                <th>Name</th>
                                <th>Vendor</th>
                                <th>Manufacturer</th>
                                <th>Model</th>
                                <th>Collection</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Updated By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($lines as $line)
                                <tr>
                                    <td>{{ $line->id }}</td>
                                    <td>{{ $line->productType->name ?? 'N/A' }}</td>
                                    <td>{{ $line->name }}</td>
                                    <td>{{ $line->vendor }}</td>
                                    <td>{{ $line->manufacturer }}</td>
                                    <td>{{ $line->model }}</td>
                                    <td>{{ $line->collection }}</td>
                                    <td>{{ ucfirst($line->status) }}</td>
                                    <td>{{ $line->created_by }}</td>
                                    <td>{{ $line->updated_by }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
    <a href="{{ route('admin.product-lines.edit', $line->id) }}"
       class="inline-block px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700 transition-colors shadow-sm">
        Edit
    </a>

    <form action="{{ route('admin.product-lines.destroy', $line->id) }}" method="POST" class="inline-block">
        @csrf
        @method('DELETE')
        <button type="submit" 
                onclick="return confirm('Delete this product line?')" 
                class="inline-block px-3 py-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors shadow-sm">
            Delete
        </button>
    </form>
</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4">No product lines found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $lines->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
