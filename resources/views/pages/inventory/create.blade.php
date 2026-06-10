{{-- resources/views/pages/inventory/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Inventory</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">New Record</span>
            </nav>

            <form method="POST" action="{{ route('pages.inventory.store') }}"
                  x-data="inventoryCreate()" @submit.prevent="submitForm">
                @csrf

                {{-- Product search card --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Product</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Search for a product from the catalog. Item name and unit will be filled in automatically.</p>

                    {{-- Hidden fields submitted with form --}}
                    <input type="hidden" name="product_style_id" :value="selectedProduct?.id">
                    <input type="hidden" name="item_name" :value="itemName">
                    <input type="hidden" name="unit" :value="selectedUnit">

                    {{-- Search input --}}
                    <div class="relative" x-ref="searchWrap">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search catalog <span class="text-red-500">*</span></label>
                        <input type="text"
                               x-model="searchText"
                               @input.debounce.300ms="search()"
                               @focus="if (searchText.length >= 2) search()"
                               @keydown.escape="closeDropdown()"
                               @keydown.arrow-down.prevent="highlightNext()"
                               @keydown.arrow-up.prevent="highlightPrev()"
                               @keydown.enter.prevent="selectHighlighted()"
                               placeholder="Type a product name, SKU, colour, or manufacturer…"
                               autocomplete="off"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               :class="{ 'border-red-400': errors.product_style_id }">

                        {{-- Dropdown results --}}
                        <div x-show="showDropdown && results.length > 0"
                             x-cloak
                             @click.outside="closeDropdown()"
                             class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                            <template x-for="(item, index) in results" :key="item.id">
                                <div @click="selectProduct(item)"
                                     @mouseover="highlighted = index"
                                     class="px-4 py-3 cursor-pointer text-sm border-b border-gray-100 dark:border-gray-700 last:border-0"
                                     :class="highlighted === index
                                         ? 'bg-teal-50 dark:bg-teal-900/30'
                                         : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="item.name"></div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        <span x-show="item.manufacturer" x-text="item.manufacturer + ' · '"></span>
                                        <span x-show="item.line_name" x-text="item.line_name"></span>
                                        <span x-show="item.sku"> · SKU: <span x-text="item.sku"></span></span>
                                        <span x-show="item.color"> · <span x-text="item.color"></span></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- No results --}}
                        <div x-show="showDropdown && results.length === 0 && searchText.length >= 2 && !searching"
                             x-cloak
                             class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg px-4 py-3 text-sm text-gray-400 dark:text-gray-500">
                            No products match your search.
                        </div>

                        <p x-show="errors.product_style_id" x-text="errors.product_style_id" class="mt-1 text-xs text-red-600"></p>
                    </div>

                    {{-- Selected product card --}}
                    <div x-show="selectedProduct" x-cloak
                         class="rounded-lg border border-teal-200 dark:border-teal-800 bg-teal-50 dark:bg-teal-900/20 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white text-sm" x-text="selectedProduct?.name"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span x-text="selectedProduct?.manufacturer"></span>
                                    <span x-show="selectedProduct?.line_name"> · <span x-text="selectedProduct?.line_name"></span></span>
                                    <span x-show="selectedProduct?.sku"> · SKU: <span x-text="selectedProduct?.sku"></span></span>
                                    <span x-show="selectedProduct?.color"> · <span x-text="selectedProduct?.color"></span></span>
                                </div>
                            </div>
                            <button type="button" @click="clearProduct()"
                                    class="text-xs text-red-500 hover:text-red-700 whitespace-nowrap">
                                Change
                            </button>
                        </div>

                        {{-- Editable item name --}}
                        <div class="mt-3">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Item name (editable)</label>
                            <input type="text" x-model="itemName"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    @error('product_style_id')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('item_name')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Receipt details card --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-5 mt-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Receipt Details</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                        {{-- Quantity --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Quantity received <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="quantity_received"
                                   x-model.number="quantity"
                                   @input="calcTotal()"
                                   step="0.01" min="0.01"
                                   value="{{ old('quantity_received') }}"
                                   placeholder="0.00"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                   :class="{ 'border-red-400': errors.quantity_received }">
                            <p x-show="errors.quantity_received" x-text="errors.quantity_received" class="mt-1 text-xs text-red-600"></p>
                            @error('quantity_received')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Unit --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Unit <span class="text-red-500">*</span>
                            </label>
                            <select x-model="selectedUnit"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    :class="{ 'border-red-400': errors.unit }">
                                <option value="">— Select unit —</option>
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->code }}" {{ old('unit') === $unit->code ? 'selected' : '' }}>
                                        {{ $unit->code }} — {{ $unit->label }}
                                    </option>
                                @endforeach
                            </select>
                            <p x-show="errors.unit" x-text="errors.unit" class="mt-1 text-xs text-red-600"></p>
                            @error('unit')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Cost per unit --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Cost per unit
                                <span x-show="selectedUnit" x-text="'(' + selectedUnit + ')'" class="text-gray-400 font-normal"></span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400 text-sm">$</span>
                                <input type="number" name="cost_price"
                                       x-model.number="costPerUnit"
                                       @input="calcTotal()"
                                       step="0.0001" min="0"
                                       value="{{ old('cost_price') }}"
                                       placeholder="0.00"
                                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white pl-7">
                            </div>
                            @error('cost_price')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Total cost (calculated) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Total cost</label>
                            <div class="flex items-center h-[38px] rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 px-3 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                <span x-text="totalCostFormatted" class="text-gray-500 dark:text-gray-400" x-show="!totalCost">—</span>
                                <span x-show="totalCost" x-text="'$' + totalCostFormatted"></span>
                            </div>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Qty × cost per unit</p>
                        </div>

                        {{-- Received date --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Received date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="received_date"
                                   value="{{ old('received_date', now()->toDateString()) }}"
                                   class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @error('received_date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="3"
                                  placeholder="Optional notes about this receipt…"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 mt-6">
                    <a href="{{ route('pages.inventory.index') }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                            :disabled="!selectedProduct"
                            class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-teal-700 dark:hover:bg-teal-800">
                        Save Record
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
    const INVENTORY_SEARCH_URL = @json(route('pages.inventory.api.search-products'));

    function inventoryCreate() {
        return {
            searchText:    '',
            results:       [],
            showDropdown:  false,
            searching:     false,
            highlighted:   -1,
            selectedProduct: null,
            itemName:      '',
            selectedUnit:  '{{ old('unit', '') }}',
            quantity:      {{ old('quantity_received', 0) }},
            costPerUnit:   {{ old('cost_price', 0) }},
            totalCost:     0,
            totalCostFormatted: '0.00',
            errors:        {},

            async search() {
                if (this.searchText.length < 2) {
                    this.results = [];
                    this.showDropdown = false;
                    return;
                }
                this.searching = true;
                this.showDropdown = true;
                this.highlighted = -1;
                try {
                    const r = await fetch(INVENTORY_SEARCH_URL + '?q=' + encodeURIComponent(this.searchText));
                    this.results = await r.json();
                } finally {
                    this.searching = false;
                }
            },

            selectProduct(item) {
                this.selectedProduct = item;
                const color = (item.color && item.color !== item.name) ? item.color : null;
                this.itemName = [item.name, color].filter(Boolean).join(' — ');
                this.costPerUnit     = item.cost_price ?? 0;
                if (item.unit_code) this.selectedUnit = item.unit_code;
                this.closeDropdown();
                this.calcTotal();
            },

            selectHighlighted() {
                if (this.highlighted >= 0 && this.results[this.highlighted]) {
                    this.selectProduct(this.results[this.highlighted]);
                }
            },

            highlightNext() {
                this.highlighted = Math.min(this.highlighted + 1, this.results.length - 1);
            },

            highlightPrev() {
                this.highlighted = Math.max(this.highlighted - 1, -1);
            },

            closeDropdown() {
                this.showDropdown = false;
                this.highlighted  = -1;
            },

            clearProduct() {
                this.selectedProduct = null;
                this.itemName        = '';
                this.searchText      = '';
                this.results         = [];
            },

            calcTotal() {
                const qty  = parseFloat(this.quantity)  || 0;
                const cost = parseFloat(this.costPerUnit) || 0;
                this.totalCost          = qty * cost;
                this.totalCostFormatted = (qty * cost).toLocaleString('en-CA', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            },

            submitForm(e) {
                this.errors = {};
                if (!this.selectedProduct) {
                    this.errors.product_style_id = 'Please select a product from the catalog.';
                    return;
                }
                if (!this.quantity || this.quantity <= 0) {
                    this.errors.quantity_received = 'Quantity must be greater than zero.';
                    return;
                }
                if (!this.selectedUnit) {
                    this.errors.unit = 'Please select a unit.';
                    return;
                }
                e.target.submit();
            },
        };
    }
    </script>
</x-app-layout>
