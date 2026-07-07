<x-admin-layout>
<div class="py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Invoice {{ $invoice->invoice_number }}</h1>
            <p class="text-sm text-gray-600 mt-0.5">Sale #{{ $sale->sale_number }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
               class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                ← View Invoice
            </a>
            <button type="submit" form="invoice-edit-form"
                    class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                Save Invoice
            </button>
        </div>
    </div>

    @if (session('error'))
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Main form --}}
    <form id="invoice-edit-form" method="POST" action="{{ route('pages.sales.invoices.update', [$sale, $invoice]) }}">
    @csrf
    @method('PUT')

    {{-- Invoice Details card --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                <select name="status"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="draft" {{ old('status', $invoice->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent"  {{ old('status', $invoice->status) === 'sent'  ? 'selected' : '' }}>Sent</option>
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Payment Terms</label>
                <select name="payment_term_id" id="payment_term_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
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
                <label class="block mb-1 text-sm font-medium text-gray-700">Due Date</label>
                <input type="date" name="due_date" id="due_date"
                    value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Customer PO #</label>
                <input type="text" name="customer_po_number"
                    value="{{ old('customer_po_number', $invoice->customer_po_number) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
            </div>

            <div class="sm:col-span-2">
                <label class="block mb-1 text-sm font-medium text-gray-700">Notes</label>
                <textarea name="notes" rows="2"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">{{ old('notes', $invoice->notes) }}</textarea>
            </div>

            {{-- Bill To Override --}}
            <div class="sm:col-span-3" x-data="{ override: {{ old('bill_to_name', $invoice->bill_to_name) ? 'true' : 'false' }} }">
                <label class="flex items-center gap-2 cursor-pointer select-none mb-1">
                    <input type="checkbox" x-model="override"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Override "Bill To" on PDF</span>
                </label>
                <p class="text-xs text-gray-400 mb-3">Use when billing a different party than the sale customer (e.g. homeowner overage).</p>

                <div x-show="override" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4 pl-6 border-l-2 border-blue-200">
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Name / Company</label>
                        <input type="text" name="bill_to_name"
                               value="{{ old('bill_to_name', $invoice->bill_to_name) }}"
                               placeholder="e.g. John Smith"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Address <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" name="bill_to_address"
                               value="{{ old('bill_to_address', $invoice->bill_to_address) }}"
                               placeholder="e.g. 123 Main St, Vancouver BC"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600">Email <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="email" name="bill_to_email"
                               value="{{ old('bill_to_email', $invoice->bill_to_email) }}"
                               placeholder="e.g. homeowner@example.com"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Rooms --}}
    <div id="rooms-container" class="space-y-6">

    @foreach ($invoice->rooms as $roomIndex => $room)
    @php
        $materials = $room->items->where('item_type', 'material')->values();
        $freightItems = $room->items->where('item_type', 'freight')->values();
        $labourItems  = $room->items->where('item_type', 'labour')->values();
    @endphp

    <div class="room-card w-full bg-white border border-gray-200 rounded-lg shadow-sm overflow-visible" data-room-index="{{ $roomIndex }}">

        {{-- Room header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div class="flex items-center gap-3">
                <h2 class="room-title text-lg font-semibold text-gray-900">Room {{ $roomIndex + 1 }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="move-up {{ $roomIndex === 0 ? 'hidden' : '' }} inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">↑</button>
                <button type="button" class="move-down {{ $roomIndex === $invoice->rooms->count() - 1 ? 'hidden' : '' }} inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">↓</button>
                <button type="button" class="delete-room inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete Room</button>
            </div>
        </div>

        <div class="p-6 space-y-8">

            {{-- Room name --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Room Name</label>
                <input type="hidden" name="rooms[{{ $roomIndex }}][id]" value="{{ $room->id }}">
                <input type="hidden" class="room-delete-flag" name="rooms[{{ $roomIndex }}][_delete]" value="0">
                <input type="hidden" class="room-subtotal-materials-input" name="rooms[{{ $roomIndex }}][subtotal_materials]" value="{{ number_format((float)$room->items->where('item_type','material')->sum('line_total'), 2, '.', '') }}">
                <input type="hidden" class="room-subtotal-freight-input"   name="rooms[{{ $roomIndex }}][subtotal_freight]"   value="{{ number_format((float)$room->items->where('item_type','freight')->sum('line_total'), 2, '.', '') }}">
                <input type="hidden" class="room-subtotal-labour-input"    name="rooms[{{ $roomIndex }}][subtotal_labour]"    value="{{ number_format((float)$room->items->where('item_type','labour')->sum('line_total'), 2, '.', '') }}">
                <input type="hidden" class="room-total-input"              name="rooms[{{ $roomIndex }}][room_total]"         value="{{ number_format((float)$room->items->sum('line_total'), 2, '.', '') }}">
                <input type="text" name="rooms[{{ $roomIndex }}][room_name]"
                    value="{{ old("rooms.$roomIndex.room_name", $room->name) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    placeholder="e.g. Living Room">
            </div>

            {{-- Materials --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Materials</h3>
                    <button type="button"
                        class="add-material-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                        + Add Material Row
                    </button>
                </div>

                <div class="border border-gray-200 rounded-lg overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Product Type</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Manufacturer</th>
                                <th class="px-3 py-3">Style</th>
                                <th class="px-3 py-3">Color / Item #</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3 w-28 text-right">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="materials-tbody">
                        @foreach ($materials as $i => $item)
                        @php
                            $qty  = (float)($item->quantity ?? 0);
                            $sell = (float)($item->sell_price ?? 0);
                            $line = (float)($item->line_total ?? ($qty * $sell));
                        @endphp
                        <tr class="bg-white border-t">
                            <td class="px-3 py-2 relative">
                                <input type="hidden" name="rooms[{{ $roomIndex }}][materials][{{ $i }}][id]" value="{{ $item->id }}">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][product_type]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.product_type", $item->label) }}"
                                    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    autocomplete="off"
                                    data-product-type-input>
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                    data-product-type-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-product-type-options></ul>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][quantity]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.quantity", $qty) }}"
                                    class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][unit]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.unit", $item->unit ?? '') }}"
                                    class="w-14 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2 relative">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][manufacturer]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.manufacturer", '') }}"
                                    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    autocomplete="off"
                                    placeholder="Manufacturer"
                                    data-manufacturer-input>
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                    data-manufacturer-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-manufacturer-options></ul>
                                </div>
                            </td>
                            <td class="px-3 py-2 relative">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][style]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.style", '') }}"
                                    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    autocomplete="off"
                                    placeholder="Style"
                                    data-style-input>
                                <input type="hidden"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][product_line_id]"
                                    class="js-product-line-id-input" value="">
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                    data-style-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-style-options></ul>
                                </div>
                            </td>
                            <td class="px-3 py-2 relative">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][color_item_number]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.color_item_number", '') }}"
                                    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    autocomplete="off"
                                    placeholder="Color / Item #"
                                    data-color-input>
                                <input type="hidden"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][product_style_id]"
                                    class="js-product-style-id-input" value="">
                                <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg hidden"
                                    data-color-dropdown>
                                    <ul class="py-1 max-h-56 overflow-auto" data-color-options></ul>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][sell_price]"
                                    value="{{ old("rooms.$roomIndex.materials.$i.sell_price", number_format($sell, 4, '.', '')) }}"
                                    class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2">
                                <span class="line-total-display inline-block w-28 text-right font-medium">${{ number_format($line, 2) }}</span>
                                <input type="hidden"
                                    name="rooms[{{ $roomIndex }}][materials][{{ $i }}][line_total]"
                                    value="{{ number_format($line, 2, '.', '') }}">
                            </td>
                            <td class="px-3 py-2">
                                <button type="button" class="delete-material-row text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Material row template --}}
                <template class="material-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2 relative">
                            <input type="text"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][product_type]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Product Type"
                                autocomplete="off"
                                data-product-type-input>
                            <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                data-product-type-dropdown>
                                <ul class="py-1 text-sm text-gray-700" data-product-type-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="any"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][quantity]"
                                class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][unit]"
                                class="w-14 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="SF">
                        </td>
                        <td class="px-3 py-2 relative">
                            <input type="text"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][manufacturer]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Manufacturer"
                                autocomplete="off"
                                data-manufacturer-input>
                            <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                data-manufacturer-dropdown>
                                <ul class="py-1 text-sm text-gray-700" data-manufacturer-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2 relative">
                            <input type="text"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][style]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Style"
                                autocomplete="off"
                                data-style-input>
                            <input type="hidden"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][product_line_id]"
                                class="js-product-line-id-input" value="">
                            <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                data-style-dropdown>
                                <ul class="py-1 text-sm text-gray-700" data-style-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2 relative">
                            <input type="text"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][color_item_number]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Color / Item #"
                                autocomplete="off"
                                data-color-input>
                            <input type="hidden"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][product_style_id]"
                                class="js-product-style-id-input" value="">
                            <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg hidden"
                                data-color-dropdown>
                                <ul class="py-1 max-h-56 overflow-auto" data-color-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="0.0001"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][sell_price]"
                                class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0.00">
                        </td>
                        <td class="px-3 py-2">
                            <span class="line-total-display inline-block w-28 text-right font-medium">$0.00</span>
                            <input type="hidden"
                                name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][line_total]"
                                value="0">
                        </td>
                        <td class="px-3 py-2">
                            <button type="button" class="delete-material-row text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                </template>
            </div>

            {{-- Freight --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Freight</h3>
                    <button type="button"
                        class="add-freight-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                        + Add Freight Row
                    </button>
                </div>

                <div class="border border-gray-200 rounded-lg overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3 w-28 text-right">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="freight-tbody">
                        @foreach ($freightItems as $i => $item)
                        @php
                            $qty  = (float)($item->quantity ?? 0);
                            $sell = (float)($item->sell_price ?? 0);
                            $line = (float)($item->line_total ?? ($qty * $sell));
                        @endphp
                        <tr class="bg-white border-t">
                            <td class="px-3 py-2">
                                <div class="relative">
                                    <input type="hidden" name="rooms[{{ $roomIndex }}][freight][{{ $i }}][id]" value="{{ $item->id }}">
                                    <input type="text"
                                        name="rooms[{{ $roomIndex }}][freight][{{ $i }}][freight_description]"
                                        value="{{ old("rooms.$roomIndex.freight.$i.freight_description", $item->label ?? '') }}"
                                        class="w-80 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                        autocomplete="off"
                                        data-freight-desc-input>
                                    <div class="absolute z-50 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden"
                                        data-freight-desc-dropdown>
                                        <ul class="max-h-56 overflow-auto p-1" data-freight-desc-options></ul>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any"
                                    name="rooms[{{ $roomIndex }}][freight][{{ $i }}][quantity]"
                                    value="{{ old("rooms.$roomIndex.freight.$i.quantity", $qty) }}"
                                    class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001"
                                    name="rooms[{{ $roomIndex }}][freight][{{ $i }}][sell_price]"
                                    value="{{ old("rooms.$roomIndex.freight.$i.sell_price", number_format($sell, 4, '.', '')) }}"
                                    class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2">
                                <span class="line-total-display inline-block w-28 text-right font-medium">${{ number_format($line, 2) }}</span>
                                <input type="hidden"
                                    name="rooms[{{ $roomIndex }}][freight][{{ $i }}][line_total]"
                                    value="{{ number_format($line, 2, '.', '') }}">
                            </td>
                            <td class="px-3 py-2">
                                <button type="button" class="delete-freight-row text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Freight row template --}}
                <template class="freight-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2">
                            <div class="relative">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][freight_description]"
                                    class="w-80 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    placeholder="Freight description"
                                    autocomplete="off"
                                    data-freight-desc-input>
                                <div class="absolute z-50 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden"
                                    data-freight-desc-dropdown>
                                    <ul class="max-h-56 overflow-auto p-1" data-freight-desc-options></ul>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="any"
                                name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][quantity]"
                                class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="0.0001"
                                name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][sell_price]"
                                class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0.00">
                        </td>
                        <td class="px-3 py-2">
                            <span class="line-total-display inline-block w-28 text-right font-medium">$0.00</span>
                            <input type="hidden"
                                name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][line_total]"
                                value="0">
                        </td>
                        <td class="px-3 py-2">
                            <button type="button" class="delete-freight-row text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                </template>
            </div>

            {{-- Labour --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Labour</h3>
                    <button type="button"
                        class="add-labour-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                        + Add Labour Row
                    </button>
                </div>

                <div class="border border-gray-200 rounded-lg overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Labour Type</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3 w-28 text-right">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="labour-tbody">
                        @foreach ($labourItems as $i => $item)
                        @php
                            $qty  = (float)($item->quantity ?? 0);
                            $sell = (float)($item->sell_price ?? 0);
                            $line = (float)($item->line_total ?? ($qty * $sell));
                        @endphp
                        <tr class="bg-white border-t">
                            <td class="px-3 py-2 overflow-visible">
                                <div class="relative">
                                    <input type="hidden" name="rooms[{{ $roomIndex }}][labour][{{ $i }}][id]" value="{{ $item->id }}">
                                    <input type="text"
                                        name="rooms[{{ $roomIndex }}][labour][{{ $i }}][labour_type]"
                                        value="{{ old("rooms.$roomIndex.labour.$i.labour_type", '') }}"
                                        class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                        autocomplete="off"
                                        placeholder="Labour Type"
                                        data-labour-type-input>
                                    <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                        data-labour-type-dropdown>
                                        <ul class="py-1 text-sm text-gray-700" data-labour-type-options></ul>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="any"
                                    name="rooms[{{ $roomIndex }}][labour][{{ $i }}][quantity]"
                                    value="{{ old("rooms.$roomIndex.labour.$i.quantity", $qty) }}"
                                    class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][labour][{{ $i }}][unit]"
                                    value="{{ old("rooms.$roomIndex.labour.$i.unit", $item->unit ?? '') }}"
                                    class="w-14 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    data-labour-unit-input>
                            </td>
                            <td class="px-3 py-2 overflow-visible">
                                <div class="relative">
                                    <input type="text"
                                        name="rooms[{{ $roomIndex }}][labour][{{ $i }}][description]"
                                        value="{{ old("rooms.$roomIndex.labour.$i.description", $item->label ?? '') }}"
                                        class="w-64 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                        autocomplete="off"
                                        data-labour-desc-input>
                                    <div class="hidden absolute left-0 top-full z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                        data-labour-desc-dropdown>
                                        <ul class="py-1 text-sm text-gray-700" data-labour-desc-options></ul>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001"
                                    name="rooms[{{ $roomIndex }}][labour][{{ $i }}][sell_price]"
                                    value="{{ old("rooms.$roomIndex.labour.$i.sell_price", number_format($sell, 4, '.', '')) }}"
                                    class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                            </td>
                            <td class="px-3 py-2">
                                <span class="line-total-display inline-block w-28 text-right font-medium">${{ number_format($line, 2) }}</span>
                                <input type="hidden"
                                    name="rooms[{{ $roomIndex }}][labour][{{ $i }}][line_total]"
                                    value="{{ number_format($line, 2, '.', '') }}">
                            </td>
                            <td class="px-3 py-2">
                                <button type="button" class="delete-labour-row text-red-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Labour row template --}}
                <template class="labour-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2 overflow-visible">
                            <div class="relative">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][labour_type]"
                                    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    placeholder="Labour Type"
                                    autocomplete="off"
                                    data-labour-type-input>
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                    data-labour-type-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-labour-type-options></ul>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="any"
                                name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][quantity]"
                                class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text"
                                name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][unit]"
                                class="w-14 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="SF"
                                data-labour-unit-input>
                        </td>
                        <td class="px-3 py-2 overflow-visible">
                            <div class="relative">
                                <input type="text"
                                    name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][description]"
                                    class="w-64 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                    placeholder="Description"
                                    autocomplete="off"
                                    data-labour-desc-input>
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
                                    data-labour-desc-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-labour-desc-options></ul>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="0.0001"
                                name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][sell_price]"
                                class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0.00">
                        </td>
                        <td class="px-3 py-2">
                            <span class="line-total-display inline-block w-28 text-right font-medium">$0.00</span>
                            <input type="hidden"
                                name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][line_total]"
                                value="0">
                        </td>
                        <td class="px-3 py-2">
                            <button type="button" class="delete-labour-row text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                </template>
            </div>

            {{-- Room summary --}}
            <div class="border-t pt-4 mt-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-material-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Material Total</p>
                        <p class="room-material-value text-lg font-semibold text-gray-900">${{ number_format((float)$room->items->where('item_type','material')->sum('line_total'), 2) }}</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-freight-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Freight Total</p>
                        <p class="room-freight-value text-lg font-semibold text-gray-900">${{ number_format((float)$room->items->where('item_type','freight')->sum('line_total'), 2) }}</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-labour-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Labour Total</p>
                        <p class="room-labour-value text-lg font-semibold text-gray-900">${{ number_format((float)$room->items->where('item_type','labour')->sum('line_total'), 2) }}</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <p class="room-total-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Total</p>
                        <p class="room-total-value text-lg font-bold text-gray-900">${{ number_format((float)$room->items->sum('line_total'), 2) }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endforeach

    </div>{{-- end #rooms-container --}}

    {{-- Add Room button --}}
    <div class="mt-4">
        <button type="button" id="add-room-btn"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
            + Add Room
        </button>
    </div>

    {{-- Invoice totals --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mt-6">
        <div class="flex flex-col items-end gap-2 text-sm max-w-xs ml-auto">
            <div class="flex justify-between w-full text-gray-600">
                <span>Subtotal</span>
                <span class="invoice-subtotal-value font-medium">${{ number_format((float)$invoice->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between w-full text-gray-600">
                <span>Tax ({{ number_format((float)($sale->tax_rate_percent ?? 0), 2) }}%)</span>
                <span class="invoice-tax-value font-medium">${{ number_format((float)$invoice->tax_amount, 2) }}</span>
            </div>
            <div class="flex justify-between w-full text-base font-bold text-gray-900 border-t pt-2 mt-1">
                <span>Total</span>
                <span class="invoice-grand-total-value">${{ number_format((float)$invoice->grand_total, 2) }}</span>
            </div>
            @if((float)$invoice->amount_paid > 0)
            <div class="flex justify-between w-full text-sm text-green-700 border-t border-dashed border-gray-200 pt-2 mt-1">
                <span>Amount Paid</span>
                <span class="font-semibold">−${{ number_format((float)$invoice->amount_paid, 2) }}</span>
            </div>
            <div class="flex justify-between w-full text-sm font-bold border-t border-gray-200 pt-1 {{ $invoice->balance_due > 0 ? 'text-red-600' : 'text-green-700' }}">
                <span>Balance Due</span>
                <span>${{ number_format(max(0, (float)$invoice->balance_due), 2) }}</span>
            </div>
            @endif
        </div>

        <input type="hidden" id="subtotal_input" name="subtotal" value="{{ number_format((float)$invoice->subtotal, 2, '.', '') }}">
        <input type="hidden" id="tax_amount_input" name="tax_amount" value="{{ number_format((float)$invoice->tax_amount, 2, '.', '') }}">
        <input type="hidden" id="grand_total_input" name="grand_total" value="{{ number_format((float)$invoice->grand_total, 2, '.', '') }}">
    </div>

    {{-- Actions --}}
    <div class="flex gap-3 mt-4">
        <button type="submit"
            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            Save Invoice
        </button>
        <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
        </a>
    </div>

    </form>

    {{-- Room template for new rooms --}}
    <template id="room-template">
    <div class="room-card w-full bg-white border border-gray-200 rounded-lg shadow-sm overflow-visible" data-room-index="__ROOM_INDEX__">

        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div class="flex items-center gap-3">
                <h2 class="room-title text-lg font-semibold text-gray-900">Room</h2>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="move-up hidden inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">↑</button>
                <button type="button" class="move-down hidden inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">↓</button>
                <button type="button" class="delete-room inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete Room</button>
            </div>
        </div>

        <div class="p-6 space-y-8">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Room Name</label>
                <input type="hidden" class="room-delete-flag" name="rooms[__ROOM_INDEX__][_delete]" value="0">
                <input type="hidden" class="room-subtotal-materials-input" name="rooms[__ROOM_INDEX__][subtotal_materials]" value="0.00">
                <input type="hidden" class="room-subtotal-freight-input"   name="rooms[__ROOM_INDEX__][subtotal_freight]"   value="0.00">
                <input type="hidden" class="room-subtotal-labour-input"    name="rooms[__ROOM_INDEX__][subtotal_labour]"    value="0.00">
                <input type="hidden" class="room-total-input"              name="rooms[__ROOM_INDEX__][room_total]"         value="0.00">
                <input type="text" name="rooms[__ROOM_INDEX__][room_name]"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    placeholder="e.g. Living Room">
            </div>

            {{-- Materials --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Materials</h3>
                    <button type="button" class="add-material-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">+ Add Material Row</button>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Product Type</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Manufacturer</th>
                                <th class="px-3 py-3">Style</th>
                                <th class="px-3 py-3">Color / Item #</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3 w-28 text-right">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="materials-tbody"></tbody>
                    </table>
                </div>
                <template class="material-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2 relative">
                            <input type="text" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][product_type]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Product Type" autocomplete="off" data-product-type-input>
                            <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto" data-product-type-dropdown>
                                <ul class="py-1 text-sm text-gray-700" data-product-type-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input type="number" step="any" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][quantity]" class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="0"></td>
                        <td class="px-3 py-2"><input type="text" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][unit]" class="w-14 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="SF"></td>
                        <td class="px-3 py-2 relative">
                            <input type="text" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][manufacturer]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Manufacturer" autocomplete="off" data-manufacturer-input>
                            <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto" data-manufacturer-dropdown>
                                <ul class="py-1 text-sm text-gray-700" data-manufacturer-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2 relative">
                            <input type="text" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][style]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Style" autocomplete="off" data-style-input>
                            <input type="hidden" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][product_line_id]" class="js-product-line-id-input" value="">
                            <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto" data-style-dropdown>
                                <ul class="py-1 text-sm text-gray-700" data-style-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2 relative">
                            <input type="text" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][color_item_number]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Color / Item #" autocomplete="off" data-color-input>
                            <input type="hidden" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][product_style_id]" class="js-product-style-id-input" value="">
                            <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg hidden" data-color-dropdown>
                                <ul class="py-1 max-h-56 overflow-auto" data-color-options></ul>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input type="number" step="0.0001" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][sell_price]" class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="0.00"></td>
                        <td class="px-3 py-2">
                            <span class="line-total-display inline-block w-28 text-right font-medium">$0.00</span>
                            <input type="hidden" name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][line_total]" value="0">
                        </td>
                        <td class="px-3 py-2"><button type="button" class="delete-material-row text-red-600 hover:underline">Delete</button></td>
                    </tr>
                </template>
            </div>

            {{-- Freight --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Freight</h3>
                    <button type="button" class="add-freight-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">+ Add Freight Row</button>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3 w-28 text-right">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="freight-tbody"></tbody>
                    </table>
                </div>
                <template class="freight-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2">
                            <div class="relative">
                                <input type="text" name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][freight_description]"
                                    class="w-80 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Freight description" autocomplete="off" data-freight-desc-input>
                                <div class="absolute z-50 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden" data-freight-desc-dropdown>
                                    <ul class="max-h-56 overflow-auto p-1" data-freight-desc-options></ul>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input type="number" step="any" name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][quantity]" class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="0"></td>
                        <td class="px-3 py-2"><input type="number" step="0.0001" name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][sell_price]" class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="0.00"></td>
                        <td class="px-3 py-2">
                            <span class="line-total-display inline-block w-28 text-right font-medium">$0.00</span>
                            <input type="hidden" name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][line_total]" value="0">
                        </td>
                        <td class="px-3 py-2"><button type="button" class="delete-freight-row text-red-600 hover:underline">Delete</button></td>
                    </tr>
                </template>
            </div>

            {{-- Labour --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-900">Labour</h3>
                    <button type="button" class="add-labour-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">+ Add Labour Row</button>
                </div>
                <div class="border border-gray-200 rounded-lg overflow-x-auto overflow-y-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Labour Type</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3 w-28 text-right">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="labour-tbody"></tbody>
                    </table>
                </div>
                <template class="labour-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2 overflow-visible">
                            <div class="relative">
                                <input type="text" name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][labour_type]"
                                    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Labour Type" autocomplete="off" data-labour-type-input>
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto" data-labour-type-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-labour-type-options></ul>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input type="number" step="any" name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][quantity]" class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="0"></td>
                        <td class="px-3 py-2"><input type="text" name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][unit]" class="w-14 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="SF" data-labour-unit-input></td>
                        <td class="px-3 py-2 overflow-visible">
                            <div class="relative">
                                <input type="text" name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][description]"
                                    class="w-64 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="Description" autocomplete="off" data-labour-desc-input>
                                <div class="hidden absolute left-0 top-full z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto" data-labour-desc-dropdown>
                                    <ul class="py-1 text-sm text-gray-700" data-labour-desc-options></ul>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-2"><input type="number" step="0.0001" name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][sell_price]" class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2" placeholder="0.00"></td>
                        <td class="px-3 py-2">
                            <span class="line-total-display inline-block w-28 text-right font-medium">$0.00</span>
                            <input type="hidden" name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][line_total]" value="0">
                        </td>
                        <td class="px-3 py-2"><button type="button" class="delete-labour-row text-red-600 hover:underline">Delete</button></td>
                    </tr>
                </template>
            </div>

            {{-- Room summary --}}
            <div class="border-t pt-4 mt-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-material-label text-xs text-gray-500">Room Material Total</p>
                        <p class="room-material-value text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-freight-label text-xs text-gray-500">Room Freight Total</p>
                        <p class="room-freight-value text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-labour-label text-xs text-gray-500">Room Labour Total</p>
                        <p class="room-labour-value text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <p class="room-total-label text-xs text-gray-500">Room Total</p>
                        <p class="room-total-value text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
    </template>

</div>
</div>

<script>
  // FM Catalog API URLs
  window.FM_CATALOG_PRODUCT_TYPES_URL  = "{{ route('pages.estimates.api.product-types') }}";
  window.FM_CATALOG_MANUFACTURERS_URL  = "{{ route('pages.estimates.api.manufacturers') }}";
  window.FM_CATALOG_FREIGHT_ITEMS_URL  = "{{ route('pages.estimates.api.freight-items') }}";
  window.FM_CATALOG_LABOUR_TYPES_URL   = "{{ route('pages.estimates.api.labour-types') }}";
  window.FM_INVOICE_TAX_RATE           = {{ (float)($sale->tax_rate_percent ?? 0) }};

  // Payment terms: auto-calculate due date
  document.addEventListener('DOMContentLoaded', function () {
    const ptSelect = document.getElementById('payment_term_id');
    const dueDateInput = document.getElementById('due_date');
    if (ptSelect && dueDateInput) {
      ptSelect.addEventListener('change', function () {
        const opt  = this.options[this.selectedIndex];
        const dom  = parseInt(opt.dataset.dom);
        const days = parseInt(opt.dataset.days);
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
    }
  });
</script>

<script src="{{ asset('assets/js/invoices/invoice_edit.js') }}" defer></script>

</x-admin-layout>
