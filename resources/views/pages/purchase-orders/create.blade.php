{{-- resources/views/pages/purchase-orders/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Purchase Order</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Sale: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $sale->sale_number }}</span>
                        @if($sale->customer_name)
                            &mdash; {{ $sale->customer_name }}
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
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

            <form method="POST" action="{{ route('pages.sales.purchase-orders.store', $sale) }}"
                  x-data="poCreate()" @submit.prevent="submitForm">

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
                        {{-- Hidden input always submits vendor_id — the select can be disabled without losing the value --}}
                        <input type="hidden" name="vendor_id" :value="vendorId">
                        <select id="vendor_id"
                                x-model="vendorId"
                                @change="onVendorChange()"
                                :disabled="selectedItems.length > 0 && vendorId !== ''"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            <option value="">— Select a vendor —</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}"
                                        data-email="{{ $vendor->email }}"
                                        {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->company_name }}
                                    @if($vendor->email) ({{ $vendor->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            PO will be emailed to the vendor's stored email address.
                        </p>
                        <template x-if="selectedItems.length > 0 && vendorId !== ''">
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                Vendor locked. Uncheck all items to change vendor.
                            </p>
                        </template>
                        <template x-if="selectedItems.length > 0 && vendorId === ''">
                            <p class="mt-1 text-xs text-blue-600 dark:text-blue-400">
                                Items selected — now choose the vendor to order from.
                            </p>
                        </template>
                        <template x-if="vendorId !== '' && selectedItems.length === 0">
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                Only items from this vendor can be added to this PO.
                            </p>
                        </template>
                    </div>
                </div>

                {{-- Material Items --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Material Items</h2>
                            <button type="button" @click="toggleAll"
                                    class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                Toggle All
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Select which material items to include on this PO.</p>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($sale->rooms as $room)
                            @if($room->items->isNotEmpty())
                                {{-- Room header --}}
                                <div class="bg-gray-50 px-6 py-3 dark:bg-gray-700/40">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ $room->room_name ?: 'Unnamed Room' }}
                                    </span>
                                </div>

                                @foreach($room->items as $item)
                                    @php
                                        $nameParts = array_filter([
                                            $item->product_type,
                                            $item->manufacturer,
                                            $item->style,
                                            $item->color_item_number,
                                        ]);
                                        $displayName  = implode(' — ', $nameParts) ?: 'Material Item';
                                        $remaining    = $remainingQtys[$item->id] ?? (float) $item->quantity;
                                        $fullyOrdered = $remaining <= 0;
                                    @endphp
                                    <div class="px-6 py-4"
                                         :class="{
                                             'opacity-50': {{ $fullyOrdered ? 'true' : 'false' }} || isItemLocked('{{ $item->id }}'),
                                             'hover:bg-gray-50 dark:hover:bg-gray-700/30': !({{ $fullyOrdered ? 'true' : 'false' }} || isItemLocked('{{ $item->id }}')),
                                             'bg-blue-50 dark:bg-blue-900/10': selectedItems.map(String).includes('{{ $item->id }}')
                                         }">
                                        <label class="flex items-start gap-4"
                                               :class="({{ $fullyOrdered ? 'true' : 'false' }} || isItemLocked('{{ $item->id }}')) ? 'cursor-not-allowed' : 'cursor-pointer'">
                                            <input type="checkbox"
                                                   name="items[]"
                                                   value="{{ $item->id }}"
                                                   x-model="selectedItems"
                                                   :disabled="{{ $fullyOrdered ? 'true' : 'false' }} || isItemLocked('{{ $item->id }}')"
                                                   @change="onItemCheck('{{ $item->id }}', $event.target.checked)"
                                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 disabled:cursor-not-allowed">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $displayName }}</p>
                                                    @if($fullyOrdered)
                                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            Fully ordered
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                                            {{ $remaining }} {{ $item->unit }} remaining
                                                        </span>
                                                    @endif
                                                    <template x-if="isItemLocked('{{ $item->id }}')">
                                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                                            Wrong vendor
                                                        </span>
                                                    </template>
                                                </div>
                                                <div class="mt-1 flex flex-wrap gap-3 text-xs text-gray-500 dark:text-gray-400">
                                                    <span>Sale Qty: <strong class="text-gray-700 dark:text-gray-300">{{ $item->quantity }} {{ $item->unit }}</strong></span>
                                                    <span>Unit Cost: <strong class="text-gray-700 dark:text-gray-300">${{ number_format($item->cost_price, 2) }}</strong></span>
                                                </div>
                                                @if($item->po_notes)
                                                    <p class="mt-1 text-xs italic text-gray-400 dark:text-gray-500">{{ $item->po_notes }}</p>
                                                @endif
                                            </div>
                                        </label>

                                        @if(! $fullyOrdered)
                                        {{-- Editable overrides — visible when checked --}}
                                        <div x-show="selectedItems.map(String).includes('{{ $item->id }}')"
                                             style="display:none"
                                             class="mt-3 ml-8 space-y-3">
                                            <div class="flex flex-wrap items-end gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                        Qty <span class="text-gray-400">(max {{ $remaining }})</span>
                                                    </label>
                                                    <input type="number" name="qty[{{ $item->id }}]"
                                                           value="{{ old('qty.' . $item->id, $remaining) }}"
                                                           min="0.01" max="{{ $remaining }}" step="0.01"
                                                           @input="validateQty('{{ $item->id }}', $event.target.value, {{ $remaining }})"
                                                           :class="qtyErrors['{{ $item->id }}'] ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                                           class="w-28 rounded-lg border bg-white px-2 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    <p x-show="qtyErrors['{{ $item->id }}']"
                                                       x-text="qtyErrors['{{ $item->id }}']"
                                                       style="display:none"
                                                       class="mt-1 text-xs text-red-600 dark:text-red-400"></p>
                                                    @error('qty.' . $item->id)
                                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                        Unit Cost
                                                    </label>
                                                    <div class="relative">
                                                        <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 text-sm text-gray-500">$</span>
                                                        <input type="number" name="cost[{{ $item->id }}]"
                                                               value="{{ old('cost.' . $item->id, $item->cost_price) }}"
                                                               min="0" step="0.01"
                                                               class="w-32 rounded-lg border border-gray-300 bg-white py-1.5 pl-6 pr-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                                    PO Notes
                                                </label>
                                                <textarea name="po_notes[{{ $item->id }}]"
                                                          rows="2"
                                                          placeholder="Notes for this item on the PO..."
                                                          class="block w-full max-w-lg rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ old('po_notes.' . $item->id, $item->po_notes) }}</textarea>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        @empty
                            <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No material items found on this sale.
                            </div>
                        @endforelse
                    </div>

                    {{-- Validation error --}}
                    @error('items')
                        <div class="border-t border-red-100 bg-red-50 px-6 py-3 dark:border-red-900 dark:bg-gray-800">
                            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        </div>
                    @enderror
                </div>

                {{-- Delivery & Dates --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Fulfillment & Delivery</h2>
                    </div>
                    <div class="space-y-6 p-6">

                        {{-- Expected ETA --}}
                        <div>
                            <label for="expected_delivery_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Expected ETA
                            </label>
                            <input type="date" id="expected_delivery_date" name="expected_delivery_date"
                                   value="{{ old('expected_delivery_date') }}"
                                   class="block w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        {{-- Fulfillment Method --}}
                        <div>
                            <label for="fulfillment_method" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Fulfillment Method <span class="text-red-500">*</span>
                            </label>
                            <select id="fulfillment_method" name="fulfillment_method" required
                                    x-model="fulfillmentMethod"
                                    class="block w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                                <option value="">— Select fulfillment method —</option>
                                <option value="delivery_site"      {{ old('fulfillment_method') === 'delivery_site'      ? 'selected' : '' }}>Delivery to Site Address</option>
                                <option value="delivery_warehouse" {{ old('fulfillment_method') === 'delivery_warehouse' ? 'selected' : '' }}>Delivery to Warehouse / Shop</option>
                                <option value="delivery_custom"    {{ old('fulfillment_method') === 'delivery_custom'    ? 'selected' : '' }}>Delivery to Custom Address</option>
                                <option value="pickup"             {{ old('fulfillment_method') === 'pickup'             ? 'selected' : '' }}>Pickup</option>
                            </select>

                            <p x-show="fulfillmentMethod === 'delivery_site'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $sale->job_address ?: 'No site address on this sale' }}
                            </p>
                            <p x-show="fulfillmentMethod === 'delivery_warehouse'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $warehouseAddress ?: 'No warehouse address configured in branding settings' }}
                            </p>
                            <div x-show="fulfillmentMethod === 'pickup'" x-cloak class="mt-3 space-y-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    No delivery address needed — we will pick up from the vendor.
                                </p>
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/10">
                                    <p class="mb-3 text-xs font-medium text-blue-700 dark:text-blue-400">
                                        Schedule Warehouse Pickup — syncs to RM Warehouse calendar
                                    </p>
                                    <div class="flex flex-wrap gap-4">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pickup Date</label>
                                            <input type="date" name="pickup_date"
                                                   value="{{ old('pickup_date') }}"
                                                   :disabled="fulfillmentMethod !== 'pickup'"
                                                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @error('pickup_date')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pickup Time</label>
                                            <input type="time" name="pickup_time"
                                                   value="{{ old('pickup_time', '09:00') }}"
                                                   :disabled="fulfillmentMethod !== 'pickup'"
                                                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @error('pickup_time')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Leave blank to skip scheduling. Event duration is 1 hour.</p>
                                </div>
                            </div>

                            <div x-show="fulfillmentMethod === 'delivery_custom'" x-cloak class="mt-2">
                                <textarea name="delivery_address" rows="3"
                                          placeholder="Enter delivery address..."
                                          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">{{ old('delivery_address') }}</textarea>
                            </div>

                            @error('fulfillment_method')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Special Instructions --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Special Instructions</h2>
                    </div>
                    <div>
                        <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
                        <div id="si-quill-editor" style="min-height:110px; font-size:14px;"></div>
                        <input type="hidden" name="special_instructions" id="si-input" value="{{ old('special_instructions') }}">
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            :disabled="selectedItems.length === 0 || !vendorId || !fulfillmentMethod"
                            class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Create Purchase Order
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
    function poCreate() {
        return {
            vendorId: '{{ old('vendor_id', '') }}',
            selectedItems: [],
            fulfillmentMethod: '{{ old('fulfillment_method', '') }}',
            qtyErrors: {},

            // sale_item_id => vendor_id from product_lines (null if item has no catalog link)
            itemVendorMap: @json($itemVendorMap),

            // Called when vendor dropdown changes
            onVendorChange() {
                if (this.vendorId) {
                    // Deselect any items locked out by the new vendor selection
                    this.selectedItems = this.selectedItems.filter(id => !this.isItemLocked(id));
                }
            },

            // Called when an item checkbox changes
            onItemCheck(itemId, checked) {
                this.$nextTick(() => {
                    if (this.selectedItems.length === 0) {
                        // All unchecked — release vendor lock
                        this.vendorId = '';
                        return;
                    }

                    if (checked) {
                        // Guard: if item is locked, force-uncheck it
                        if (this.isItemLocked(itemId)) {
                            this.selectedItems = this.selectedItems.filter(id => String(id) !== String(itemId));
                            return;
                        }

                        // Auto-select vendor from this item's product line vendor_id
                        if (!this.vendorId) {
                            const itemVendor = this.itemVendorMap[String(itemId)];
                            if (itemVendor) { this.vendorId = String(itemVendor); }
                        }
                    }
                });
            },

            // Returns true if this item cannot be added to the current PO.
            // An item is locked when its product_line.vendor_id differs from the selected vendor.
            // Items with no vendor_id on their product line are never locked.
            isItemLocked(itemId) {
                if (this.vendorId === '') return false;
                const itemVendor = this.itemVendorMap[String(itemId)];
                if (!itemVendor) return false; // no catalog vendor link — always allowed
                return String(itemVendor) !== this.vendorId;
            },

            toggleAll() {
                const allIds = @json(
                    collect($remainingQtys)->filter(fn($r) => $r > 0)->keys()
                ).map(String);

                const eligible = allIds.filter(id => !this.isItemLocked(id));
                const allSelected = eligible.length > 0 && eligible.every(id => this.selectedItems.includes(id));

                if (allSelected) {
                    this.selectedItems = this.selectedItems.filter(id => !eligible.includes(id));
                    if (this.selectedItems.length === 0) {
                        this.vendorId = '';
                    }
                } else {
                    // Auto-set vendor from first eligible item's product line if not already set
                    if (this.vendorId === '' && eligible.length > 0) {
                        const firstVendor = this.itemVendorMap[eligible[0]];
                        if (firstVendor) { this.vendorId = String(firstVendor); }
                    }
                    const toAdd = eligible.filter(id => !this.isItemLocked(id));
                    this.selectedItems = [...new Set([...this.selectedItems, ...toAdd])];
                }
            },

            validateQty(itemId, value, maxQty) {
                const qty = parseFloat(value);
                if (isNaN(qty) || qty <= 0) {
                    this.qtyErrors[itemId] = 'Qty must be greater than 0.';
                } else if (qty > maxQty + 0.001) {
                    this.qtyErrors[itemId] = `Cannot exceed available qty of ${maxQty}.`;
                } else {
                    delete this.qtyErrors[itemId];
                }
            },

            submitForm() {
                if (this.selectedItems.length === 0) {
                    alert('Please select at least one material item.');
                    return;
                }
                if (!this.vendorId) {
                    alert('Please select a vendor.');
                    return;
                }
                if (!this.fulfillmentMethod) {
                    alert('Please select a fulfillment method.');
                    return;
                }
                if (Object.keys(this.qtyErrors).length > 0) {
                    alert('Please fix qty errors before submitting.');
                    return;
                }
                this.$el.submit();
            },
        };
    }
    </script>

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const siQuill = new Quill('#si-quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [['bold','italic','underline'],[{'color':[]}],['clean']]
            },
            placeholder: 'Any special instructions for the vendor...',
        });
        function syncSiInput() {
            const html = siQuill.root.innerHTML;
            document.getElementById('si-input').value = (html === '<p><br></p>') ? '' : html;
        }
        siQuill.on('text-change', syncSiInput);
        const siExisting = @json(old('special_instructions', ''));
        if (siExisting) siQuill.clipboard.dangerouslyPasteHTML(siExisting);
        else syncSiInput();
    </script>
</x-app-layout>
