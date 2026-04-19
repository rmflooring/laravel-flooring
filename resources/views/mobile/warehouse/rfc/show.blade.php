{{-- resources/views/mobile/warehouse/rfc/show.blade.php --}}
<x-mobile-layout :title="$customerReturn->rfc_number">

    @php
        $statusColors = [
            'draft'     => 'bg-gray-100 text-gray-700',
            'received'  => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
        $badgeClass = $statusColors[$customerReturn->status] ?? 'bg-gray-100 text-gray-700';
    @endphp

    <a href="{{ route('mobile.warehouse.rfc.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Customer Returns
    </a>

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Customer Return</p>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $customerReturn->rfc_number }}</h1>
                @if ($customerReturn->sale)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Sale #{{ $customerReturn->sale->sale_number }}</p>
                    @if ($customerReturn->sale->customer_name)
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $customerReturn->sale->customer_name }}</p>
                    @endif
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                {{ $customerReturn->status_label }}
            </span>
        </div>

        @if ($customerReturn->pickTicket)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                PT #{{ $customerReturn->pickTicket->pt_number }}
            </div>
        @endif

        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 space-y-1">
            @if ($customerReturn->received_date)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Received date</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $customerReturn->received_date->format('M j, Y') }}</span>
                </div>
            @endif
            @if ($customerReturn->creator)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">Created by</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $customerReturn->creator->name }}</span>
                </div>
            @endif
            <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Created</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $customerReturn->created_at->format('M j, Y') }}</span>
            </div>
        </div>

        @if ($customerReturn->notes)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $customerReturn->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Items ({{ $customerReturn->items->count() }})</p>
        </div>
        @forelse ($customerReturn->items as $item)
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->item_name ?? '—' }}</p>
                <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>Returned: <strong class="text-gray-700 dark:text-gray-300">{{ rtrim(rtrim(number_format((float)$item->quantity_returned, 2), '0'), '.') }} {{ $item->unit ?? '' }}</strong></span>
                    @if ($item->condition)
                        <span class="capitalize">Condition: <strong class="text-gray-700 dark:text-gray-300">{{ $item->condition }}</strong></span>
                    @endif
                </div>
                @if ($item->notes)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $item->notes }}</p>
                @endif
            </div>
        @empty
            <div class="px-4 py-6 text-center text-sm text-gray-400">No items recorded</div>
        @endforelse
    </div>

    {{-- Link to Pick Ticket --}}
    @if ($customerReturn->pickTicket)
        <a href="{{ route('mobile.warehouse.pick-tickets.show', $customerReturn->pickTicket) }}"
           class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:bg-gray-50">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/40">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-gray-900 dark:text-white">View Pick Ticket</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">PT #{{ $customerReturn->pickTicket->pt_number }}</p>
            </div>
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @endif

</x-mobile-layout>
