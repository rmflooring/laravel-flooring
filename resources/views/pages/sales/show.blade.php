<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Sale {{ $sale->sale_number ?? ('#' . $sale->id) }}
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Read-only view of the sale record.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.sales.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        Back
                    </a>

                    @can('view sale status')
                    <a href="{{ route('pages.sales.status', $sale) }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        Status
                    </a>
                    @endcan

                    <a href="{{ route('pages.sales.edit', $sale) }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
                        Edit
                    </a>
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-send-email-modal'))"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-purple-700 rounded-lg hover:bg-purple-800 focus:outline-none focus:ring-4 focus:ring-purple-300">
                        Send Email
                    </button>
                    <a href="{{ route('pages.sales.pdf', $sale) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                        </svg>
                        Print
                    </a>

                    @can('create purchase orders')
                    <a href="{{ route('pages.sales.purchase-orders.create', $sale) }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                        + Create PO
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Slim status strip --}}
            @can('view sale status')
            @php
                $stripPoCount       = $sale->purchaseOrders->where('status', '<>', 'cancelled')->count();
                $stripReceivedCount = $sale->purchaseOrders->where('status', 'received')->count();
                $stripWoCount       = $sale->workOrders->count();
            @endphp
            <div class="text-sm text-gray-400">
                {{ $stripPoCount }} {{ Str::plural('PO', $stripPoCount) }}
                &nbsp;·&nbsp;
                {{ $stripReceivedCount }} received
                &nbsp;·&nbsp;
                {{ $stripWoCount }} {{ Str::plural('work order', $stripWoCount) }}
                &nbsp;·&nbsp;
                <a href="{{ route('pages.sales.status', $sale) }}"
                   class="text-blue-500 hover:underline font-medium">
                    View status →
                </a>
            </div>
            @endcan

            {{-- Summary cards --}}
            @php
                $revisedContract = (float) ($sale->revised_contract_total ?? 0);
                $lockedGrand     = (float) ($sale->locked_grand_total ?? 0);
                $grandTotal      = (float) ($sale->grand_total ?? 0);

                // Treat 0.00 as "not set" (common when column has a default), so we can fall back.
                $revisedContractDisplay = $revisedContract != 0.0
                    ? $revisedContract
                    : ($lockedGrand != 0.0 ? $lockedGrand : $grandTotal);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Status</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $sale->status ?? '—' }}</div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Locked</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ $sale->locked_at ? 'Yes' : 'No' }}
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Revised Contract Total</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ number_format($revisedContractDisplay, 2) }}
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Invoiced Total</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ number_format((float) ($sale->invoiced_total ?? 0), 2) }}
                    </div>
                </div>
            </div>

            {{-- Details --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

                    {{-- Left: job identifiers --}}
                    <div class="space-y-3">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Customer</div>
                            <div class="mt-0.5 font-medium text-gray-900">{{ $sale->customer_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">PM</div>
                            <div class="mt-0.5 font-medium text-gray-900">{{ $sale->pm_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Job Name</div>
                            <div class="mt-0.5 font-medium text-gray-900">{{ $sale->job_name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Job #</div>
                            <div class="mt-0.5 text-base font-bold text-gray-900">{{ $sale->job_no ?? '—' }}</div>
                        </div>
                    </div>

                    {{-- Right: job site contact & address --}}
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-1">Job Site</div>
                        @if ($sale->homeowner_name || $sale->job_address || $sale->job_phone || $sale->job_email)
                            <div class="space-y-0.5 text-sm">
                                @if ($sale->homeowner_name)
                                    <div class="font-medium text-gray-900">{{ $sale->homeowner_name }}</div>
                                @endif
                                @if ($sale->job_address)
                                    <div class="text-gray-700 whitespace-pre-line leading-relaxed">{{ $sale->job_address }}</div>
                                @endif
                                @if ($sale->job_phone)
                                    <div class="text-gray-700">{{ $sale->job_phone }}</div>
                                @endif
                                @if ($sale->job_email)
                                    <div>
                                        <a href="mailto:{{ $sale->job_email }}"
                                           class="text-blue-600 hover:underline">{{ $sale->job_email }}</a>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-gray-400">—</div>
                        @endif
                    </div>

                </div>

                {{-- Secondary row: source estimate + notes --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm pt-4 border-t border-gray-100">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Source Estimate #</div>
                        <div class="mt-0.5 font-medium text-gray-900">{{ $sale->source_estimate_number ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-400">Notes</div>
                        <div class="mt-0.5 font-medium text-gray-900 whitespace-pre-line">{{ $sale->notes ?? '—' }}</div>
                    </div>
                </div>

            </div>

            {{-- Rooms --}}
            @if ($sale->rooms->isNotEmpty())
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Rooms &amp; Items</h2>

                    @foreach ($sale->rooms as $room)
                        @php
                            $materials = $room->items->where('item_type', 'material');
                            $labour    = $room->items->where('item_type', 'labour');
                            $freight   = $room->items->where('item_type', 'freight');
                            $roomTotal = $room->items->sum('line_total');
                        @endphp

                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                            {{-- Room header --}}
                            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
                                <span class="font-semibold text-gray-800 text-sm">{{ $room->room_name ?: 'Unnamed Room' }}</span>
                                <span class="text-sm font-medium text-gray-600">${{ number_format($roomTotal, 2) }}</span>
                            </div>

                            <div class="divide-y divide-gray-100">

                                {{-- Materials --}}
                                @if ($materials->isNotEmpty())
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Materials</p>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-gray-700">
                                                <thead class="text-xs text-gray-500 border-b border-gray-100">
                                                    <tr>
                                                        <th class="pb-1 pr-4 font-medium">Product Type</th>
                                                        <th class="pb-1 pr-4 font-medium">Manufacturer</th>
                                                        <th class="pb-1 pr-4 font-medium">Style</th>
                                                        <th class="pb-1 pr-4 font-medium">Colour / Item #</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Qty</th>
                                                        <th class="pb-1 pr-4 font-medium">Unit</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Sell Price</th>
                                                        <th class="pb-1 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach ($materials as $item)
                                                        @php
                                                            $poStatus = $itemPoStatusMap[$item->id] ?? null;
                                                            $qtyBg = match($poStatus) {
                                                                'delivered' => '#ccfbf1',
                                                                'received'  => '#dcfce7',
                                                                'ordered'   => '#fef9c3',
                                                                'pending'   => '#ffedd5',
                                                                default     => '',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->product_type ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->manufacturer ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->style ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->color_item_number ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right rounded"
                                                                @if($qtyBg) style="background-color:{{ $qtyBg }}" @endif>{{ $item->quantity }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->unit ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">${{ number_format($item->sell_price, 2) }}</td>
                                                            <td class="py-1.5 text-right font-medium">${{ number_format($item->line_total, 2) }}</td>
                                                        </tr>
                                                        @if ($item->notes)
                                                            <tr>
                                                                <td colspan="8" class="pb-1.5 pr-4 text-xs text-gray-400 italic">{{ $item->notes }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                {{-- Labour --}}
                                @if ($labour->isNotEmpty())
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Labour</p>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-gray-700">
                                                <thead class="text-xs text-gray-500 border-b border-gray-100">
                                                    <tr>
                                                        <th class="pb-1 pr-4 font-medium">Type</th>
                                                        <th class="pb-1 pr-4 font-medium">Description</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Qty</th>
                                                        <th class="pb-1 pr-4 font-medium">Unit</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Sell Price</th>
                                                        <th class="pb-1 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach ($labour as $item)
                                                        @php
                                                            $woStatus = $itemWoStatusMap[$item->id] ?? null;
                                                            $woQtyBg = match($woStatus) {
                                                                'completed' => '#dcfce7',
                                                                'scheduled' => '#fef9c3',
                                                                'created'   => '#ffedd5',
                                                                default     => '',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->labour_type ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->description ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right rounded"
                                                                @if($woQtyBg) style="background-color:{{ $woQtyBg }}" @endif>{{ $item->quantity }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->unit ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">${{ number_format($item->sell_price, 2) }}</td>
                                                            <td class="py-1.5 text-right font-medium">${{ number_format($item->line_total, 2) }}</td>
                                                        </tr>
                                                        @if ($item->notes)
                                                            <tr>
                                                                <td colspan="6" class="pb-1.5 pr-4 text-xs text-gray-400 italic">{{ $item->notes }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                {{-- Freight --}}
                                @if ($freight->isNotEmpty())
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Freight</p>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-gray-700">
                                                <thead class="text-xs text-gray-500 border-b border-gray-100">
                                                    <tr>
                                                        <th class="pb-1 pr-4 font-medium">Description</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Qty</th>
                                                        <th class="pb-1 pr-4 font-medium">Unit</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Sell Price</th>
                                                        <th class="pb-1 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach ($freight as $item)
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->freight_description ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">{{ $item->quantity }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->unit ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">${{ number_format($item->sell_price, 2) }}</td>
                                                            <td class="py-1.5 text-right font-medium">${{ number_format($item->line_total, 2) }}</td>
                                                        </tr>
                                                        @if ($item->notes)
                                                            <tr>
                                                                <td colspan="5" class="pb-1.5 pr-4 text-xs text-gray-400 italic">{{ $item->notes }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <div class="max-w-xs ml-auto space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Materials</span>
                            <span>${{ number_format($sale->subtotal_materials, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Labour</span>
                            <span>${{ number_format($sale->subtotal_labour, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Freight</span>
                            <span>${{ number_format($sale->subtotal_freight, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 border-t border-gray-100 pt-1.5">
                            <span>Subtotal</span>
                            <span>${{ number_format($sale->pretax_total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax ({{ $sale->tax_rate_percent }}%)</span>
                            <span>${{ number_format($sale->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-1.5 text-base">
                            <span>Grand Total</span>
                            <span>${{ number_format($sale->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm text-sm text-gray-500">
                    No rooms or items on this sale.
                </div>
            @endif

            {{-- Purchase Orders --}}
            @can('view purchase orders')
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Purchase Orders</h2>
                        <p class="text-xs text-gray-500 mt-0.5">POs raised against this sale.</p>
                    </div>
                    @can('create purchase orders')
                    <a href="{{ route('pages.sales.purchase-orders.create', $sale) }}"
                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800">
                        + Create PO
                    </a>
                    @endcan
                </div>

                @if($sale->purchaseOrders->isEmpty())
                    <div class="px-5 py-6 text-sm text-gray-400">No purchase orders yet.</div>
                @else
                    @php
                        $poStatusColors = [
                            'pending'   => 'bg-yellow-100 text-yellow-800',
                            'ordered'   => 'bg-blue-100 text-blue-800',
                            'received'  => 'bg-green-100 text-green-800',
                            'delivered' => 'bg-teal-700 text-white',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700">
                            <thead class="text-xs text-gray-500 bg-gray-50 border-b border-gray-100 uppercase">
                                <tr>
                                    <th class="px-5 py-3">PO Number</th>
                                    <th class="px-5 py-3">Vendor</th>
                                    <th class="px-5 py-3">Status</th>
                                    <th class="px-5 py-3">Fulfillment</th>
                                    <th class="px-5 py-3">Expected</th>
                                    <th class="px-5 py-3">Total Cost</th>
                                    <th class="px-5 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($sale->purchaseOrders as $po)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 font-medium text-gray-900">{{ $po->po_number }}</td>
                                        <td class="px-5 py-3">{{ $po->vendor->company_name }}</td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $poStatusColors[$po->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $po->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500">{{ $po->fulfillment_label }}</td>
                                        <td class="px-5 py-3 text-gray-500">
                                            {{ $po->expected_delivery_date?->format('M j, Y') ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3 font-medium">${{ number_format($po->items->sum('cost_total'), 2) }}</td>
                                        <td class="px-5 py-3">
                                            <a href="{{ route('pages.purchase-orders.show', $po) }}"
                                               class="text-sm font-medium text-blue-600 hover:underline">View</a>
                                            @can('edit purchase orders')
                                            &nbsp;·&nbsp;
                                            <a href="{{ route('pages.purchase-orders.edit', $po) }}"
                                               class="text-sm font-medium text-blue-600 hover:underline">Edit</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @endcan

            {{-- Work Orders --}}
            @can('view work orders')
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Work Orders</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Installation and labour tasks for this sale.</p>
                    </div>
                    @can('create work orders')
                    <a href="{{ route('pages.sales.work-orders.create', $sale) }}"
                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                        + Create Work Order
                    </a>
                    @endcan
                </div>

                @if ($sale->workOrders->isEmpty())
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
                                @foreach ($sale->workOrders as $wo)
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
            @endcan

        </div>
    </div>

{{-- Send Email Modal --}}
@php $homeownerEmail = $sale->sourceEstimate?->homeowner_email ?? ''; @endphp
<div x-data="{ open: false }"
     @open-send-email-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.5)">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" @click.outside="open = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h5 class="text-base font-semibold text-gray-800">Send Sale Email</h5>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('pages.sales.send-email', $sale) }}">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                @if (! $homeownerEmail)
                    <div class="p-3 text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
                        No homeowner email found on the source estimate. Enter a recipient below.
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="email" name="to" value="{{ $homeownerEmail }}"
                           placeholder="customer@example.com"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" value="{{ $emailSubject }}"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="body" rows="10"
                              class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-mono">{{ $emailBody }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <a href="{{ route('pages.sales.pdf', $sale) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-colors">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        <span>Sale-{{ $sale->sale_number ?? $sale->id }}.pdf</span>
                        <span class="text-xs text-gray-400 ml-1">— click to preview</span>
                    </a>
                </div>

                <p class="text-xs text-gray-400">
                    @if (auth()->user()->microsoftAccount?->mail_connected)
                        Sending from <strong>{{ auth()->user()->microsoftAccount->email }}</strong> via your personal MS365 account (Track 2).
                    @else
                        Sending from the shared mailbox via Track 1.
                    @endif
                </p>
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-purple-700 rounded-lg hover:bg-purple-800">
                    Send Sale Email
                </button>
            </div>
        </form>
    </div>
</div>

</x-admin-layout>
