<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('pages.samples.index') }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Print Batch Labels</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Avery 5163/8163 · 10 labels per page</p>
                </div>
            </div>

            <form method="POST" action="{{ route('pages.samples.batch-label') }}" target="_blank">
                @csrf

                {{-- Options --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Show pricing on labels</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Uncheck to print labels without prices</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_price" value="1" class="sr-only peer"
                               {{ $showPrice ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                {{-- Samples --}}
                @if ($samples->count())
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Individual Samples
                            <span class="ml-1.5 text-xs font-normal text-gray-400">({{ $samples->count() }})</span>
                        </p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($samples as $s)
                        <div class="flex items-center gap-4 px-5 py-3">
                            {{-- Hidden sample ID --}}
                            <input type="hidden" name="samples[]" value="{{ $s->id }}">
                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $s->sample_id }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $s->productStyle->name }}
                                    @if ($s->productStyle->productLine?->manufacturer)
                                        · {{ $s->productStyle->productLine->manufacturer }}
                                    @endif
                                    @if ($s->productStyle->color)
                                        · {{ $s->productStyle->color }}
                                    @endif
                                </p>
                            </div>
                            {{-- Qty --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <label class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Copies</label>
                                <input type="number" name="qty[s_{{ $s->id }}]" value="1" min="1" max="20"
                                       class="w-16 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-1.5">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Sample Sets --}}
                @if ($sets->count())
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/20">
                        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                            Sample Sets
                            <span class="ml-1.5 text-xs font-normal text-indigo-400">({{ $sets->count() }})</span>
                        </p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($sets as $set)
                        <div class="flex items-center gap-4 px-5 py-3">
                            <input type="hidden" name="sets[]" value="{{ $set->id }}">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $set->set_id }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $set->name ?? $set->productLine->name }}
                                    @if ($set->productLine?->manufacturer)
                                        · {{ $set->productLine->manufacturer }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <label class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Copies</label>
                                <input type="number" name="qty[set_{{ $set->id }}]" value="1" min="1" max="20"
                                       class="w-16 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-1.5">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Label count summary --}}
                @php
                    $totalItems = $samples->count() + $sets->count();
                    $minLabels  = $totalItems;
                    $pages      = ceil($minLabels / 10);
                @endphp
                <p class="text-xs text-gray-500 dark:text-gray-400 text-right">
                    {{ $totalItems }} {{ Str::plural('item', $totalItems) }} selected
                    ({{ $pages }} {{ Str::plural('page', $pages) }} minimum at 1 copy each)
                </p>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('pages.samples.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                        </svg>
                        Generate PDF
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
