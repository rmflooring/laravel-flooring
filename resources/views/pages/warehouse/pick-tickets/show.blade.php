{{-- resources/views/pages/warehouse/pick-tickets/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.warehouse.pick-tickets.index') }}"
                   class="hover:text-gray-700 dark:hover:text-gray-200">Pick Tickets</a>
                <span>/</span>
                @if ($pickTicket->sale)
                    <a href="{{ route('pages.sales.status', $pickTicket->sale) }}"
                       class="hover:text-gray-700 dark:hover:text-gray-200">
                        Sale #{{ $pickTicket->sale->sale_number }}
                    </a>
                    <span>/</span>
                @endif
                <span class="font-medium text-gray-900 dark:text-white">{{ $pickTicket->pt_number }}</span>
            </nav>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main card --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Header card --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-bold text-gray-900 dark:text-white">PT #{{ $pickTicket->pt_number }}</span>
                                @include('pages.warehouse.pick-tickets._status-badge', ['status' => $pickTicket->status])
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('pages.warehouse.pick-tickets.pdf', $pickTicket) }}"
                                   target="_blank"
                                   class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    Print PDF
                                </a>
                                @if (in_array($pickTicket->status, ['staged', 'partially_delivered']))
                                    <button type="button"
                                            onclick="document.getElementById('pt-deliver-modal').classList.remove('hidden')"
                                            class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                                        {{ $pickTicket->status === 'partially_delivered' ? 'Record Delivery' : 'Mark Delivered' }}
                                    </button>
                                    @if ($pickTicket->status === 'staged')
                                    <button type="button"
                                            onclick="document.getElementById('pt-unstage-modal').classList.remove('hidden')"
                                            class="inline-flex items-center rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                                        Unstage
                                    </button>
                                    @endif
                                @elseif ($pickTicket->status === 'pending')
                                    <form method="POST" action="{{ route('pages.warehouse.pick-tickets.update-status', $pickTicket) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="mark_ready">
                                        <button type="submit"
                                                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-700">
                                            Mark Ready
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('pages.warehouse.pick-tickets.update-status', $pickTicket) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="cancel">
                                        <button type="submit"
                                                onclick="return confirm('Cancel this pick ticket?')"
                                                class="inline-flex items-center rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">
                                            Cancel
                                        </button>
                                    </form>
                                @elseif ($pickTicket->status === 'ready')
                                    <form method="POST" action="{{ route('pages.warehouse.pick-tickets.update-status', $pickTicket) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="mark_picked">
                                        <button type="submit"
                                                class="inline-flex items-center rounded-md bg-purple-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-purple-700">
                                            Mark Picked
                                        </button>
                                    </form>
                                @elseif ($pickTicket->status === 'picked')
                                    <button type="button"
                                            onclick="document.getElementById('pt-deliver-modal').classList.remove('hidden')"
                                            class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                                        Mark Delivered
                                    </button>
                                @elseif ($pickTicket->status === 'delivered')
                                    <form method="POST" action="{{ route('pages.warehouse.pick-tickets.update-status', $pickTicket) }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="action" value="return">
                                        <button type="submit"
                                                onclick="return confirm('Mark these materials as returned to warehouse?')"
                                                class="inline-flex items-center rounded-md border border-amber-400 px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50">
                                            Mark Returned
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- Items table --}}
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Room</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ordered</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Delivered</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($pickTicket->items as $ptItem)
                                    @php
                                        $ordered   = (float) $ptItem->quantity;
                                        $delivered = (float) $ptItem->delivered_qty;
                                        $remaining = max(0, $ordered - $delivered);
                                        $fullyDone = $remaining <= 0;
                                    @endphp
                                    <tr class="{{ $fullyDone ? 'opacity-60' : '' }}">
                                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $ptItem->item_name }}
                                            @if ($ptItem->unit)
                                                <span class="text-gray-400 text-xs ml-1">({{ $ptItem->unit }})</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $ptItem->saleItem?->room?->room_name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                            {{ rtrim(rtrim(number_format($ordered, 2), '0'), '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium {{ $delivered > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                            {{ $delivered > 0 ? rtrim(rtrim(number_format($delivered, 2), '0'), '.') : '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-medium {{ $fullyDone ? 'text-green-600 dark:text-green-400' : 'text-orange-600 dark:text-orange-400' }}">
                                            {{ $fullyDone ? '✓' : rtrim(rtrim(number_format($remaining, 2), '0'), '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-6 text-center text-sm text-gray-400">No items.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if ($pickTicket->notes)
                            <div class="border-t border-gray-100 px-6 py-3 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                <span class="font-medium">Notes:</span> {{ $pickTicket->notes }}
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">

                    {{-- Links --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Linked to</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Sale</dt>
                                <dd>
                                    @if ($pickTicket->sale)
                                        <a href="{{ route('pages.sales.status', $pickTicket->sale) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            Sale #{{ $pickTicket->sale->sale_number }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Work order</dt>
                                <dd class="text-gray-700 dark:text-gray-300">
                                    @if ($pickTicket->workOrder && $pickTicket->sale)
                                        <a href="{{ route('pages.sales.work-orders.show', [$pickTicket->sale, $pickTicket->workOrder]) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            WO #{{ $pickTicket->workOrder->wo_number }}
                                        </a>
                                    @elseif ($pickTicket->workOrder)
                                        WO #{{ $pickTicket->workOrder->wo_number }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($pickTicket->workOrder?->installer)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Installer</dt>
                                    <dd class="text-gray-700 dark:text-gray-300 text-right">
                                        {{ $pickTicket->workOrder->installer->company_name }}
                                    </dd>
                                </div>
                            @endif
                            @if ($pickTicket->workOrder?->scheduled_date)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Install date</dt>
                                    <dd class="text-gray-700 dark:text-gray-300">
                                        {{ $pickTicket->workOrder->scheduled_date->format('M j, Y') }}
                                        @if ($pickTicket->workOrder->scheduled_time)
                                            · {{ \Carbon\Carbon::createFromFormat('H:i', $pickTicket->workOrder->scheduled_time)->format('g:i A') }}
                                        @endif
                                    </dd>
                                </div>
                            @endif
                            @if ($pickTicket->sale?->job_name)
                                <div class="flex justify-between">
                                    <dt class="text-gray-500">Job</dt>
                                    <dd class="text-gray-700 dark:text-gray-300 text-right max-w-[160px] truncate"
                                        title="{{ $pickTicket->sale->job_name }}">
                                        {{ $pickTicket->sale->job_name }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Timeline --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Timeline</h3>
                        <ul class="space-y-2 text-sm">
                            <li class="flex justify-between gap-2">
                                <span class="text-gray-500 shrink-0">Created</span>
                                <span class="text-gray-700 dark:text-gray-300 text-right">
                                    {{ $pickTicket->created_at->format('M j, Y g:ia') }}
                                    @if ($pickTicket->creator)
                                        <span class="block text-xs text-gray-400">by {{ $pickTicket->creator->name }}</span>
                                    @endif
                                </span>
                            </li>
                            @if ($pickTicket->ready_at)
                                <li class="flex justify-between gap-2">
                                    <span class="text-gray-500 shrink-0">Ready</span>
                                    <span class="text-gray-700 dark:text-gray-300 text-right">{{ $pickTicket->ready_at->format('M j, Y g:ia') }}</span>
                                </li>
                            @endif
                            @if ($pickTicket->picked_at)
                                <li class="flex justify-between gap-2">
                                    <span class="text-gray-500 shrink-0">Picked</span>
                                    <span class="text-gray-700 dark:text-gray-300 text-right">{{ $pickTicket->picked_at->format('M j, Y g:ia') }}</span>
                                </li>
                            @endif
                            @if ($pickTicket->delivered_at)
                                <li class="flex justify-between gap-2">
                                    <span class="text-gray-500 shrink-0">Delivered</span>
                                    <span class="text-gray-700 dark:text-gray-300 text-right">{{ $pickTicket->delivered_at->format('M j, Y g:ia') }}</span>
                                </li>
                            @endif
                            @if ($pickTicket->returned_at)
                                <li class="flex justify-between gap-2">
                                    <span class="text-gray-500 shrink-0">Returned</span>
                                    <span class="text-gray-700 dark:text-gray-300 text-right">{{ $pickTicket->returned_at->format('M j, Y g:ia') }}</span>
                                </li>
                            @endif
                            @if ($pickTicket->unstaged_at)
                                <li class="flex justify-between gap-2">
                                    <span class="text-red-500 shrink-0">Unstaged</span>
                                    <span class="text-gray-700 dark:text-gray-300 text-right">
                                        {{ $pickTicket->unstaged_at->format('M j, Y g:ia') }}
                                        @if ($pickTicket->unstagedBy)
                                            <span class="block text-xs text-gray-400">by {{ $pickTicket->unstagedBy->name }}</span>
                                        @endif
                                    </span>
                                </li>
                            @endif
                        </ul>

                        {{-- Staging notes --}}
                        @if ($pickTicket->staging_notes)
                            <div class="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Staging notes</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $pickTicket->staging_notes }}</p>
                            </div>
                        @endif

                        {{-- Unstage reason --}}
                        @if ($pickTicket->unstage_reason)
                            <div class="mt-3 border-t border-red-100 pt-3 dark:border-red-900/30">
                                <p class="text-xs font-medium text-red-500 mb-1">Unstage reason</p>
                                <p class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $pickTicket->unstage_reason }}</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- Mark Delivered Modal --}}
    @if (in_array($pickTicket->status, ['staged', 'picked', 'partially_delivered']))
    <div id="pt-deliver-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Record Delivery</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Enter the quantity delivered for each item. Leave at 0 for items not delivered this trip.</p>
                </div>
                <button type="button" onclick="document.getElementById('pt-deliver-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('pages.warehouse.pick-tickets.update-status', $pickTicket) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="deliver">

                {{-- Per-item qty table --}}
                <div class="overflow-x-auto border-b border-gray-100 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Room</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ordered</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Delivered</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Remaining</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-32">Delivering Now</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                            @foreach ($pickTicket->items as $ptItem)
                                @php
                                    $ordered   = (float) $ptItem->quantity;
                                    $delivered = (float) $ptItem->delivered_qty;
                                    $remaining = max(0, $ordered - $delivered);
                                @endphp
                                <tr class="{{ $remaining <= 0 ? 'opacity-50' : '' }}">
                                    <td class="px-4 py-2.5 text-gray-900 dark:text-white font-medium">
                                        {{ $ptItem->item_name }}
                                        @if ($ptItem->unit)
                                            <span class="text-xs text-gray-400 ml-1">{{ $ptItem->unit }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">
                                        {{ $ptItem->saleItem?->room?->room_name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-gray-700 dark:text-gray-300">
                                        {{ rtrim(rtrim(number_format($ordered, 2), '0'), '.') }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right {{ $delivered > 0 ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400' }}">
                                        {{ $delivered > 0 ? rtrim(rtrim(number_format($delivered, 2), '0'), '.') : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right {{ $remaining > 0 ? 'text-orange-600 dark:text-orange-400 font-medium' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $remaining > 0 ? rtrim(rtrim(number_format($remaining, 2), '0'), '.') : '✓' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        @if ($remaining > 0)
                                            <input type="number"
                                                   name="items[{{ $ptItem->id }}]"
                                                   value="{{ rtrim(rtrim(number_format($remaining, 2), '0'), '.') }}"
                                                   min="0"
                                                   max="{{ $remaining }}"
                                                   step="0.01"
                                                   class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @else
                                            <span class="text-xs text-green-600 dark:text-green-400">Done</span>
                                            <input type="hidden" name="items[{{ $ptItem->id }}]" value="0">
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Received by + notes --}}
                <div class="space-y-3 px-6 py-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Received by <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <input type="text" name="received_by"
                                   placeholder="Name of person at site"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Delivery notes <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <input type="text" name="delivery_notes"
                                   placeholder="e.g. Left in garage"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                        </div>
                    </div>
                    <div class="rounded-md bg-green-50 px-3 py-2 text-xs text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        Delivered by: <strong>{{ auth()->user()->name }}</strong> · {{ now()->format('M j, Y') }}
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button"
                            onclick="document.getElementById('pt-deliver-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Confirm Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Unstage Modal --}}
    @if ($pickTicket->status === 'staged')
    <div id="pt-unstage-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Unstage Pick Ticket</h3>
                <button type="button" onclick="document.getElementById('pt-unstage-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('pages.warehouse.pick-tickets.unstage', $pickTicket) }}">
                @csrf
                <div class="space-y-4 px-6 py-4">
                    <div class="rounded-md bg-gray-50 px-3 py-2.5 text-xs text-gray-600 dark:bg-gray-700/50 dark:text-gray-400 space-y-1">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">PT #:</span>
                            {{ $pickTicket->pt_number }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Staged by:</span>
                            {{ $pickTicket->creator?->name ?? '—' }}
                            · {{ $pickTicket->created_at->format('M j, Y g:i a') }}
                        </div>
                        @if ($pickTicket->staging_notes)
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Staging notes:</span>
                                {{ $pickTicket->staging_notes }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Reason for unstaging <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea name="unstage_reason" rows="3"
                                  placeholder="e.g. Materials not ready. Rescheduling install."
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"></textarea>
                    </div>
                    <div class="rounded-md bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-400">
                        Unstaged by: <strong>{{ auth()->user()->name }}</strong> · {{ now()->format('M j, Y') }}
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button"
                            onclick="document.getElementById('pt-unstage-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Unstage Pick Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif


</x-app-layout>
