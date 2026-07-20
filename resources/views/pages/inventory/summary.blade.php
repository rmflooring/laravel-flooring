{{-- resources/views/pages/inventory/summary.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Stock Summary</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Total available quantity per product line and style, across all receiving batches.
                    </p>
                </div>
                <a href="{{ route('pages.inventory.index') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    View All Records
                </a>
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalStyles }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Styles in stock</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">
                        {{ rtrim(rtrim(number_format($totalUnitsAvailable, 2), '0'), '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total units available</div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('pages.inventory.summary') }}" class="flex flex-wrap items-end gap-3">

                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="q" value="{{ $q }}"
                               placeholder="Manufacturer, product line, or style…"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="flex items-center gap-2 pb-1">
                        <input type="checkbox" id="show_zero_stock" name="show_zero_stock" value="1"
                               {{ $showZeroStock ? 'checked' : '' }}
                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700">
                        <label for="show_zero_stock" class="text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">Include out-of-stock styles</label>
                    </div>

                    <button type="submit"
                            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300 dark:bg-teal-700 dark:hover:bg-teal-800">
                        Filter
                    </button>

                    @if ($q || $showZeroStock)
                        <a href="{{ route('pages.inventory.summary') }}"
                           class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Clear
                        </a>
                    @endif

                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden" id="style-summary">

                @if ($summary->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                        @if ($q)
                            No styles match your search.
                        @else
                            No stock available. <a href="{{ route('pages.inventory.summary', ['show_zero_stock' => 1]) }}" class="text-teal-600 hover:underline">Include out-of-stock styles</a>.
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Manufacturer</th>
                                    <th class="px-5 py-3 font-medium">Product Line</th>
                                    <th class="px-5 py-3 font-medium">Style</th>
                                    <th class="px-5 py-3 font-medium text-right">Received</th>
                                    <th class="px-5 py-3 font-medium text-right">Available</th>
                                    <th class="px-5 py-3 font-medium text-right">Batches</th>
                                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($summary as $row)
                                    @php $isDepleted = $row->total_available <= 0; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $isDepleted ? 'opacity-60' : '' }}">
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                            {{ $row->manufacturer ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 dark:text-gray-400">
                                            {{ $row->line_name ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $row->style_name ?: '—' }}
                                            </div>
                                            @if ($row->sku || $row->style_number || $row->color)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                                    {{ implode(' · ', array_filter([$row->sku, $row->style_number, $row->color])) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ rtrim(rtrim(number_format($row->total_received, 2), '0'), '.') }} {{ $row->unit }}
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            @if ($isDepleted)
                                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Out of stock
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-teal-100 dark:bg-teal-900/30 px-2.5 py-0.5 text-xs font-semibold text-teal-700 dark:text-teal-400">
                                                    {{ rtrim(rtrim(number_format($row->total_available, 2), '0'), '.') }} {{ $row->unit }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ $row->record_count }}
                                        </td>
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            <a href="{{ route('pages.inventory.index', ['product_style_id' => $row->product_style_id, 'show_depleted' => 1]) }}"
                                               class="text-teal-600 hover:underline dark:text-teal-400">
                                                View Records
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>

            {{-- Unlinked RFC stock --}}
            @if($unlinkedSummary->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <h2 class="font-semibold text-gray-900 dark:text-white text-sm">RFC Returns — No Style Linked</h2>
                    <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">
                        {{ $unlinkedSummary->count() }} {{ Str::plural('item', $unlinkedSummary->count()) }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">These returns have no product style linked — use Edit on the individual record to link one.</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                        <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <tr>
                                <th class="px-5 py-3 font-medium">Item</th>
                                <th class="px-5 py-3 font-medium text-right">Received</th>
                                <th class="px-5 py-3 font-medium text-right">Available</th>
                                <th class="px-5 py-3 font-medium text-right">Batches</th>
                                <th class="px-5 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($unlinkedSummary as $row)
                                @php $isDepleted = $row->total_available <= 0; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $isDepleted ? 'opacity-60' : '' }}">
                                    <td class="px-5 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $row->item_name }}</div>
                                        <div class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">No style linked</div>
                                    </td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ rtrim(rtrim(number_format($row->total_received, 2), '0'), '.') }} {{ $row->unit }}
                                    </td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        @if($isDepleted)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">Out of stock</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-teal-100 dark:bg-teal-900/30 px-2.5 py-0.5 text-xs font-semibold text-teal-700 dark:text-teal-400">
                                                {{ rtrim(rtrim(number_format($row->total_available, 2), '0'), '.') }} {{ $row->unit }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap text-gray-500 dark:text-gray-400">
                                        {{ $row->record_count }}
                                    </td>
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('pages.inventory.index', ['q' => $row->item_name, 'show_depleted' => 1]) }}"
                                           class="text-teal-600 hover:underline dark:text-teal-400">
                                            View Records
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
