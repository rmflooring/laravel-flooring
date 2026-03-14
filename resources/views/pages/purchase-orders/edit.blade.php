{{-- resources/views/pages/purchase-orders/edit.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Edit Purchase Order
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $purchaseOrder->po_number }}</span>
                        <span class="text-gray-400">•</span>
                        <span>Sale: {{ $purchaseOrder->sale->sale_number }}</span>
                        @if($purchaseOrder->sale->customer_name)
                            <span class="text-gray-400">•</span>
                            <span>{{ $purchaseOrder->sale->customer_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.purchase-orders.show', $purchaseOrder) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Cancel
                    </a>
                    <a href="{{ route('pages.purchase-orders.pdf', $purchaseOrder) }}" target="_blank"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Print PDF
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

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-6 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400" role="alert">
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.purchase-orders.update', $purchaseOrder) }}"
                  x-data="poEdit()">
                @csrf
                @method('PUT')

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
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            <option value="">— Select a vendor —</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}"
                                        {{ old('vendor_id', $purchaseOrder->vendor_id) == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->company_name }}
                                    @if($vendor->email) ({{ $vendor->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Status --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Status</h2>
                    </div>
                    <div class="space-y-5 p-6">

                        <div>
                            <label for="status" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                PO Status <span class="text-red-500">*</span>
                            </label>
                            <select id="status" name="status" required
                                    x-model="status"
                                    class="block w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                                <option value="pending"   {{ old('status', $purchaseOrder->status) === 'pending'   ? 'selected' : '' }}>Pending</option>
                                <option value="ordered"   {{ old('status', $purchaseOrder->status) === 'ordered'   ? 'selected' : '' }}>Ordered</option>
                                <option value="received"  {{ old('status', $purchaseOrder->status) === 'received'  ? 'selected' : '' }}>Received</option>
                                <option value="cancelled" {{ old('status', $purchaseOrder->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        {{-- Vendor Order Number — required when status = ordered --}}
                        <div x-show="status === 'ordered' || status === 'received'" x-cloak>
                            <label for="vendor_order_number" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Vendor Order Number
                                <span x-show="status === 'ordered'" class="text-red-500">*</span>
                            </label>
                            <input type="text" id="vendor_order_number" name="vendor_order_number"
                                   value="{{ old('vendor_order_number', $purchaseOrder->vendor_order_number) }}"
                                   placeholder="e.g. VND-2026-98123"
                                   :required="status === 'ordered'"
                                   class="block w-full max-w-sm rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            @error('vendor_order_number')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p x-show="status === 'ordered'" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Required to set status to Ordered.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- PO Items --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                     x-data="poItems()">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Qty and unit cost can be adjusted. Item list is fixed at creation.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Item</th>
                                    <th class="px-6 py-3 text-right">Qty</th>
                                    <th class="px-6 py-3">Unit</th>
                                    <th class="px-6 py-3 text-right">Unit Cost</th>
                                    <th class="px-6 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($purchaseOrder->items as $item)
                                    @php $maxInfo = $maxQtys[$item->id] ?? ['max' => $item->quantity, 'sale_qty' => $item->quantity]; @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</td>
                                        <td class="px-6 py-3 text-right">
                                            <input type="number"
                                                   name="po_items[{{ $item->id }}][quantity]"
                                                   value="{{ old('po_items.' . $item->id . '.quantity', $item->quantity) }}"
                                                   min="0.01" max="{{ $maxInfo['max'] }}" step="0.01"
                                                   @input="recalcRow({{ $item->id }}, $event, {{ $maxInfo['max'] }})"
                                                   :class="qtyErrors[{{ $item->id }}] ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'"
                                                   class="w-24 rounded-lg border bg-white px-2 py-1 text-right text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <div class="mt-0.5 text-right text-xs text-gray-400">max {{ $maxInfo['max'] }}</div>
                                            <div x-show="qtyErrors[{{ $item->id }}]"
                                                 x-text="qtyErrors[{{ $item->id }}]"
                                                 style="display:none"
                                                 class="mt-0.5 text-right text-xs text-red-600 dark:text-red-400"></div>
                                            @error('po_items.' . $item->id . '.quantity')
                                                <div class="mt-0.5 text-right text-xs text-red-600 dark:text-red-400">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $item->unit }}</td>
                                        <td class="px-6 py-3 text-right">
                                            <div class="relative inline-flex items-center">
                                                <span class="absolute left-2.5 text-sm text-gray-500">$</span>
                                                <input type="number"
                                                       name="po_items[{{ $item->id }}][cost_price]"
                                                       value="{{ old('po_items.' . $item->id . '.cost_price', $item->cost_price) }}"
                                                       min="0" step="0.01"
                                                       @input="recalcRow({{ $item->id }}, $event, {{ $maxInfo['max'] }})"
                                                       class="w-28 rounded-lg border border-gray-300 bg-white py-1 pl-6 pr-2 text-right text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-right font-semibold"
                                            x-text="'$' + (rows[{{ $item->id }}] ?? {{ $item->cost_total }}).toLocaleString('en-CA', {minimumFractionDigits:2, maximumFractionDigits:2})">
                                            ${{ number_format($item->cost_total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="border-t-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-700/40">
                                <tr>
                                    <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">Grand Total</td>
                                    <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white"
                                        x-text="'$' + grandTotal.toLocaleString('en-CA', {minimumFractionDigits:2, maximumFractionDigits:2})">
                                        ${{ number_format($purchaseOrder->items->sum('cost_total'), 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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
                                   value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date?->format('Y-m-d')) }}"
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
                                <option value="delivery_site"      {{ old('fulfillment_method', $purchaseOrder->fulfillment_method) === 'delivery_site'      ? 'selected' : '' }}>Delivery to Site Address</option>
                                <option value="delivery_warehouse" {{ old('fulfillment_method', $purchaseOrder->fulfillment_method) === 'delivery_warehouse' ? 'selected' : '' }}>Delivery to Warehouse / Shop</option>
                                <option value="delivery_custom"    {{ old('fulfillment_method', $purchaseOrder->fulfillment_method) === 'delivery_custom'    ? 'selected' : '' }}>Delivery to Custom Address</option>
                                <option value="pickup"             {{ old('fulfillment_method', $purchaseOrder->fulfillment_method) === 'pickup'             ? 'selected' : '' }}>Pickup</option>
                            </select>

                            <p x-show="fulfillmentMethod === 'delivery_site'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $purchaseOrder->sale->job_address ?: 'No site address on this sale' }}
                            </p>
                            <p x-show="fulfillmentMethod === 'delivery_warehouse'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $warehouseAddress ?: 'No warehouse address configured' }}
                            </p>
                            <p x-show="fulfillmentMethod === 'pickup'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                No delivery address needed — we will pick up from the vendor.
                            </p>

                            <div x-show="fulfillmentMethod === 'delivery_custom'" x-cloak class="mt-2">
                                <textarea name="delivery_address" rows="3"
                                          placeholder="Enter delivery address..."
                                          class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">{{ old('delivery_address', $purchaseOrder->fulfillment_method === 'delivery_custom' ? $purchaseOrder->delivery_address : '') }}</textarea>
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
                    <div class="p-6">
                        <textarea id="special_instructions" name="special_instructions" rows="4"
                                  placeholder="Any special instructions for the vendor..."
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">{{ old('special_instructions', $purchaseOrder->special_instructions) }}</textarea>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('pages.purchase-orders.show', $purchaseOrder) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
    function poEdit() {
        return {
            status: '{{ old('status', $purchaseOrder->status) }}',
            fulfillmentMethod: '{{ old('fulfillment_method', $purchaseOrder->fulfillment_method) }}',
        };
    }

    function poItems() {
        const initial = @json(
            $purchaseOrder->items->mapWithKeys(fn($i) => [$i->id => (float) $i->cost_total])
        );
        return {
            rows: { ...initial },
            qtyErrors: {},
            get grandTotal() {
                return Object.values(this.rows).reduce((s, v) => s + v, 0);
            },
            recalcRow(id, event, maxQty) {
                const row  = event.target.closest('tr');
                const qty  = parseFloat(row.querySelector('input[name*="[quantity]"]').value)  || 0;
                const cost = parseFloat(row.querySelector('input[name*="[cost_price]"]').value) || 0;
                this.rows[id] = Math.round(qty * cost * 100) / 100;

                if (maxQty !== undefined && qty > maxQty + 0.001) {
                    this.qtyErrors[id] = `Exceeds max of ${maxQty}`;
                } else {
                    delete this.qtyErrors[id];
                }
            },
        };
    }
    </script>
</x-app-layout>
