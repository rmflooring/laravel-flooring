{{-- resources/views/pages/warehouse/receive-lookup.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Receive Inventory</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Enter a PO number from the packing slip, or select a PO from the list below.
                </p>
            </div>

            {{-- PO# Lookup --}}
            <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Look up by PO number</h2>
                </div>
                <div class="p-6">
                    <form method="GET" action="{{ route('pages.warehouse.receive') }}"
                          class="flex items-start gap-3">
                        <div class="flex-1 max-w-xs">
                            <input type="text"
                                   name="q"
                                   value="{{ $q }}"
                                   placeholder="e.g. 3-8 or 4"
                                   autofocus
                                   autocomplete="off"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-green-500">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Find PO
                        </button>
                    </form>

                    @if($error)
                        <div class="mt-3 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                            </svg>
                            {{ $error }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Ordered POs quick-pick list --}}
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Awaiting receipt</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                All POs currently in <span class="font-medium text-blue-600 dark:text-blue-400">Ordered</span> status — sorted by expected delivery date.
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                            {{ $orderedPos->count() }} PO{{ $orderedPos->count() !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>

                @if($orderedPos->isEmpty())
                    <div class="px-6 py-10 text-center">
                        <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No POs are currently awaiting receipt.</p>
                    </div>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($orderedPos as $po)
                            @php
                                $isOverdue = $po->expected_delivery_date && $po->expected_delivery_date->isPast();
                                $isDueToday = $po->expected_delivery_date && $po->expected_delivery_date->isToday();
                            @endphp
                            <div class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30">

                                {{-- PO info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $po->po_number }}
                                        </span>
                                        @if($po->sale)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Sale #{{ $po->sale->sale_number }}
                                                @if($po->sale->customer_name)
                                                    &mdash; {{ $po->sale->customer_name }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                Stock PO
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $po->vendor->company_name }}</span>
                                        <span>{{ $po->items->count() }} item{{ $po->items->count() !== 1 ? 's' : '' }}</span>
                                        @if($po->vendor_order_number)
                                            <span>Vendor #{{ $po->vendor_order_number }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- ETA --}}
                                <div class="hidden sm:block text-right shrink-0">
                                    @if($po->expected_delivery_date)
                                        <p class="text-xs font-medium
                                            {{ $isOverdue ? 'text-red-600 dark:text-red-400' : ($isDueToday ? 'text-amber-600 dark:text-amber-400' : 'text-gray-700 dark:text-gray-300') }}">
                                            {{ $isOverdue ? 'Overdue' : ($isDueToday ? 'Due today' : 'ETA') }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $po->expected_delivery_date->format('M j, Y') }}
                                        </p>
                                    @else
                                        <p class="text-xs text-gray-400 dark:text-gray-500">No ETA</p>
                                    @endif
                                </div>

                                {{-- Receive button --}}
                                @can('edit purchase orders')
                                <a href="{{ route('pages.purchase-orders.receive.form', $po) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg bg-green-700 px-3 py-2 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Receive
                                </a>
                                @endcan

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
