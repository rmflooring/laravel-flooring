{{-- resources/views/pages/opportunities/documents/create-generated.blade.php --}}
<x-app-layout>
    <div class="max-w-screen-lg mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
               class="hover:text-blue-600 dark:hover:text-blue-400">
                Opportunity #{{ $opportunity->job_no ?? $opportunity->id }}
            </a>
            <span>/</span>
            <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
               class="hover:text-blue-600 dark:hover:text-blue-400">Documents</a>
            <span>/</span>
            <span class="text-gray-800 dark:text-white">{{ $document ? 'Edit' : 'Create' }}: {{ $template->name }}</span>
        </nav>

        {{-- Header card --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $document ? 'Edit Document' : 'Create Document' }}
                    </h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Template: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $template->name }}</span>
                        @if ($template->description)
                            &nbsp;·&nbsp; {{ $template->description }}
                        @endif
                    </p>
                </div>
                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    ← Back
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Form --}}
        <form method="POST"
              action="{{ $document
                  ? route('pages.opportunities.documents.update-generated', [$opportunity->id, $document->id])
                  : route('pages.opportunities.documents.store-generated', $opportunity->id) }}">
            @csrf
            @if ($document)
                @method('PUT')
            @endif

            <input type="hidden" name="template_id" value="{{ $template->id }}">

            <div class="space-y-6">

                {{-- Document Name --}}
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Document</h2>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Document Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="document_name"
                               value="{{ old('document_name', $document?->original_name ?? $template->name) }}"
                               required
                               class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">This name appears in the Documents list.</p>
                    </div>
                </div>

                {{-- Sale selector (needs_sale templates) --}}
                @if ($template->needs_sale)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sale</h2>
                    @if ($opportunitySales->isNotEmpty())
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Associated Sale <span class="text-red-500">*</span>
                            </label>
                            <select name="sale_id" required
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">— Choose a sale —</option>
                                @foreach ($opportunitySales as $s)
                                    <option value="{{ $s->id }}"
                                        {{ old('sale_id', $sale?->id) == $s->id ? 'selected' : '' }}>
                                        Sale #{{ $s->sale_number }}{{ $s->job_name ? ' — ' . $s->job_name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            This template requires a sale, but no sales exist for this opportunity yet.
                        </div>
                    @endif
                </div>
                @else
                    <input type="hidden" name="sale_id" value="">
                @endif

                {{-- Field inputs --}}
                @if (count($fields) > 0)
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Fields</h2>
                    <p class="mb-4 text-xs text-gray-400 dark:text-gray-500">Pre-filled from the opportunity. Edit any field as needed before saving.</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($fields as $key => $value)
                            @php
                                $label = $tagLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                                $isTextarea = in_array($key, ['job_site_address']);
                            @endphp

                            <div class="{{ $isTextarea ? 'sm:col-span-2' : '' }}">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $label }}
                                </label>
                                @if ($isTextarea)
                                    <textarea name="fields[{{ $key }}]"
                                              rows="3"
                                              class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500">{{ old('fields.' . $key, $value) }}</textarea>
                                @else
                                    <input type="text"
                                           name="fields[{{ $key }}]"
                                           value="{{ old('fields.' . $key, $value) }}"
                                           class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500 dark:focus:ring-blue-500">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 dark:focus:ring-emerald-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $document ? 'Save Changes' : 'Save Document' }}
                    </button>
                </div>

            </div>
        </form>

    </div>
</x-app-layout>
