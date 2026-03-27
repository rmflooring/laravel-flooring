<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Document Template</h1>
                <a href="{{ route('admin.document-templates.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    Cancel
                </a>
            </div>

            <form method="POST" action="{{ route('admin.document-templates.store') }}" class="space-y-6">
                @csrf
                @include('admin.document-templates._form', ['template' => null])

                <div class="flex justify-end">
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Create Template
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
