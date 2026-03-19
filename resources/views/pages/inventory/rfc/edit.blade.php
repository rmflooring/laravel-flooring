{{-- resources/views/pages/inventory/rfc/edit.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.rfc.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">RFC</a>
                <span>/</span>
                <a href="{{ route('pages.inventory.rfc.show', $rfc) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $rfc->rfc_number }}</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">Edit</span>
            </nav>

            <form method="POST" action="{{ route('pages.inventory.rfc.update', $rfc) }}">
                @csrf @method('PUT')

                {{-- Return details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Return Details</h2>

                    @if ($rfc->pickTicket)
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            Linked to
                            <a href="{{ route('pages.warehouse.pick-tickets.show', $rfc->pickTicket) }}"
                               class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                PT #{{ $rfc->pickTicket->pt_number }}
                            </a>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for return</label>
                        <textarea name="reason" rows="2"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('reason', $rfc->reason) }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes', $rfc->notes) }}</textarea>
                    </div>
                </div>

                {{-- Items --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm mt-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items Being Returned</h2>
                        <button type="button" id="add-item-btn"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add item
                        </button>
                    </div>

                    <div id="items-container" class="divide-y divide-gray-100 dark:divide-gray-700">
                        {{-- Pre-populated by JS from existing items --}}
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('pages.inventory.rfc.show', $rfc) }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>

{{-- Item row template --}}
<template id="item-row-template">
    <div class="item-row px-6 py-4 space-y-3" data-index="__IDX__">
        <div class="flex items-start gap-3">
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Item name</label>
                    <input type="text" name="items[__IDX__][item_name]" required
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Unit</label>
                    <input type="text" name="items[__IDX__][unit]"
                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
            </div>
            <button type="button" class="remove-item-btn mt-6 text-gray-400 hover:text-red-500 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Qty returning</label>
                <input type="number" name="items[__IDX__][quantity_returned]" required min="0.01" step="0.01"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Condition</label>
                <select name="items[__IDX__][condition]"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">— Select —</option>
                    <option value="good">Good</option>
                    <option value="damaged">Damaged</option>
                    <option value="partial">Partial / Open box</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Item notes</label>
                <input type="text" name="items[__IDX__][notes]"
                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
        </div>
        <input type="hidden" name="items[__IDX__][pick_ticket_item_id]" class="pt-item-id-input">
        <input type="hidden" name="items[__IDX__][sale_item_id]" class="sale-item-id-input">
    </div>
</template>

<script>
(function () {
    let itemIndex = 0;
    const container = document.getElementById('items-container');
    const template  = document.getElementById('item-row-template');

    function addRow(data = {}) {
        const idx  = itemIndex++;
        const html = template.innerHTML.replaceAll('__IDX__', idx);
        const div  = document.createElement('div');
        div.innerHTML = html;
        const row = div.firstElementChild;

        if (data.item_name)       row.querySelector('[name$="[item_name]"]').value        = data.item_name;
        if (data.unit)            row.querySelector('[name$="[unit]"]').value              = data.unit;
        if (data.qty !== undefined) row.querySelector('[name$="[quantity_returned]"]').value = data.qty;
        if (data.condition)       row.querySelector('[name$="[condition]"]').value         = data.condition;
        if (data.notes)           row.querySelector('[name$="[notes]"]').value             = data.notes;
        if (data.ptItemId)        row.querySelector('.pt-item-id-input').value             = data.ptItemId;
        if (data.saleItemId)      row.querySelector('.sale-item-id-input').value           = data.saleItemId;

        row.querySelector('.remove-item-btn').addEventListener('click', function () {
            row.remove();
        });

        container.appendChild(row);
    }

    // Pre-populate existing items
    const existingItems = @json($rfc->items->map(fn($i) => [
        'item_name'           => $i->item_name,
        'unit'                => $i->unit,
        'qty'                 => (float) $i->quantity_returned,
        'condition'           => $i->condition,
        'notes'               => $i->notes,
        'ptItemId'            => $i->pick_ticket_item_id,
        'saleItemId'          => $i->sale_item_id,
    ]));

    existingItems.forEach(addRow);

    document.getElementById('add-item-btn').addEventListener('click', function () {
        addRow();
    });
})();
</script>

</x-app-layout>
