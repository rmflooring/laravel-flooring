{{-- resources/views/mobile/purchase-orders/show.blade.php --}}
<x-mobile-layout :title="'PO ' . $purchaseOrder->po_number">

    @php
        $statusColors = [
            'pending'   => 'bg-yellow-100 text-yellow-800',
            'ordered'   => 'bg-blue-100 text-blue-800',
            'received'  => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
        $statusColor = $statusColors[$purchaseOrder->status] ?? 'bg-gray-100 text-gray-800';
    @endphp

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- PO Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Purchase Order</p>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $purchaseOrder->po_number }}</h1>
                <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $purchaseOrder->vendor->company_name }}</p>
                @if($purchaseOrder->sale)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Sale #{{ $purchaseOrder->sale->sale_number }}
                        @if($purchaseOrder->sale->customer_name) &mdash; {{ $purchaseOrder->sale->customer_name }} @endif
                    </p>
                @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Stock PO</p>
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                {{ $purchaseOrder->status_label }}
            </span>
        </div>

        @if($purchaseOrder->expected_delivery_date)
            @php $isOverdue = $purchaseOrder->expected_delivery_date->isPast() && $purchaseOrder->status !== 'received'; @endphp
            <div class="mt-3 flex items-center gap-1.5 text-xs {{ $isOverdue ? 'text-red-600' : 'text-gray-500 dark:text-gray-400' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/>
                </svg>
                {{ $isOverdue ? 'Overdue — ' : 'ETA: ' }}{{ $purchaseOrder->expected_delivery_date->format('M j, Y') }}
            </div>
        @endif
    </div>

    {{-- Action cards --}}

    {{-- Receive Inventory --}}
    @can('edit purchase orders')
    @if($purchaseOrder->status === 'ordered')
    <a href="{{ route('pages.purchase-orders.receive.form', $purchaseOrder) }}"
       class="flex items-center gap-4 rounded-xl border-2 border-green-500 bg-green-50 dark:bg-green-900/20 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-600 text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-green-800 dark:text-green-300">Receive Inventory</p>
            <p class="text-xs text-green-600 dark:text-green-400">Confirm quantities and mark as received</p>
        </div>
        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
    @endif
    @endcan

    {{-- View full PO --}}
    <a href="{{ route('pages.purchase-orders.show', $purchaseOrder) }}"
       class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900 dark:text-white">View Full PO</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Details, items, and history</p>
        </div>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Back to Sale --}}
    @if($purchaseOrder->sale)
    <a href="{{ route('pages.sales.show', $purchaseOrder->sale) }}"
       class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40">
            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900 dark:text-white">View Sale #{{ $purchaseOrder->sale->sale_number }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $purchaseOrder->sale->customer_name ?? $purchaseOrder->sale->job_name ?? 'Sale details' }}
            </p>
        </div>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
    @endif

    {{-- Print PDF --}}
    <a href="{{ route('pages.purchase-orders.pdf', $purchaseOrder) }}" target="_blank"
       class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900 dark:text-white">Print PDF</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Open printable purchase order</p>
        </div>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Items summary --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden shadow-sm">
        <div class="border-b border-gray-100 dark:border-gray-700 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                Items ({{ $purchaseOrder->items->count() }})
            </h2>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($purchaseOrder->items as $item)
                <div class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        {{ $item->quantity }} {{ $item->unit }}
                        &nbsp;&middot;&nbsp;
                        ${{ number_format($item->cost_total, 2) }}
                    </p>
                    @if($item->po_notes)
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 whitespace-pre-line">{{ $item->po_notes }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40 px-4 py-3 text-right">
            <span class="text-sm font-bold text-gray-900 dark:text-white">
                Total: ${{ number_format($purchaseOrder->items->sum('cost_total'), 2) }}
            </span>
        </div>
    </div>

</x-mobile-layout>
