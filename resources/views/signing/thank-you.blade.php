<x-signing-layout title="Document Signed">
    <div class="text-center py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Document Signed</h1>
        <p class="text-gray-600 mb-2">
            Thank you, <strong>{{ $signingRequest->client_name }}</strong>.
        </p>
        <p class="text-gray-500 text-sm">
            A copy of your signed document has been sent to <strong>{{ $signingRequest->client_email }}</strong>.
        </p>

        @if ($nextRequest)
            <div class="mt-8 mx-auto max-w-md rounded-xl border border-blue-200 bg-blue-50 p-6 text-left">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 mb-1">{{ $remainingCount > 1 ? 'Next — ' . $remainingCount . ' documents left to sign' : 'One more document to sign' }}</p>
                <p class="text-lg font-semibold text-gray-900 mb-4">{{ $nextRequest->documentLabel() }}</p>
                <a href="{{ route('sign.show', $nextRequest->uuid) }}"
                   class="block w-full rounded-lg bg-blue-700 px-5 py-3 text-center text-base font-semibold text-white hover:bg-blue-800">
                    Review &amp; Sign Next Document →
                </a>
            </div>
        @endif

        @if (! $nextRequest && $signingRequest->isBatched())
            <p class="mt-6 text-sm font-medium text-green-700">That's everything — all your documents are signed.</p>
        @endif
    </div>
</x-signing-layout>
