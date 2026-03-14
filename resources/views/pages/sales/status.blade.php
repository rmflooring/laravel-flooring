{{-- resources/views/pages/sales/status.blade.php --}}
<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ── Header ───────────────────────────────────────────────── --}}
            @php
                $saleLabel = $sale->sale_number ?? ('#' . $sale->id);

                $overallBadge = match($overallStatus) {
                    'Ready'        => 'bg-green-100 text-green-800',
                    'In progress'  => 'bg-blue-100 text-blue-800',
                    'Needs action' => 'bg-amber-100 text-amber-800',
                    default        => 'bg-gray-100 text-gray-600',   // Not started
                };
            @endphp

            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    {{-- Breadcrumb --}}
                    <nav class="flex items-center gap-1.5 text-sm text-gray-500 mb-2">
                        <a href="{{ route('pages.sales.show', $sale) }}"
                           class="inline-flex items-center gap-1 hover:text-gray-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Sale {{ $saleLabel }}
                        </a>
                        @if ($sale->customer_name)
                            <span class="text-gray-300">·</span>
                            <span>{{ $sale->customer_name }}</span>
                        @endif
                    </nav>

                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-900">
                            Sale status · {{ $saleLabel }}
                        </h1>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $overallBadge }}">
                            {{ $overallStatus }}
                        </span>
                    </div>

                    @if ($sale->job_name)
                        <p class="text-sm text-gray-500 mt-0.5">{{ $sale->job_name }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/>
                        </svg>
                        Print summary
                    </button>
                </div>
            </div>

            {{-- ── Progress bar ─────────────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <div class="flex items-center justify-between mb-2 text-sm">
                    <span class="font-medium text-gray-700">Overall job readiness</span>
                    <span class="font-semibold text-gray-900">{{ $progressPercent }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                    <div class="h-3 rounded-full transition-all"
                         style="width: {{ $progressPercent }}%; background-color: {{ $progressPercent >= 100 ? '#16a34a' : ($progressPercent > 0 ? '#2563eb' : '#d1d5db') }}">
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-400">
                    Based on material items received and work orders scheduled or completed
                </p>
            </div>

            {{-- ── Stat cards ───────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $totalMaterialItems }}</div>
                    <div class="text-xs text-gray-500 mt-1">Total material items</div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $posCreated }}</div>
                    <div class="text-xs text-gray-500 mt-1">POs created</div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $itemsReceived }}</div>
                    <div class="text-xs text-gray-500 mt-1">Items received</div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-amber-500">{{ $posPending }}</div>
                    <div class="text-xs text-gray-500 mt-1">POs pending</div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $totalWOs }}</div>
                    <div class="text-xs text-gray-500 mt-1">Work orders</div>
                </div>

            </div>

            {{-- ── Purchase Orders ──────────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Purchase Orders</h2>
                        <p class="text-xs text-gray-500 mt-0.5">All non-cancelled POs on this sale.</p>
                    </div>
                    @can('create purchase orders')
                        <a href="{{ route('pages.sales.purchase-orders.create', $sale) }}"
                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800">
                            + Create PO
                        </a>
                    @endcan
                </div>

                @if ($activePOs->isEmpty())
                    <div class="px-5 py-6 text-sm text-gray-400">
                        No purchase orders yet — create one from the sale page.
                    </div>
                @else
                    @php
                        $poStatusColors = [
                            'pending'   => 'bg-yellow-100 text-yellow-800',
                            'ordered'   => 'bg-blue-100 text-blue-800',
                            'received'  => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700">
                            <thead class="text-xs text-gray-500 bg-gray-50 border-b border-gray-100 uppercase">
                                <tr>
                                    <th class="px-5 py-3 font-medium">PO Number</th>
                                    <th class="px-5 py-3 font-medium">Vendor</th>
                                    <th class="px-5 py-3 font-medium text-right">Items</th>
                                    <th class="px-5 py-3 font-medium">Expected</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium">Vendor order #</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($activePOs->sortBy('po_number') as $po)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 font-medium text-gray-900">
                                            <a href="{{ route('pages.purchase-orders.show', $po) }}"
                                               class="text-blue-600 hover:underline">
                                                {{ $po->po_number }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3">{{ $po->vendor->company_name }}</td>
                                        <td class="px-5 py-3 text-right">{{ $po->items->count() }}</td>
                                        <td class="px-5 py-3 text-gray-500">
                                            {{ $po->expected_delivery_date?->format('M j, Y') ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $poStatusColors[$po->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $po->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500">
                                            {{ $po->vendor_order_number ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <a href="{{ route('pages.purchase-orders.show', $po) }}"
                                               class="text-sm font-medium text-blue-600 hover:underline">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ── Work Orders ───────────────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Work Orders</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Installation and labour tasks.</p>
                    </div>
                    @can('create work orders')
                        <a href="{{ route('pages.sales.work-orders.create', $sale) }}"
                           class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                            + Create Work Order
                        </a>
                    @endcan
                </div>

                @if ($activeWOs->isEmpty())
                    <div class="px-5 py-6 text-sm text-gray-400">No work orders yet.</div>
                @else
                    @php
                        $woStatusColors = [
                            'created'     => 'bg-gray-100 text-gray-700',
                            'scheduled'   => 'bg-blue-100 text-blue-800',
                            'in_progress' => 'bg-amber-100 text-amber-800',
                            'completed'   => 'bg-green-100 text-green-800',
                            'cancelled'   => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700">
                            <thead class="text-xs text-gray-500 bg-gray-50 border-b border-gray-100 uppercase">
                                <tr>
                                    <th class="px-5 py-3 font-medium">WO Number</th>
                                    <th class="px-5 py-3 font-medium">Installer</th>
                                    <th class="px-5 py-3 font-medium">Items</th>
                                    <th class="px-5 py-3 font-medium">Scheduled</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium">Calendar</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($activeWOs->sortByDesc('created_at') as $wo)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 font-medium text-gray-900">
                                            <a href="{{ route('pages.sales.work-orders.show', [$sale, $wo]) }}"
                                               class="text-blue-600 hover:underline">
                                                {{ $wo->wo_number }}
                                            </a>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500">{{ $wo->installer?->company_name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-gray-500">{{ $wo->items->count() }} {{ Str::plural('item', $wo->items->count()) }}</td>
                                        <td class="px-5 py-3 text-gray-500">
                                            @if ($wo->scheduled_date)
                                                {{ $wo->scheduled_date->format('M j, Y') }}
                                                @if ($wo->scheduled_time)
                                                    · {{ \Carbon\Carbon::createFromFormat('H:i', $wo->scheduled_time)->format('g:i A') }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $woStatusColors[$wo->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $wo->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3">
                                            @if ($wo->calendar_synced)
                                                <span class="inline-flex items-center gap-1 text-xs text-green-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                                    On calendar
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">Not synced</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <a href="{{ route('pages.sales.work-orders.show', [$sale, $wo]) }}"
                                               class="text-sm font-medium text-blue-600 hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ── Material Coverage ────────────────────────────────────── --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-900">Material coverage</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Every material line item and its fulfilment source.</p>
                </div>

                @if ($coverageItems->isEmpty())
                    <div class="px-5 py-6 text-sm text-gray-400">
                        No material items on this sale.
                    </div>
                @else
                    {{-- Colour legend --}}
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 px-5 py-3 bg-gray-50 border-b border-gray-100 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background:#16a34a"></span>
                            Received
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background:#2563eb"></span>
                            Ordered
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background:#7c3aed"></span>
                            PO created
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background:#d97706"></span>
                            No PO yet
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0" style="background:#db2777"></span>
                            From inventory <span class="italic">(coming soon)</span>
                        </span>
                    </div>

                    <div class="divide-y divide-gray-100">
                        @foreach ($coverageItems as $coverage)
                            @php
                                $item      = $coverage['item'];
                                $dotStatus = $coverage['dot_status'];
                                $po        = $coverage['po'];

                                $dotColors = [
                                    'received' => '#16a34a',
                                    'ordered'  => '#2563eb',
                                    'pending'  => '#7c3aed',
                                    'none'     => '#d97706',
                                ];
                                $dotColor = $dotColors[$dotStatus] ?? '#d97706';

                                $badgeColors = [
                                    'received' => 'bg-green-100 text-green-800',
                                    'ordered'  => 'bg-blue-100 text-blue-800',
                                    'pending'  => 'bg-purple-100 text-purple-800',
                                    'none'     => 'bg-amber-100 text-amber-800',
                                ];
                                $badgeColor = $badgeColors[$dotStatus] ?? 'bg-gray-100 text-gray-700';

                                $badgeLabels = [
                                    'received' => 'Received',
                                    'ordered'  => 'Ordered',
                                    'pending'  => 'PO created',
                                    'none'     => 'No PO yet',
                                ];
                                $badgeLabel = $badgeLabels[$dotStatus] ?? 'Unknown';
                                if ($po) {
                                    $badgeLabel .= ' · ' . $po->po_number;
                                }

                                // Build a readable item name
                                $nameParts = array_filter([
                                    $item->product_type,
                                    $item->manufacturer,
                                    $item->style,
                                    $item->color_item_number,
                                ]);
                                $itemName = $nameParts ? implode(' — ', $nameParts) : 'Material item';
                            @endphp

                            <div class="flex items-center gap-3 px-5 py-3 text-sm text-gray-700">
                                <span class="inline-block w-2.5 h-2.5 rounded-full shrink-0"
                                      style="background:{{ $dotColor }}"></span>
                                <span class="flex-1 min-w-0 truncate font-medium text-gray-900"
                                      title="{{ $itemName }}">
                                    {{ $itemName }}
                                </span>
                                <span class="text-gray-400 whitespace-nowrap shrink-0">
                                    {{ $item->quantity }} {{ $item->unit }}
                                </span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap shrink-0 {{ $badgeColor }}">
                                    {{ $badgeLabel }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-admin-layout>
