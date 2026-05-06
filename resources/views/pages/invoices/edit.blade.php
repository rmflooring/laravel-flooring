<x-app-layout>
<div class="py-8">
<div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Invoice {{ $invoice->invoice_number }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Invoice {{ $invoice->invoice_number }}</h1>

    @if (session('error'))
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

@php
    $roomsJson = $invoice->rooms->map(fn($room) => [
        'id'    => $room->id,
        'name'  => $room->name,
        'items' => $room->items->map(fn($item) => [
            'id'         => $item->id,
            'item_type'  => $item->item_type,
            'label'      => $item->label,
            'quantity'   => (float) $item->quantity,
            'unit'       => $item->unit ?? '',
            'sell_price' => (float) $item->sell_price,
            'tax_rate'   => (float) $item->tax_rate,
        ])->values()->all(),
    ])->values()->all();
    $taxRate = (float) ($sale->tax_rate_percent ?? 0);
@endphp

<div x-data="invoiceEditor({{ json_encode($roomsJson) }}, {{ $taxRate }})" x-init="init({{ json_encode($roomsJson) }})">
<form action="{{ route('pages.sales.invoices.update', [$sale, $invoice]) }}" method="POST">
    @csrf @method('PUT')

    {{-- Header card --}}
    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 space-y-5 mb-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Invoice Details</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="draft" {{ old('status', $invoice->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent"  {{ old('status', $invoice->status) === 'sent'  ? 'selected' : '' }}>Sent</option>
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Payment Terms</label>
                <select name="payment_term_id" id="payment_term_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">— None —</option>
                    @foreach ($paymentTerms as $term)
                        <option value="{{ $term->id }}"
                            data-days="{{ $term->days }}"
                            data-dom="{{ $term->day_of_month }}"
                            {{ old('payment_term_id', $invoice->payment_term_id) == $term->id ? 'selected' : '' }}>
                            {{ $term->name }}{{ $term->days ? ' (Net ' . $term->days . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                <input type="date" name="due_date" id="due_date"
                    value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Customer PO #</label>
                <input type="text" name="customer_po_number"
                    value="{{ old('customer_po_number', $invoice->customer_po_number) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="sm:col-span-2">
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                <textarea name="notes" rows="2"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $invoice->notes) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Rooms --}}
    <template x-for="(room, ri) in rooms" :key="room._key">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 mb-4 overflow-hidden">

            {{-- Room header --}}
            <div class="flex items-center gap-3 px-5 py-3 bg-blue-700">
                <svg class="h-4 w-4 text-white flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.5L12 4l9 5.5V20H3V9.5z"/>
                </svg>
                <input type="hidden" :name="`rooms[${ri}][id]`" :value="room.id ?? ''">
                <input :name="`rooms[${ri}][name]`" x-model="room.name"
                    placeholder="Room name"
                    class="flex-1 bg-transparent border-0 border-b border-blue-400 text-white placeholder-blue-300 text-sm font-semibold focus:outline-none focus:border-white py-0.5">
                <button type="button" @click="removeRoom(ri)"
                    class="ml-auto text-blue-200 hover:text-white flex-shrink-0"
                    title="Remove room">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>

            {{-- Items table --}}
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <th class="px-4 py-2 text-left w-28">Type</th>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-center w-20">Qty</th>
                        <th class="px-4 py-2 text-center w-20">Unit</th>
                        <th class="px-4 py-2 text-right w-28">Unit Price</th>
                        <th class="px-4 py-2 text-right w-28">Line Total</th>
                        <th class="px-4 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="(item, ii) in room.items" :key="item._key">
                        <tr>
                            <td class="px-4 py-2">
                                <input type="hidden" :name="`rooms[${ri}][items][${ii}][id]`" :value="item.id ?? ''">
                                <select :name="`rooms[${ri}][items][${ii}][item_type]`" x-model="item.item_type"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option value="material">Material</option>
                                    <option value="labour">Labour</option>
                                    <option value="freight">Freight</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input :name="`rooms[${ri}][items][${ii}][label]`" x-model="item.label"
                                    placeholder="Description"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" :name="`rooms[${ri}][items][${ii}][quantity]`" x-model="item.quantity"
                                    min="0" step="0.01"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 text-center dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </td>
                            <td class="px-4 py-2">
                                <input :name="`rooms[${ri}][items][${ii}][unit]`" x-model="item.unit"
                                    placeholder="SF"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 text-center dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" :name="`rooms[${ri}][items][${ii}][sell_price]`" x-model="item.sell_price"
                                    min="0" step="0.01"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 text-right dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </td>
                            <td class="px-4 py-2 text-right font-medium text-gray-800 dark:text-gray-200"
                                x-text="'$' + lineTotal(item).toFixed(2)"></td>
                            <td class="px-4 py-2 text-center">
                                <button type="button" @click="removeItem(ri, ii)"
                                    class="text-red-400 hover:text-red-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-gray-700 text-sm">
                        <td colspan="4" class="px-4 py-2">
                            <button type="button" @click="addItem(ri)"
                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                                + Add Item
                            </button>
                        </td>
                        <td class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">Room Subtotal</td>
                        <td class="px-4 py-2 text-right font-bold text-gray-900 dark:text-white"
                            x-text="'$' + roomSubtotal(ri).toFixed(2)"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </template>

    {{-- Add room button --}}
    <div class="mb-6">
        <button type="button" @click="addRoom()"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 dark:bg-gray-800 dark:text-blue-400 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Room
        </button>
    </div>

    {{-- Totals --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 p-5 mb-6">
        <div class="flex flex-col items-end gap-1 text-sm">
            <div class="flex gap-8 text-gray-600 dark:text-gray-300">
                <span>Subtotal</span>
                <span class="w-32 text-right" x-text="'$' + subtotal().toFixed(2)"></span>
            </div>
            <div class="flex gap-8 text-gray-600 dark:text-gray-300">
                <span>Tax ({{ number_format($taxRate, 1) }}%)</span>
                <span class="w-32 text-right" x-text="'$' + taxTotal().toFixed(2)"></span>
            </div>
            <div class="flex gap-8 text-base font-bold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-600 pt-2 mt-1">
                <span>Total</span>
                <span class="w-32 text-right" x-text="'$' + grandTotal().toFixed(2)"></span>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <button type="submit"
            class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-6 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
            Save Invoice
        </button>
        <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
            class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
            Cancel
        </a>
    </div>

</form>
</div>

</div>
</div>

<script>
function invoiceEditor(initialRooms, taxRate) {
    return {
        rooms: [],
        taxRate: taxRate,
        _nextKey: 1000,

        init(rooms) {
            this.rooms = rooms.map(room => ({
                ...room,
                _key: this._nextKey++,
                items: room.items.map(item => ({
                    ...item,
                    _key: this._nextKey++,
                })),
            }));
        },

        addRoom() {
            this.rooms.push({
                _key: this._nextKey++,
                id: null,
                name: '',
                items: [],
            });
        },

        removeRoom(ri) {
            if (confirm('Remove this room and all its items?')) {
                this.rooms.splice(ri, 1);
            }
        },

        addItem(ri) {
            this.rooms[ri].items.push({
                _key: this._nextKey++,
                id: null,
                item_type: 'material',
                label: '',
                quantity: 1,
                unit: '',
                sell_price: 0,
                tax_rate: this.taxRate,
            });
        },

        removeItem(ri, ii) {
            this.rooms[ri].items.splice(ii, 1);
        },

        lineTotal(item) {
            return Math.round(parseFloat(item.quantity || 0) * parseFloat(item.sell_price || 0) * 100) / 100;
        },

        roomSubtotal(ri) {
            return this.rooms[ri].items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },

        subtotal() {
            return this.rooms.reduce((sum, room, ri) => sum + this.roomSubtotal(ri), 0);
        },

        taxTotal() {
            return Math.round(this.subtotal() * this.taxRate / 100 * 100) / 100;
        },

        grandTotal() {
            return Math.round((this.subtotal() + this.taxTotal()) * 100) / 100;
        },
    };
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('payment_term_id').addEventListener('change', function () {
        const opt  = this.options[this.selectedIndex];
        const dom  = parseInt(opt.dataset.dom);
        const days = parseInt(opt.dataset.days);
        const dueDateInput = document.getElementById('due_date');
        if (dom > 0) {
            const d = new Date();
            d.setMonth(d.getMonth() + 1, dom);
            dueDateInput.value = d.toISOString().slice(0, 10);
        } else if (days >= 0) {
            const d = new Date();
            d.setDate(d.getDate() + days);
            dueDateInput.value = d.toISOString().slice(0, 10);
        }
    });
});
</script>
</x-app-layout>
