{{-- resources/views/mobile/work-orders/show.blade.php --}}
<x-mobile-layout :title="'WO ' . $workOrder->wo_number">

    @php
        $statusColors = [
            'created'     => 'bg-gray-100 text-gray-700',
            'scheduled'   => 'bg-blue-100 text-blue-800',
            'in_progress' => 'bg-amber-100 text-amber-800',
            'completed'   => 'bg-green-100 text-green-800',
            'cancelled'   => 'bg-red-100 text-red-800',
        ];
        $statusColor = $statusColors[$workOrder->status] ?? 'bg-gray-100 text-gray-800';

        $sale      = $workOrder->sale;
        $installer = $workOrder->installer;

        $itemsByRoom = $workOrder->items->groupBy(fn($item) => $item->saleItem?->sale_room_id ?? 0);
    @endphp

    {{-- WO Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Work Order</p>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $workOrder->wo_number }}</h1>
                @if($installer)
                    <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $installer->company_name }}</p>
                @endif
                @if($sale)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Sale #{{ $sale->sale_number }}
                        @if($sale->customer_name) &mdash; {{ $sale->customer_name }} @endif
                    </p>
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                {{ $workOrder->status_label }}
            </span>
        </div>

        {{-- Schedule --}}
        @if($workOrder->scheduled_date)
            <div class="mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-4 py-3">
                <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-1">Scheduled</p>
                <p class="text-base font-bold text-blue-900 dark:text-blue-100">
                    {{ $workOrder->scheduled_date->format('l, F j, Y') }}
                </p>
                @if($workOrder->scheduled_time)
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-0.5">
                        at {{ \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Job site card --}}
    @if($sale && ($sale->job_address || $sale->job_name || $sale->homeowner_name))
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-3">Job Site</p>

        @if($sale->homeowner_name)
            <div class="flex items-start gap-3 mb-2">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $sale->homeowner_name }}</span>
            </div>
        @endif

        @if($sale->job_name)
            <div class="flex items-start gap-3 mb-2">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/>
                </svg>
                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $sale->job_name }}</span>
            </div>
        @endif

        @if($sale->job_address)
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                <a href="https://maps.google.com/?q={{ urlencode($sale->job_address) }}"
                   target="_blank"
                   class="text-sm text-blue-600 dark:text-blue-400 underline whitespace-pre-line">{{ $sale->job_address }}</a>
            </div>
        @endif
    </div>
    @endif

    {{-- Items grouped by room --}}
    @foreach($itemsByRoom as $roomId => $roomItems)
        @php
            $roomName = $roomItems->first()->saleItem?->room?->room_name ?? 'Uncategorized';
        @endphp
        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
            {{-- Room header --}}
            <div class="bg-blue-50 dark:bg-blue-900/30 border-b border-blue-200 dark:border-blue-800 px-4 py-2.5 flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
                <span class="text-sm font-bold text-blue-800 dark:text-blue-200">{{ $roomName }}</span>
            </div>

            {{-- Labour items --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($roomItems as $item)
                    <div class="px-4 py-3">
                        {{-- Related materials --}}
                        @if($item->relatedMaterials->isNotEmpty())
                            <div class="mb-2 space-y-1">
                                @foreach($item->relatedMaterials as $mat)
                                    @php
                                        $si = $mat->saleItem;
                                        $matName = $si
                                            ? implode(' — ', array_filter([$si->product_type, $si->manufacturer, $si->style, $si->color_item_number]))
                                            : 'Material';
                                    @endphp
                                    <div class="flex items-start gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                        </svg>
                                        <span>{{ $matName }}@if($si) &nbsp;&middot;&nbsp; {{ number_format((float)$si->quantity, 2) }} {{ $si->unit }}@endif</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Labour item name --}}
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item->item_name }}</p>

                        {{-- Qty / cost --}}
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ number_format($item->quantity, 2) }} {{ $item->unit }}
                            &nbsp;&middot;&nbsp;
                            ${{ number_format($item->cost_price, 2) }}/{{ $item->unit }}
                            &nbsp;&middot;&nbsp;
                            <span class="font-semibold text-gray-700 dark:text-gray-300">${{ number_format($item->cost_total, 2) }}</span>
                        </p>

                        {{-- WO notes --}}
                        @if($item->wo_notes)
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line bg-gray-50 dark:bg-gray-700/40 rounded px-2 py-1.5">{{ $item->wo_notes }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- Grand Total --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-4 py-3 flex items-center justify-between shadow-sm">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Grand Total</span>
        <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($workOrder->grand_total, 2) }}</span>
    </div>

    {{-- Notes --}}
    @if($workOrder->notes)
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-4 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-2">Notes / Special Instructions</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $workOrder->notes }}</p>
    </div>
    @endif

    {{-- Action links --}}
    <a href="{{ route('pages.sales.work-orders.pdf', [$workOrder->sale_id, $workOrder->id]) }}" target="_blank"
       class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900 dark:text-white">Print PDF</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Open printable work order</p>
        </div>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</x-mobile-layout>
