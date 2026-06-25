<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6"
             x-data="{
                 topOffset: 0,
                 leftOffset: 0,
                 init() {
                     this.topOffset  = parseFloat(localStorage.getItem('label_top_offset')  ?? 0);
                     this.leftOffset = parseFloat(localStorage.getItem('label_left_offset') ?? 0);
                 },
                 save() {
                     localStorage.setItem('label_top_offset',  this.topOffset);
                     localStorage.setItem('label_left_offset', this.leftOffset);
                 },
                 reset() {
                     this.topOffset = 0; this.leftOffset = 0; this.save();
                 }
             }">

            {{-- Header --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('pages.samples.index') }}"
                   class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Print Product Line Labels</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Avery 5163/8163 · 10 labels per page · QR links to all colors</p>
                </div>
            </div>

            {{-- Filter form (GET) --}}
            <form method="GET" action="{{ route('pages.samples.product-line-labels.form') }}"
                  class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text" name="search" value="{{ $search }}"
                               placeholder="Search by name or manufacturer…"
                               class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                    </div>
                    <div class="sm:w-48">
                        <select name="type_id"
                                class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                            <option value="">All categories</option>
                            @foreach ($productTypes as $pt)
                                <option value="{{ $pt->id }}" {{ $typeId == $pt->id ? 'selected' : '' }}>
                                    {{ $pt->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-md hover:bg-gray-800 focus:ring-4 focus:ring-gray-300 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        Filter
                    </button>
                </div>
            </form>

            {{-- Print form (POST) --}}
            <form method="POST" action="{{ route('pages.samples.product-line-labels') }}" target="_blank">
                @csrf

                {{-- Pass current filter context so validation errors can redirect back properly --}}
                <input type="hidden" name="_search" value="{{ $search }}">
                <input type="hidden" name="_type_id" value="{{ $typeId }}">

                {{-- Show price toggle --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Show pricing on labels</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Uses the product line default price</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_price" value="1" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                {{-- Alignment offsets --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Label alignment offsets</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Shift labels to compensate for printer margins. Values saved per browser.</p>
                        </div>
                        <button type="button" @click="reset()"
                                class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 underline">
                            Reset
                        </button>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Top offset (mm)
                                <span class="font-normal text-gray-400">— positive = move down</span>
                            </label>
                            <input type="number" name="top_offset_mm" step="0.5" min="-20" max="20"
                                   x-model="topOffset" @change="save()"
                                   class="w-full text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-2">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Left offset (mm)
                                <span class="font-normal text-gray-400">— positive = move right</span>
                            </label>
                            <input type="number" name="left_offset_mm" step="0.5" min="-20" max="20"
                                   x-model="leftOffset" @change="save()"
                                   class="w-full text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-2">
                        </div>
                    </div>
                </div>

                {{-- Product lines list --}}
                @if ($productLines->isEmpty())
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center text-sm text-gray-400">
                        No active product lines found{{ $search || $typeId ? ' matching your filter' : '' }}.
                    </div>
                @else
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-green-50 dark:bg-green-900/10 flex items-center justify-between">
                        <p class="text-sm font-semibold text-green-700 dark:text-green-300">
                            Product Lines
                            <span class="ml-1.5 text-xs font-normal text-green-500">({{ $productLines->count() }})</span>
                        </p>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-500 dark:text-gray-400 select-none"
                               x-data
                               @click.prevent="
                                   const boxes = $el.closest('form').querySelectorAll('input[name=\'lines[]\']');
                                   const allChecked = [...boxes].every(b => b.checked);
                                   boxes.forEach(b => b.checked = !allChecked);
                               ">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" @click.stop>
                            Select all
                        </label>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($productLines as $pl)
                        <div class="flex items-center gap-4 px-5 py-3">
                            {{-- Checkbox --}}
                            <input type="checkbox" name="lines[]" value="{{ $pl->id }}"
                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 flex-shrink-0">

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $pl->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    @if ($pl->manufacturer){{ $pl->manufacturer }}@endif
                                    @if ($pl->productType?->name) &middot; {{ $pl->productType->name }}@endif
                                    @if ($pl->product_styles_count)
                                        &middot; {{ $pl->product_styles_count }} {{ Str::plural('color', $pl->product_styles_count) }}
                                    @endif
                                </p>
                            </div>

                            {{-- Price --}}
                            @if ($pl->default_sell_price)
                                <p class="text-sm font-semibold text-blue-600 dark:text-blue-400 flex-shrink-0 tabular-nums">
                                    ${{ number_format($pl->default_sell_price, 2) }}
                                </p>
                            @endif

                            {{-- Copies --}}
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <label class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Copies</label>
                                <input type="number" name="qty[l_{{ $pl->id }}]" value="1" min="1" max="20"
                                       class="w-16 text-sm text-center border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-blue-500 focus:border-blue-500 p-1.5">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('pages.samples.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 disabled:opacity-50">
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
