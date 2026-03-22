{{-- resources/views/pages/purchase-orders/create-stock.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Stock Purchase Order</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Order sundry items (adhesives, underpads, etc.) not tied to a specific sale.
                    </p>
                </div>
                <div>
                    <a href="{{ route('pages.purchase-orders.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                </div>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-gray-800">
                    <p class="mb-2 text-sm font-semibold text-red-800 dark:text-red-400">Please fix the following errors:</p>
                    <ul class="list-inside list-disc text-sm text-red-700 dark:text-red-400 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.purchase-orders.store-stock') }}"
                  x-data="poStockCreate()" @submit.prevent="submitForm">
                @csrf

                {{-- Vendor --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Vendor</h2>
                    </div>
                    <div class="p-6">
                        <label for="vendor_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Select Vendor <span class="text-red-500">*</span>
                        </label>
                        <select id="vendor_id" name="vendor_id" required
                                x-model="vendorId"
                                @change="onVendorChange"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select a vendor —</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->company_name }}
                                    @if($vendor->email) ({{ $vendor->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Selecting a vendor filters the product catalog search to that vendor's products.
                        </p>
                        @error('vendor_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Items --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Search the product catalog or enter items manually.
                                </p>
                            </div>
                            <button type="button" @click="addRow"
                                    class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-gray-600 dark:hover:bg-gray-500">
                                + Add Item
                            </button>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="(row, index) in rows" :key="row.id">
                            <div class="p-4 space-y-3">

                                {{-- Catalog search --}}
                                <div class="relative">
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        Search Catalog
                                        <span class="font-normal text-gray-400">(optional — or fill in manually below)</span>
                                    </label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 20 20">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                            </svg>
                                        </div>
                                        <input type="text"
                                               x-model="row.search"
                                               @input.debounce.300ms="searchCatalog(row)"
                                               @focus="row.results.length > 0 && (row.showDropdown = true)"
                                               @keydown.escape="row.showDropdown = false"
                                               :placeholder="vendorId ? 'Search this vendor\'s products...' : 'Search all products...'"
                                               autocomplete="off"
                                               class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-10 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                               :class="row.catalogLabel ? 'border-green-400 bg-green-50 dark:bg-green-900/10' : ''">
                                        {{-- Clear catalog button --}}
                                        <button type="button"
                                                x-show="row.catalogLabel"
                                                @click="clearCatalog(row)"
                                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-red-500">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    {{-- Catalog selected badge --}}
                                    <p x-show="row.catalogLabel" x-cloak
                                       class="mt-1 text-xs text-green-700 dark:text-green-400">
                                        ✓ From catalog: <span x-text="row.catalogLabel" class="font-medium"></span>
                                        <span class="text-gray-400 ml-1">(fields pre-filled — edit as needed)</span>
                                    </p>
                                    {{-- Dropdown --}}
                                    <div x-show="row.showDropdown && row.results.length > 0"
                                         x-cloak
                                         @click.outside="row.showDropdown = false"
                                         class="absolute left-0 right-0 z-20 mt-1 max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-800">
                                        <template x-for="result in row.results" :key="result.id">
                                            <button type="button"
                                                    @click="selectCatalogItem(row, result)"
                                                    class="block w-full px-4 py-2.5 text-left text-sm hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                                <div class="font-medium text-gray-900 dark:text-white" x-text="result.label"></div>
                                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                    Cost: $<span x-text="result.cost_price.toFixed(2)"></span>
                                                    <span x-show="result.unit"> / <span x-text="result.unit"></span></span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="row.searching" class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">
                                            Searching...
                                        </div>
                                    </div>
                                    {{-- No results --}}
                                    <div x-show="row.showDropdown && !row.searching && row.results.length === 0 && row.search.length >= 2"
                                         x-cloak
                                         class="absolute left-0 right-0 z-20 mt-1 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 shadow-lg dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        No catalog products found — fill in manually below.
                                    </div>
                                </div>

                                {{-- Item fields --}}
                                <div class="grid grid-cols-12 gap-3 items-start">

                                    {{-- Hidden product_style_id --}}
                                    <input type="hidden"
                                           :name="`items[${index}][product_style_id]`"
                                           :value="row.product_style_id ?? ''">

                                    {{-- Description --}}
                                    <div class="col-span-12 sm:col-span-5">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            Description <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text"
                                               :name="`items[${index}][item_name]`"
                                               x-model="row.item_name"
                                               placeholder="Item description"
                                               required
                                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>

                                    {{-- Qty --}}
                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            Qty <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number"
                                               :name="`items[${index}][quantity]`"
                                               x-model="row.quantity"
                                               @input="recalc(row)"
                                               @blur="checkBoxQtyForRow(row, $event.target)"
                                               min="0.01" step="0.01" required
                                               :style="row.boxAligned ? 'background-color:#fed7aa;border-color:#fb923c;color:#9a3412;' : ''"
                                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        <p x-show="row.use_box_qty && row.units_per > 0" x-cloak
                                           class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                                            Box: <span x-text="row.units_per"></span> units
                                        </p>
                                    </div>

                                    {{-- Unit --}}
                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Unit</label>
                                        <input type="text"
                                               :name="`items[${index}][unit]`"
                                               x-model="row.unit"
                                               placeholder="ea, gal..."
                                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>

                                    {{-- Unit Cost --}}
                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                            Unit Cost <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">$</span>
                                            <input type="number"
                                                   :name="`items[${index}][cost_price]`"
                                                   x-model="row.cost_price"
                                                   @input="recalc(row)"
                                                   min="0" step="0.01" required
                                                   class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-7 pr-3 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>

                                    {{-- Total + remove --}}
                                    <div class="col-span-12 sm:col-span-1 flex sm:flex-col sm:items-end gap-2">
                                        <div class="flex-1 sm:flex-none">
                                            <div class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 sm:text-right">Total</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white sm:text-right"
                                                 x-text="'$' + (row.total || 0).toLocaleString('en-CA', {minimumFractionDigits:2, maximumFractionDigits:2})">
                                            </div>
                                        </div>
                                        <button type="button" @click="removeRow(row.id)"
                                                x-show="rows.length > 1"
                                                style="display:none"
                                                class="mt-1 text-red-400 hover:text-red-600 dark:text-red-500 dark:hover:text-red-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- PO Notes --}}
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">PO Notes</label>
                                    <textarea :name="`items[${index}][po_notes]`"
                                              x-model="row.po_notes"
                                              rows="1"
                                              placeholder="Notes for this item on the PO (optional)..."
                                              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-600 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"></textarea>
                                </div>

                            </div>
                        </template>
                    </div>

                    {{-- Grand Total footer --}}
                    <div class="border-t-2 border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-700/40 flex justify-end">
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            Grand Total: $<span x-text="grandTotal.toLocaleString('en-CA', {minimumFractionDigits:2, maximumFractionDigits:2})"></span>
                        </span>
                    </div>

                    @error('items')
                        <div class="border-t border-red-100 bg-red-50 px-6 py-3">
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        </div>
                    @enderror
                </div>

                {{-- Delivery & Dates --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Fulfillment & Delivery</h2>
                    </div>
                    <div class="space-y-6 p-6">

                        <div>
                            <label for="expected_delivery_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Expected ETA
                            </label>
                            <input type="date" id="expected_delivery_date" name="expected_delivery_date"
                                   value="{{ old('expected_delivery_date') }}"
                                   class="block w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label for="fulfillment_method" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Fulfillment Method <span class="text-red-500">*</span>
                            </label>
                            <select id="fulfillment_method" name="fulfillment_method" required
                                    x-model="fulfillmentMethod"
                                    class="block w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">— Select fulfillment method —</option>
                                <option value="delivery_warehouse" {{ old('fulfillment_method') === 'delivery_warehouse' ? 'selected' : '' }}>Delivery to Warehouse / Shop</option>
                                <option value="delivery_custom"    {{ old('fulfillment_method') === 'delivery_custom'    ? 'selected' : '' }}>Delivery to Custom Address</option>
                                <option value="pickup"             {{ old('fulfillment_method') === 'pickup'             ? 'selected' : '' }}>Pickup</option>
                            </select>

                            <p x-show="fulfillmentMethod === 'delivery_warehouse'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $warehouseAddress ?: 'No warehouse address configured in branding settings' }}
                            </p>
                            <div x-show="fulfillmentMethod === 'pickup'" x-cloak class="mt-3 space-y-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">No delivery address needed — we will pick up from the vendor.</p>
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/10">
                                    <p class="mb-3 text-xs font-medium text-blue-700 dark:text-blue-400">
                                        Schedule Warehouse Pickup — syncs to RM Warehouse calendar
                                    </p>
                                    <div class="flex flex-wrap gap-4">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pickup Date</label>
                                            <input type="date" name="pickup_date" value="{{ old('pickup_date') }}"
                                                   :disabled="fulfillmentMethod !== 'pickup'"
                                                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pickup Time</label>
                                            <input type="time" name="pickup_time" value="{{ old('pickup_time', '09:00') }}"
                                                   :disabled="fulfillmentMethod !== 'pickup'"
                                                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-400">Leave blank to skip scheduling.</p>
                                </div>
                            </div>
                            <div x-show="fulfillmentMethod === 'delivery_custom'" x-cloak class="mt-2">
                                <textarea name="delivery_address" rows="3" placeholder="Enter delivery address..."
                                          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('delivery_address') }}</textarea>
                            </div>
                            @error('fulfillment_method')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Special Instructions --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Special Instructions</h2>
                    </div>
                    <div class="p-6">
                        <textarea name="special_instructions" rows="4"
                                  placeholder="Any special instructions for the vendor..."
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('special_instructions') }}</textarea>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('pages.purchase-orders.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            :disabled="!vendorId || !fulfillmentMethod"
                            class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-50">
                        Create Purchase Order
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
    const CATALOG_SEARCH_URL = '{{ route('pages.purchase-orders.catalog-search') }}';

    function poStockCreate() {
        let nextId = 1;

        return {
            vendorId:         '{{ old('vendor_id', '') }}',
            fulfillmentMethod:'{{ old('fulfillment_method', '') }}',
            rows: [newRow()],

            get grandTotal() {
                return this.rows.reduce((sum, r) => sum + (r.total || 0), 0);
            },

            onVendorChange() {
                // Clear any open dropdowns when vendor changes (results may no longer be valid)
                this.rows.forEach(r => {
                    r.showDropdown = false;
                    r.results      = [];
                    // Re-run search on the current query if non-empty
                    if (r.search.length >= 2) {
                        this.searchCatalog(r);
                    }
                });
            },

            addRow() {
                this.rows.push(newRow());
            },

            removeRow(id) {
                if (this.rows.length > 1) {
                    this.rows = this.rows.filter(r => r.id !== id);
                }
            },

            recalc(row) {
                const qty  = parseFloat(row.quantity)   || 0;
                const cost = parseFloat(row.cost_price) || 0;
                row.total  = Math.round(qty * cost * 100) / 100;

                // Live box-alignment highlight
                if (row.use_box_qty && row.units_per > 0 && qty > 0) {
                    row.boxAligned = Math.abs(Math.round(qty / row.units_per) * row.units_per - qty) < 0.001;
                } else {
                    row.boxAligned = false;
                }
            },

            checkBoxQtyForRow(row, inputEl) {
                if (!row.use_box_qty || !row.units_per || row.units_per <= 0) return;
                const qty = parseFloat(row.quantity) || 0;
                if (!qty) return;

                const boxes  = Math.ceil(qty / row.units_per);
                const boxQty = parseFloat((boxes * row.units_per).toFixed(4));

                if (Math.abs(qty - boxQty) < 0.001) return; // already aligned

                showBoxQtyModal(inputEl, qty, row.units_per, boxes, boxQty, row.catalogLabel || row.item_name);
            },

            searchCatalog(row) {
                if (row.search.length < 2) {
                    row.results      = [];
                    row.showDropdown = false;
                    return;
                }
                row.searching    = true;
                row.showDropdown = true;

                const params = new URLSearchParams({ q: row.search });
                if (this.vendorId) params.set('vendor_id', this.vendorId);

                fetch(CATALOG_SEARCH_URL + '?' + params.toString())
                    .then(r => r.json())
                    .then(data => {
                        row.results   = data;
                        row.searching = false;
                    })
                    .catch(() => {
                        row.searching = false;
                    });
            },

            selectCatalogItem(row, item) {
                row.item_name        = item.item_name;
                row.cost_price       = item.cost_price;
                row.unit             = item.unit;
                row.catalogLabel     = item.label;
                row.search           = item.label;
                row.product_style_id = item.product_style_id ?? null;
                row.use_box_qty      = item.use_box_qty ?? false;
                row.units_per        = item.units_per   ?? 0;
                row.showDropdown     = false;
                this.recalc(row);

                // Auto-select the vendor if none is chosen yet and the item has one
                if (!this.vendorId && item.vendor_id) {
                    this.vendorId = String(item.vendor_id);
                }
            },

            clearCatalog(row) {
                row.catalogLabel     = '';
                row.search           = '';
                row.results          = [];
                row.showDropdown     = false;
                row.product_style_id = null;
                row.use_box_qty      = false;
                row.units_per        = 0;
                row.boxAligned       = false;
            },

            submitForm() {
                if (!this.vendorId) {
                    alert('Please select a vendor.');
                    return;
                }
                if (!this.fulfillmentMethod) {
                    alert('Please select a fulfillment method.');
                    return;
                }
                const hasItems = this.rows.some(r => r.item_name.trim() !== '');
                if (!hasItems) {
                    alert('Please add at least one item.');
                    return;
                }
                this.$el.submit();
            },
        };

        function newRow() {
            return {
                id:               nextId++,
                item_name:        '',
                quantity:         '',
                unit:             '',
                cost_price:       '',
                po_notes:         '',
                total:            0,
                search:           '',
                results:          [],
                showDropdown:     false,
                searching:        false,
                catalogLabel:     '',
                product_style_id: null,
                use_box_qty:      false,
                units_per:        0,
                boxAligned:       false,
            };
        }
    }

    function showBoxQtyModal(inputEl, currentQty, unitsPer, boxes, boxQty, styleName) {
        const modalEl = document.getElementById('box-qty-modal');
        if (!modalEl) return;

        window._boxQtyPendingInput = inputEl;
        window._boxQtyPendingValue = boxQty;

        modalEl.querySelectorAll('[data-box-style-name]').forEach(el => el.textContent = styleName || 'this style');
        modalEl.querySelectorAll('[data-box-units-per]').forEach(el => el.textContent = unitsPer);
        modalEl.querySelectorAll('[data-box-current-qty]').forEach(el => el.textContent = currentQty);
        modalEl.querySelectorAll('[data-box-count]').forEach(el => el.textContent = boxes + (boxes === 1 ? ' box' : ' boxes'));
        modalEl.querySelectorAll('[data-box-suggested-qty]').forEach(el => el.textContent = boxQty);

        modalEl.style.display = 'flex';
    }
    </script>

@include('components.modals.box-qty-modal')
</x-app-layout>
