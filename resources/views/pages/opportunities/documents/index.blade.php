{{-- resources/views/pages/opportunities/documents/index.blade.php --}}
<x-app-layout>
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Top Banner (Flowbite card) --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Documents for Opportunity #{{ $opportunity->id }}
            </h1>

            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    ← Back to Opportunity
                </a>
                <a href="{{ route('pages.opportunities.media.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
                    </svg>
                    Photo Gallery
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

        {{-- Action bar --}}
        <div class="mb-6">
            <div class="flex items-center justify-end gap-3">

                {{-- Create Document picker --}}
                @if ($activeTemplates->isNotEmpty())
                <div x-data="{
                        open: false,
                        templateId: '',
                        needsSale: false,
                        specialFlow: '',
                        saleId: '',
                        templates: {{ $activeTemplates->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'needs_sale' => (bool) $t->needs_sale, 'special_flow' => $t->special_flow ?? ''])->toJson() }},
                        selectTemplate(id) {
                            this.templateId = id;
                            const t = this.templates.find(t => String(t.id) === String(id));
                            this.needsSale = t ? (t.needs_sale || t.special_flow === 'flooring_sign_off') : false;
                            this.specialFlow = t ? t.special_flow : '';
                        },
                        go() {
                            if (!this.templateId) return;
                            const base = '{{ route('pages.opportunities.documents.create-generated', [$opportunity->id, '__TPL__']) }}'.replace('__TPL__', this.templateId);
                            const url = this.saleId ? base + '?sale_id=' + this.saleId : base;
                            window.location = url;
                        }
                     }"
                     class="relative">

                    <button type="button"
                            @click="open = !open"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 dark:focus:ring-emerald-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        Create Document
                        <svg class="w-3.5 h-3.5 ml-0.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div x-show="open"
                         x-cloak
                         @click.outside="open = false"
                         class="absolute right-0 z-20 mt-2 w-80 rounded-lg border border-gray-200 bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-800">

                        <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Choose a template</p>

                        <select @change="selectTemplate($event.target.value)"
                                class="mb-3 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select template —</option>
                            @foreach ($activeTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                            @endforeach
                        </select>

                        <div x-show="needsSale" x-cloak class="mb-3">
                            @if ($opportunitySales->isNotEmpty())
                                <select x-model="saleId"
                                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">— Select sale (required) —</option>
                                    @foreach ($opportunitySales as $s)
                                        <option value="{{ $s->id }}">
                                            Sale #{{ $s->sale_number }}{{ $s->job_name ? ' — ' . $s->job_name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <p class="rounded-lg border border-amber-200 bg-amber-50 p-2.5 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                    This template requires a sale, but no sales exist yet.
                                </p>
                            @endif
                        </div>

                        <button type="button"
                                @click="go()"
                                :disabled="!templateId || (needsSale && !saleId && {{ $opportunitySales->isNotEmpty() ? 'true' : 'false' }})"
                                class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 disabled:opacity-40 disabled:cursor-not-allowed dark:bg-emerald-600 dark:hover:bg-emerald-700">
                            Continue →
                        </button>
                    </div>
                </div>
                @endif

                <button type="button"
                        id="toggle-upload-panel"
                        class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Upload Files
                </button>
            </div>

            <div id="upload-panel"
                 class="hidden mt-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Upload New Document or Media</h2>

                {{-- Drop Zone --}}
                <div id="drop-zone"
                     class="cursor-pointer select-none rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-600 dark:bg-gray-900/30">
                    <svg class="mx-auto mb-2 h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                    </svg>
                    <p class="font-medium text-gray-800 dark:text-gray-100">Drag &amp; drop files here or click to select</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Images, PDFs, Word docs, and more</p>
                    <input id="file-upload-input" type="file" multiple class="hidden">
                </div>

                {{-- Per-file queue --}}
                <div id="file-queue" class="hidden mt-3 space-y-2"></div>

                {{-- Apply-to-all defaults (shown when 2+ files queued) --}}
                <div id="queue-defaults" class="hidden mt-3 rounded-lg border border-blue-100 bg-blue-50 p-3 dark:border-blue-900/40 dark:bg-blue-900/20">
                    <p class="mb-2 text-xs font-semibold text-blue-700 dark:text-blue-300">Apply to all files:</p>
                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                        <div>
                            <select id="global-label-id"
                                    class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">— Apply label to all —</option>
                                @foreach ($labels as $label)
                                    <option value="{{ $label->id }}">{{ $label->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <input type="text"
                                   id="global-description"
                                   class="block w-full rounded-lg border border-gray-300 bg-white p-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                   placeholder="Apply description to all files">
                        </div>
                    </div>
                </div>

                {{-- Progress bar --}}
                <div id="upload-progress-wrap" class="hidden mt-3">
                    <div class="mb-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                        <span id="upload-progress-label">Uploading…</span>
                        <span id="upload-progress-pct">0%</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div id="upload-progress-bar"
                             class="h-2 rounded-full bg-blue-600 transition-all duration-150"
                             style="width: 0%"></div>
                    </div>
                </div>

                {{-- Buttons --}}
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <button type="button"
                            id="upload-submit-btn"
                            class="hidden inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                        Upload
                    </button>
                    <button type="button"
                            id="close-upload-panel"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Cancel
                    </button>
                </div>
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
                        All ({{ $counts['all'] }})
                    </a>

                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) . '?' . http_build_query(array_merge($baseParams, ['type' => 'documents'])) }}"
                       class="{{ $btnBase }} {{ $btnMid }} {{ ($type === 'documents')
                            ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'
                        }}">
                        Documents ({{ $counts['documents'] }})
                    </a>

                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) . '?' . http_build_query(array_merge($baseParams, ['type' => 'media'])) }}"
                       class="{{ $btnBase }} {{ $btnRight }} {{ ($type === 'media')
                            ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-200 dark:text-gray-900 dark:border-gray-200'
                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700'
                        }}">
                        Media ({{ $counts['media'] }})
                    </a>
                </div>

                {{-- Label filter --}}
                <form method="GET"
                      action="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                      class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                    <input type="hidden" name="type" value="{{ $type }}">

                    <div class="w-full sm:max-w-xs">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Search</label>
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="File name or description…"
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-500 dark:focus:ring-blue-500">
                    </div>

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
                            @php
                                $docExt   = strtolower($doc->extension ?? '');
                                $docMime  = $doc->mime_type ?? '';
                                $docType  = match($docExt) {
                                    'pdf'  => 'pdf',
                                    'docx' => 'docx',
                                    default => null,
                                };
                                $isImage  = str_starts_with($docMime, 'image/') || in_array($docExt, ['jpg','jpeg','png','gif','webp','bmp','tiff','heic','heif','avif']);
                                $isVideo  = str_starts_with($docMime, 'video/') || in_array($docExt, ['mp4','mov','avi','mkv','webm','wmv','m4v']);
                                $docUrl   = asset('storage/' . $doc->path);
                            @endphp
                            <tr class="border-t border-gray-200 {{ $doc->trashed() ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-white dark:bg-gray-800' }} hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                @if($docType)
                                    data-doc-id="{{ $doc->id }}"
                                    data-doc-url="{{ $docUrl }}"
                                    data-doc-name="{{ $doc->original_name }}"
                                    data-doc-type="{{ $docType }}"
                                @elseif($isImage)
                                    data-doc-id="{{ $doc->id }}"
                                    data-doc-url="{{ $docUrl }}"
                                    data-doc-name="{{ $doc->original_name }}"
                                    data-doc-type="image"
                                @endif
                            >
                                <td class="px-4 py-3">
                                    <input type="checkbox"
                                           class="doc-checkbox h-4 w-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-blue-600"
                                           value="{{ $doc->id }}"
                                           data-trashed="{{ $doc->trashed() ? '1' : '0' }}">
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if($isImage)
                                            <img src="{{ $docUrl }}"
                                                 alt="{{ $doc->original_name }}"
                                                 loading="lazy"
                                                 decoding="async"
                                                 class="h-10 w-10 rounded object-cover shrink-0 cursor-pointer"
                                                 @if(!$doc->trashed()) onclick="openDocImageViewer('{{ $docUrl }}', '{{ addslashes($doc->original_name) }}')" @endif>
                                        @elseif($isVideo)
                                            <div class="flex h-10 w-10 items-center justify-center rounded bg-gray-800 text-white text-sm shrink-0">▶</div>
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded bg-gray-100 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-200 shrink-0">
                                                {{ strtoupper($docExt ?: 'FILE') }}
                                            </div>
                                        @endif
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
                                    @if ($doc->category === 'generated_document')
                                        <span class="inline-flex items-center rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            Generated
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                            {{ $doc->category_override ?? $doc->category }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $doc->created_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($doc->category === 'generated_document')
                                            @if ($doc->rendered_body)
                                                <a href="{{ route('pages.opportunities.documents.show-generated', [$opportunity->id, $doc->id]) }}"
                                                   class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-200 dark:border-emerald-700 dark:bg-gray-800 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                                                    Open
                                                </a>
                                            @else
                                                {{-- Legacy stored-PDF document --}}
                                                <a href="{{ route('pages.opportunities.documents.reprint', [$opportunity->id, $doc->id]) }}"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 rounded-lg border border-emerald-300 bg-white px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-200 dark:border-emerald-700 dark:bg-gray-800 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                                                    Print
                                                </a>
                                            @endif
                                        @elseif($docType)
                                            <button type="button"
                                                    class="doc-view-btn inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                                                    data-doc-id="{{ $doc->id }}">
                                                View
                                            </button>
                                        @elseif($isImage)
                                            <button type="button"
                                                    onclick="openDocImageViewer('{{ $docUrl }}', '{{ addslashes($doc->original_name) }}')"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                View
                                            </button>
                                        @else
                                            <a href="{{ $docUrl }}"
                                               target="_blank"
                                               class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                                                View
                                            </a>
                                        @endif

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

    {{-- ============================================================ --}}
    {{-- Flooring Sign-Offs Section                                   --}}
    {{-- ============================================================ --}}
    @if ($signOffs->isNotEmpty())
    <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Flooring Selection Sign-Offs</h3>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($signOffs as $signOff)
            <div class="flex items-center justify-between px-4 py-3 {{ $signOff->trashed() ? 'opacity-50' : '' }}">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        Sign-Off #{{ $signOff->id }}
                        @if ($signOff->sale)
                            — Sale #{{ $signOff->sale->sale_number }}{{ $signOff->sale->job_name ? ' · ' . $signOff->sale->job_name : '' }}
                        @endif
                        @if ($signOff->trashed())
                            <span class="ml-1 inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Archived</span>
                        @endif
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $signOff->date?->format('M j, Y') }}
                        &nbsp;·&nbsp;
                        <span @class([
                            'inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium',
                            'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => $signOff->status === 'draft',
                            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $signOff->status === 'finalized',
                        ])>{{ ucfirst($signOff->status) }}</span>
                    </p>
                </div>
                <div class="ml-4 flex shrink-0 items-center gap-2">
                    @if (!$signOff->trashed())
                        <a href="{{ route('pages.opportunities.sign-offs.show', [$opportunity->id, $signOff->id]) }}"
                           class="inline-flex items-center rounded-lg bg-blue-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Edit / View
                        </a>
                        <a href="{{ route('pages.opportunities.sign-offs.pdf', [$opportunity->id, $signOff->id]) }}"
                           target="_blank"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                            PDF
                        </a>
                        <form method="POST"
                              action="{{ route('pages.opportunities.sign-offs.destroy', [$opportunity->id, $signOff->id]) }}"
                              onsubmit="return confirm('Archive this sign-off?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:bg-gray-700 dark:border-red-700 dark:text-red-400 dark:hover:bg-gray-600">
                                Archive
                            </button>
                        </form>
                    @else
                        @if (auth()->user()?->hasRole('admin'))
                            <form method="POST"
                                  action="{{ route('pages.opportunities.sign-offs.forceDestroy', [$opportunity->id, $signOff->id]) }}"
                                  onsubmit="return confirm('Permanently delete this sign-off? This cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-800">
                                    Delete Permanently
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    {{-- ============================================================ --}}

    </div>


    {{-- ============================================================ --}}
    {{-- Image Viewer Modal                                           --}}
    {{-- ============================================================ --}}
    <div id="docImageViewer"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/90 p-4"
         aria-hidden="true"
         onclick="if(event.target===this)closeDocImageViewer()">
        <button type="button"
                onclick="closeDocImageViewer()"
                class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white/10 text-white hover:bg-white/20">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="flex flex-col items-center gap-3 max-w-5xl max-h-full w-full">
            <img id="docImageViewerImg" src="" alt=""
                 class="max-h-[85vh] max-w-full rounded-lg object-contain shadow-2xl">
            <p id="docImageViewerName" class="text-sm text-white/70 truncate max-w-full"></p>
        </div>
    </div>
    {{-- ============================================================ --}}

    {{-- ============================================================ --}}
    {{-- Document Viewer Modal                                        --}}
    {{-- ============================================================ --}}
    <div id="docViewer"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-0 sm:p-6"
         aria-hidden="true">

        {{-- Panel --}}
        <div class="relative flex flex-col w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-5xl sm:w-full sm:rounded-xl bg-white sm:shadow-xl overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-white shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <span id="docViewerBadge"
                          class="shrink-0 inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold uppercase tracking-wide">
                        —
                    </span>
                    <span id="docViewerName"
                          class="truncate text-sm font-medium text-gray-900">
                    </span>
                </div>
                <button type="button"
                        id="docViewerClose"
                        class="ml-4 shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    ✕
                </button>
            </div>

            {{-- Body --}}
            <div class="relative flex-1 overflow-hidden bg-gray-100">

                {{-- Loading spinner --}}
                <div id="docViewerLoading"
                     class="absolute inset-0 flex items-center justify-center bg-gray-100 z-10">
                    <div class="flex flex-col items-center gap-3 text-gray-500">
                        <svg class="animate-spin h-8 w-8 text-blue-600"
                             xmlns="http://www.w3.org/2000/svg"
                             fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm">Loading document…</span>
                    </div>
                </div>

                {{-- PDF renderer (PDF.js renders pages as <canvas> elements here) --}}
                <div id="docViewerPdf"
                     class="hidden w-full overflow-y-auto bg-gray-200 px-4 py-4"
                     style="height: 75vh;">
                </div>

                {{-- Word renderer --}}
                <div id="docViewerWord"
                     class="hidden w-full overflow-y-auto"
                     style="height: 75vh;">
                    <div id="docViewerWordContent"
                         class="max-w-3xl mx-auto bg-white shadow-sm my-6 px-10 py-12 text-sm text-gray-900 leading-relaxed">
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-white shrink-0">
                <div class="flex items-center gap-2">
                    <button type="button"
                            id="docViewerPrev"
                            class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        ← Prev
                    </button>
                    <button type="button"
                            id="docViewerNext"
                            class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Next →
                    </button>
                </div>
                <div id="docViewerCounter"
                     class="text-sm text-gray-500">
                    — / —
                </div>
            </div>

        </div>
    </div>
    {{-- ============================================================ --}}

    <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3/build/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1/mammoth.browser.min.js"></script>
    <script>
        function openDocImageViewer(url, name) {
            const modal = document.getElementById('docImageViewer');
            const img   = document.getElementById('docImageViewerImg');
            const label = document.getElementById('docImageViewerName');
            if (!modal || !img) return;
            img.src = url;
            img.alt = name;
            if (label) label.textContent = name;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeDocImageViewer() {
            const modal = document.getElementById('docImageViewer');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
            document.getElementById('docImageViewerImg').src = '';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeDocImageViewer();
        });

        // Set PDF.js worker before any document interactions
        if (typeof pdfjsLib !== 'undefined') {
            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdn.jsdelivr.net/npm/pdfjs-dist@3/build/pdf.worker.min.js';
        }
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

            // =========================================================
            // Upload panel — per-file queue with XHR progress
            // =========================================================
            const toggleBtn    = document.getElementById('toggle-upload-panel');
            const closeBtn     = document.getElementById('close-upload-panel');
            const uploadPanel  = document.getElementById('upload-panel');
            const dropZone     = document.getElementById('drop-zone');
            const uploadInput  = document.getElementById('file-upload-input');
            const fileQueueEl  = document.getElementById('file-queue');
            const queueDefaults = document.getElementById('queue-defaults');
            const submitBtn    = document.getElementById('upload-submit-btn');
            const progressWrap = document.getElementById('upload-progress-wrap');
            const progressBar  = document.getElementById('upload-progress-bar');
            const progressPct  = document.getElementById('upload-progress-pct');
            const progressLabel = document.getElementById('upload-progress-label');
            const globalLabelEl = document.getElementById('global-label-id');
            const globalDescEl  = document.getElementById('global-description');

            const labelsData = @json($labels->map(fn($l) => ['id' => $l->id, 'name' => $l->name])->values());
            const uploadUrl  = '{{ route('pages.opportunities.documents.store', $opportunity->id) }}';
            const csrfToken  = '{{ csrf_token() }}';

            let fileQueue = []; // [{ file: File, labelId: '', description: '' }]

            function fmtBytes(b) {
                if (b < 1024) return b + ' B';
                if (b < 1048576) return Math.round(b / 1024) + ' KB';
                return (b / 1048576).toFixed(1) + ' MB';
            }

            function buildLabelOpts(sel) {
                let h = '<option value="">\u2014 Label \u2014</option>';
                labelsData.forEach(l => {
                    h += `<option value="${l.id}"${String(l.id) === String(sel) ? ' selected' : ''}>${l.name}</option>`;
                });
                return h;
            }

            function renderQueue() {
                if (!fileQueueEl) return;
                fileQueueEl.innerHTML = '';

                const count = fileQueue.length;
                fileQueueEl.classList.toggle('hidden', count === 0);
                if (queueDefaults) queueDefaults.classList.toggle('hidden', count < 2);
                if (submitBtn) {
                    submitBtn.classList.toggle('hidden', count === 0);
                    submitBtn.innerHTML = count === 0 ? 'Upload'
                        : `<svg class="h-4 w-4 inline-block mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg> Upload ${count} File${count !== 1 ? 's' : ''}`;
                }
                if (count === 0) return;

                fileQueue.forEach((item, i) => {
                    const isImg = item.file.type.startsWith('image/');
                    const objUrl = isImg ? URL.createObjectURL(item.file) : null;

                    const thumb = isImg
                        ? `<img src="${objUrl}" data-obj-url="${objUrl}" class="w-10 h-10 rounded object-cover shrink-0" alt="">`
                        : `<div class="w-10 h-10 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0 text-gray-400 dark:text-gray-300">
                               <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                               </svg>
                           </div>`;

                    const row = document.createElement('div');
                    row.className = 'file-queue-row flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-700/50';
                    row.dataset.index = i;
                    row.innerHTML = `
                        ${thumb}
                        <div class="min-w-0 w-36 lg:w-44 shrink-0">
                            <p class="text-xs font-medium text-gray-900 dark:text-white truncate" title="${item.file.name.replace(/"/g, '&quot;')}">${item.file.name}</p>
                            <p class="text-[11px] text-gray-400">${fmtBytes(item.file.size)}</p>
                        </div>
                        <div class="w-36 shrink-0">
                            <select class="queue-label block w-full rounded-lg border border-gray-300 bg-white p-1.5 text-xs text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" data-index="${i}">
                                ${buildLabelOpts(item.labelId)}
                            </select>
                        </div>
                        <div class="flex-1 min-w-0">
                            <input type="text"
                                   class="queue-desc block w-full rounded-lg border border-gray-300 bg-white p-1.5 text-xs text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                   placeholder="Description (optional)"
                                   value="${item.description.replace(/"/g, '&quot;')}"
                                   data-index="${i}">
                        </div>
                        <div class="w-5 shrink-0 flex items-center justify-center" id="file-status-${i}"></div>
                        <button type="button" class="remove-file shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full text-gray-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/40 dark:hover:text-red-400" data-index="${i}" title="Remove">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>`;
                    fileQueueEl.appendChild(row);
                });

                fileQueueEl.querySelectorAll('.queue-label').forEach(sel => {
                    sel.addEventListener('change', () => { fileQueue[sel.dataset.index].labelId = sel.value; });
                });
                fileQueueEl.querySelectorAll('.queue-desc').forEach(inp => {
                    inp.addEventListener('input', () => { fileQueue[inp.dataset.index].description = inp.value; });
                });
                fileQueueEl.querySelectorAll('.remove-file').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const idx = parseInt(btn.dataset.index);
                        const img = fileQueueEl.querySelector(`img[data-obj-url]`);
                        if (img && fileQueue[idx]?.file?.type?.startsWith('image/')) URL.revokeObjectURL(img.dataset.objUrl);
                        fileQueue.splice(idx, 1);
                        renderQueue();
                    });
                });
            }

            function addFiles(newFiles) {
                Array.from(newFiles).forEach(f => {
                    if (!fileQueue.some(item => item.file.name === f.name && item.file.size === f.size)) {
                        fileQueue.push({ file: f, labelId: '', description: '' });
                    }
                });
                renderQueue();
                uploadInput.value = '';
            }

            function resetPanel() {
                fileQueueEl?.querySelectorAll('img[data-obj-url]').forEach(img => URL.revokeObjectURL(img.dataset.objUrl));
                fileQueue = [];
                renderQueue();
                if (progressWrap) progressWrap.classList.add('hidden');
                if (progressBar) { progressBar.style.width = '0%'; progressBar.style.backgroundColor = ''; }
                if (globalLabelEl) globalLabelEl.value = '';
                if (globalDescEl) globalDescEl.value = '';
                uploadInput.value = '';
            }

            // Toggle panel open/close
            if (toggleBtn && uploadPanel) {
                toggleBtn.addEventListener('click', () => uploadPanel.classList.toggle('hidden'));
            }
            if (closeBtn && uploadPanel) {
                closeBtn.addEventListener('click', () => { uploadPanel.classList.add('hidden'); resetPanel(); });
            }

            // Drop zone
            if (dropZone && uploadInput) {
                dropZone.addEventListener('click', () => uploadInput.click());
                uploadInput.addEventListener('change', () => { if (uploadInput.files.length) addFiles(uploadInput.files); });
                ['dragenter', 'dragover'].forEach(evt => {
                    dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.add('ring-2', 'ring-blue-400'); });
                });
                ['dragleave', 'drop'].forEach(evt => {
                    dropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.remove('ring-2', 'ring-blue-400'); });
                });
                dropZone.addEventListener('drop', e => {
                    const files = e.dataTransfer?.files;
                    if (files?.length) addFiles(files);
                });
            }

            // Apply-to-all label
            if (globalLabelEl) {
                globalLabelEl.addEventListener('change', () => {
                    const val = globalLabelEl.value;
                    fileQueue.forEach(item => item.labelId = val);
                    renderQueue();
                });
            }

            // Apply-to-all description
            if (globalDescEl) {
                globalDescEl.addEventListener('input', () => {
                    const val = globalDescEl.value;
                    fileQueue.forEach(item => item.description = val);
                    fileQueueEl?.querySelectorAll('.queue-desc').forEach(inp => { inp.value = val; });
                });
            }

            // XHR upload with progress
            if (submitBtn) {
                submitBtn.addEventListener('click', () => {
                    if (fileQueue.length === 0 || submitBtn.disabled) return;

                    const fd = new FormData();
                    fd.append('_token', csrfToken);
                    fileQueue.forEach(item => {
                        fd.append('files[]', item.file);
                        fd.append('label_ids[]', item.labelId);
                        fd.append('descriptions[]', item.description);
                    });

                    if (progressWrap) progressWrap.classList.remove('hidden');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Uploading…';

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', uploadUrl);
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

                    xhr.upload.addEventListener('progress', e => {
                        if (!e.lengthComputable) return;
                        const pct = Math.round((e.loaded / e.total) * 100);
                        if (progressBar) progressBar.style.width = pct + '%';
                        if (progressPct) progressPct.textContent = pct + '%';
                        if (progressLabel) progressLabel.textContent = `Uploading ${fileQueue.length} file${fileQueue.length !== 1 ? 's' : ''}…`;
                    });

                    xhr.addEventListener('load', () => {
                        let data = null;
                        try { data = JSON.parse(xhr.responseText); } catch {}

                        if (data?.success) {
                            if (progressBar) progressBar.style.width = '100%';
                            if (progressPct) progressPct.textContent = '100%';
                            if (progressLabel) progressLabel.textContent = `${data.count ?? fileQueue.length} file(s) uploaded!`;
                            setTimeout(() => window.location.reload(), 1200);
                        } else {
                            let msg = data?.message || (data?.errors ? Object.values(data.errors).flat().join(' ') : null) || `Upload failed (HTTP ${xhr.status}).`;
                            if (progressLabel) progressLabel.textContent = msg;
                            if (progressBar) progressBar.style.backgroundColor = '#ef4444';
                            submitBtn.disabled = false;
                        }
                    });

                    xhr.addEventListener('error', () => {
                        if (progressLabel) progressLabel.textContent = 'Upload failed — network error.';
                        if (progressBar) progressBar.style.backgroundColor = '#ef4444';
                        submitBtn.disabled = false;
                    });

                    xhr.send(fd);
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

            // =========================================================
            // Document Viewer
            // =========================================================

            // Build viewable docs array from table rows with data-doc-type
            const viewableDocs = Array.from(
                document.querySelectorAll('tr[data-doc-type]')
            ).map(row => ({
                id:   row.dataset.docId,
                url:  row.dataset.docUrl,
                name: row.dataset.docName,
                type: row.dataset.docType,
            }));

            // Modal elements
            const docViewer          = document.getElementById('docViewer');
            const docViewerBadge     = document.getElementById('docViewerBadge');
            const docViewerName      = document.getElementById('docViewerName');
            const docViewerClose     = document.getElementById('docViewerClose');
            const docViewerPrev      = document.getElementById('docViewerPrev');
            const docViewerNext      = document.getElementById('docViewerNext');
            const docViewerCounter   = document.getElementById('docViewerCounter');
            const docViewerLoading   = document.getElementById('docViewerLoading');
            const docViewerPdf       = document.getElementById('docViewerPdf');
            const docViewerWord      = document.getElementById('docViewerWord');
            const docViewerWordContent = document.getElementById('docViewerWordContent');

            let docCurrentIndex = -1;

            function docShowLoading() {
                docViewerLoading.classList.remove('hidden');
                docViewerPdf.classList.add('hidden');
                docViewerWord.classList.add('hidden');
            }

            async function docOpenPdf(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error(`HTTP ${response.status} fetching PDF`);
                    const arrayBuffer = await response.arrayBuffer();

                    // PDF.js checks data.length (TypedArray), not data.byteLength (ArrayBuffer).
                    // Wrap in Uint8Array so the length property is readable.
                    const pdfData = new Uint8Array(arrayBuffer);
                    if (pdfData.length === 0) {
                        throw new Error('__empty__');
                    }

                    const pdf = await pdfjsLib.getDocument({ data: pdfData }).promise;

                    // Clear any previous render
                    docViewerPdf.innerHTML = '';

                    // Render each page as a canvas, stacked vertically
                    for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                        const page = await pdf.getPage(pageNum);
                        const viewport = page.getViewport({ scale: 1.5 });

                        const canvas = document.createElement('canvas');
                        canvas.width  = viewport.width;
                        canvas.height = viewport.height;
                        canvas.style.display     = 'block';
                        canvas.style.width       = '100%';
                        canvas.style.marginBottom = '6px';
                        canvas.style.background  = '#fff';
                        canvas.style.boxShadow   = '0 1px 3px rgba(0,0,0,.15)';

                        docViewerPdf.appendChild(canvas);
                        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                    }

                    docViewerPdf.scrollTop = 0;
                    docViewerPdf.classList.remove('hidden');
                    docViewerLoading.classList.add('hidden');
                } catch (err) {
                    const msg = (err?.message === '__empty__' || err?.message?.includes('zero bytes'))
                        ? 'This file appears to be empty or was not uploaded correctly. Try re-uploading it.'
                        : `Could not render PDF. Try downloading the file instead. (${err?.message ?? err})`;
                    docViewerPdf.innerHTML = `<p style="color:#b91c1c;padding:1rem;">${msg}</p>`;
                    docViewerPdf.classList.remove('hidden');
                    docViewerLoading.classList.add('hidden');
                }
            }

            async function docOpenWord(url) {
                try {
                    const response = await fetch(url);
                    const arrayBuffer = await response.arrayBuffer();
                    const result = await mammoth.convertToHtml({ arrayBuffer });
                    docViewerWordContent.innerHTML = result.value;
                    docViewerWord.scrollTop = 0;
                    docViewerWord.classList.remove('hidden');
                    docViewerLoading.classList.add('hidden');
                } catch (err) {
                    docViewerWordContent.innerHTML =
                        '<p style="color:#b91c1c;">Failed to load document. Please download it to view.</p>';
                    docViewerWord.classList.remove('hidden');
                    docViewerLoading.classList.add('hidden');
                }
            }

            function docOpen(index) {
                if (index < 0 || index >= viewableDocs.length) return;
                docCurrentIndex = index;
                const doc = viewableDocs[index];

                // Badge
                if (doc.type === 'pdf') {
                    docViewerBadge.textContent = 'PDF';
                    docViewerBadge.style.background = '#fee2e2';
                    docViewerBadge.style.color = '#b91c1c';
                } else {
                    docViewerBadge.textContent = 'Word';
                    docViewerBadge.style.background = '#dbeafe';
                    docViewerBadge.style.color = '#1d4ed8';
                }

                // Filename + counter
                docViewerName.textContent = doc.name;
                docViewerCounter.textContent = `${index + 1} / ${viewableDocs.length}`;

                // Reset renderers
                docShowLoading();
                docViewerWordContent.innerHTML = '';

                // Render
                if (doc.type === 'pdf') {
                    docOpenPdf(doc.url);
                } else {
                    docOpenWord(doc.url);
                }

                // Show modal
                docViewer.classList.remove('hidden');
                docViewer.classList.add('flex');
                document.documentElement.classList.add('overflow-hidden');
            }

            function docClose() {
                docViewer.classList.add('hidden');
                docViewer.classList.remove('flex');
                document.documentElement.classList.remove('overflow-hidden');
                docViewerPdf.innerHTML = '';
                docViewerWordContent.innerHTML = '';
            }

            function docNext() {
                if (!viewableDocs.length) return;
                docOpen((docCurrentIndex + 1) % viewableDocs.length);
            }

            function docPrev() {
                if (!viewableDocs.length) return;
                docOpen((docCurrentIndex - 1 + viewableDocs.length) % viewableDocs.length);
            }

            // View button clicks
            document.querySelectorAll('.doc-view-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const id  = btn.dataset.docId;
                    const idx = viewableDocs.findIndex(d => d.id === id);
                    if (idx !== -1) docOpen(idx);
                });
            });

            // Controls
            docViewerClose?.addEventListener('click', docClose);
            docViewerPrev?.addEventListener('click', docPrev);
            docViewerNext?.addEventListener('click', docNext);

            // Backdrop click — close when clicking the overlay itself, not the panel
            docViewer?.addEventListener('click', e => {
                if (e.target === docViewer) docClose();
            });

            // Keyboard
            document.addEventListener('keydown', e => {
                if (!docViewer || docViewer.classList.contains('hidden')) return;
                if (e.key === 'Escape')     docClose();
                if (e.key === 'ArrowRight') docNext();
                if (e.key === 'ArrowLeft')  docPrev();
            });

            // Swipe — same 40px threshold as media gallery
            let docSwipeStartX = null;

            docViewer?.addEventListener('touchstart', e => {
                if (!e.touches || e.touches.length !== 1) return;
                docSwipeStartX = e.touches[0].clientX;
            }, { passive: true });

            docViewer?.addEventListener('touchend', e => {
                if (docSwipeStartX === null) return;
                const endX = e.changedTouches?.[0]?.clientX ?? docSwipeStartX;
                const diff = endX - docSwipeStartX;
                docSwipeStartX = null;
                if (Math.abs(diff) < 40) return;
                if (diff < 0) docNext();
                else docPrev();
            }, { passive: true });

        });
    </script>
</x-app-layout>
