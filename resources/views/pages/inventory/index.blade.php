{{-- resources/views/pages/inventory/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        Stock records and available quantities.
                    </p>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalReceipts }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total records</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-teal-600 dark:text-teal-400">{{ $totalInStock }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Records with stock available</div>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-400 dark:text-gray-500">{{ $totalDepleted }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Fully allocated / depleted</div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('pages.inventory.index') }}" class="flex flex-wrap items-end gap-3">

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Record #</label>
                        <input type="number" name="record_id" value="{{ $recordId }}"
                               placeholder="ID…" min="1"
                               class="w-24 rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Product ID</label>
                        <input type="number" name="product_style_id" value="{{ $productStyleId }}"
                               placeholder="Style ID…" min="1"
                               class="w-28 rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search item</label>
                        <input type="text" name="q" value="{{ $q }}"
                               placeholder="Item name…"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Received from</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Received to</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div class="flex items-center gap-2 pb-1">
                        <input type="checkbox" id="show_depleted" name="show_depleted" value="1"
                               {{ $showDepleted ? 'checked' : '' }}
                               class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700">
                        <label for="show_depleted" class="text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">Show depleted</label>
                    </div>

                    <button type="submit"
                            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300 dark:bg-teal-700 dark:hover:bg-teal-800">
                        Filter
                    </button>

                    @if ($recordId || $productStyleId || $q || $dateFrom || $dateTo || $showDepleted)
                        <a href="{{ route('pages.inventory.index') }}"
                           class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Clear
                        </a>
                    @endif

                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">

                @if ($receipts->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                        @if ($recordId || $productStyleId || $q || $dateFrom || $dateTo)
                            No records match your filters.
                        @elseif (! $showDepleted)
                            No stock available. <a href="{{ route('pages.inventory.index', ['show_depleted' => 1]) }}" class="text-teal-600 hover:underline">Show depleted records</a>.
                        @else
                            No inventory records yet. Receive items from a
                            <a href="{{ route('pages.purchase-orders.index') }}" class="text-teal-600 hover:underline">Purchase Order</a>.
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Item</th>
                                    <th class="px-5 py-3 font-medium">Source</th>
                                    <th class="px-5 py-3 font-medium">Received</th>
                                    <th class="px-5 py-3 font-medium text-right">Qty received</th>
                                    <th class="px-5 py-3 font-medium text-right">Allocated</th>
                                    <th class="px-5 py-3 font-medium text-right">Available</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($receipts as $receipt)
                                    @php
                                        $allocated = (float) ($receipt->allocations_sum_quantity ?? 0);
                                        $available = max(0, (float) $receipt->quantity_received - $allocated);
                                        $isDepleted = $available <= 0;
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 cursor-pointer {{ $isDepleted ? 'opacity-60' : '' }}"
                                        onclick="window.location='{{ route('pages.inventory.show', $receipt) }}'"
                                        >

                                        {{-- Item name + unit --}}
                                        <td class="px-5 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $receipt->item_name }}
                                            </div>
                                            @if ($receipt->unit)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $receipt->unit }}</div>
                                            @endif
                                            @if ($receipt->product_style_id)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">Product ID: {{ $receipt->product_style_id }}</div>
                                            @endif
                                            @if ($receipt->notes)
                                                <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs" title="{{ $receipt->notes }}">
                                                    {{ $receipt->notes }}
                                                </div>
                                            @endif
                                        </td>

                                        {{-- Source --}}
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            @if ($receipt->purchaseOrder)
                                                <a href="{{ route('pages.purchase-orders.show', $receipt->purchaseOrder) }}"
                                                   class="text-blue-600 hover:underline dark:text-blue-400 font-medium">
                                                    PO {{ $receipt->purchaseOrder->po_number }}
                                                </a>
                                                @if ($receipt->purchaseOrder->vendor_order_number)
                                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                                        VON {{ $receipt->purchaseOrder->vendor_order_number }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">Manual</span>
                                            @endif
                                        </td>

                                        {{-- Received date --}}
                                        <td class="px-5 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ $receipt->received_date?->format('M j, Y') }}
                                        </td>

                                        {{-- Qty received --}}
                                        <td class="px-5 py-3 text-right font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ rtrim(rtrim(number_format((float)$receipt->quantity_received, 2), '0'), '.') }}
                                            <span class="text-xs text-gray-400 font-normal">{{ $receipt->unit }}</span>
                                        </td>

                                        {{-- Allocated --}}
                                        <td class="px-5 py-3 text-right whitespace-nowrap {{ $allocated > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">
                                            {{ $allocated > 0 ? rtrim(rtrim(number_format($allocated, 2), '0'), '.') : '—' }}
                                        </td>

                                        {{-- Available --}}
                                        <td class="px-5 py-3 text-right whitespace-nowrap">
                                            @if ($isDepleted)
                                                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Depleted
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-teal-100 dark:bg-teal-900/30 px-2.5 py-0.5 text-xs font-semibold text-teal-700 dark:text-teal-400">
                                                    {{ rtrim(rtrim(number_format($available, 2), '0'), '.') }} {{ $receipt->unit }}
                                                </span>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if ($receipts->hasPages())
                        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-4">
                            {{ $receipts->links() }}
                        </div>
                    @endif
                @endif

            </div>

        </div>
    </div>
</x-app-layout>
