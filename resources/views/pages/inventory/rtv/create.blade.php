{{-- resources/views/pages/inventory/rtv/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.rtv.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">RTV</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">New Return to Vendor</span>
            </nav>

            <form method="POST" action="{{ route('pages.inventory.rtv.store') }}" id="rtv-form">
                @csrf

                {{-- Receipt selector --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Source Inventory Record</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Select the inventory record to return. This can be stock received from a PO <strong>or</strong> stock that came back from a customer via an RFC.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inventory Record</label>
                        <select name="inventory_receipt_id" id="receipt-select"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select a record —</option>
                            @foreach ($receipts as $r)
                                @php
                                    $isPo      = (bool) $r->purchase_order_id;
                                    $isRfc     = ! $isPo && $r->customerReturnItem;
                                    $availQty  = (float) $r->available_qty;

                                    if ($isPo) {
                                        // PO receipt: items come from PO items
                                        $items = $r->purchaseOrder->items->map(fn($i) => [
                                            'po_item_id'  => $i->id,
                                            'item_name'   => $i->item_name,
                                            'unit'        => $i->unit,
                                            'available'   => max(0, (float)$i->quantity - (float)$i->returned_quantity),
                                            'unit_cost'   => (float) $i->cost_price,
                                        ])->filter(fn($i) => $i['available'] > 0)->values();

                                        $sourceLabel = 'PO #' . $r->purchaseOrder->po_number . ' — ' . ($r->purchaseOrder->vendor->company_name ?? 'Unknown vendor');
                                    } else {
                                        // RFC or manual receipt: one row from the receipt itself
                                        $rfcNumber = $r->customerReturnItem?->customerReturn?->rfc_number ?? null;
                                        $items = collect([[
                                            'po_item_id'  => null,
                                            'item_name'   => $r->item_name,
                                            'unit'        => $r->unit,
                                            'available'   => $availQty,
                                            'unit_cost'   => 0,
                                        ]]);
                                        $sourceLabel = $rfcNumber ? 'RFC ' . $rfcNumber : 'Manual receipt #' . $r->id;
                                    }
                                @endphp
                                <option value="{{ $r->id }}"
                                        data-source="{{ $isPo ? 'po' : 'rfc' }}"
                                        data-vendor-name="{{ $isPo ? ($r->purchaseOrder->vendor->company_name ?? '') : '' }}"
                                        data-source-label="{{ $sourceLabel }}"
                                        data-items="{{ json_encode($items) }}"
                                        {{ old('inventory_receipt_id') == $r->id || (isset($receipt) && $receipt->id == $r->id) ? 'selected' : '' }}>
                                    {{ $sourceLabel }} — {{ $r->item_name }} ({{ rtrim(rtrim(number_format($availQty, 2), '0'), '.') }} {{ $r->unit }} available)
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_receipt_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Source info bar --}}
                    <div id="source-info" style="display:none"
                         class="text-sm text-gray-600 dark:text-gray-400 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg px-4 py-3">
                    </div>

                    {{-- Vendor selector — shown only for non-PO (RFC/manual) receipts --}}
                    <div id="vendor-selector" style="display:none">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Vendor to return to <span class="text-red-500">*</span>
                        </label>
                        <select name="vendor_id"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select vendor —</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>
                                    {{ $v->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('vendor_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Return details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mt-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Return Details</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason <span class="text-red-500">*</span></label>
                        <select name="reason" required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select a reason —</option>
                            @foreach (\App\Models\InventoryReturn::REASON_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ old('reason') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  placeholder="Any additional notes…"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Items --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm mt-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items to Return</h2>
                    </div>

                    <div id="items-container" class="divide-y divide-gray-100 dark:divide-gray-700"></div>

                    <div id="no-items-msg" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        Select an inventory record above to load items.
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('pages.inventory.rtv.index') }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-orange-600 px-5 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-300">
                        Save as Draft
                    </button>
                </div>

            </form>
        </div>
    </div>

{{-- Item row template --}}
<template id="item-row-template">
    <div class="item-row px-6 py-4 space-y-3" data-index="__IDX__">
        <div class="flex items-start gap-4">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white item-name-display"></p>
                <p class="text-xs text-gray-400 mt-0.5 item-unit-display"></p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                    <div>Available: <span class="item-qty-available font-semibold text-gray-900 dark:text-white"></span></div>
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Qty returning</label>
                    <input type="number" name="items[__IDX__][quantity_returned]" required min="0.01" step="0.01"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div class="w-28 unit-cost-field">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Unit cost ($)</label>
                    <input type="number" name="items[__IDX__][unit_cost]" min="0" step="0.0001"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>
        <input type="hidden" name="items[__IDX__][purchase_order_item_id]" class="po-item-id-input">
        <input type="hidden" name="items[__IDX__][item_name]" class="item-name-input">
        <input type="hidden" name="items[__IDX__][unit]" class="item-unit-input">
    </div>
</template>

<script>
(function () {
    let itemIndex = 0;
    const container    = document.getElementById('items-container');
    const noItemsMsg   = document.getElementById('no-items-msg');
    const template     = document.getElementById('item-row-template');
    const sourceInfo   = document.getElementById('source-info');
    const vendorSel    = document.getElementById('vendor-selector');

    function syncNoItemsMsg() {
        noItemsMsg.style.display = container.querySelectorAll('.item-row').length > 0 ? 'none' : 'block';
    }

    function addRow(item, isPo) {
        const idx  = itemIndex++;
        const html = template.innerHTML.replaceAll('__IDX__', idx);
        const div  = document.createElement('div');
        div.innerHTML = html;
        const row = div.firstElementChild;

        row.querySelector('.item-name-display').textContent  = item.item_name;
        row.querySelector('.item-unit-display').textContent  = item.unit || '';
        row.querySelector('.item-qty-available').textContent = item.available;

        const qtyInput  = row.querySelector('[name$="[quantity_returned]"]');
        qtyInput.value  = item.available;
        qtyInput.max    = item.available;

        const costInput = row.querySelector('[name$="[unit_cost]"]');
        if (isPo && item.unit_cost > 0) {
            // PO receipt: pre-fill cost, make it readonly (comes from PO item)
            costInput.value    = item.unit_cost;
            costInput.readOnly = true;
            costInput.classList.add('bg-gray-50', 'dark:bg-gray-700/50', 'text-gray-500');
        } else {
            // RFC/manual receipt: user must enter cost
            costInput.value       = '';
            costInput.placeholder = 'Enter cost…';
        }

        row.querySelector('.po-item-id-input').value  = item.po_item_id || '';
        row.querySelector('.item-name-input').value   = item.item_name || '';
        row.querySelector('.item-unit-input').value   = item.unit || '';

        container.appendChild(row);
        syncNoItemsMsg();
    }

    document.getElementById('receipt-select').addEventListener('change', function () {
        container.innerHTML = '';
        sourceInfo.style.display = 'none';
        sourceInfo.textContent   = '';
        vendorSel.style.display  = 'none';

        const selected = this.options[this.selectedIndex];
        if (!selected.value) { syncNoItemsMsg(); return; }

        const source  = selected.dataset.source;   // 'po' or 'rfc'
        const isPo    = source === 'po';
        const items   = selected.dataset.items ? JSON.parse(selected.dataset.items) : [];

        items.forEach(function (item) {
            if (item.available <= 0) return;
            addRow(item, isPo);
        });

        sourceInfo.style.display = 'block';
        if (isPo) {
            sourceInfo.innerHTML = '<span class="font-medium">' + selected.dataset.sourceLabel + '</span> — vendor auto-detected from PO.';
        } else {
            sourceInfo.innerHTML = '<span class="font-medium">' + selected.dataset.sourceLabel + '</span> — RFC stock. Select the vendor to return to below.';
            vendorSel.style.display = 'block';
        }

        syncNoItemsMsg();
    });

    // Auto-load if pre-selected
    const sel = document.getElementById('receipt-select');
    if (sel.value) sel.dispatchEvent(new Event('change'));

    syncNoItemsMsg();
})();
</script>

</x-app-layout>
