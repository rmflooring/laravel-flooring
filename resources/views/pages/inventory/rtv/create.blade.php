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
                    <p class="text-sm text-gray-500 dark:text-gray-400">Select the inventory record to return. Items from the linked purchase order will load automatically.</p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inventory Record</label>
                        <select name="inventory_receipt_id" id="receipt-select"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select a record —</option>
                            @foreach ($receipts as $r)
                                <option value="{{ $r->id }}"
                                        data-receipt-id="{{ $r->id }}"
                                        data-items="{{ json_encode($r->purchaseOrder->items->map(fn($i) => [
                                            'id'                => $i->id,
                                            'item_name'         => $i->item_name,
                                            'unit'              => $i->unit,
                                            'quantity'          => (float) $i->quantity,
                                            'returned_quantity' => (float) $i->returned_quantity,
                                            'cost_price'        => (float) $i->cost_price,
                                        ])) }}"
                                        {{ old('inventory_receipt_id') == $r->id || (isset($receipt) && $receipt->id == $r->id) ? 'selected' : '' }}>
                                    Record #{{ $r->id }}
                                    — {{ $r->purchaseOrder->vendor->name ?? 'Unknown vendor' }}
                                    (PO #{{ $r->purchaseOrder->po_number }})
                                </option>
                            @endforeach
                        </select>
                        @error('inventory_receipt_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="vendor-info" class="hidden text-sm text-gray-600 dark:text-gray-400 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg px-4 py-3">
                    </div>
                </div>

                {{-- Return details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mt-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Return Details</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason <span class="text-red-500">*</span></label>
                        <select name="reason"
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

                    <div id="items-container" class="divide-y divide-gray-100 dark:divide-gray-700">
                    </div>

                    <div id="no-items-msg" class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        Select an inventory record above to load PO items.
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
    <div class="item-row px-6 py-4" data-index="__IDX__">
        <div class="flex items-start gap-4">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white item-name-display"></p>
                <p class="text-xs text-gray-400 mt-0.5 item-unit-display"></p>
            </div>
            <div class="flex items-center gap-4 shrink-0">
                <div class="text-right text-xs text-gray-500">
                    <div>Ordered: <span class="item-qty-ordered font-medium text-gray-700 dark:text-gray-300"></span></div>
                    <div>Already returned: <span class="item-qty-returned text-orange-600 font-medium"></span></div>
                    <div>Remaining: <span class="item-qty-remaining font-semibold text-gray-900 dark:text-white"></span></div>
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Qty returning</label>
                    <input type="number" name="items[__IDX__][quantity_returned]" required min="0.01" step="0.01"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
        </div>
        <input type="hidden" name="items[__IDX__][purchase_order_item_id]" class="po-item-id-input">
    </div>
</template>

<script>
(function () {
    let itemIndex = 0;
    const container    = document.getElementById('items-container');
    const noItemsMsg   = document.getElementById('no-items-msg');
    const template     = document.getElementById('item-row-template');
    const vendorInfo   = document.getElementById('vendor-info');

    function syncNoItemsMsg() {
        const hasRows = container.querySelectorAll('.item-row').length > 0;
        noItemsMsg.style.display = hasRows ? 'none' : 'block';
    }

    function addRow(item) {
        const remaining = Math.max(0, item.quantity - item.returned_quantity);
        const idx  = itemIndex++;
        const html = template.innerHTML.replaceAll('__IDX__', idx);
        const div  = document.createElement('div');
        div.innerHTML = html;
        const row = div.firstElementChild;

        row.querySelector('.item-name-display').textContent = item.item_name;
        row.querySelector('.item-unit-display').textContent = item.unit || '';
        row.querySelector('.item-qty-ordered').textContent  = item.quantity;
        row.querySelector('.item-qty-returned').textContent = item.returned_quantity;
        row.querySelector('.item-qty-remaining').textContent = remaining;

        const qtyInput = row.querySelector('[name$="[quantity_returned]"]');
        qtyInput.value = remaining;
        qtyInput.max   = remaining;

        row.querySelector('.po-item-id-input').value = item.id;

        container.appendChild(row);
        syncNoItemsMsg();
    }

    document.getElementById('receipt-select').addEventListener('change', function () {
        container.innerHTML = '';
        vendorInfo.classList.add('hidden');
        vendorInfo.textContent = '';

        const selected = this.options[this.selectedIndex];
        if (!selected.value) {
            syncNoItemsMsg();
            return;
        }

        const items = selected.dataset.items ? JSON.parse(selected.dataset.items) : [];
        items.forEach(function (item) {
            const remaining = item.quantity - item.returned_quantity;
            if (remaining <= 0) return;
            addRow(item);
        });

        if (items.length) {
            vendorInfo.classList.remove('hidden');
            vendorInfo.innerHTML = '<span class="font-medium">Record #' + selected.dataset.receiptId + '</span> — ' + selected.text.split('—').slice(1).join('—').trim();
        }

        syncNoItemsMsg();
    });

    // Auto-load if pre-selected
    const sel = document.getElementById('receipt-select');
    if (sel.value) {
        sel.dispatchEvent(new Event('change'));
    }

    syncNoItemsMsg();
})();
</script>

</x-app-layout>
