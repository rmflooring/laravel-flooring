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
    </div>
</x-signing-layout>
