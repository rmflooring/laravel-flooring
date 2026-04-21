{{-- resources/views/pages/inventory/show.blade.php --}}
<x-app-layout>

@php
    // Collect all linked sale numbers for the tag
    $tagSaleNumbers = collect();
    if ($inventoryReceipt->purchaseOrder?->sale)
        $tagSaleNumbers->push($inventoryReceipt->purchaseOrder->sale->sale_number);
    foreach ($inventoryReceipt->allocations as $alloc)
        if ($alloc->saleItem?->sale && ! $tagSaleNumbers->contains($alloc->saleItem->sale->sale_number))
            $tagSaleNumbers->push($alloc->saleItem->sale->sale_number);

    // QR code linking to mobile inventory view
    $tagQrUrl  = route('mobile.inventory.show', $inventoryReceipt);
    $tagQrB64  = base64_encode((string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(96)->margin(1)->generate($tagQrUrl));
@endphp

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
                                        Record #{{ $inventoryReceipt->id }}
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
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                                    Received {{ $inventoryReceipt->received_date?->format('M j, Y') }}
                                </div>
                                <button type="button" onclick="document.getElementById('print-tag-modal').classList.remove('hidden')"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Print Tag
                                </button>
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

                    {{-- Record details --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Record details</h3>
                        <dl class="space-y-3 text-sm">

                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Record #</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $inventoryReceipt->id }}</dd>
                            </div>

                            @if ($inventoryReceipt->product_style_id)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Product ID</dt>
                                    <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $inventoryReceipt->product_style_id }}</dd>
                                </div>
                            @endif

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
                            <p class="text-sm text-gray-500">Manual record — not linked to a PO.</p>
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

{{-- ============================================================
     PRINT TAG MODAL
     ============================================================ --}}
<div id="print-tag-modal"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
     onclick="if(event.target===this) this.classList.add('hidden')">

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Inventory Tag
            </h2>
            <button onclick="document.getElementById('print-tag-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tag preview --}}
        <div class="px-6 pt-5 pb-2">
            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-3">Tag preview — 4" × 6"</p>

            <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white text-gray-900 font-sans text-sm" style="aspect-ratio:4/6; max-height:360px; display:flex; flex-direction:column; overflow:hidden;">

                {{-- Header bar --}}
                <div style="background:#1d4ed8; color:#fff; padding:6px 10px; border-radius:4px; margin-bottom:8px; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:10px; font-weight:700; letter-spacing:0.05em; text-transform:uppercase;">Inventory Record</div>
                    <div style="font-size:14px; font-weight:700; font-family:monospace;">#{{ $inventoryReceipt->id }}</div>
                </div>

                {{-- Item name --}}
                <div style="font-size:14px; font-weight:700; color:#111; line-height:1.25; margin-bottom:5px;">
                    {{ $inventoryReceipt->item_name }}
                </div>

                {{-- Qty + Date --}}
                <div style="font-size:11px; color:#555; margin-bottom:3px;">
                    <strong>Qty:</strong>
                    {{ rtrim(rtrim(number_format((float) $inventoryReceipt->quantity_received, 2), '0'), '.') }}
                    {{ $inventoryReceipt->unit }}
                </div>
                <div style="font-size:11px; color:#777; margin-bottom:8px;">
                    Received: {{ $inventoryReceipt->received_date?->format('M j, Y') ?? '—' }}
                </div>

                {{-- PO source --}}
                @if ($inventoryReceipt->purchaseOrder)
                    <div style="font-size:11px; color:#555; margin-bottom:6px;">
                        <strong>PO:</strong> {{ $inventoryReceipt->purchaseOrder->po_number }}
                        @if ($inventoryReceipt->purchaseOrder->vendor)
                            — {{ $inventoryReceipt->purchaseOrder->vendor->company_name }}
                        @endif
                    </div>
                @endif

                {{-- Job # --}}
                @if ($tagSaleNumbers->isNotEmpty())
                    <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:4px; padding:5px 8px; margin-bottom:8px;">
                        <div style="font-size:9px; color:#166534; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">Job #</div>
                        <div style="font-size:16px; font-weight:700; color:#15803d; font-family:monospace;">
                            {{ $tagSaleNumbers->implode(' · ') }}
                        </div>
                    </div>
                @endif

                {{-- Notes preview card --}}
                <div id="modal-note-card" style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:4px; padding:5px 8px; margin-bottom:8px; display:none;">
                    <div style="font-size:9px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:2px;">Notes</div>
                    <div id="modal-note-preview" style="font-size:11px; color:#374151; white-space:pre-wrap;"></div>
                </div>

                {{-- Spacer --}}
                <div style="flex:1;"></div>

                {{-- QR + Footer --}}
                <div style="margin-top:6px; padding-top:5px; border-top:1px solid #e5e7eb; display:flex; align-items:flex-end; justify-content:space-between;">
                    <div style="font-size:8px; color:#9ca3af;">
                        <div>RM Flooring</div>
                        <div>{{ now()->format('M j, Y') }}</div>
                    </div>
                    <div style="text-align:center;">
                        <img src="data:image/svg+xml;base64,{{ $tagQrB64 }}" style="width:48px; height:48px; display:block;">
                        <div style="font-size:6px; color:#c0c0c0; margin-top:1px;">Scan</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Note field --}}
        <div class="px-6 pt-3 pb-5">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                Tag note <span class="text-gray-400 font-normal">(optional — printed on tag)</span>
            </label>
            <textarea id="tag-note-input" rows="3" maxlength="200"
                      placeholder="e.g. Hold for installer, Room 2 only…"
                      oninput="syncTagNote(this.value)"
                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white resize-none"></textarea>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 rounded-b-xl">
            <button type="button" onclick="document.getElementById('print-tag-modal').classList.add('hidden')"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                Cancel
            </button>
            <button type="button" onclick="printTag()"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Tag
            </button>
        </div>
    </div>
</div>

<script>
function syncTagNote(value) {
    document.getElementById('modal-note-preview').textContent = value;
    document.getElementById('modal-note-card').style.display  = value.trim() ? 'block' : 'none';
}

function printTag() {
    const note    = document.getElementById('tag-note-input').value;
    const format  = @json($tagFormat);

    const noteHtml = note
        ? `<div class="tag-notes">
               <div class="tag-notes-label">Notes</div>
               <div class="tag-notes-text">${note.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
           </div>`
        : '';

    const jobHtml = @json($tagSaleNumbers->isNotEmpty())
        ? `<div class="tag-job">
               <div class="tag-job-label">Job #</div>
               <div class="tag-job-number">{{ $tagSaleNumbers->implode(' · ') }}</div>
           </div>`
        : '';

    const poHtml = @json((bool) $inventoryReceipt->purchaseOrder)
        ? `<div class="tag-detail"><strong>PO:</strong> {{ $inventoryReceipt->purchaseOrder?->po_number }}{{ $inventoryReceipt->purchaseOrder?->vendor ? ' — ' . $inventoryReceipt->purchaseOrder->vendor->company_name : '' }}</div>`
        : '';

    const qrSrc = `data:image/svg+xml;base64,{{ $tagQrB64 }}`;

    let html;

    if (format === 'zebra') {
        // Zebra thermal: 4" × 6" portrait (vertical label stock)
        html = `<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
    @page { size: 4in 6in; margin: 0; }
    * { box-sizing: border-box; }
    html, body { width: 4in; height: 6in; margin: 0; padding: 0; overflow: hidden; }
    .tag {
        width: 4in; height: 6in; padding: 0.2in;
        font-family: Arial, Helvetica, sans-serif;
        display: flex; flex-direction: column; overflow: hidden;
    }
    .tag-header {
        background: #1d4ed8; color: #fff;
        padding: 7px 10px; border-radius: 4px; margin-bottom: 10px;
        display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
    }
    .tag-header-label { font-size: 10pt; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
    .tag-header-id    { font-size: 14pt; font-weight: 700; font-family: monospace; }
    .tag-item-name    { font-size: 16pt; font-weight: 700; color: #111; line-height: 1.2; margin-bottom: 6px; flex-shrink: 0; }
    .tag-detail       { font-size: 10pt; color: #555; margin-bottom: 4px; flex-shrink: 0; }
    .tag-date         { font-size: 9pt; color: #777; margin-bottom: 10px; flex-shrink: 0; }
    .tag-job          { background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; flex-shrink: 0; }
    .tag-job-label    { font-size: 8pt; color: #166534; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
    .tag-job-number   { font-size: 18pt; font-weight: 700; color: #15803d; font-family: monospace; }
    .tag-spacer       { flex: 1; min-height: 0; }
    .tag-notes        { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; flex-shrink: 0; }
    .tag-notes-label  { font-size: 8pt; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
    .tag-notes-text   { font-size: 10pt; color: #374151; white-space: pre-wrap; word-break: break-word; }
    .tag-footer       { margin-top: 8px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 7pt; color: #9ca3af; display: flex; justify-content: space-between; align-items: flex-end; flex-shrink: 0; }
    .tag-qr           { text-align: center; }
    .tag-qr img       { width: 72pt; height: 72pt; display: block; }
    .tag-qr-caption   { font-size: 6pt; color: #c0c0c0; margin-top: 2pt; }
</style>
</head><body>
<div class="tag">
    <div class="tag-header">
        <span class="tag-header-label">Inventory Record</span>
        <span class="tag-header-id">#{{ $inventoryReceipt->id }}</span>
    </div>
    <div class="tag-item-name">{{ addslashes($inventoryReceipt->item_name) }}</div>
    <div class="tag-detail"><strong>Qty:</strong> {{ rtrim(rtrim(number_format((float) $inventoryReceipt->quantity_received, 2), '0'), '.') }} {{ $inventoryReceipt->unit }}</div>
    <div class="tag-date">Received: {{ $inventoryReceipt->received_date?->format('M j, Y') ?? '—' }}</div>
    ${poHtml}
    ${jobHtml}
    ${noteHtml}
    <div class="tag-spacer"></div>
    <div class="tag-footer">
        <div>
            <div>RM Flooring</div>
            <div>{{ now()->format('M j, Y') }}</div>
        </div>
        <div class="tag-qr">
            <img src="${qrSrc}" alt="QR">
            <div class="tag-qr-caption">Scan for details</div>
        </div>
    </div>
</div>
</body></html>`;
    } else {
        // Standard: 4" × 6" portrait
        html = `<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
    @page { size: 4in 6in; margin: 0; }
    * { box-sizing: border-box; }
    html, body { width: 4in; height: 6in; margin: 0; padding: 0; overflow: hidden; }
    .tag {
        width: 4in; height: 6in; padding: 0.2in;
        font-family: Arial, Helvetica, sans-serif;
        display: flex; flex-direction: column; overflow: hidden;
    }
    .tag-header {
        background: #1d4ed8; color: #fff;
        padding: 7px 10px; border-radius: 4px; margin-bottom: 10px;
        display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
    }
    .tag-header-label { font-size: 10pt; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
    .tag-header-id    { font-size: 14pt; font-weight: 700; font-family: monospace; }
    .tag-item-name    { font-size: 16pt; font-weight: 700; color: #111; line-height: 1.2; margin-bottom: 6px; flex-shrink: 0; }
    .tag-detail       { font-size: 10pt; color: #555; margin-bottom: 4px; flex-shrink: 0; }
    .tag-date         { font-size: 9pt; color: #777; margin-bottom: 10px; flex-shrink: 0; }
    .tag-job          { background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; flex-shrink: 0; }
    .tag-job-label    { font-size: 8pt; color: #166534; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
    .tag-job-number   { font-size: 18pt; font-weight: 700; color: #15803d; font-family: monospace; }
    .tag-spacer       { flex: 1; min-height: 0; }
    .tag-notes        { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px 10px; margin-bottom: 10px; flex-shrink: 0; }
    .tag-notes-label  { font-size: 8pt; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 2px; }
    .tag-notes-text   { font-size: 10pt; color: #374151; white-space: pre-wrap; word-break: break-word; }
    .tag-footer       { margin-top: 8px; padding-top: 6px; border-top: 1px solid #e5e7eb; font-size: 7pt; color: #9ca3af; display: flex; justify-content: space-between; align-items: flex-end; flex-shrink: 0; }
    .tag-qr           { text-align: center; }
    .tag-qr img       { width: 72pt; height: 72pt; display: block; }
    .tag-qr-caption   { font-size: 6pt; color: #c0c0c0; margin-top: 2pt; }
</style>
</head><body>
<div class="tag">
    <div class="tag-header">
        <span class="tag-header-label">Inventory Record</span>
        <span class="tag-header-id">#{{ $inventoryReceipt->id }}</span>
    </div>
    <div class="tag-item-name">{{ addslashes($inventoryReceipt->item_name) }}</div>
    <div class="tag-detail"><strong>Qty:</strong> {{ rtrim(rtrim(number_format((float) $inventoryReceipt->quantity_received, 2), '0'), '.') }} {{ $inventoryReceipt->unit }}</div>
    <div class="tag-date">Received: {{ $inventoryReceipt->received_date?->format('M j, Y') ?? '—' }}</div>
    ${poHtml}
    ${jobHtml}
    ${noteHtml}
    <div class="tag-spacer"></div>
    <div class="tag-footer">
        <div>
            <div>RM Flooring</div>
            <div>{{ now()->format('M j, Y') }}</div>
        </div>
        <div class="tag-qr">
            <img src="${qrSrc}" alt="QR">
            <div class="tag-qr-caption">Scan for details</div>
        </div>
    </div>
</div>
</body></html>`;
    }

    const winW = 384;
    const winH = 576;
    const win  = window.open('', '_blank', `width=${winW},height=${winH}`);
    win.document.write(html);
    win.document.close();
    win.focus();
    win.onload = () => { win.print(); win.close(); };

    document.getElementById('print-tag-modal').classList.add('hidden');
}
</script>

</x-app-layout>
