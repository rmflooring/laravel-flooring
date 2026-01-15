{{-- resources/views/pages/opportunities/documents/index.blade.php --}}
<x-app-layout>
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Top Banner (Flowbite card) --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Documents for Opportunity #{{ $opportunity->id }}
            </h1>

            <div class="mt-4 flex justify-center">
    <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
       class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
        ← Back to Opportunity
    </a>
</div>
        </div>

        {{-- Flash Messages (Flowbite alerts) --}}
        @if (session('success'))
            <div class="mb-4 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400"
                 role="alert">
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-center rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400"
                 role="alert">
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Upload (collapsed by default) --}}
        <div class="mb-6">
            <div class="flex items-center justify-end">
                <button type="button"
                        id="toggle-upload-panel"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Upload Files
                </button>
            </div>

            <div id="upload-panel"
                 class="hidden mt-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Upload New Document or Media</h2>

                <form id="upload-form"
                      method="POST"
                      action="{{ route('pages.opportunities.documents.store', $opportunity->id) }}"
                      enctype="multipart/form-data"
                      class="space-y-4">
                    @csrf

                    {{-- Drop Zone --}}
                    <div id="drop-zone"
                         class="cursor-pointer select-none rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-600 dark:bg-gray-900/30">
                        <div class="font-medium text-gray-800 dark:text-gray-100">
                            Drag & drop files here or click to select
                        </div>
                        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Browse Files</div>

                        <input id="file-upload-input"
                               type="file"
                               name="files[]"
                               multiple
                               class="hidden">
                    </div>

                    {{-- Selected files list --}}
                    <div id="selected-files-wrap" class="hidden">
                        <div class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Selected files:</div>
                        <ul id="selected-files" class="list-disc space-y-1 pl-5 text-sm text-gray-600 dark:text-gray-300"></ul>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        {{-- Label --}}
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Document Label</label>
                            <select name="label_id"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                                <option value="">-- Select Label --</option>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}">{{ $label->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="lg:col-span-2">
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Description (optional)</label>
                            <input type="text"
                                   name="description"
                                   class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                   placeholder="Applies to all uploaded files">
                        </div>
                    </div>

                    {{-- Upload button --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Upload
                        </button>

                        <button type="button"
                                id="close-upload-panel"
                                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Filters (Flowbite card) --}}
        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end">

                {{-- Type tabs (Flowbite button group style) --}}
                <div class="inline-flex rounded-lg shadow-sm" role="group">
                    @php
                        $baseParams = request()->except(['type', 'page']);
                    @endphp

                    @php
                        $btnBase = 'px-4 py-2 text-sm font-medium border focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700';
                        $btnLeft = 'rounded-l-lg';
                        $btnMid  = '';
                        $btnRight= 'rounded-r-lg';
                    @endphp

                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) . (count(array_merge($baseParams, ['type' => null])) ? '?' . http_build_query(array_merge($baseParams, ['type' => null])) : '') }}"
                       class="{{ $btnBase }} {{ $btnLeft }} {{ empty($type)
                            ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'
                        }}">
                        Show All
                    </a>

                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) . '?' . http_build_query(array_merge($baseParams, ['type' => 'documents'])) }}"
                       class="{{ $btnBase }} {{ $btnMid }} {{ ($type === 'documents')
                            ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'
                        }}">
                        Documents
                    </a>

                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) . '?' . http_build_query(array_merge($baseParams, ['type' => 'media'])) }}"
                       class="{{ $btnBase }} {{ $btnRight }} {{ ($type === 'media')
                            ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'
                        }}">
                        Media
                    </a>
                </div>

                {{-- Label filter --}}
                <form method="GET"
                      action="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                      class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="w-full sm:max-w-xs">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Filter by Label</label>
                        <select name="label_id"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                            <option value="">All Labels</option>
                            @foreach ($labels as $label)
                                <option value="{{ $label->id }}" {{ (string)$labelId === (string)$label->id ? 'selected' : '' }}>
                                    {{ $label->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2 sm:pb-2">
                        <input id="show_archived"
                               type="checkbox"
                               name="show_archived"
                               value="1"
                               {{ !empty($showArchived) ? 'checked' : '' }}
                               class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600">
                        <label for="show_archived" class="text-sm text-gray-700 dark:text-gray-200">Show Archived</label>
                    </div>

                    <div class="sm:pb-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:bg-gray-200 dark:text-gray-900 dark:hover:bg-white dark:focus:ring-gray-700">
                            Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bulk actions bar (Flowbite toolbar style) --}}
        <div id="bulk-actions"
             class="hidden mb-3 flex items-center justify-between rounded-lg border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="text-sm text-gray-700 dark:text-gray-200">
                Selected: <span id="selected-count" class="font-semibold">0</span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        id="bulk-restore-btn"
                        class="hidden inline-flex items-center justify-center rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800">
                    Restore Selected
                </button>

                <button type="button"
                        id="bulk-delete-btn"
                        class="hidden inline-flex items-center justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                    Archive Selected
                </button>

                <button type="button"
                        id="bulk-force-delete-btn"
                        class="hidden inline-flex items-center justify-center rounded-lg bg-red-800 px-3 py-2 text-sm font-medium text-white hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
                    Delete Selected (Permanent)
                </button>
            </div>
        </div>

        {{-- Bulk forms (submitted by JS) --}}
        <form id="bulk-archive-form"
              method="POST"
              action="{{ route('pages.opportunities.documents.bulkDestroy', $opportunity->id) }}"
              class="hidden">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <div id="bulk-ids-container"></div>
        </form>

        <form id="bulk-restore-form"
              method="POST"
              action="{{ route('pages.opportunities.documents.bulkRestore', $opportunity->id) }}"
              class="hidden">
            @csrf
            <div id="bulk-restore-ids-container"></div>
        </form>

        <form id="bulk-force-delete-form"
              method="POST"
              action="{{ route('pages.opportunities.documents.bulkForceDestroy', $opportunity->id) }}"
              class="hidden">
            @csrf
            <input type="hidden" name="_method" value="DELETE">
            <div id="bulk-force-delete-ids-container"></div>
        </form>

        {{-- Table (Flowbite table wrapper) --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="relative overflow-x-auto">
                <table class="min-w-[1100px] w-full text-left text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                        <tr>
                            <th scope="col" class="px-4 py-3 w-32">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="select-all"
                                           class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600">
                                    <span id="select-all-label" class="text-xs font-medium text-gray-600 dark:text-gray-200">
                                        Select All
                                    </span>
                                </div>
                            </th>
                            <th scope="col" class="px-4 py-3">File Name</th>
                            <th scope="col" class="px-4 py-3">Description</th>
                            <th scope="col" class="px-4 py-3">Label</th>
                            <th scope="col" class="px-4 py-3">Category</th>
                            <th scope="col" class="px-4 py-3">Uploaded At</th>
                            <th scope="col" class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($documents as $doc)
                            <tr class="border-t border-gray-200 {{ $doc->trashed() ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-white dark:bg-gray-800' }} hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           class="doc-checkbox h-4 w-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600"
                                           value="{{ $doc->id }}"
                                           data-trashed="{{ $doc->trashed() ? '1' : '0' }}">
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-200">
                                            FILE
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate font-medium text-gray-900 dark:text-white">
                                                {{ $doc->original_name }}
                                            </div>
                                            <div class="truncate text-xs text-gray-500 dark:text-gray-300">
                                                {{ $doc->mime_type ?? '—' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    @if(!$doc->trashed())
                                        <form method="POST" action="{{ route('pages.opportunities.documents.update', [$opportunity->id, $doc->id]) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="text"
                                                   name="description"
                                                   value="{{ $doc->description }}"
                                                   placeholder="Add description..."
                                                   class="doc-desc block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                   data-url="{{ route('pages.opportunities.documents.update', [$opportunity->id, $doc->id]) }}"
                                                   data-token="{{ csrf_token() }}">
                                        </form>
                                    @else
                                        <div class="italic text-gray-500 dark:text-gray-400">Archived</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    @if(!$doc->trashed())
                                        <form method="POST" action="{{ route('pages.opportunities.documents.update', [$opportunity->id, $doc->id]) }}">
                                            @csrf
                                            @method('PATCH')

                                            <select name="label_id"
                                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500"
                                                    onchange="this.form.submit()">
                                                <option value="">—</option>
                                                @foreach ($labels as $label)
                                                    <option value="{{ $label->id }}" {{ (string)$doc->label_id === (string)$label->id ? 'selected' : '' }}>
                                                        {{ $label->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </form>
                                    @else
                                        <div class="italic text-gray-500 dark:text-gray-400">Archived</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $doc->category_override ?? $doc->category }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $doc->created_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ asset('storage/' . $doc->path) }}"
                                           target="_blank"
                                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                            View
                                        </a>

                                        @if ($doc->trashed())
                                            <form method="POST" action="{{ route('pages.opportunities.documents.restore', [$opportunity->id, $doc->id]) }}">
                                                @csrf
                                                <button type="submit"
                                                        class="inline-flex items-center rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800">
                                                    Restore
                                                </button>
                                            </form>

                                            @if(auth()->user()?->hasRole('admin'))
                                                <form method="POST"
                                                      action="{{ route('pages.opportunities.documents.forceDestroy', [$opportunity->id, $doc->id]) }}"
                                                      onsubmit="return confirm('Permanently delete this file? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <form method="POST"
                                                  action="{{ route('pages.opportunities.documents.destroy', [$opportunity->id, $doc->id]) }}"
                                                  onsubmit="return confirm('Archive this file?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                                                    Archive
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                {{-- FIX: colspan must match 7 columns --}}
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No documents found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                {{ $documents->links() }}
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            // -------------------------
            // Bulk select + bulk delete
            // -------------------------
            const selectAll = document.getElementById('select-all');
            const selectAllLabel = document.getElementById('select-all-label');

            const bulkBar = document.getElementById('bulk-actions');
            const selectedCountEl = document.getElementById('selected-count');

            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            const bulkForm = document.getElementById('bulk-archive-form');
            const bulkIdsContainer = document.getElementById('bulk-ids-container');

            const bulkRestoreBtn = document.getElementById('bulk-restore-btn');
            const bulkRestoreForm = document.getElementById('bulk-restore-form');
            const bulkRestoreIdsContainer = document.getElementById('bulk-restore-ids-container');

            const bulkForceDeleteBtn = document.getElementById('bulk-force-delete-btn');
            const bulkForceDeleteForm = document.getElementById('bulk-force-delete-form');
            const bulkForceDeleteIdsContainer = document.getElementById('bulk-force-delete-ids-container');

            const checkboxes = Array.from(document.querySelectorAll('.doc-checkbox'));

            function updateBulkUI() {
                const selected = checkboxes.filter(cb => cb.checked);
                const count = selected.length;

                // show/hide bulk bar + count
                if (bulkBar && selectedCountEl) {
                    selectedCountEl.textContent = String(count);
                    bulkBar.classList.toggle('hidden', count === 0);
                }

                // Toggle which bulk buttons show
                const trashedSelected = selected.filter(cb => cb.dataset.trashed === '1');
                const activeSelected  = selected.filter(cb => cb.dataset.trashed === '0');

                if (bulkRestoreBtn) {
                    bulkRestoreBtn.classList.toggle('hidden', trashedSelected.length === 0);
                }

                if (bulkDeleteBtn) {
                    bulkDeleteBtn.classList.toggle('hidden', activeSelected.length === 0);
                }

                if (bulkForceDeleteBtn) {
                    bulkForceDeleteBtn.classList.toggle('hidden', trashedSelected.length === 0);
                }

                // Select All / Clear Selection label
                if (selectAllLabel) {
                    if (checkboxes.length > 0 && count === checkboxes.length) {
                        selectAllLabel.textContent = 'Clear Selection';
                    } else {
                        selectAllLabel.textContent = 'Select All';
                    }
                }
            }

            // row checkbox changes
            checkboxes.forEach(cb => cb.addEventListener('change', updateBulkUI));

            // select all toggle
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes.forEach(cb => {
                        cb.checked = selectAll.checked;
                    });
                    updateBulkUI();
                });
            }

            // bulk delete submit (archives via your DELETE bulk route)
            if (bulkDeleteBtn && bulkForm && bulkIdsContainer) {
                bulkDeleteBtn.addEventListener('click', () => {
                    const selectedIds = Array.from(document.querySelectorAll('.doc-checkbox:checked'))
                        .map(cb => cb.value)
                        .filter(Boolean);

                    if (selectedIds.length === 0) {
                        alert('No files selected.');
                        return;
                    }

                    if (!confirm(`Archive ${selectedIds.length} file(s)?`)) return;

                    bulkIdsContainer.innerHTML = '';
                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        bulkIdsContainer.appendChild(input);
                    });

                    bulkForm.submit();
                });
            } else {
                console.warn('[bulk] missing elements:', {
                    bulkDeleteBtn: !!bulkDeleteBtn,
                    bulkForm: !!bulkForm,
                    bulkIdsContainer: !!bulkIdsContainer,
                });
            }

            // bulk restore submit (restores via your POST bulk-restore route)
            if (bulkRestoreBtn && bulkRestoreForm && bulkRestoreIdsContainer) {
                bulkRestoreBtn.addEventListener('click', () => {

                    const restoreIds = Array.from(document.querySelectorAll('.doc-checkbox:checked'))
                        .filter(cb => cb.dataset.trashed === '1')
                        .map(cb => cb.value)
                        .filter(Boolean);

                    if (restoreIds.length === 0) {
                        alert('No archived files selected.');
                        return;
                    }

                    if (!confirm(`Restore ${restoreIds.length} file(s)?`)) return;

                    bulkRestoreIdsContainer.innerHTML = '';
                    restoreIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        bulkRestoreIdsContainer.appendChild(input);
                    });

                    bulkRestoreForm.submit();
                });
            }

            // bulk permanent delete submit (DELETE bulk-force route)
            if (bulkForceDeleteBtn && bulkForceDeleteForm && bulkForceDeleteIdsContainer) {
                bulkForceDeleteBtn.addEventListener('click', () => {

                    const deleteIds = Array.from(document.querySelectorAll('.doc-checkbox:checked'))
                        .filter(cb => cb.dataset.trashed === '1')
                        .map(cb => cb.value)
                        .filter(Boolean);

                    if (deleteIds.length === 0) {
                        alert('No archived files selected.');
                        return;
                    }

                    if (!confirm(`PERMANENTLY delete ${deleteIds.length} file(s)? This cannot be undone.`)) return;

                    bulkForceDeleteIdsContainer.innerHTML = '';
                    deleteIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        bulkForceDeleteIdsContainer.appendChild(input);
                    });

                    bulkForceDeleteForm.submit();
                });
            }

            updateBulkUI();

            // -------------------------
            // Toggle upload panel
            // -------------------------
            const toggleBtn = document.getElementById('toggle-upload-panel');
            const closeBtn = document.getElementById('close-upload-panel');
            const uploadPanel = document.getElementById('upload-panel');

            if (toggleBtn && uploadPanel) {
                toggleBtn.addEventListener('click', () => {
                    uploadPanel.classList.toggle('hidden');
                });
            }

            if (closeBtn && uploadPanel) {
                closeBtn.addEventListener('click', () => {
                    uploadPanel.classList.add('hidden');
                });
            }

            // -------------------------
            // Mass upload drop zone
            // -------------------------
            const dropZone = document.getElementById('drop-zone');
            const uploadInput = document.getElementById('file-upload-input');
            const selectedWrap = document.getElementById('selected-files-wrap');
            const selectedList = document.getElementById('selected-files');

            function renderSelectedFiles(files) {
                if (!selectedWrap || !selectedList) return;

                selectedList.innerHTML = '';
                if (!files || files.length === 0) {
                    selectedWrap.classList.add('hidden');
                    return;
                }

                selectedWrap.classList.remove('hidden');
                Array.from(files).forEach((f) => {
                    const li = document.createElement('li');
                    li.textContent = `${f.name} (${Math.round(f.size / 1024)} KB)`;
                    selectedList.appendChild(li);
                });
            }

            if (dropZone && uploadInput) {
                dropZone.addEventListener('click', () => uploadInput.click());

                uploadInput.addEventListener('change', () => {
                    renderSelectedFiles(uploadInput.files);
                });

                ['dragenter', 'dragover'].forEach((evt) => {
                    dropZone.addEventListener(evt, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropZone.classList.add('ring-2', 'ring-blue-400');
                    });
                });

                ['dragleave', 'drop'].forEach((evt) => {
                    dropZone.addEventListener(evt, (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        dropZone.classList.remove('ring-2', 'ring-blue-400');
                    });
                });

                dropZone.addEventListener('drop', (e) => {
                    const dt = e.dataTransfer;
                    if (!dt || !dt.files || dt.files.length === 0) return;

                    uploadInput.files = dt.files;
                    renderSelectedFiles(uploadInput.files);
                });
            }

            // -------------------------
            // Inline description AJAX save
            // -------------------------
            document.querySelectorAll('input.doc-desc').forEach((input) => {
                let lastValue = input.value;

                input.addEventListener('blur', async () => {
                    const newValue = input.value;
                    if (newValue === lastValue) return;

                    const url = input.dataset.url;
                    const token = input.dataset.token;

                    try {
                        const res = await fetch(url, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ description: newValue }),
                        });

                        if (!res.ok) throw new Error('Save failed');

                        lastValue = newValue;
                        input.classList.remove('border-red-400');
                    } catch (e) {
                        input.classList.add('border-red-400');
                    }
                });
            });

        });
    </script>
</x-app-layout>
