{{-- resources/views/mobile/inventory/show.blade.php --}}
<x-mobile-layout :title="$receipt->item_name">

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-4 py-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Inventory Item</p>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">{{ $receipt->item_name }}</h1>

        @if ($receipt->purchaseOrder)
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 font-medium">
                {{ $receipt->purchaseOrder->vendor->company_name ?? '—' }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                PO {{ $receipt->purchaseOrder->po_number }}
                @if ($receipt->purchaseOrder->sale)
                    &nbsp;&middot;&nbsp; Sale #{{ $receipt->purchaseOrder->sale->sale_number }}
                @endif
            </p>
        @endif
    </div>

    {{-- Qty summary --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-4 py-4 text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Received</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ rtrim(rtrim(number_format((float)$receipt->quantity_received, 2), '0'), '.') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $receipt->unit }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 px-4 py-4 text-center
            {{ $receipt->available_qty > 0
                ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                : 'bg-gray-50 dark:bg-gray-800' }}">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Available</p>
            <p class="text-3xl font-bold {{ $receipt->available_qty > 0 ? 'text-green-700 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                {{ rtrim(rtrim(number_format((float)$receipt->available_qty, 2), '0'), '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $receipt->unit }}</p>
        </div>
    </div>

    {{-- Receipt details --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-4 py-4 space-y-2">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">Receipt Details</p>
        <div class="flex justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Receipt #</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $receipt->id }}</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Date received</span>
            <span class="font-medium text-gray-900 dark:text-white">{{ $receipt->received_date->format('M j, Y') }}</span>
        </div>
        @if ($receipt->notes)
            <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $receipt->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Allocations --}}
    @if ($receipt->allocations->isNotEmpty())
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
            <div class="border-b border-gray-100 dark:border-gray-700 px-4 py-3">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Allocated to</h2>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($receipt->allocations as $alloc)
                    <div class="px-4 py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                Sale #{{ $alloc->sale->sale_number ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $alloc->sale->customer_name ?? $alloc->sale->job_name ?? '' }}
                            </p>
                        </div>
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                            {{ rtrim(rtrim(number_format((float)$alloc->quantity, 2), '0'), '.') }} {{ $receipt->unit }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/20 px-4 py-3">
            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Unallocated — available for any sale</p>
        </div>
    @endif

    {{-- Links --}}
    @if ($receipt->purchaseOrder)
        <a href="{{ route('mobile.purchase-orders.show', $receipt->purchaseOrder) }}"
           class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-gray-900 dark:text-white">View Purchase Order</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">PO {{ $receipt->purchaseOrder->po_number }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @endif

</x-mobile-layout>
