<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Payment Terms</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage payment terms used on invoices, vendors, and subcontractors.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
            @endif

            {{-- Create form --}}
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Add New Payment Term</h2>
                <form action="{{ route('admin.payment-terms.store') }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                placeholder="e.g. Net 30, Due on Receipt"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                required>
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Days (optional)</label>
                            <input type="number" name="days" value="{{ old('days') }}" min="0" max="365"
                                placeholder="e.g. 30"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Description (optional)</label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                placeholder="Short note..."
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Add Term
                        </button>
                    </div>
                </form>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.payment-terms.index') }}" class="flex items-center gap-3">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search terms..."
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-48 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                    <input type="checkbox" name="show_inactive" value="1" {{ $showInactive ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                    Show inactive
                </label>
                <button type="submit"
                    class="text-white bg-gray-700 hover:bg-gray-800 font-medium rounded-lg text-sm px-4 py-2.5 dark:bg-gray-600 dark:hover:bg-gray-700">
                    Filter
                </button>
                @if($search || $showInactive)
                    <a href="{{ route('admin.payment-terms.index') }}"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 underline">Clear</a>
                @endif
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Days</th>
                            <th class="px-6 py-3">Description</th>
                            <th class="px-6 py-3">Invoices</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($terms as $term)
                            <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $term->name }}</td>
                                <td class="px-6 py-4">{{ $term->days ? 'Net ' . $term->days : '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $term->description ?: '—' }}</td>
                                <td class="px-6 py-4">{{ $term->invoices_count }}</td>
                                <td class="px-6 py-4">
                                    @if($term->is_active)
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Active</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="{{ route('admin.payment-terms.edit', $term) }}"
                                        class="font-medium text-blue-600 hover:underline dark:text-blue-500">Edit</a>
                                    @if($term->invoices_count === 0)
                                        <form action="{{ route('admin.payment-terms.destroy', $term) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Delete this payment term?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-500">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">In use</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-400">No payment terms found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($terms->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $terms->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
