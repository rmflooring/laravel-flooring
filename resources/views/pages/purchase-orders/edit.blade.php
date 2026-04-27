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

            <div x-data="poEdit()">
            <form method="POST" action="{{ route('pages.purchase-orders.update', $purchaseOrder) }}">
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
                                    @change="handleStatusChange()"
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
                                        <td class="px-6 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</div>
                                            <textarea name="po_items[{{ $item->id }}][po_notes]"
                                                      rows="2"
                                                      placeholder="PO notes..."
                                                      class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ old('po_items.' . $item->id . '.po_notes', $item->po_notes) }}</textarea>
                                        </td>
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
                                {{ $purchaseOrder->sale?->job_address ?: 'No site address on this sale' }}
                            </p>
                            <p x-show="fulfillmentMethod === 'delivery_warehouse'" x-cloak class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $warehouseAddress ?: 'No warehouse address configured' }}
                            </p>
                            <div x-show="fulfillmentMethod === 'pickup'" x-cloak class="mt-3 space-y-3">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    No delivery address needed — we will pick up from the vendor.
                                </p>
                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/10">
                                    <p class="mb-3 text-xs font-medium text-blue-700 dark:text-blue-400">
                                        Schedule Warehouse Pickup — syncs to RM Warehouse calendar
                                        @if($purchaseOrder->calendar_event_id)
                                            <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">Synced</span>
                                        @endif
                                    </p>
                                    <div class="flex flex-wrap gap-4">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pickup Date</label>
                                            <input type="date" name="pickup_date"
                                                   value="{{ old('pickup_date', $purchaseOrder->pickup_at?->format('Y-m-d')) }}"
                                                   class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            @error('pickup_date')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Pickup Time</label>
                                            <input type="time" name="pickup_time"
                                                   value="{{ old('pickup_time', $purchaseOrder->pickup_at?->format('H:i') ?? '09:00') }}"
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
                    <div>
                        <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
                        <div id="si-quill-editor" style="min-height:110px; font-size:14px;"></div>
                        <input type="hidden" name="special_instructions" id="si-input">
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

            {{-- Receive Inventory Modal --}}
            @can('edit purchase orders')
            <div x-show="showReceiveModal"
                 x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                 @keydown.escape.window="showReceiveModal = false">
                <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-800 flex flex-col max-h-[90vh]"
                     @click.stop>

                    {{-- Modal header --}}
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700 shrink-0">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Receive items</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Confirm quantities received. The PO status will be set to
                                <span class="font-medium text-green-700 dark:text-green-400">Received</span>.
                            </p>
                        </div>
                        <button type="button" @click="showReceiveModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Receive form --}}
                    <form method="POST" action="{{ route('pages.purchase-orders.receive', $purchaseOrder) }}"
                          x-data="receiveModalForm()" class="flex flex-col min-h-0 flex-1 overflow-hidden">
                        @csrf

                        <div class="overflow-y-auto flex-1 p-6 space-y-6">

                            {{-- Received date --}}
                            <div>
                                <label for="modal-received-date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Date received <span class="text-red-500">*</span>
                                </label>
                                <input type="date"
                                       id="modal-received-date"
                                       name="received_date"
                                       value="{{ now()->toDateString() }}"
                                       max="{{ now()->toDateString() }}"
                                       required
                                       class="w-48 rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>

                            {{-- Items table --}}
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Items ordered</h4>
                                    <button type="button" @click="setAllToOrdered()"
                                            class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                        Reset all to ordered qty
                                    </button>
                                </div>
                                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400">
                                            <tr>
                                                <th class="px-4 py-3">Item</th>
                                                <th class="px-4 py-3 text-right whitespace-nowrap">Ordered qty</th>
                                                <th class="px-4 py-3 text-right whitespace-nowrap">Qty received</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach ($purchaseOrder->items as $item)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                    <td class="px-4 py-3">
                                                        <div class="font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</div>
                                                        @if ($item->po_notes)
                                                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $item->po_notes }}</div>
                                                        @endif
                                                        <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $item->unit }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                        {{ $item->quantity }} {{ $item->unit }}
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        <input type="number"
                                                               name="quantities[{{ $item->id }}]"
                                                               value="{{ $item->quantity }}"
                                                               min="0"
                                                               step="0.01"
                                                               data-ordered="{{ $item->quantity }}"
                                                               class="modal-qty-input w-24 rounded-lg border-gray-300 text-sm text-right shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                               required>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Vendor / PO info reminder --}}
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-5 py-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $purchaseOrder->vendor->company_name }}</span>
                                <span class="mx-2 text-gray-300">·</span>
                                PO {{ $purchaseOrder->po_number }}
                                @if ($purchaseOrder->vendor_order_number)
                                    <span class="mx-2 text-gray-300">·</span>
                                    Vendor order #{{ $purchaseOrder->vendor_order_number }}
                                @endif
                            </div>

                        </div>

                        {{-- Modal footer --}}
                        <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700 shrink-0">
                            <button type="button" @click="showReceiveModal = false"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Confirm receipt &amp; mark received
                            </button>
                        </div>
                    </form>

                </div>
            </div>
            @endcan

            </div>{{-- /x-data="poEdit()" --}}
        </div>
    </div>

    <script>
    function poEdit() {
        return {
            status: '{{ old('status', $purchaseOrder->status) }}',
            fulfillmentMethod: '{{ old('fulfillment_method', $purchaseOrder->fulfillment_method) }}',
            originalStatus: '{{ $purchaseOrder->status }}',
            showReceiveModal: false,
            handleStatusChange() {
                if (this.status === 'received') {
                    this.status = this.originalStatus;
                    this.showReceiveModal = true;
                }
            },
        };
    }

    function receiveModalForm() {
        return {
            setAllToOrdered() {
                document.querySelectorAll('.modal-qty-input').forEach(input => {
                    input.value = input.dataset.ordered;
                });
            },
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

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const siQuill = new Quill('#si-quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [['bold','italic','underline'],[{'color':[]}],['clean']]
            },
        });
        function syncSiInput() {
            const html = siQuill.root.innerHTML;
            document.getElementById('si-input').value = (html === '<p><br></p>') ? '' : html;
        }
        siQuill.on('text-change', syncSiInput);
        const siExisting = @json(old('special_instructions', $purchaseOrder->special_instructions ?? ''));
        if (siExisting) siQuill.clipboard.dangerouslyPasteHTML(siExisting);
        else syncSiInput();
    </script>
</x-app-layout>
