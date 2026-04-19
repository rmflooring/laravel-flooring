{{-- resources/views/mobile/inventory/index.blade.php --}}
<x-mobile-layout title="Stock / Inventory">

    <a href="{{ route('mobile.warehouse.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Warehouse
    </a>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.inventory.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search item name…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
            <input type="hidden" name="show_depleted" value="0">
            <input type="checkbox" name="show_depleted" value="1" {{ $showDepleted ? 'checked' : '' }}
                   onchange="this.form.submit()"
                   class="rounded border-gray-300 text-teal-600 focus:ring-teal-500">
            Show depleted items
        </label>
    </form>

    @if ($receipts->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <p class="text-sm">No inventory items found</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($receipts as $receipt)
                <a href="{{ route('mobile.inventory.show', $receipt) }}"
                   class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50">
                    {{-- Availability indicator --}}
                    <div class="shrink-0 w-2.5 h-2.5 rounded-full {{ $receipt->available_qty > 0 ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $receipt->item_name }}</p>
                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                            @if ($receipt->purchaseOrder?->vendor)
                                <span class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $receipt->purchaseOrder->vendor->company_name }}</span>
                            @endif
                            @if ($receipt->received_date)
                                <span class="text-xs text-gray-400">{{ $receipt->received_date->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-bold {{ $receipt->available_qty > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-400' }}">
                            {{ rtrim(rtrim(number_format((float)$receipt->available_qty, 2), '0'), '.') }}
                        </p>
                        <p class="text-xs text-gray-400">/ {{ rtrim(rtrim(number_format((float)$receipt->quantity_received, 2), '0'), '.') }} {{ $receipt->unit }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        @if ($receipts->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($receipts->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $receipts->previousPageUrl() }}" class="text-sm text-teal-600 dark:text-teal-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $receipts->currentPage() }} / {{ $receipts->lastPage() }}</span>
                @if ($receipts->hasMorePages())
                    <a href="{{ $receipts->nextPageUrl() }}" class="text-sm text-teal-600 dark:text-teal-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
