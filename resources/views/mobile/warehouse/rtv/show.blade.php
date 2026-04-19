{{-- resources/views/mobile/warehouse/rtv/show.blade.php --}}
<x-mobile-layout :title="$inventoryReturn->return_number">

    @php
        $statusColors = [
            'draft'    => 'bg-gray-100 text-gray-700',
            'shipped'  => 'bg-blue-100 text-blue-800',
            'resolved' => 'bg-green-100 text-green-800',
        ];
        $badgeClass = $statusColors[$inventoryReturn->status] ?? 'bg-gray-100 text-gray-700';
    @endphp

    <a href="{{ route('mobile.warehouse.rtv.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Returns to Vendor
    </a>

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Return to Vendor</p>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $inventoryReturn->return_number }}</h1>
                @if ($inventoryReturn->vendor)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $inventoryReturn->vendor->company_name }}</p>
                @endif
                @if ($inventoryReturn->purchaseOrder)
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        PO {{ $inventoryReturn->purchaseOrder->po_number }}
                        @if ($inventoryReturn->purchaseOrder->sale)
                            &nbsp;&middot;&nbsp; Sale #{{ $inventoryReturn->purchaseOrder->sale->sale_number }}
                        @endif
                    </p>
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                {{ $inventoryReturn->status_label }}
            </span>
        </div>

        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-1">
            @if ($inventoryReturn->reason)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Reason</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $inventoryReturn->reason_label }}</span>
                </div>
            @endif
            @if ($inventoryReturn->outcome && $inventoryReturn->outcome !== 'pending')
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Outcome</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $inventoryReturn->outcome_label }}</span>
                </div>
            @endif
            @if ($inventoryReturn->returnedBy)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Returned by</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $inventoryReturn->returnedBy->name }}</span>
                </div>
            @endif
            <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Created</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $inventoryReturn->created_at->format('M j, Y') }}</span>
            </div>
        </div>

        @if ($inventoryReturn->notes)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $inventoryReturn->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Items ({{ $inventoryReturn->items->count() }})</p>
        </div>
        @forelse ($inventoryReturn->items as $item)
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->item_name_resolved }}</p>
                <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>Qty: <strong class="text-gray-700 dark:text-gray-300">{{ rtrim(rtrim(number_format((float)$item->quantity_returned, 2), '0'), '.') }} {{ $item->unit_resolved }}</strong></span>
                    @if ($item->unit_cost)
                        <span>Unit cost: <strong class="text-gray-700 dark:text-gray-300">${{ number_format((float)$item->unit_cost, 2) }}</strong></span>
                    @endif
                </div>
                @if ($item->credit_received)
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1 font-medium">
                        Credit received: ${{ number_format((float)$item->credit_received, 2) }}
                    </p>
                @endif
                @if ($item->notes)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $item->notes }}</p>
                @endif
            </div>
        @empty
            <div class="px-4 py-6 text-center text-sm text-gray-400">No items recorded</div>
        @endforelse
    </div>

    {{-- Link to PO --}}
    @if ($inventoryReturn->purchaseOrder)
        <a href="{{ route('mobile.purchase-orders.show', $inventoryReturn->purchaseOrder) }}"
           class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:bg-gray-50">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/40">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-gray-900 dark:text-white">View Purchase Order</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">PO {{ $inventoryReturn->purchaseOrder->po_number }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @endif

</x-mobile-layout>
