{{-- resources/views/pages/inventory/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.index') }}"
                   class="hover:text-gray-700 dark:hover:text-gray-200">Inventory</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $inventoryReceipt->item_name }}</span>
            </nav>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Header card --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wide">
                                        Receipt #{{ $inventoryReceipt->id }}
                                    </span>
                                    @if ($available <= 0)
                                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                            Depleted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-teal-100 dark:bg-teal-900/30 px-2 py-0.5 text-xs font-semibold text-teal-700 dark:text-teal-400">
                                            In stock
                                        </span>
                                    @endif
                                </div>
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $inventoryReceipt->item_name }}</h1>
                                @if ($inventoryReceipt->unit)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Unit: {{ $inventoryReceipt->unit }}</p>
                                @endif
                            </div>
                            <div class="text-right text-sm text-gray-500 dark:text-gray-400 shrink-0">
                                <div>Received {{ $inventoryReceipt->received_date?->format('M j, Y') }}</div>
                            </div>
                        </div>

                        {{-- Qty summary --}}
                        <div class="grid grid-cols-3 divide-x divide-gray-100 dark:divide-gray-700">
                            <div class="px-6 py-4 text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ rtrim(rtrim(number_format((float) $inventoryReceipt->quantity_received, 2), '0'), '.') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Received</div>
                            </div>
                            <div class="px-6 py-4 text-center">
                                <div class="text-2xl font-bold {{ $allocated > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                                    {{ $allocated > 0 ? rtrim(rtrim(number_format($allocated, 2), '0'), '.') : '—' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Allocated</div>
                            </div>
                            <div class="px-6 py-4 text-center">
                                <div class="text-2xl font-bold {{ $available > 0 ? 'text-teal-600 dark:text-teal-400' : 'text-gray-400' }}">
                                    {{ rtrim(rtrim(number_format($available, 2), '0'), '.') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Available</div>
                            </div>
                        </div>
                    </div>

                    {{-- Allocations --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Allocations
                                @if ($inventoryReceipt->allocations->isNotEmpty())
                                    <span class="ml-1.5 text-xs font-normal text-gray-400">({{ $inventoryReceipt->allocations->count() }})</span>
                                @endif
                            </h2>
                        </div>

                        @if ($inventoryReceipt->allocations->isEmpty())
                            <div class="px-6 py-8 text-center text-sm text-gray-400">No allocations yet.</div>
                        @else
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-700/40">
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sale item</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sale</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pick ticket</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($inventoryReceipt->allocations as $allocation)
                                        @php
                                            $ptItem = $allocation->pickTicketItems->first();
                                            $pt     = $ptItem?->pickTicket;
                                            $ptColors = [
                                                'pending'   => 'bg-gray-100 text-gray-700',
                                                'ready'     => 'bg-blue-100 text-blue-800',
                                                'picked'    => 'bg-purple-100 text-purple-800',
                                                'delivered' => 'bg-green-100 text-green-800',
                                                'returned'  => 'bg-amber-100 text-amber-800',
                                                'cancelled' => 'bg-gray-100 text-gray-400',
                                            ];
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                                @if ($allocation->saleItem)
                                                    <div class="font-medium">
                                                        {{ implode(' — ', array_filter([
                                                            $allocation->saleItem->product_type,
                                                            $allocation->saleItem->manufacturer,
                                                            $allocation->saleItem->style,
                                                            $allocation->saleItem->color_item_number,
                                                        ])) ?: ('Item #' . $allocation->saleItem->id) }}
                                                    </div>
                                                    @if ($allocation->saleItem->room)
                                                        <div class="text-xs text-gray-400">{{ $allocation->saleItem->room->name }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                @if ($allocation->saleItem?->sale)
                                                    <a href="{{ route('pages.sales.status', $allocation->saleItem->sale) }}"
                                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">
                                                        Sale #{{ $allocation->saleItem->sale->sale_number }}
                                                    </a>
                                                    @if ($allocation->saleItem->sale->customer_name)
                                                        <div class="text-xs text-gray-400">{{ $allocation->saleItem->sale->customer_name }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                @if ($pt)
                                                    <a href="{{ route('pages.warehouse.pick-tickets.show', $pt) }}"
                                                       class="inline-flex items-center gap-1 text-teal-700 hover:text-teal-900 dark:text-teal-400 font-medium">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                        </svg>
                                                        {{ $pt->pt_number }}
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">
                                                {{ rtrim(rtrim(number_format((float) $allocation->quantity, 2), '0'), '.') }}
                                                @if ($inventoryReceipt->unit)
                                                    <span class="text-xs text-gray-400 font-normal">{{ $inventoryReceipt->unit }}</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-sm">
                                                @if ($pt)
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ptColors[$pt->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                        {{ \App\Models\PickTicket::STATUS_LABELS[$pt->status] ?? ucfirst($pt->status) }}
                                                    </span>
                                                @elseif ($allocation->released_at)
                                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Released</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Reserved</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">

                    {{-- Receipt details --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Receipt details</h3>
                        <dl class="space-y-3 text-sm">

                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Receipt #</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $inventoryReceipt->id }}</dd>
                            </div>

                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Date received</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    {{ $inventoryReceipt->received_date?->format('M j, Y') ?? '—' }}
                                </dd>
                            </div>

                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Unit</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $inventoryReceipt->unit ?: '—' }}</dd>
                            </div>

                        </dl>
                    </div>

                    {{-- Source --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Source</h3>

                        @if ($inventoryReceipt->purchaseOrder)
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Purchase order</dt>
                                    <dd>
                                        <a href="{{ route('pages.purchase-orders.show', $inventoryReceipt->purchaseOrder) }}"
                                           class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            PO {{ $inventoryReceipt->purchaseOrder->po_number }}
                                        </a>
                                    </dd>
                                </div>

                                @if ($inventoryReceipt->purchaseOrder->vendor)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500 shrink-0">Vendor</dt>
                                        <dd class="text-gray-900 dark:text-white text-right">
                                            {{ $inventoryReceipt->purchaseOrder->vendor->company_name }}
                                        </dd>
                                    </div>
                                @endif

                                @if ($inventoryReceipt->purchaseOrder->vendor_order_number)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500 shrink-0">Vendor order #</dt>
                                        <dd class="font-mono text-gray-900 dark:text-white">
                                            {{ $inventoryReceipt->purchaseOrder->vendor_order_number }}
                                        </dd>
                                    </div>
                                @endif

                                @if ($inventoryReceipt->purchaseOrder->sale)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500 shrink-0">Sale</dt>
                                        <dd>
                                            <a href="{{ route('pages.sales.status', $inventoryReceipt->purchaseOrder->sale) }}"
                                               class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                                Sale #{{ $inventoryReceipt->purchaseOrder->sale->sale_number }}
                                            </a>
                                        </dd>
                                    </div>
                                @endif

                                @if ($inventoryReceipt->purchaseOrderItem)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500 shrink-0">Ordered qty</dt>
                                        <dd class="text-gray-900 dark:text-white">
                                            {{ rtrim(rtrim(number_format((float) $inventoryReceipt->purchaseOrderItem->quantity, 2), '0'), '.') }}
                                            {{ $inventoryReceipt->unit }}
                                        </dd>
                                    </div>
                                @endif

                            </dl>
                        @else
                            <p class="text-sm text-gray-500">Manual receipt — not linked to a PO.</p>
                        @endif
                    </div>

                    {{-- Notes --}}
                    @if ($inventoryReceipt->notes)
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Notes</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $inventoryReceipt->notes }}</p>
                        </div>
                    @endif

                    {{-- Audit --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Audit</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Logged by</dt>
                                <dd class="text-gray-900 dark:text-white text-right">
                                    {{ $inventoryReceipt->creator?->name ?? '—' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Logged at</dt>
                                <dd class="text-gray-500 dark:text-gray-400 text-right text-xs">
                                    {{ $inventoryReceipt->created_at->format('M j, Y g:ia') }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
