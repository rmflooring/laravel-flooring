<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center gap-4">
                <a href="javascript:history.back()"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Samples from Styles</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $styles->count() }} {{ Str::plural('style', $styles->count()) }} selected</p>
                </div>
            </div>

            <form method="POST" action="{{ route('pages.samples.add-from-styles') }}">
                @csrf

                {{-- Shared options --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm space-y-4">
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Options <span class="font-normal text-gray-400">(applied to all samples)</span></p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Showroom Location <span class="font-normal text-gray-400">— optional</span>
                            </label>
                            <input type="text" name="location" placeholder="e.g. Aisle 3, Shelf B"
                                   value="{{ old('location') }}"
                                   class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Date Received <span class="font-normal text-gray-400">— optional</span>
                            </label>
                            <input type="date" name="received_at"
                                   value="{{ old('received_at') }}"
                                   class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-2">
                        </div>
                    </div>
                </div>

                {{-- Style list --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Styles</p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($styles as $style)
                        <div class="flex items-center gap-4 px-5 py-3">
                            <input type="hidden" name="styles[]" value="{{ $style->id }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $style->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    @if ($style->productLine?->manufacturer){{ $style->productLine->manufacturer }} · @endif
                                    @if ($style->productLine?->name){{ $style->productLine->name }}@endif
                                    @if ($style->color) · {{ $style->color }}@endif
                                    @if ($style->sku) · <span class="font-mono">{{ $style->sku }}</span>@endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <label class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Qty</label>
                                <input type="number" name="qty[{{ $style->id }}]" value="{{ old("qty.{$style->id}", 1) }}"
                                       min="1" max="99"
                                       class="w-16 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-1.5">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Errors --}}
                @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-red-900/20 dark:text-red-400">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="javascript:history.back()"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:ring-green-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create {{ $styles->count() }} {{ Str::plural('Sample', $styles->count()) }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
