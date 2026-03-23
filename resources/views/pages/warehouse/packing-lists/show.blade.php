<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.warehouse.pick-tickets.index') }}"
                   class="hover:text-gray-700 dark:hover:text-gray-200">Pick Tickets</a>
                <span>/</span>
                <a href="{{ route('pages.warehouse.pick-tickets.show', $packingList->pickTicket) }}"
                   class="hover:text-gray-700 dark:hover:text-gray-200">PT #{{ $packingList->pickTicket->pt_number }}</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $packingList->pl_number }}</span>
            </nav>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('info'))
                <div class="rounded-md bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                    {{ session('info') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main card --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Header card --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div>
                                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $packingList->pl_number }}</span>
                                <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">Packing List</span>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('pages.warehouse.packing-lists.pdf', $packingList) }}"
                                   target="_blank"
                                   class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <svg class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                    Print PDF
                                </a>
                            </div>
                        </div>

                        {{-- Delivery summary --}}
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
                            <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Pick Ticket:</span>
                                <a href="{{ route('pages.warehouse.pick-tickets.show', $packingList->pickTicket) }}"
                                   class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                    PT #{{ $packingList->pickTicket->pt_number }}
                                </a>
                                @if($packingList->pickTicket->delivered_at)
                                    <span class="text-gray-500 dark:text-gray-400">Delivered:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $packingList->pickTicket->delivered_at->format('M j, Y') }}
                                    </span>
                                @endif
                                @if($packingList->sale)
                                    <span class="text-gray-500 dark:text-gray-400">Sale:</span>
                                    <a href="{{ route('pages.sales.show', $packingList->sale) }}"
                                       class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                        #{{ $packingList->sale->sale_number }}
                                    </a>
                                @endif
                            </div>
                        </div>

                        {{-- Items --}}
                        <div class="px-6 py-4">
                            @php
                                $deliveredItems = $packingList->pickTicket->items->filter(fn($item) => (float)$item->delivered_qty > 0);
                                $itemsByRoom = $deliveredItems->groupBy(fn($item) => $item->saleItem?->sale_room_id ?? 0);
                            @endphp

                            @forelse($itemsByRoom as $roomId => $roomItems)
                                @php $roomName = $roomItems->first()->saleItem?->room?->room_name ?? null; @endphp

                                @if($roomName)
                                    <div class="flex items-center gap-2 mb-2 mt-4 first:mt-0">
                                        <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                        </svg>
                                        <span class="text-sm font-semibold text-blue-700 dark:text-blue-400">{{ $roomName }}</span>
                                    </div>
                                @endif

                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 mb-4">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th class="pb-2 pr-4">Item</th>
                                            <th class="pb-2 pr-4 text-right">Qty Delivered</th>
                                            <th class="pb-2 text-right">Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach($roomItems as $ptItem)
                                            <tr>
                                                <td class="py-2 pr-4 text-sm text-gray-900 dark:text-white">{{ $ptItem->item_name }}</td>
                                                <td class="py-2 pr-4 text-sm font-semibold text-right text-gray-900 dark:text-white">
                                                    {{ number_format((float)$ptItem->delivered_qty, 2) }}
                                                </td>
                                                <td class="py-2 text-sm text-right text-gray-500 dark:text-gray-400">{{ $ptItem->unit }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">No delivered items on this pick ticket.</p>
                            @endforelse
                        </div>

                        {{-- Notes --}}
                        @if($packingList->notes)
                            <div class="px-6 pb-4">
                                <div class="rounded-md bg-gray-50 border border-gray-200 px-4 py-3 dark:bg-gray-700 dark:border-gray-600">
                                    <p class="text-xs font-semibold uppercase text-gray-500 mb-1">Notes</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $packingList->notes }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">

                    {{-- Receipt Details form --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Receipt Details</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">These fields appear on the printed PDF.</p>
                        </div>
                        <form method="POST" action="{{ route('pages.warehouse.packing-lists.update', $packingList) }}" class="px-4 py-4 space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                    Received By
                                </label>
                                <input type="text" name="received_by"
                                    value="{{ old('received_by', $packingList->received_by) }}"
                                    placeholder="Full name"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                    Date Received
                                </label>
                                <input type="date" name="received_date"
                                    value="{{ old('received_date', $packingList->received_date?->format('Y-m-d')) }}"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                    Company / Installer
                                </label>
                                <input type="text" name="received_company"
                                    value="{{ old('received_company', $packingList->received_company) }}"
                                    placeholder="Company or installer name"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase text-gray-500 dark:text-gray-400 mb-1">
                                    Notes
                                </label>
                                <textarea name="notes" rows="3"
                                    placeholder="Optional notes"
                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $packingList->notes) }}</textarea>
                            </div>

                            <button type="submit"
                                class="w-full inline-flex justify-center items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Save
                            </button>
                        </form>
                    </div>

                    {{-- Meta --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="px-4 py-3 space-y-3 text-sm">
                            <div>
                                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">PL Number</span>
                                <p class="mt-0.5 font-medium text-gray-900 dark:text-white">{{ $packingList->pl_number }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Created</span>
                                <p class="mt-0.5 text-gray-700 dark:text-gray-300">
                                    {{ $packingList->created_at->format('M j, Y g:i A') }}
                                    @if($packingList->creator)
                                        <span class="text-gray-500 dark:text-gray-400"> by {{ $packingList->creator->name }}</span>
                                    @endif
                                </p>
                            </div>
                            @if($packingList->pickTicket->workOrder)
                                <div>
                                    <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Work Order</span>
                                    <p class="mt-0.5 text-gray-700 dark:text-gray-300">WO #{{ $packingList->pickTicket->workOrder->wo_number }}</p>
                                </div>
                                @if($packingList->pickTicket->workOrder->installer)
                                    <div>
                                        <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Installer</span>
                                        <p class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $packingList->pickTicket->workOrder->installer->company_name }}</p>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>
