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
                    @if($sale->status === 'approved')
                    <a href="{{ route('pages.sales.purchase-orders.create', $sale) }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                        + Create PO
                    </a>
                    @else
                    <span title="Sale must be approved before creating a PO"
                          class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed border border-gray-200">
                        + Create PO
                    </span>
                    @endif
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
                                                            $qtyStyle = match($poStatus) {
                                                                'delivered' => 'background-color:#0f766e; color:#ffffff;',
                                                                'received'  => 'background-color:#bbf7d0; color:#166534;',
                                                                'ordered'   => 'background-color:#fef9c3; color:#854d0e;',
                                                                'pending'   => 'background-color:#ffedd5; color:#9a3412;',
                                                                default     => '',
                                                            };
                                                        @endphp
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->product_type ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->manufacturer ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->style ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->color_item_number ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right rounded font-medium"
                                                                @if($qtyStyle) style="{{ $qtyStyle }}" @endif>{{ $item->quantity }}</td>
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
                    @if($sale->status === 'approved')
                    <a href="{{ route('pages.sales.purchase-orders.create', $sale) }}"
                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800">
                        + Create PO
                    </a>
                    @else
                    <span title="Sale must be approved before creating a PO"
                          class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed border border-gray-200">
                        + Create PO
                    </span>
                    @endif
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

            {{-- Change Orders Section --}}
            @can('edit estimates')
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">Change Orders</h3>
                <div class="flex items-center gap-2">
                    @if($sale->status === 'approved')
                        @php
                            $hasActiveCo = $sale->changeOrders->whereIn('status', ['draft', 'sent'])->isNotEmpty();
                            $hasOrderedPo = $sale->purchaseOrders->whereNotIn('status', ['cancelled', 'pending'])->isNotEmpty();
                        @endphp
                        @if($hasActiveCo)
                            <span class="text-xs text-amber-700 dark:text-amber-400">CO in progress</span>
                        @elseif($hasOrderedPo)
                            <span class="text-xs text-gray-400 dark:text-gray-500">Blocked — POs exist</span>
                        @else
                            <a href="{{ route('pages.sales.change-orders.create', $sale) }}"
                               class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                New Change Order
                            </a>
                        @endif
                    @elseif($sale->status === 'change_in_progress')
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-300">
                            Change In Progress
                        </span>
                    @endif
                </div>
            </div>

            @if($sale->changeOrders->isEmpty())
                <div class="px-5 py-6 text-sm text-gray-400">No change orders yet.</div>
            @else
                @php
                    $coStatusColors = [
                        'draft'     => 'bg-gray-100 text-gray-700',
                        'sent'      => 'bg-sky-100 text-sky-800',
                        'approved'  => 'bg-green-100 text-green-800',
                        'rejected'  => 'bg-red-100 text-red-800',
                        'cancelled' => 'bg-gray-100 text-gray-500',
                    ];
                @endphp
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                            <th class="px-5 py-3 text-left">CO #</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Original</th>
                            <th class="px-4 py-3 text-right">Delta</th>
                            <th class="px-4 py-3 text-right">Revised Total</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                        @foreach($sale->changeOrders as $co)
                            @php
                                $coDelta = $co->original_grand_total > 0
                                    ? ($sale->grand_total - $co->original_grand_total)
                                    : null;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                    <a href="{{ route('pages.sales.change-orders.show', [$sale, $co]) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $co->co_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $co->title ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $coStatusColors[$co->status] ?? '' }}">
                                        {{ ucfirst($co->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">
                                    ${{ number_format($co->original_grand_total, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">
                                    @if($co->status === 'approved' && $coDelta !== null)
                                        <span class="{{ $coDelta >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                            {{ $coDelta >= 0 ? '+' : '' }}${{ number_format($coDelta, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-200">
                                    @if($co->status === 'approved')
                                        ${{ number_format($co->locked_grand_total, 2) }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $co->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('pages.sales.change-orders.show', [$sale, $co]) }}"
                                       class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            </div>
            @endcan

            {{-- Invoices Section --}}
            @can('view invoices')
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Invoices</h3>
                        @php
                            $invoiceableStatuses = ['approved','scheduled','in_progress','completed','partially_invoiced','invoiced','change_in_progress'];
                        @endphp
                        @if($sale->is_fully_invoiced)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Fully Invoiced</span>
                        @elseif($sale->invoiced_total > 0)
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-300">Partially Invoiced</span>
                        @endif
                    </div>
                    @can('create invoices')
                        @if(in_array($sale->status, $invoiceableStatuses))
                            <a href="{{ route('pages.sales.invoices.create', $sale) }}"
                               class="inline-flex items-center gap-1 rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                New Invoice
                            </a>
                        @else
                            <span class="text-xs text-gray-400 dark:text-gray-500">Sale must be approved to invoice</span>
                        @endif
                    @endcan
                </div>

                @if($sale->invoices->isEmpty())
                    <div class="px-5 py-6 text-sm text-gray-400">No invoices yet.</div>
                @else
                    @php
                        $invStatusColors = [
                            'draft'          => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            'sent'           => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300',
                            'paid'           => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'overdue'        => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            'partially_paid' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
                            'voided'         => 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400',
                        ];
                    @endphp
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                                <th class="px-5 py-3 text-left">Invoice #</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Due Date</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Paid</th>
                                <th class="px-4 py-3 text-right">Balance</th>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach($sale->invoices as $inv)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 {{ $inv->status === 'voided' ? 'opacity-50' : '' }}">
                                    <td class="px-5 py-3 font-medium">
                                        <a href="{{ route('pages.sales.invoices.show', [$sale, $inv]) }}"
                                           class="text-blue-600 hover:underline dark:text-blue-400">{{ $inv->invoice_number }}</a>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $invStatusColors[$inv->status] ?? '' }}">
                                            {{ match($inv->status) {
                                                'draft'          => 'Draft',
                                                'sent'           => 'Sent',
                                                'paid'           => 'Paid',
                                                'overdue'        => 'Overdue',
                                                'partially_paid' => 'Partially Paid',
                                                'voided'         => 'Voided',
                                                default          => ucfirst($inv->status),
                                            } }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                        @if($inv->due_date)
                                            <span class="{{ $inv->due_date->isPast() && !in_array($inv->status, ['paid','voided']) ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                                                {{ $inv->due_date->format('M j, Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format((float)$inv->grand_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-green-700 dark:text-green-400">${{ number_format((float)$inv->amount_paid, 2) }}</td>
                                    <td class="px-4 py-3 text-right {{ $inv->balance_due > 0 && !in_array($inv->status, ['voided']) ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-400' }}">
                                        ${{ number_format(max(0, $inv->balance_due), 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $inv->created_at->format('M j, Y') }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('pages.sales.invoices.show', [$sale, $inv]) }}"
                                           class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 dark:bg-gray-700 text-sm font-semibold">
                                <td colspan="3" class="px-5 py-3 text-right text-gray-600 dark:text-gray-300">Total Invoiced (excl. voided)</td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">${{ number_format((float)$sale->invoiced_total, 2) }}</td>
                                <td class="px-4 py-3 text-right text-green-700 dark:text-green-400">${{ number_format((float)$sale->invoices->whereNotIn('status',['voided'])->sum('amount_paid'), 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif

            </div>
            @endcan

            {{-- Archived Records (admin only) --}}
            @role('admin')
            @if ($trashedWorkOrders->isNotEmpty() || $trashedPurchaseOrders->isNotEmpty())
            <div class="bg-white border border-orange-200 rounded-xl shadow-sm mt-6">
                <div class="px-6 py-4 border-b border-orange-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <h2 class="text-sm font-semibold text-orange-700 uppercase tracking-wide">Archived Records</h2>
                    <span class="text-xs text-orange-500 ml-1">These soft-deleted records are blocking sale deletion. Permanently delete them to clear the way.</span>
                </div>

                {{-- Archived Work Orders --}}
                @if ($trashedWorkOrders->isNotEmpty())
                <div class="px-6 py-4 {{ $trashedPurchaseOrders->isNotEmpty() ? 'border-b border-orange-100' : '' }}">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Work Orders</h3>
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase border-b">
                            <tr>
                                <th class="pb-2 pr-4">WO #</th>
                                <th class="pb-2 pr-4">Installer</th>
                                <th class="pb-2 pr-4">Status at deletion</th>
                                <th class="pb-2 pr-4">Deleted</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($trashedWorkOrders as $two)
                                @php
                                    $woBlocked = \App\Models\PickTicket::where('work_order_id', $two->id)
                                        ->whereNotIn('status', ['staged', 'cancelled'])
                                        ->exists();
                                @endphp
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-900">{{ $two->wo_number }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $two->installer?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ ucfirst(str_replace('_', ' ', $two->status)) }}</td>
                                    <td class="py-2 pr-4 text-gray-500">{{ $two->deleted_at->format('M j, Y') }}</td>
                                    <td class="py-2 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <form method="POST" action="{{ route('pages.sales.work-orders.restore', [$sale, $two]) }}"
                                                  onsubmit="return confirm('Restore WO {{ $two->wo_number }}?')">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-green-600 hover:underline">
                                                    Restore
                                                </button>
                                            </form>
                                            @if ($woBlocked)
                                                <span class="text-xs text-gray-400 italic" title="Has processed pick tickets">Cannot delete</span>
                                            @else
                                                <form method="POST" action="{{ route('pages.sales.work-orders.force-destroy', [$sale, $two]) }}"
                                                      onsubmit="return confirm('Permanently delete WO {{ $two->wo_number }}? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline">
                                                        Permanently Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Archived Purchase Orders --}}
                @if ($trashedPurchaseOrders->isNotEmpty())
                <div class="px-6 py-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Purchase Orders</h3>
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase border-b">
                            <tr>
                                <th class="pb-2 pr-4">PO #</th>
                                <th class="pb-2 pr-4">Vendor</th>
                                <th class="pb-2 pr-4">Status at deletion</th>
                                <th class="pb-2 pr-4">Deleted</th>
                                <th class="pb-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($trashedPurchaseOrders as $tpo)
                                @php
                                    $poBlocked = \DB::table('inventory_receipts')
                                        ->where('purchase_order_id', $tpo->id)
                                        ->exists();
                                @endphp
                                <tr>
                                    <td class="py-2 pr-4 font-medium text-gray-900">{{ $tpo->po_number }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $tpo->vendor?->company_name ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ ucfirst($tpo->status) }}</td>
                                    <td class="py-2 pr-4 text-gray-500">{{ $tpo->deleted_at->format('M j, Y') }}</td>
                                    <td class="py-2 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            <form method="POST" action="{{ route('pages.purchase-orders.restore', $tpo) }}"
                                                  onsubmit="return confirm('Restore PO {{ $tpo->po_number }}?')">
                                                @csrf
                                                <button type="submit" class="text-sm font-medium text-green-600 hover:underline">
                                                    Restore
                                                </button>
                                            </form>
                                            @if ($poBlocked)
                                                <span class="text-xs text-gray-400 italic" title="Inventory has been received against this PO">Cannot delete</span>
                                            @else
                                                <form method="POST" action="{{ route('pages.purchase-orders.force-destroy', $tpo) }}"
                                                      onsubmit="return confirm('Permanently delete PO {{ $tpo->po_number }}? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline">
                                                        Permanently Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

            </div>
            @endif
            @endrole

        </div>
    </div>

{{-- Send Email Modal --}}
@php
    $homeownerEmail = $sale->job_email ?: ($sale->sourceEstimate?->homeowner_email ?? '');
@endphp
<div x-data="{
        open: false,
        toEmail: '{{ $homeownerEmail }}',
        customTo: '',
        selected: '{{ $homeownerEmail ? 'jobsite' : 'custom' }}',
        get finalTo() { return this.selected === 'custom' ? this.customTo : this.toEmail; },
        select(val, email) { this.selected = val; this.toEmail = email; }
     }"
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>

                    {{-- Recipient quick-select buttons --}}
                    <div class="flex flex-wrap gap-2 mb-2">
                        @if ($homeownerEmail)
                            <button type="button"
                                    @click="select('jobsite', '{{ $homeownerEmail }}')"
                                    :class="selected === 'jobsite' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Job Site — {{ $homeownerEmail }}
                            </button>
                        @endif

                        @if (!empty($pmEmail))
                            <button type="button"
                                    @click="select('pm', '{{ $pmEmail }}')"
                                    :class="selected === 'pm' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                PM — {{ $pmEmail }}
                            </button>
                        @endif

                        <button type="button"
                                @click="select('custom', ''); $nextTick(() => $refs.customToInput.focus())"
                                :class="selected === 'custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Custom
                        </button>
                    </div>

                    {{-- Display selected email or custom input --}}
                    <template x-if="selected !== 'custom'">
                        <div class="w-full bg-gray-100 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-700" x-text="toEmail"></div>
                    </template>
                    <template x-if="selected === 'custom'">
                        <input type="email" x-ref="customToInput" x-model="customTo"
                               placeholder="Enter email address"
                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                    </template>

                    {{-- Hidden input that always submits the final value --}}
                    <input type="hidden" name="to" :value="finalTo">

                    @if (! $homeownerEmail && empty($pmEmail))
                        <p class="mt-1.5 text-xs text-yellow-700">No job site or PM email on this sale. Use Custom to enter a recipient.</p>
                    @endif
                </div>

                {{-- CC Addresses --}}
                <div x-data="{ ccEmails: [], ccInput: '' }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CC <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="ccEmails.length > 0">
                        <template x-for="(email, i) in ccEmails" :key="i">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">
                                <span x-text="email"></span>
                                <input type="hidden" name="cc[]" :value="email">
                                <button type="button" @click="ccEmails.splice(i, 1)" class="text-blue-400 hover:text-blue-600 leading-none ml-1">&times;</button>
                            </span>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="email" x-model="ccInput"
                               @keydown.enter.prevent="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                               placeholder="cc@example.com"
                               class="flex-1 bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <button type="button"
                                @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                            Add
                        </button>
                    </div>
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
