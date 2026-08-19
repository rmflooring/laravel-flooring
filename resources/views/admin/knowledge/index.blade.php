<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knowledge Base</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Entries the staff chat assistant can search — pricing, protocols, policies, SOPs, and FAQs.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.settings.knowledge-agent') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Tool Access Settings
                    </a>
                    <a href="{{ route('admin.knowledge.create') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        + New Entry
                    </a>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Search</label>
                    <input type="text" name="q" value="{{ $q }}" placeholder="Title or content..."
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
                <div>
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Category</label>
                    <select name="category" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected($category === $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">Filter</button>
                <a href="{{ route('admin.knowledge.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">Reset</a>
            </form>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3">Title</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Visible To</th>
                                <th class="px-6 py-3">Chunks</th>
                                <th class="px-6 py-3">Created</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $entry->title }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                            {{ ucfirst($entry->category) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                        {{ collect($entry->visible_to_roles)->map(fn ($r) => ucfirst($r))->implode(', ') }}
                                    </td>
                                    <td class="px-6 py-4">{{ $entry->embeddings_count }}</td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $entry->created_at->format('Y-m-d') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.knowledge.edit', $entry) }}" class="text-blue-600 hover:underline dark:text-blue-400">Edit</a>
                                            <form action="{{ route('admin.knowledge.destroy', $entry) }}" method="POST" onsubmit="return confirm('Delete this knowledge entry?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:underline">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">No knowledge entries yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t dark:border-gray-700">
                    {{ $entries->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
