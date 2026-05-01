<x-app-layout>
<div class="max-w-4xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quick Return</h1>
            <p class="text-sm text-gray-500 mt-0.5">Look up the original sale, select items, and issue a refund</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="return-form" action="{{ route('pages.quick-returns.store') }}" method="POST"
          x-data="quickReturnForm()">
        @csrf

        {{-- Hidden fields populated on submit --}}
        <input type="hidden" name="sale_id" :value="sale?.id ?? ''">
        <div id="item-inputs"></div>

        <div class="space-y-6">

            {{-- STEP 1: Find the sale --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600 text-xs font-bold">1</span>
                    Find Original Sale
                </h2>

                <div class="relative">
                    <input type="text"
                        x-model="saleSearch"
                        @input.debounce.300="searchSales()"
                        @focus="if (saleSearch.length >= 1 && !sale) showResults = true"
                        placeholder="Search by sale number, customer name, or job name…"
                        :disabled="!!sale"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-400 focus:border-rose-400 disabled:bg-gray-50 disabled:text-gray-500">
                    <ul x-show="showResults && searchResults.length > 0"
                        @click.outside="showResults = false"
                        class="absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto text-sm">
                        <template x-for="s in searchResults" :key="s.id">
                            <li @click="selectSale(s)"
                                class="px-4 py-3 hover:bg-rose-50 cursor-pointer flex justify-between items-center border-b border-gray-100 last:border-0">
                                <div>
                                    <span class="font-semibold text-gray-800" x-text="'Sale #' + s.sale_number"></span>
                                    <span x-show="s.is_quick" class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700">Quick</span>
                                    <div class="text-xs text-gray-500 mt-0.5" x-text="s.label + (s.job_name ? ' — ' + s.job_name : '')"></div>
                                </div>
                                <div class="text-right text-xs text-gray-400 ml-4 shrink-0">
                                    <div x-text="s.date"></div>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Selected sale chip --}}
                <div x-show="sale" x-cloak class="mt-4 bg-rose-50 border border-rose-200 rounded-lg px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-rose-800" x-text="'Sale #' + sale?.sale_number + ' — ' + sale?.label"></p>
                        <p class="text-xs text-rose-600 mt-0.5" x-text="sale?.date + (sale?.job_name ? ' · ' + sale?.job_name : '')"></p>
                    </div>
                    <button type="button" @click="clearSale()" class="text-rose-400 hover:text-rose-600 ml-4 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div x-show="loading" x-cloak class="mt-3 text-sm text-gray-400">Loading items…</div>
            </div>

            {{-- STEP 2: Select items --}}
            <div x-show="sale && saleItems.length > 0" x-cloak
                 class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">

                <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600 text-xs font-bold">2</span>
                    Select Items to Return
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                                <th class="pb-2 text-left w-8"></th>
                                <th class="pb-2 text-left">Item</th>
                                <th class="pb-2 text-center w-20">Sold</th>
                                <th class="pb-2 text-center w-20">Returned</th>
                                <th class="pb-2 text-center w-20">Available</th>
                                <th class="pb-2 text-right w-24">Price Paid</th>
                                <th class="pb-2 text-center w-28">Return Qty</th>
                                <th class="pb-2 text-right w-24">Refund</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template x-for="item in saleItems" :key="item.id">
                                <tr :class="item.returnable <= 0 ? 'opacity-40' : ''">
                                    <td class="py-3 pr-2">
                                        <input type="checkbox"
                                            :disabled="item.returnable <= 0"
                                            x-model="selected[item.id].checked"
                                            @change="onToggle(item)"
                                            class="rounded border-gray-300 text-rose-600 focus:ring-rose-400 disabled:cursor-not-allowed">
                                    </td>
                                    <td class="py-3 pr-4">
                                        <p class="font-medium text-gray-800" x-text="item.label"></p>
                                        <p x-show="item.manufacturer" class="text-xs text-gray-400" x-text="item.manufacturer"></p>
                                        <p x-show="item.returnable <= 0" class="text-xs text-rose-400 mt-0.5">Fully returned</p>
                                    </td>
                                    <td class="py-3 text-center text-gray-600"
                                        x-text="fmtQty(item.quantity_sold) + (item.unit ? ' ' + item.unit : '')"></td>
                                    <td class="py-3 text-center text-gray-400"
                                        x-text="item.already_returned > 0 ? fmtQty(item.already_returned) : '—'"></td>
                                    <td class="py-3 text-center font-medium"
                                        :class="item.returnable > 0 ? 'text-gray-800' : 'text-gray-400'"
                                        x-text="fmtQty(item.returnable)"></td>
                                    <td class="py-3 text-right text-gray-700"
                                        x-text="'$' + item.sell_price.toFixed(2)"></td>
                                    <td class="py-3 text-center">
                                        <input type="number" step="0.01" min="0.01"
                                            :max="item.returnable"
                                            x-model="selected[item.id].quantity"
                                            @input="clampQty(item); recalc()"
                                            :disabled="!selected[item.id].checked"
                                            class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm text-center focus:ring-2 focus:ring-rose-400 disabled:bg-gray-50 disabled:text-gray-400">
                                    </td>
                                    <td class="py-3 text-right font-medium text-gray-800"
                                        x-text="selected[item.id].checked ? '$' + lineTotal(item).toFixed(2) : '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <p x-show="saleItems.every(i => i.returnable <= 0)" x-cloak
                   class="mt-3 text-sm text-rose-600">All items on this sale have already been fully returned.</p>
            </div>

            {{-- STEP 3: Refund details + submit --}}
            <div x-show="anySelected()" x-cloak class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Summary --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-rose-100 text-rose-600 text-xs font-bold">3</span>
                        Refund Summary
                    </h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-medium text-gray-800" x-text="'$' + totals.subtotal.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between" x-show="totals.taxRate > 0">
                            <span class="text-gray-500">Tax (<span x-text="totals.taxRate.toFixed(3)"></span>%)</span>
                            <span class="font-medium text-gray-800" x-text="'$' + totals.taxAmount.toFixed(2)"></span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 flex justify-between text-base font-bold">
                            <span class="text-gray-800">Total Refund</span>
                            <span class="text-rose-600" x-text="'$' + totals.grandTotal.toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                {{-- Refund method + notes --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H5a2 2 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Refund Method
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Method <span class="text-red-500">*</span></label>
                            <select name="refund_method" x-model="refundMethod"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-400">
                                @foreach ($refundMethods as $value => $label)
                                    <option value="{{ $value }}" {{ old('refund_method', 'cash') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="refundMethod !== 'cash'" x-cloak>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reference / Transaction #</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                                placeholder="Optional"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-400">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                            <textarea name="notes" rows="2" placeholder="Reason for return, condition of goods, etc."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-rose-400">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="anySelected()" x-cloak>
                <button type="button" @click="submitReturn()"
                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm py-3 px-4 rounded-xl shadow transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Record Return & Issue Refund
                </button>
            </div>

        </div>
    </form>
</div>

<script>
const QR_SALES_URL      = @json(route('pages.quick-returns.api.sales'));
const QR_SALE_ITEMS_URL = @json(url('pages/quick-returns/api/sales'));

function quickReturnForm() {
    return {
        saleSearch:    '',
        showResults:   false,
        searchResults: [],
        sale:          null,
        saleItems:     [],
        selected:      {},
        loading:       false,
        refundMethod:  '{{ old('refund_method', 'cash') }}',
        totals:        { subtotal: 0, taxRate: 0, taxAmount: 0, grandTotal: 0 },

        async searchSales() {
            const q = this.saleSearch.trim();
            if (q.length < 1) { this.searchResults = []; this.showResults = false; return; }
            const r = await fetch(QR_SALES_URL + '?q=' + encodeURIComponent(q));
            this.searchResults = await r.json();
            this.showResults   = this.searchResults.length > 0;
        },

        async selectSale(s) {
            this.sale        = s;
            this.saleSearch  = 'Sale #' + s.sale_number + ' — ' + s.label;
            this.showResults = false;
            this.loading     = true;
            this.saleItems   = [];
            this.selected    = {};

            const r     = await fetch(QR_SALE_ITEMS_URL + '/' + s.id + '/items');
            const items = await r.json();
            this.saleItems = items;

            items.forEach(item => {
                this.selected[item.id] = { checked: false, quantity: item.returnable };
            });

            this.loading = false;
            this.recalc();
        },

        clearSale() {
            this.sale          = null;
            this.saleSearch    = '';
            this.saleItems     = [];
            this.selected      = {};
            this.searchResults = [];
            this.totals        = { subtotal: 0, taxRate: 0, taxAmount: 0, grandTotal: 0 };
        },

        onToggle(item) {
            if (this.selected[item.id].checked && !parseFloat(this.selected[item.id].quantity)) {
                this.selected[item.id].quantity = item.returnable;
            }
            this.recalc();
        },

        clampQty(item) {
            const val = parseFloat(this.selected[item.id].quantity) || 0;
            if (val > item.returnable) this.selected[item.id].quantity = item.returnable;
        },

        lineTotal(item) {
            if (!this.selected[item.id]?.checked) return 0;
            return Math.round((parseFloat(this.selected[item.id].quantity) || 0) * item.sell_price * 100) / 100;
        },

        recalc() {
            let subtotal = 0;
            this.saleItems.forEach(item => { subtotal += this.lineTotal(item); });
            subtotal = Math.round(subtotal * 100) / 100;

            const taxRate   = parseFloat(this.sale?.tax_rate_percent || 0);
            const taxAmount = Math.round(subtotal * (taxRate / 100) * 100) / 100;

            this.totals = { subtotal, taxRate, taxAmount, grandTotal: subtotal + taxAmount };
        },

        anySelected() {
            return Object.values(this.selected).some(s => s.checked);
        },

        fmtQty(n) {
            return parseFloat(n).toFixed(2).replace(/\.?0+$/, '');
        },

        submitReturn() {
            // Build hidden item inputs dynamically
            const container = document.getElementById('item-inputs');
            container.innerHTML = '';
            let idx = 0;
            this.saleItems.forEach(item => {
                if (!this.selected[item.id]?.checked) return;
                const addInput = (name, value) => {
                    const el = document.createElement('input');
                    el.type = 'hidden'; el.name = name; el.value = value;
                    container.appendChild(el);
                };
                addInput(`items[${idx}][sale_item_id]`, item.id);
                addInput(`items[${idx}][quantity]`, this.selected[item.id].quantity);
                idx++;
            });

            if (idx === 0) { alert('Please select at least one item to return.'); return; }

            document.getElementById('return-form').submit();
        },
    };
}
</script>
</x-app-layout>
