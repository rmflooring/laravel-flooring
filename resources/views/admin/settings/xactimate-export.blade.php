<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Excel Export (Xactimate)</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Set the XA Cat, XA Selector and an optional description for each material type. These are filled in whenever an estimate is exported to Excel.
                    </p>
                </div>
                <a href="{{ route('admin.settings') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Back to Settings</a>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Info banner --}}
            <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>
                    These apply to <strong>material</strong> rows only. Leave a field blank to keep the current behaviour:
                    blank XA columns, and the item's own description.
                    If <strong>Override Description</strong> is filled in, it replaces the description for every material of that type
                    (e.g. Laminate &rarr; "As per independent analysis").
                </span>
            </div>

            @if ($productTypes->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    No product types found.
                </div>
            @else

            <form method="POST" action="{{ route('admin.settings.xactimate-export.update') }}">
                @csrf

                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3 w-48">Material Type</th>
                                <th class="px-4 py-3 w-36">XA Cat</th>
                                <th class="px-4 py-3 w-36">XA Selector</th>
                                <th class="px-4 py-3">Override Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($productTypes as $type)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $type->name }}
                                    @if ($type->status && $type->status !== 'active')
                                        <span class="ml-1 text-xs font-normal text-gray-400">({{ $type->status }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" maxlength="50"
                                           name="types[{{ $type->id }}][xa_cat]"
                                           value="{{ old("types.{$type->id}.xa_cat", $type->xa_cat) }}"
                                           class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" maxlength="50"
                                           name="types[{{ $type->id }}][xa_selector]"
                                           value="{{ old("types.{$type->id}.xa_selector", $type->xa_selector) }}"
                                           class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="text" maxlength="500"
                                           name="types[{{ $type->id }}][xa_description_override]"
                                           value="{{ old("types.{$type->id}.xa_description_override", $type->xa_description_override) }}"
                                           placeholder="Leave blank to use the item's description"
                                           class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>

            @endif
        </div>
    </div>
</x-app-layout>
