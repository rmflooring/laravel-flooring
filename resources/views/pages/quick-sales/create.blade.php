<x-app-layout>
<div class="max-w-5xl mx-auto px-4 py-6">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quick Sale</h1>
            <p class="text-sm text-gray-500 mt-0.5">Cash & Carry — walk-in purchase</p>
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

    <form action="{{ route('pages.quick-sales.store') }}" method="POST" id="quick-sale-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- LEFT COLUMN — Customer + Items + Notes --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- CUSTOMER --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                     x-data="customerPanel()">

                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        Customer
                    </h2>

                    {{-- Mode toggle --}}
                    <div class="flex gap-2 mb-4">
                        <button type="button"
                            @click="setMode('existing')"
                            :class="mode === 'existing'
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-4 py-1.5 text-sm font-medium rounded-lg border transition">
                            Select Existing
                        </button>
                        <button type="button"
                            @click="setMode('new')"
                            :class="mode === 'new'
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                            class="px-4 py-1.5 text-sm font-medium rounded-lg border transition">
                            + New Customer
                        </button>
                    </div>

                    <input type="hidden" name="customer_mode" :value="mode">

                    {{-- Existing customer search --}}
                    <div x-show="mode === 'existing'" x-cloak>
                        <div class="relative">
                            <input type="text"
                                x-model="searchText"
                                @input.debounce.300="search()"
                                @focus="if(searchText.length >= 1) open = true"
                                placeholder="Search by name, phone, or email…"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            <ul x-show="open && results.length > 0"
                                @click.outside="open = false"
                                class="absolute z-20 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-52 overflow-y-auto text-sm">
                                <template x-for="c in results" :key="c.id">
                                    <li @click="select(c)"
                                        class="px-3 py-2 hover:bg-indigo-50 cursor-pointer flex flex-col">
                                        <span class="font-medium text-gray-800" x-text="c.name"></span>
                                        <span class="text-gray-500 text-xs" x-text="[c.phone, c.email].filter(Boolean).join(' · ')"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <input type="hidden" name="customer_id" :value="selected?.id ?? ''">

                        {{-- Selected customer chip --}}
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

                    {{-- New customer fields --}}
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
                                    placeholder="(250) 555-0100"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                                <input type="email" name="new_customer_email" value="{{ old('new_customer_email') }}"
                                    placeholder="email@example.com"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ITEMS --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                     x-data="itemsPanel()" x-init="addRow()">

                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Items
                    </h2>

                    <div class="space-y-3">
                        <template x-for="(row, idx) in rows" :key="row.uid">
                            <div class="relative border border-gray-200 rounded-lg p-3 bg-gray-50"
                                 x-data="rowCatalog(row)"
                                 x-init="syncRow()">

                                {{-- Catalog search --}}
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
                                            <li @click="selectProduct(p)"
                                                class="px-3 py-2 hover:bg-indigo-50 cursor-pointer">
                                                <div class="font-medium text-gray-800" x-text="p.name + (p.color ? ' — ' + p.color : '')"></div>
                                                <div class="text-xs text-gray-500" x-text="[p.manufacturer, p.line_name, p.sku].filter(Boolean).join(' · ')"></div>
                                                <div class="text-xs text-indigo-600 font-semibold mt-0.5" x-text="'$' + parseFloat(p.sell_price || 0).toFixed(2)"></div>
                                            </li>
                                        </template>
                                    </ul>
                                </div>

                                <input type="hidden" :name="'items[' + idx + '][product_style_id]'" :value="row.product_style_id ?? ''">

                                {{-- Qty / Unit / Price / Total --}}
                                <div class="grid grid-cols-12 gap-2 items-end">
                                    <div class="col-span-2">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Qty</label>
                                        <input type="number" step="0.01" min="0.01"
                                            x-model="row.quantity"
                                            @input="recalc()"
                                            :name="'items[' + idx + '][quantity]'"
                                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-center focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Unit</label>
                                        <input type="text"
                                            x-model="row.unit"
                                            :name="'items[' + idx + '][unit]'"
                                            placeholder="ea"
                                            class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white">
                                    </div>
                                    <div class="col-span-3">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Unit Price</label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                            <input type="number" step="0.01" min="0"
                                                x-model="row.sell_price"
                                                @input="recalc()"
                                                :name="'items[' + idx + '][sell_price]'"
                                                class="w-full border border-gray-300 rounded-lg pl-5 pr-2 py-1.5 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white">
                                        </div>
                                    </div>
                                    <div class="col-span-4">
                                        <label class="text-xs text-gray-500 mb-0.5 block">Line Total</label>
                                        <div class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm font-semibold text-gray-700 bg-gray-100 text-right"
                                            x-text="'$' + lineTotal().toFixed(2)"></div>
                                    </div>
                                    <div class="col-span-1 flex items-center pb-0.5">
                                        <button type="button" @click="removeRow(row.uid)"
                                            class="text-gray-300 hover:text-red-500 transition"
                                            title="Remove">
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
                    <textarea name="notes" rows="2" placeholder="Any notes for this sale…"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">{{ old('notes') }}</textarea>
                </div>
            </div>

            {{-- RIGHT COLUMN — Totals + Payment --}}
            <div class="space-y-6">

                {{-- Tax group --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-3">Tax</h2>
                    <select name="tax_group_id" id="tax_group_id"
                        onchange="fetchTaxRate(this.value)"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        <option value="">— Select tax group —</option>
                        @foreach ($taxGroups as $tg)
                            <option value="{{ $tg->id }}" {{ old('tax_group_id') == $tg->id ? 'selected' : '' }}>
                                {{ $tg->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Order summary --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                    <h2 class="text-base font-semibold text-gray-800 mb-4">Summary</h2>
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
                            <span class="text-gray-800">Total</span>
                            <span class="text-indigo-700" id="summary-total">$0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5"
                     x-data="paymentPanel()">

                    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H5a2 2 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        Payment
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Method <span class="text-red-500">*</span></label>
                            <select name="payment_method" x-model="method"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                                @foreach (\App\Models\InvoicePayment::PAYMENT_METHODS as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                <span x-text="method === 'cash' ? 'Amount Tendered' : 'Amount'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-sm">$</span>
                                <input type="number" step="0.01" min="0" name="amount_tendered" id="amount_tendered"
                                    x-model="tendered"
                                    @input="calcChange()"
                                    class="w-full border border-gray-300 rounded-lg pl-5 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            </div>
                        </div>

                        {{-- Change due (cash only) --}}
                        <div x-show="method === 'cash' && changeDue > 0" x-cloak
                             class="flex justify-between items-center bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                            <span class="text-sm font-medium text-green-700">Change Due</span>
                            <span class="text-lg font-bold text-green-700" x-text="'$' + changeDue.toFixed(2)"></span>
                        </div>

                        {{-- Reference # (non-cash) --}}
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
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm py-3 px-4 rounded-xl shadow transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Complete Sale & Print Receipt
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const QS_CUSTOMERS_URL = @json(route('pages.quick-sales.api.customers'));
const QS_PRODUCTS_URL  = @json(route('pages.quick-sales.api.products'));
const QS_TAX_RATE_URL  = '/pages/estimates/api/tax-groups/';

// ── Shared totals state ───────────────────────────────────────────────────
window._qs = { rows: [], taxRate: 0, taxLabel: '—' };

function recalcTotals() {
    const subtotal = window._qs.rows.reduce((sum, r) => {
        return sum + (parseFloat(r.quantity) || 0) * (parseFloat(r.sell_price) || 0);
    }, 0);
    const taxAmt  = subtotal * (window._qs.taxRate / 100);
    const total   = subtotal + taxAmt;

    document.getElementById('summary-subtotal').textContent  = '$' + subtotal.toFixed(2);
    document.getElementById('summary-tax').textContent       = '$' + taxAmt.toFixed(2);
    document.getElementById('summary-total').textContent     = '$' + total.toFixed(2);
    document.getElementById('summary-tax-label').textContent = window._qs.taxLabel;

    // Auto-fill amount tendered if user hasn't touched it
    const inp = document.getElementById('amount_tendered');
    if (inp && !inp._touched) {
        inp.value = total.toFixed(2);
        inp.dispatchEvent(new Event('input'));
    }
}

async function fetchTaxRate(groupId) {
    if (!groupId) {
        window._qs.taxRate  = 0;
        window._qs.taxLabel = '—';
        recalcTotals();
        return;
    }
    try {
        const r = await fetch(QS_TAX_RATE_URL + groupId + '/rate');
        const d = await r.json();
        window._qs.taxRate  = parseFloat(d.tax_rate_percent || 0);
        window._qs.taxLabel = d.group_name || '';
        recalcTotals();
    } catch {}
}

// ── Customer panel ────────────────────────────────────────────────────────
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
            const r = await fetch(QS_CUSTOMERS_URL + '?q=' + encodeURIComponent(this.searchText));
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

// ── Items panel ───────────────────────────────────────────────────────────
function itemsPanel() {
    return {
        rows: [],
        nextUid: 1,
        addRow() {
            const row = { uid: this.nextUid++, description: '', quantity: 1, unit: '', sell_price: '', product_style_id: null };
            this.rows.push(row);
            window._qs.rows = this.rows;
        },
        removeRow(uid) {
            this.rows = this.rows.filter(r => r.uid !== uid);
            window._qs.rows = this.rows;
            recalcTotals();
        },
    };
}

// ── Per-row catalog ───────────────────────────────────────────────────────
function rowCatalog(row) {
    return {
        row,
        catalogResults: [],
        catalogOpen: false,
        syncRow() {
            // Ensure shared state stays in sync after Alpine renders
            window._qs.rows = window._qs.rows;
        },
        async searchCatalog() {
            if (this.row.description.length < 1) { this.catalogResults = []; this.catalogOpen = false; return; }
            const r = await fetch(QS_PRODUCTS_URL + '?q=' + encodeURIComponent(this.row.description));
            this.catalogResults = await r.json();
            this.catalogOpen = this.catalogResults.length > 0;
        },
        selectProduct(p) {
            this.row.description      = p.name + (p.color ? ' — ' + p.color : '');
            this.row.sell_price       = parseFloat(p.sell_price || 0);
            this.row.product_style_id = p.id;
            this.catalogOpen          = false;
            recalcTotals();
        },
        lineTotal() {
            return (parseFloat(this.row.quantity) || 0) * (parseFloat(this.row.sell_price) || 0);
        },
        recalc() {
            recalcTotals();
        },
    };
}

// ── Payment panel ─────────────────────────────────────────────────────────
function paymentPanel() {
    return {
        method: 'cash',
        tendered: '',
        changeDue: 0,
        calcChange() {
            const total    = parseFloat((document.getElementById('summary-total').textContent || '').replace('$', '')) || 0;
            const tendered = parseFloat(this.tendered) || 0;
            this.changeDue = Math.max(0, tendered - total);
        },
        init() {
            const inp = document.getElementById('amount_tendered');
            if (inp) {
                inp.addEventListener('input', () => {
                    inp._touched = true;
                    this.calcChange();
                });
            }
        },
    };
}
</script>
</x-app-layout>
