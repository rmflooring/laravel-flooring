<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Product Lines
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Add New Button -->
            <div class="flex justify-end mb-6">
                <a href="{{ route('admin.product_lines.create') }}"
                   class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                     <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
					Add New Product Line
                </a>
            </div>
			
			{{-- Filters (GET) --}}
<form method="GET" action="{{ route('admin.product_lines.index') }}"
      class="border border-gray-200 dark:border-gray-700 rounded-2xl p-6 mb-8 bg-white dark:bg-gray-800">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Search --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Search</label>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Name, vendor, manufacturer, model..."
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                          focus:ring-4 focus:ring-blue-300 focus:border-blue-500" />
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Status</label>
            <select name="status"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                           focus:ring-4 focus:ring-blue-300 focus:border-blue-500">
                <option value="">All</option>
                <option value="active"   @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
        </div>

        {{-- Product Type --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Product Type</label>
            <select name="product_type_id"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                           focus:ring-4 focus:ring-blue-300 focus:border-blue-500">
                <option value="">All</option>
                @foreach($productTypes as $pt)
                    <option value="{{ $pt->id }}" @selected((string)request('product_type_id') === (string)$pt->id)>
                        {{ $pt->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Per Page --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Per Page</label>
            <select name="per_page"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                           focus:ring-4 focus:ring-blue-300 focus:border-blue-500">
                @foreach([10,15,25,50,100] as $n)
                    <option value="{{ $n }}" @selected((int)request('per_page', 15) === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Optional: Vendor filter (uncomment if you want it) --}}
    {{--
    <div class="mt-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Vendor</label>
        <select name="vendor_id"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white
                       focus:ring-4 focus:ring-blue-300 focus:border-blue-500">
            <option value="">All</option>
            @foreach($vendors as $v)
                <option value="{{ $v->id }}" @selected((string)request('vendor_id') === (string)$v->id)>
                    {{ $v->company_name }}
                </option>
            @endforeach
        </select>
    </div>
    --}}

    <div class="mt-6 flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg
                       hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700
                       dark:focus:ring-blue-800">
            Apply
        </button>

        <a href="{{ route('admin.product_lines.index') }}"
           class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg
                  hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600
                  dark:hover:bg-gray-700 dark:focus:ring-gray-700">
            Reset
        </a>
    </div>
</form>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vendor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Manufacturer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Collection</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($lines as $line)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ $line->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ $line->productType->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
										    <a href="{{ route('admin.product_styles.index', $line->id) }}"
										       class="text-blue-700 hover:underline dark:text-blue-400">
										        {{ $line->name }}
										    </a>
										</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ $line->vendorRelation->company_name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ $line->manufacturer }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ $line->model }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">{{ $line->collection }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                            {{ ucfirst($line->status) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ route('admin.product_lines.edit', $line->id) }}"
                                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4">
                                                Edit
                                            </a>
                                            <form action="{{ route('admin.product_lines.destroy', $line->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('Delete this product line?')"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                            No product lines found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $lines->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>