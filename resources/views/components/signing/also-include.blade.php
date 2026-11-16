{{--
    "Also send for signature" checklist for the Request E-Signature modal.
    Ticked documents go out in the same email; the client signs them one after
    another (the thank-you page links to the next). Posts also[] = "type:id".

    Props: $documents — collection of ['key', 'label', 'status'] from
           DocumentSigningRequestService::signableDocumentsForOpportunity(..., includeUnavailable: true)
           (status null = can be sent; 'pending' / 'signed' = shown greyed out)
           $first     — label of the document this modal is sending (signed first)
--}}
@props(['documents', 'first'])

<div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-700/50" data-also-include>
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Also send for signature</div>

    @if ($documents->isEmpty())
        <p class="text-xs text-gray-500 dark:text-gray-400">
            There are no other signable documents on this job yet. Create a Flooring Selection sign-off or a
            Work Authorization Form first, and it will show up here so you can send them together.
        </p>
    @else
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
            Sent together in one email. The client signs <strong>{{ $first }}</strong> first, then is taken straight to the next.
        </p>
        <div class="space-y-1.5">
            @foreach ($documents as $doc)
                @php $unavailable = ! empty($doc['status']); @endphp
                <label class="flex items-start gap-2 text-sm {{ $unavailable ? 'text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200' }}">
                    <input type="checkbox" name="also[]" value="{{ $doc['key'] }}"
                           data-doc-label="{{ \Illuminate\Support\Str::before($doc['label'], ' — ') }}"
                           @checked(! $unavailable && in_array($doc['key'], (array) old('also', []), true))
                           @disabled($unavailable)
                           class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">
                    <span>
                        {{ $doc['label'] }}
                        @if ($doc['status'] === 'signed')
                            <span class="ml-1 text-xs font-medium text-green-700">· already signed</span>
                        @elseif ($doc['status'] === 'pending')
                            <span class="ml-1 text-xs font-medium text-amber-700">· awaiting signature</span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>
    @endif
</div>
