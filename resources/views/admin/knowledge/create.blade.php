<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Knowledge Entry</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Content is chunked and embedded automatically on save.</p>
                </div>
                <a href="{{ route('admin.knowledge.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    Cancel
                </a>
            </div>

            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.knowledge.store') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
                @csrf

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/40 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-3 flex items-center gap-3">
                    <input type="file" id="pdf-import-input" accept="application/pdf"
                        class="text-sm text-gray-700 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-white file:text-gray-700 file:border file:border-gray-300 hover:file:bg-gray-50 dark:file:bg-gray-700 dark:file:text-gray-200 dark:file:border-gray-600">
                    <button type="button" id="pdf-import-button"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 disabled:opacity-50">
                        Import from PDF
                    </button>
                    <span id="pdf-import-status" class="text-xs text-gray-500 dark:text-gray-400"></span>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Content <span class="text-red-500">*</span></label>
                    <textarea name="content" id="content-field" rows="10" required
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Separate distinct points with a blank line — each paragraph becomes its own searchable chunk.">{{ old('content') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PDF import replaces this box with the extracted text — review and clean it up (tables/columns often need fixing) before saving.</p>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Structured Data <span class="text-gray-400">(optional JSON — exact values like a price or markup %)</span></label>
                    <textarea name="structured_data" rows="3"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder='{"markup_percent": 38}'>{{ old('structured_data') }}</textarea>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Visible To <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($roles as $role)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="visible_to_roles[]" value="{{ $role }}"
                                    @checked(collect(old('visible_to_roles', []))->contains($role))
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                {{ ucfirst($role) }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">The admin role can always see everything, regardless of this selection.</p>
                </div>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        Save Entry
                    </button>
                    <a href="{{ route('admin.knowledge.index') }}"
                       class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        Cancel
                    </a>
                </div>
            </form>

        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('pdf-import-input');
            const button = document.getElementById('pdf-import-button');
            const status = document.getElementById('pdf-import-status');
            const content = document.getElementById('content-field');

            button.addEventListener('click', async function () {
                const file = input.files[0];
                if (! file) {
                    status.textContent = 'Choose a PDF file first.';
                    status.className = 'text-xs text-red-600';
                    return;
                }

                button.disabled = true;
                status.textContent = 'Extracting text…';
                status.className = 'text-xs text-gray-500 dark:text-gray-400';

                const formData = new FormData();
                formData.append('pdf', file);

                try {
                    const res = await fetch("{{ route('admin.knowledge.extract-pdf') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: formData,
                    });
                    const data = await res.json();

                    if (! res.ok) {
                        status.textContent = data.error || 'Could not extract text from this PDF.';
                        status.className = 'text-xs text-red-600';
                    } else {
                        content.value = data.text;
                        status.textContent = 'Extracted — review the content below before saving.';
                        status.className = 'text-xs text-green-600';
                    }
                } catch (e) {
                    status.textContent = 'Something went wrong — please try again.';
                    status.className = 'text-xs text-red-600';
                }

                button.disabled = false;
            });
        })();
    </script>
</x-app-layout>
