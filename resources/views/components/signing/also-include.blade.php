{{--
    "Also include" checklist for the Request E-Signature modals.
    Ticked documents go out in the same email; the client signs them one after
    another (the thank-you page links to the next). Posts also[] = "type:id".

    Props: $documents — collection of ['key', 'label'] from
           DocumentSigningRequestService::signableDocumentsForOpportunity()
           $first     — label of the document this modal is sending (signed first)
--}}
@props(['documents', 'first'])

@if ($documents->isNotEmpty())
    <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700/50" data-also-include>
        <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Also send for signature</div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            Sent together in one email. The client signs <strong>{{ $first }}</strong> first, then is taken straight to the next.
        </p>
        <div class="space-y-1.5">
            @foreach ($documents as $doc)
                <label class="flex items-start gap-2 text-sm text-gray-800 dark:text-gray-200">
                    <input type="checkbox" name="also[]" value="{{ $doc['key'] }}"
                           data-doc-label="{{ \Illuminate\Support\Str::before($doc['label'], ' — ') }}"
                           @checked(in_array($doc['key'], (array) old('also', []), true))
                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>{{ $doc['label'] }}</span>
                </label>
            @endforeach
        </div>
    </div>
@endif
