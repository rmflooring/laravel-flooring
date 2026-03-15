<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Page header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Document Labels</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage labels used to categorize opportunity documents.</p>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Create form --}}
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Add New Label</h2>
                <form action="{{ route('admin.opportunity_document_labels.store') }}" method="POST" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="name" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Label Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="e.g. Signed Contract, Permit, Site Photo..."
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <button
                        type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700"
                    >
                        Add Label
                    </button>
                </form>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.opportunity_document_labels.index') }}" class="flex items-center gap-3">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search labels..."
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white w-64"
                >
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="show_inactive" value="0">
                    <input type="checkbox" name="show_inactive" value="1" {{ $showInactive ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    Show inactive
                </label>
                <button type="submit" class="text-white bg-gray-700 hover:bg-gray-800 font-medium rounded-lg text-sm px-4 py-2.5">
                    Filter
                </button>
                @if ($search || $showInactive)
                    <a href="{{ route('admin.opportunity_document_labels.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Clear</a>
                @endif
            </form>

            {{-- Labels table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Name</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Documents</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($labels as $label)
                            <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $label->name }}
                                </td>
                                <td class="px-6 py-4">
                                    @if ($label->is_active)
                                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300">Active</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ $label->documents_count }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.opportunity_document_labels.edit', $label) }}"
                                       class="font-medium text-blue-600 hover:underline dark:text-blue-400 mr-4">
                                        Edit
                                    </a>
                                    @if ($label->documents_count === 0)
                                        <form action="{{ route('admin.opportunity_document_labels.destroy', $label) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('Delete label &quot;{{ addslashes($label->name) }}&quot;?')"
                                                    class="font-medium text-red-600 hover:underline dark:text-red-400">
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">In use</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No labels found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($labels->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $labels->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
