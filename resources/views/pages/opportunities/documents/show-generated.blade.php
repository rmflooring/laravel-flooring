{{-- resources/views/pages/opportunities/documents/show-generated.blade.php --}}
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
            <span class="text-gray-800 dark:text-white">{{ $document->original_name }}</span>
        </nav>

        @if (session('success'))
            <div class="mb-4 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400" role="alert">
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- Header toolbar --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $document->original_name }}</h1>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    @if ($template)
                        Template: <span class="font-medium">{{ $template->name }}</span>
                        &nbsp;·&nbsp;
                    @endif
                    Created {{ $document->created_at?->format('M j, Y g:ia') }}
                    @if ($document->updated_at && $document->updated_at->ne($document->created_at))
                        &nbsp;·&nbsp; Updated {{ $document->updated_at->format('M j, Y g:ia') }}
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('pages.opportunities.documents.edit-generated', [$opportunity->id, $document->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                    Edit Fields
                </a>

                <a href="{{ route('pages.opportunities.documents.pdf', [$opportunity->id, $document->id]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                    </svg>
                    Print / PDF
                </a>

                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600">
                    ← Documents
                </a>
            </div>
        </div>

        {{-- Document preview --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            {{-- Preview label --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-2.5 dark:border-gray-700">
                <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Document Preview</span>
                <span class="inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    Generated
                </span>
            </div>

            {{-- Rendered content --}}
            <div class="p-6 sm:p-10">
                <div class="mx-auto max-w-[800px] rounded border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-600 dark:bg-gray-900"
                     style="font-family: DejaVu Sans, sans-serif; font-size: 13px; line-height: 1.5; color: #111;">
                    {!! $document->rendered_body !!}
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
