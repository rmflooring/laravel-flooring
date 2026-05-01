<x-app-layout>
<div class="max-w-5xl mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quick Return</h1>
            <p class="text-sm text-gray-500 mt-0.5">Record a walk-in return and issue a refund</p>
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

    <form action="{{ route('pages.quick-returns.store') }}" method="POST" id="quick-return-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT COLUMN --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- CUSTOMER --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                     x-data="customerPanel()">

                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Customer
                    </h2>

                    <div class="flex gap-2 mb-4">
                        <button type="button" @click="setMode('existing')"
                            :class="mode === 'existing' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-4 py-1.5 text-sm font-medium rounded-lg border transition">Select Existing</button>
                        <button type="button" @click="setMode('new')"
                            :class="mode === 'new' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-4 py-1.5 text-sm font-medium rounded-lg border transition">+ New Customer</button>
                        <button type="button" @click="setMode('walkin')"
                            :class="mode === 'walkin' ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-4 py-1.5 text-sm font-medium rounded-lg border transition">Walk-in</button>
                    </div>

                    <input type="hidden" name="customer_mode" :value="mode">

                    {{-- Existing --}}
                    <div x-show="mode === 'existing'" x-cloak>
                        <div class="relative">
                            <input type="text" x-model="searchText"
                                @input.debounce.300="search()"
                                @focus="if(searchText.length >= 1) open = true"
                                placeholder="Search by name, phone, or email…"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            <ul x-show="open && results.length > 0" @click.outside="open = false"
                                class="absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-52 overflow-y-auto text-sm">
                                <template x-for="c in results" :key="c.id">
                                    <li @click="select(c)" class="px-3 py-2 hover:bg-indigo-50 cursor-pointer flex flex-col">
                                        <span class="font-medium text-gray-800" x-text="c.name"></span>
                                        <span class="text-gray-500 text-xs" x-text="[c.phone, c.email].filter(Boolean).join(' · ')"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <input type="hidden" name="customer_id" :value="selected?.id ?? ''">
                        <div x-show="selected" x-cloak class="mt-3 flex items-center gap-3 bg-indigo-50 border border-indigo-200 rounded-lg px-4 py-2.5">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-indigo-800" x-text="selected?.name"></p>
                                <p class="text-xs text-indigo-600" x-text="[selected?.phone, selected?.email].filter(Boolean).join(' · ')"></p>
                            </div>
                            <button type="button" @click="selected = null; searchText = ''" class="text-indigo-400 hover:text-indigo-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p x-show="!selected" class="mt-2 text-xs text-gray-400">Search and select a customer above.</p>
                    </div>

                    {{-- New customer --}}
                    <div x-show="mode === 'new'" x-cloak class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="new_customer_name" value="{{ old('new_customer_name') }}"
                                placeholder="Full name or company"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                                <input type="text" name="new_customer_phone" value="{{ old('new_customer_phone') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                                <input type="email" name="new_customer_email" value="{{ old('new_customer_email') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            </div>
                        </div>
                    </div>

                    {{-- Walk-in --}}
                    <div x-show="mode === 'walkin'" x-cloak>
                        <p class="text-sm text-gray-500">No customer record — refund issued to walk-in.</p>
                    </div>
                </div>

                {{-- ORIGINAL SALE --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-3">Original Sale <span class="text-gray-400 font-normal text-sm">(optional)</span></h2>
                    <input type="text" name="original_sale_number" value="{{ old('original_sale_number') }}"
                        placeholder="e.g. 42"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                    <p class="mt-1.5 text-xs text-gray-400">Enter the sale number this return is for, if applicable.</p>
                </div>

                {{-- ITEMS --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                     x-data="itemsPanel()" x-init="addRow()">

                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Items Being Returned
                    </h2>

                    <div class="space-y-3">
                        <template x-for="(row, idx) in rows" :key="row.uid">
                            <div class="relative border border-gray-200 rounded-lg p-3 bg-gray-50"
                                 x-data="rowCatalog(row)" x-init="syncRow()">

                                <div class="relative mb-2">
                                    <input type="text"
                                        x-model="row.description"
                                        @input.debounce.300="searchCatalog()"
                                        @focus="if(row.description.length >= 1) catalogOpen = true"
                                        :name="'items[' + idx + '][description]'"
                                        placeholder="Search catalog or type description…"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white">
                                    <ul x-show="catalogOpen && catalogResults.length > 0"
                                        @click.outside="catalogOpen = false"
                                        class="absolute z-30 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto text-sm">
                                        <template x-for="p in catalogResults" :key="p.id">
                                            <li @click="selectProduct(p)" class="px-3 py-2 hover:bg-indigo-50 cursor-pointer">
                                                <div class="font-medium text-gray-800" x-text="p.name + (p.color ? ' — ' + p.color : '')"></div>
                                                <div class="text-xs text-gray-500" x-text="[p.manufacturer, p.line_name, p.sku].filter(Boolean).join(' · ')"></div>
                                                <div class="text-xs text-indigo-600 font-semibold mt-0.5" x-text="'$' + parseFloat(p.sell_price || 0).toFixed(2)"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <input type="hidden" :name="'items[' + idx + '][product_style_id]'" :value="row.product_style_id ?? ''">

                                <div class="grid grid-cols-12 gap-2 items-end">
                                    <div class="col-span-2">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Qty</label>
                                        <input type="number" step="0.01" min="0.01"
                                            x-model="row.quantity" @input="recalc()"
                                            :name="'items[' + idx + '][quantity]'"
                                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-indigo-400 bg-white">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Unit</label>
                                        <input type="text" x-model="row.unit"
                                            :name="'items[' + idx + '][unit]'" placeholder="ea"
                                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-indigo-400 bg-white">
                                    </div>
                                    <div class="col-span-3">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Unit Price</label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                            <input type="number" step="0.01" min="0"
                                                x-model="row.unit_price" @input="recalc()"
                                                :name="'items[' + idx + '][unit_price]'"
                                                class="w-full border border-gray-300 rounded-lg pl-5 pr-2 py-1.5 text-sm focus:ring-2 focus:ring-indigo-400 bg-white">
                                        </div>
                                    </div>
                                    <div class="col-span-4">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Refund Amount</label>
                                        <div class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 bg-gray-100 text-right"
                                            x-text="'$' + lineTotal().toFixed(2)"></div>
                                    </div>
                                    <div class="col-span-1 flex items-center pb-0.5">
                                        <button type="button" @click="removeRow(row.uid)"
                                            class="text-gray-300 hover:text-red-500 transition" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addRow()"
                        class="mt-3 flex items-center gap-1.5 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Item
                    </button>
                </div>

                {{-- NOTES --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-3">Notes <span class="text-gray-400 font-normal text-sm">(optional)</span></h2>
                    <textarea name="notes" rows="2" placeholder="Reason for return, condition of goods, etc."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="space-y-6">

                {{-- Tax --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-3">Tax</h2>
                    <select name="tax_group_id" id="tax_group_id"
                        onchange="fetchTaxRate(this.value)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        <option value="">— No tax / Select group —</option>
                        @foreach ($taxGroups as $tg)
                            <option value="{{ $tg->id }}" {{ old('tax_group_id') == $tg->id ? 'selected' : '' }}>
                                {{ $tg->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-gray-400">Select the tax group that was applied to the original sale.</p>
                </div>

                {{-- Summary --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Refund Summary</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="font-medium text-gray-800" id="summary-subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tax (<span id="summary-tax-label">—</span>)</span>
                            <span class="font-medium text-gray-800" id="summary-tax">$0.00</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 flex justify-between text-base font-bold">
                            <span class="text-gray-800">Total Refund</span>
                            <span class="text-rose-600" id="summary-total">$0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Refund Method --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                     x-data="{ method: '{{ old('refund_method', 'cash') }}' }">

                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H5a2 2 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Refund Method
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Method <span class="text-red-500">*</span></label>
                            <select name="refund_method" x-model="method"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                                @foreach ($refundMethods as $value => $label)
                                    <option value="{{ $value }}" {{ old('refund_method', 'cash') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="method !== 'cash'" x-cloak>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Reference / Transaction #</label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                                placeholder="Optional"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold text-sm py-3 px-4 rounded-xl shadow transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    Record Return & Issue Refund
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const QR_CUSTOMERS_URL = @json(route('pages.quick-returns.api.customers'));
const QR_PRODUCTS_URL  = @json(route('pages.quick-returns.api.products'));
const QR_TAX_RATE_URL  = '/estimates/api/tax-groups/';

window._qr = { rows: [], taxRate: 0, taxLabel: '—' };

function recalcTotals() {
    const subtotal = window._qr.rows.reduce((sum, r) => {
        return sum + (parseFloat(r.quantity) || 0) * (parseFloat(r.unit_price) || 0);
    }, 0);
    const taxAmt = subtotal * (window._qr.taxRate / 100);
    const total  = subtotal + taxAmt;

    document.getElementById('summary-subtotal').textContent  = '$' + subtotal.toFixed(2);
    document.getElementById('summary-tax').textContent       = '$' + taxAmt.toFixed(2);
    document.getElementById('summary-total').textContent     = '$' + total.toFixed(2);
    document.getElementById('summary-tax-label').textContent = window._qr.taxLabel;
}

async function fetchTaxRate(groupId) {
    if (!groupId) {
        window._qr.taxRate  = 0;
        window._qr.taxLabel = '—';
        recalcTotals();
        return;
    }
    try {
        const r = await fetch(QR_TAX_RATE_URL + groupId + '/rate');
        const d = await r.json();
        window._qr.taxRate  = parseFloat(d.tax_rate_percent || 0);
        window._qr.taxLabel = d.group_name || '';
        recalcTotals();
    } catch {}
}

function customerPanel() {
    return {
        mode: '{{ old('customer_mode', 'existing') }}',
        searchText: '',
        results: [],
        open: false,
        selected: null,
        setMode(m) {
            this.mode = m;
            this.selected = null;
            this.searchText = '';
            this.results = [];
            this.open = false;
        },
        async search() {
            if (this.searchText.length < 1) { this.results = []; this.open = false; return; }
            const r = await fetch(QR_CUSTOMERS_URL + '?q=' + encodeURIComponent(this.searchText));
            this.results = await r.json();
            this.open = this.results.length > 0;
        },
        select(c) {
            this.selected   = c;
            this.searchText = c.name;
            this.open       = false;
        },
    };
}

function itemsPanel() {
    return {
        rows: [],
        nextUid: 1,
        addRow() {
            const row = { uid: this.nextUid++, description: '', quantity: 1, unit: '', unit_price: '', product_style_id: null };
            this.rows.push(row);
            window._qr.rows = this.rows;
        },
        removeRow(uid) {
            this.rows = this.rows.filter(r => r.uid !== uid);
            window._qr.rows = this.rows;
            recalcTotals();
        },
    };
}

function rowCatalog(row) {
    return {
        row,
        catalogResults: [],
        catalogOpen: false,
        syncRow() { window._qr.rows = window._qr.rows; },
        async searchCatalog() {
            if (this.row.description.length < 1) { this.catalogResults = []; this.catalogOpen = false; return; }
            const r = await fetch(QR_PRODUCTS_URL + '?q=' + encodeURIComponent(this.row.description));
            this.catalogResults = await r.json();
            this.catalogOpen = this.catalogResults.length > 0;
        },
        selectProduct(p) {
            this.row.description      = p.name + (p.color ? ' — ' + p.color : '');
            this.row.unit_price       = parseFloat(p.sell_price || 0);
            this.row.product_style_id = p.id;
            this.catalogOpen          = false;
            recalcTotals();
        },
        lineTotal() {
            return (parseFloat(this.row.quantity) || 0) * (parseFloat(this.row.unit_price) || 0);
        },
        recalc() { recalcTotals(); },
    };
}
</script>
</x-app-layout>
