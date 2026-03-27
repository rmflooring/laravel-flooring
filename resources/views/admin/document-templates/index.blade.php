<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Document Templates</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage printable document templates for jobs (file labels, sign-off sheets, authorization forms, etc.).
                    </p>
                </div>
                <a href="{{ route('admin.document-templates.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    New Template
                </a>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
                    <button onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400">✕</button>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <span class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</span>
                    <button onclick="this.closest('div').remove()" class="text-red-600 dark:text-red-400">✕</button>
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                @if ($templates->isEmpty())
                    <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                        <svg class="w-10 h-10 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        No templates yet.
                        <a href="{{ route('admin.document-templates.create') }}" class="text-blue-600 dark:text-blue-400 underline ml-1">Create one.</a>
                    </div>
                @else
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                            <tr>
                                <th class="px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">#</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Name</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Description</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Needs Sale</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                                <th class="px-5 py-3 font-semibold text-gray-600 dark:text-gray-300">Used</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($templates as $template)
                                @php $usageCount = $template->generatedDocuments()->count(); @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                    <td class="px-5 py-3 text-gray-400 dark:text-gray-500">{{ $template->sort_order }}</td>
                                    <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $template->name }}</td>
                                    <td class="px-5 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ $template->description ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        @if ($template->needs_sale)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">Sale required</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($template->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $usageCount }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.document-templates.edit', $template) }}"
                                               class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">Edit</a>
                                            @if ($usageCount === 0)
                                                <form method="POST" action="{{ route('admin.document-templates.destroy', $template) }}"
                                                      onsubmit="return confirm('Delete template \'{{ addslashes($template->name) }}\'?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline dark:text-red-400">Delete</button>
                                                </form>
                                            @else
                                                <span class="text-xs text-gray-400 dark:text-gray-500">In use</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
