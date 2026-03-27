<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Template</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $documentTemplate->name }}</p>
                </div>
                <a href="{{ route('admin.document-templates.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    Back
                </a>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
                    <button onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400">✕</button>
                </div>
            @endif

            @if ($usageCount > 0)
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    This template has been used to generate <strong>{{ $usageCount }}</strong> document(s). Changes will only affect future generations.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.document-templates.update', $documentTemplate) }}" class="space-y-6">
                @csrf
                @method('PUT')
                @include('admin.document-templates._form', ['template' => $documentTemplate])

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
