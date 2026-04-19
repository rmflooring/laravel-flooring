{{-- resources/views/mobile/warehouse/pick-tickets/show.blade.php --}}
<x-mobile-layout :title="'PT #' . $pickTicket->pt_number">

    @php
        $statusColors = [
            'pending'             => 'bg-gray-100 text-gray-700',
            'ready'               => 'bg-blue-100 text-blue-800',
            'picked'              => 'bg-indigo-100 text-indigo-800',
            'staged'              => 'bg-purple-100 text-purple-800',
            'partially_delivered' => 'bg-amber-100 text-amber-800',
            'delivered'           => 'bg-green-100 text-green-800',
            'returned'            => 'bg-orange-100 text-orange-800',
            'cancelled'           => 'bg-red-100 text-red-800',
        ];
        $badgeClass = $statusColors[$pickTicket->status] ?? 'bg-gray-100 text-gray-700';
    @endphp

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
            <button onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400">&times;</button>
        </div>
    @endif

    <a href="{{ route('mobile.warehouse.pick-tickets.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Pick Tickets
    </a>

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Pick Ticket</p>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">PT #{{ $pickTicket->pt_number }}</h1>
                @if ($pickTicket->sale)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Sale #{{ $pickTicket->sale->sale_number }}</p>
                    @if ($pickTicket->sale->customer_name)
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $pickTicket->sale->customer_name }}</p>
                    @endif
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                {{ $pickTicket->status_label }}
            </span>
        </div>

        @if ($pickTicket->workOrder)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
                </svg>
                WO #{{ $pickTicket->workOrder->wo_number }}
                @if ($pickTicket->workOrder->installer)
                    <span class="text-gray-400">·</span>
                    <span class="text-gray-500 dark:text-gray-400 text-xs">{{ $pickTicket->workOrder->installer->company_name }}</span>
                @endif
                @if ($pickTicket->workOrder->scheduled_date)
                    <span class="text-gray-400">·</span>
                    <span class="text-gray-500 dark:text-gray-400 text-xs">{{ $pickTicket->workOrder->scheduled_date->format('M j') }}</span>
                @endif
            </div>
        @endif

        @if ($pickTicket->notes)
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $pickTicket->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Items --}}
    @php
        $itemsByRoom = $pickTicket->items->groupBy(fn($item) => $item->saleItem?->room?->room_name ?? '');
    @endphp
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Items ({{ $pickTicket->items->count() }})</p>
        </div>
        @foreach ($itemsByRoom as $roomName => $items)
            @if ($roomName)
                <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-900/30">
                    <p class="text-xs font-semibold text-blue-700 dark:text-blue-300">{{ $roomName }}</p>
                </div>
            @endif
            @foreach ($items as $item)
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</p>
                    <div class="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>Qty: <strong class="text-gray-700 dark:text-gray-300">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }} {{ $item->unit }}</strong></span>
                        @if ((float)$item->delivered_qty > 0)
                            <span>Delivered: <strong class="text-green-600">{{ rtrim(rtrim(number_format((float)$item->delivered_qty, 2), '0'), '.') }}</strong></span>
                        @endif
                        @if ((float)$item->returned_qty > 0)
                            <span>Returned: <strong class="text-orange-600">{{ rtrim(rtrim(number_format((float)$item->returned_qty, 2), '0'), '.') }}</strong></span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    {{-- Actions --}}
    @php $status = $pickTicket->status; @endphp

    {{-- Mark Ready --}}
    @if ($status === 'pending')
        <form method="POST" action="{{ route('mobile.warehouse.pick-tickets.update-status', $pickTicket) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="mark_ready">
            <button type="submit"
                    class="w-full py-3.5 rounded-2xl bg-blue-600 text-white font-semibold text-sm active:bg-blue-700">
                Mark as Ready
            </button>
        </form>
    @endif

    {{-- Mark Picked --}}
    @if ($status === 'ready')
        <form method="POST" action="{{ route('mobile.warehouse.pick-tickets.update-status', $pickTicket) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="mark_picked">
            <button type="submit"
                    class="w-full py-3.5 rounded-2xl bg-indigo-600 text-white font-semibold text-sm active:bg-indigo-700">
                Mark as Picked
            </button>
        </form>
    @endif

    {{-- Deliver --}}
    @if (in_array($status, ['picked', 'staged', 'partially_delivered']))
        <div class="rounded-xl border border-green-200 bg-white dark:border-green-800 dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-green-100 dark:border-green-900/40 bg-green-50 dark:bg-green-900/20">
                <p class="text-sm font-bold text-green-800 dark:text-green-300">Record Delivery</p>
            </div>
            <form method="POST" action="{{ route('mobile.warehouse.pick-tickets.update-status', $pickTicket) }}" class="p-4 space-y-3">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="deliver">

                @foreach ($pickTicket->items as $item)
                    @php
                        $remaining = max(0, (float)$item->quantity - (float)$item->delivered_qty);
                    @endphp
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 truncate">
                            {{ $item->item_name }}
                            <span class="text-gray-400">(ordered: {{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }} {{ $item->unit }})</span>
                        </label>
                        <input type="number" name="items[{{ $item->id }}]"
                               value="{{ $remaining > 0 ? rtrim(rtrim(number_format($remaining, 2), '0'), '.') : '' }}"
                               step="0.01" min="0" max="{{ $item->quantity }}"
                               placeholder="Qty delivered"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                @endforeach

                <input type="text" name="received_by" placeholder="Received by (optional)"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500">

                <textarea name="delivery_notes" rows="2" placeholder="Notes (optional)"
                          class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500 resize-none"></textarea>

                <button type="submit"
                        class="w-full py-3 rounded-xl bg-green-600 text-white font-semibold text-sm active:bg-green-700">
                    Record Delivery
                </button>
            </form>
        </div>
    @endif

    {{-- Cancel (pending or ready) --}}
    @if (in_array($status, ['pending', 'ready', 'picked']))
        <form method="POST" action="{{ route('mobile.warehouse.pick-tickets.update-status', $pickTicket) }}"
              onsubmit="return confirm('Cancel this pick ticket?')">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="cancel">
            <button type="submit"
                    class="w-full py-3 rounded-2xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 font-medium text-sm active:bg-red-50">
                Cancel Pick Ticket
            </button>
        </form>
    @endif

    {{-- Revert status --}}
    @if (in_array($status, ['ready', 'picked', 'delivered', 'partially_delivered']))
        <form method="POST" action="{{ route('mobile.warehouse.pick-tickets.update-status', $pickTicket) }}"
              onsubmit="return confirm('Revert this pick ticket one step back?')">
            @csrf @method('PATCH')
            <input type="hidden" name="action" value="revert_status">
            <button type="submit"
                    class="w-full py-3 rounded-2xl border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 font-medium text-sm active:bg-gray-50">
                Revert Status
            </button>
        </form>
    @endif

</x-mobile-layout>
