<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Page Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Edit Estimate</h1>
    <a href="{{ url('/admin/estimates/mock-create') }}" class="text-sm text-blue-700 underline">
        ← Back to Create (Mock)
    </a>
    <p class="text-sm text-gray-600 mt-2">
        Estimate #
        <span class="font-semibold">
            {{ $estimate->estimate_number ?? 'Draft' }}
        </span>
    </p>
</div>

{{-- Flash Message --}}
@if (session('success'))
    <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between">
        <div>{{ session('success') }}</div>
        @if (session('estimate_id'))
            <a href="{{ route('admin.estimates.edit', session('estimate_id')) }}"
               class="text-sm font-medium text-green-900 underline">
                Open Estimate
            </a>
        @endif
    </div>
@endif

{{-- Validation Errors --}}
@if ($errors->any())
			
    <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
        <p class="font-semibold mb-2">Please fix the following:</p>
        <ul class="list-disc list-inside text-sm space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
            {{-- Edit form --}}
            <form method="POST" action="{{ route('admin.estimates.update', $estimate->id) }}">
                @csrf
                @method('PUT')
                {{-- Header Card --}}
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">
                        Customer & Job Information
                    </h2>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Left --}}
                        <div class="space-y-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Parent Customer</label>
                                <input type="text"
                                       name="parent_customer_name"
                                       value="{{ old('parent_customer_name', $estimate->customer_name ?? '') }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       placeholder="Restoration Company Name">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Project Manager (PM)</label>
                                <input type="text"
                                       name="pm_name"
                                       value="{{ old('pm_name', $estimate->pm_name ?? '') }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       placeholder="PM Name">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Notes</label>
                                <textarea name="notes" rows="4"
                                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                          placeholder="Internal notes...">{{ old('notes', $estimate->notes ?? '') }}</textarea>
                            </div>
                        </div>
                        {{-- Right --}}
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Job Number</label>
                                    <input type="text"
                                           name="job_number"
                                           value="{{ old('job_number', $estimate->job_no ?? '') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                           placeholder="e.g. 12345">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Job Name</label>
                                    <input type="text"
                                           name="job_name"
                                           value="{{ old('job_name', $estimate->job_name ?? '') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                           placeholder="e.g. Smith - Water Damage Repair">
                                </div>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Estimate Number</label>
                                <input type="text"
                                       name="estimate_number"
                                       value="{{ $estimate->estimate_number ?? '' }}"
                                       readonly
                                       class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                <p class="mt-1 text-xs text-gray-500">Auto-generated. (Read-only for now)</p>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                                <select name="status"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    @php
                                        $statuses = ['draft', 'sent', 'approved', 'rejected'];
                                        $currentStatus = old('status', $estimate->status ?? 'draft');
                                    @endphp
                                    @foreach ($statuses as $s)
                                        <option value="{{ $s }}" @selected($currentStatus === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Job Address</label>
                                <textarea name="job_address" rows="4"
                                          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                          placeholder="Full job address...">{{ old('job_address', $estimate->job_address ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
				</div>
			
			
 {{-- end max-w-7xl container --}}
<div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-16">
    <div id="rooms-container" class="mt-6 space-y-6">
        {{-- existing room cards --}}
 

			{{-- Rooms --}}
                <div id="rooms-container" class="mt-6 space-y-6">
                    @forelse ($estimate->rooms as $roomIndex => $room)
                        @php
                            $materials = $room->items->where('item_type', 'material')->values();
                            $freight = $room->items->where('item_type', 'freight')->values();
                            $labour = $room->items->where('item_type', 'labour')->values();
                            $room_materials = (float) $materials->sum(fn($i) => (float)($i->line_total ?? 0));
                            $room_freight = (float) $freight->sum(fn($i) => (float)($i->line_total ?? 0));
                            $room_labour = (float) $labour->sum(fn($i) => (float)($i->line_total ?? 0));
                            $room_total_calc = $room_materials + $room_freight + $room_labour;
                        @endphp
                        <div class="room-card bg-white border border-gray-200 rounded-lg shadow-sm p-6"
                             data-room-index="{{ $roomIndex }}">
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="flex-1">
    <input type="hidden" name="rooms[{{ $roomIndex }}][id]" value="{{ $room->getKey() }}">
    <input type="hidden" class="room-delete-flag" name="rooms[{{ $roomIndex }}][_delete]" value="0">
    <label class="block text-sm font-medium text-gray-700 mb-1">
        <span class="room-title">Room {{ $roomIndex + 1 }}</span>
    </label>
    <input type="text"
           name="rooms[{{ $roomIndex }}][room_name]"
           value="{{ old("rooms.$roomIndex.room_name", $room->room_name ?? '') }}"
           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
           placeholder="Room name (e.g. Living Room)">
    <input type="hidden" class="room-subtotal-materials-input" name="rooms[{{ $roomIndex }}][subtotal_materials]" value="{{ number_format($room_materials, 2, '.', '') }}">
    <input type="hidden" class="room-subtotal-freight-input" name="rooms[{{ $roomIndex }}][subtotal_freight]" value="{{ number_format($room_freight, 2, '.', '') }}">
    <input type="hidden" class="room-subtotal-labour-input" name="rooms[{{ $roomIndex }}][subtotal_labour]" value="{{ number_format($room_labour, 2, '.', '') }}">
    <input type="hidden" class="room-total-input" name="rooms[{{ $roomIndex }}][room_total]" value="{{ number_format($room_total_calc, 2, '.', '') }}">
</div>
								
                                <div class="flex flex-col items-end gap-3 pt-1">
    <div class="text-sm text-gray-600 whitespace-nowrap">
        <div class="flex items-center gap-2">
                <button type="button"
                    class="move-up hidden inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    ↑
                </button>

                <button type="button"
                    class="move-down hidden inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    ↓
                </button>

                <button type="button"
                    class="delete-room inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
                    Delete Room
                </button>
            </div>
        
    </div>

   
</div>
</div>
                            {{-- MATERIALS --}}
                            <div class="mt-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900">Materials</h4>
                                    <button type="button" class="add-material-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                        + Add Material Row
                                    </button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-gray-700 border-b">
                                            <th class="px-3 py-3">Product Type</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Manufacturer</th>
                                <th class="px-3 py-3">Style</th>
                                <th class="px-3 py-3">Color / Item #</th>
                                <th class="px-3 py-3">PO Notes</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3">Total</th>
                                <th class="px-3 py-3">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="materials-tbody">
                                            @foreach ($materials as $i => $item)
                                                <tr class="border-b">
                                                    <td class="py-2 pr-4">
                                                        <input type="text"
                                                               name="rooms[{{ $roomIndex }}][materials][{{ $i }}][product_type]"
                                                               value="{{ old("rooms.$roomIndex.materials.$i.product_type", $item->product_type ?? '') }}"
                                                               class="material-type-input w-40 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                               placeholder="Product Type">
                                                    </td>
                                                    <td class="px-3 py-2">
                            <input type="number" step="0.01" name="rooms[0][materials][0][quantity]"
                                class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0">
                        </td>
                                                    <td class="px-3 py-2">
                            <input type="text" name="rooms[0][materials][0][unit]"
                                class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Unit">
                        </td>
                                                    <td class="px-3 py-2">
                            <input type="text" name="rooms[0][materials][0][manufacturer]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Manufacturer">
                        </td>
                                                    <td class="px-3 py-2">
                            <input type="text" name="rooms[0][materials][0][style]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Style">
                        </td>
                                                    <td class="px-3 py-2">
                            <input type="text" name="rooms[0][materials][0][color_item_number]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Color / Item #">
                        </td>
                                                    <td class="px-3 py-2">
                            <input type="text" name="rooms[0][materials][0][po_notes]"
                                class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="PO Notes">
                        </td>
													<td class="px-3 py-2">
                            <input type="number" step="0.01" name="rooms[0][materials][0][sell_price]"
                                class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0.00">
                        </td>
													 <td class="px-3 py-2">
    <span class="material-line-total inline-block w-28 text-right font-medium">$0.00</span>
    <input type="hidden" name="rooms[0][materials][0][line_total]" class="material-line-total-input" value="0">
</td>
                        <td class="px-3 py-2">
                            <button type="button" class="delete-material-row text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <template class="material-row-template">
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][product_type]"
                                                   class="material-type-input w-40 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   placeholder="Product Type" value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][notes]"
                                                   class="material-notes-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01"
                                                   name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][quantity]"
                                                   class="material-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][unit]"
                                                   class="material-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01"
                                                   name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][sell_price]"
                                                   class="material-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="material-line-total-text font-medium">$0.00</span>
                                            <input type="hidden"
                                                   class="material-line-total-input"
                                                   name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][line_total]"
                                                   value="0.00">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <button type="button" class="delete-material-row text-sm text-red-700 underline">Delete</button>
                                        </td>
                                    </tr>
                                </template>
                            </div>
                            {{-- FREIGHT --}}
                            <div class="mt-8">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900">Freight</h4>
                                    <button type="button"
                                            class="add-freight-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                        + Add Freight Row
                                    </button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-gray-700 border-b">
                                            <th class="py-2 pr-4">Description</th>
                                            <th class="py-2 pr-4">Qty</th>
                                            <th class="py-2 pr-4">Unit</th>
                                            <th class="py-2 pr-4">Sell Price</th>
                                            <th class="py-2 pr-4">Line Total</th>
                                            <th class="py-2 pr-4">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="freight-tbody">
                                            @foreach ($freight as $i => $item)
                                                <tr class="border-b freight-row">
                                                    <td class="py-2 pr-4">
                                                        <input type="hidden"
                                                               name="rooms[{{ $roomIndex }}][freight][{{ $i }}][id]"
                                                               value="{{ $item->getKey() }}">
                                                        <input type="text"
                                                               name="rooms[{{ $roomIndex }}][freight][{{ $i }}][freight_description]"
                                                               value="{{ old("rooms.$roomIndex.freight.$i.freight_description", $item->freight_description ?? '') }}"
                                                               class="freight-desc-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="number" step="0.01"
                                                               name="rooms[{{ $roomIndex }}][freight][{{ $i }}][quantity]"
                                                               value="{{ old("rooms.$roomIndex.freight.$i.quantity", $item->quantity) }}"
                                                               class="freight-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="text"
                                                               name="rooms[{{ $roomIndex }}][freight][{{ $i }}][unit]"
                                                               value="{{ old("rooms.$roomIndex.freight.$i.unit", $item->unit) }}"
                                                               class="freight-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="number" step="0.01"
                                                               name="rooms[{{ $roomIndex }}][freight][{{ $i }}][sell_price]"
                                                               value="{{ old("rooms.$roomIndex.freight.$i.sell_price", $item->sell_price) }}"
                                                               class="freight-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <span class="freight-line-total-text font-medium">
                                                            ${{ number_format((float)($item->line_total ?? 0), 2) }}
                                                        </span>
                                                        <input type="hidden"
                                                               class="freight-line-total-input"
                                                               name="rooms[{{ $roomIndex }}][freight][{{ $i }}][line_total]"
                                                               value="{{ number_format((float)($item->line_total ?? 0), 2, '.', '') }}">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <button type="button" class="delete-freight-row text-sm text-red-700 underline">Delete</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <template class="freight-row-template">
                                    <tr class="border-b freight-row">
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][freight_description]"
                                                   class="freight-desc-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01"
                                                   name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][quantity]"
                                                   class="freight-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][unit]"
                                                   class="freight-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01"
                                                   name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][sell_price]"
                                                   class="freight-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="freight-line-total-text font-medium">$0.00</span>
                                            <input type="hidden"
                                                   class="freight-line-total-input"
                                                   name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][line_total]"
                                                   value="0.00">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <button type="button" class="delete-freight-row text-sm text-red-700 underline">Delete</button>
                                        </td>
                                    </tr>
                                </template>
                            </div>
                            {{-- LABOUR --}}
                            <div class="mt-8">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900">Labour</h4>
                                    <button type="button"
                                            class="add-labour-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                        + Add Labour Row
                                    </button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                        <tr class="text-left text-gray-700 border-b">
                                            <th class="py-2 pr-4">Labour Type</th>
                                            <th class="py-2 pr-4">Description</th>
                                            <th class="py-2 pr-4">Qty</th>
                                            <th class="py-2 pr-4">Unit</th>
                                            <th class="py-2 pr-4">Sell Price</th>
                                            <th class="py-2 pr-4">Line Total</th>
                                            <th class="py-2 pr-4">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="labour-tbody">
                                            @foreach ($labour as $i => $item)
                                                <tr class="border-b">
                                                    <td class="py-2 pr-4">
                                                        <input type="text"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][labour_type]"
                                                               value="{{ old("rooms.$roomIndex.labour.$i.labour_type", $item->labour_type ?? '') }}"
                                                               class="labour-type-input w-40 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                               placeholder="Labour Type">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="hidden"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][id]"
                                                               value="{{ $item->getKey() }}">
                                                        <input type="text"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][description]"
                                                               value="{{ old("rooms.$roomIndex.labour.$i.description", $item->description ?? '') }}"
                                                               class="labour-desc-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="number" step="0.01"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][quantity]"
                                                               value="{{ old("rooms.$roomIndex.labour.$i.quantity", $item->quantity) }}"
                                                               class="labour-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="text"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][unit]"
                                                               value="{{ old("rooms.$roomIndex.labour.$i.unit", $item->unit) }}"
                                                               class="labour-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <input type="number" step="0.01"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][sell_price]"
                                                               value="{{ old("rooms.$roomIndex.labour.$i.sell_price", $item->sell_price) }}"
                                                               class="labour-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <span class="labour-line-total-text font-medium">
                                                            ${{ number_format((float)($item->line_total ?? 0), 2) }}
                                                        </span>
                                                        <input type="hidden"
                                                               class="labour-line-total-input"
                                                               name="rooms[{{ $roomIndex }}][labour][{{ $i }}][line_total]"
                                                               value="{{ number_format((float)($item->line_total ?? 0), 2, '.', '') }}">
                                                    </td>
                                                    <td class="py-2 pr-4">
                                                        <button type="button" class="delete-labour-row text-sm text-red-700 underline">Delete</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <template class="labour-row-template">
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][labour_type]"
                                                   class="labour-type-input w-40 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   placeholder="Labour Type" value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][description]"
                                                   class="labour-desc-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01"
                                                   name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][quantity]"
                                                   class="labour-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="text"
                                                   name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][unit]"
                                                   class="labour-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <input type="number" step="0.01"
                                                   name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][sell_price]"
                                                   class="labour-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                                   value="">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <span class="labour-line-total-text font-medium">$0.00</span>
                                            <input type="hidden"
                                                   class="labour-line-total-input"
                                                   name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][line_total]"
                                                   value="0.00">
                                        </td>
                                        <td class="py-2 pr-4">
                                            <button type="button" class="delete-labour-row text-sm text-red-700 underline">Delete</button>
                                        </td>
                                    </tr>
                                </template>
                            </div>
							 {{-- Room Summary --}}
<div class="border-t pt-4 mt-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="room-material-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Material Total</p>
            <p class="room-material-value text-lg font-semibold text-gray-900">${{ number_format($room_materials, 2) }}</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="room-freight-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Freight Total</p>
            <p class="room-freight-value text-lg font-semibold text-gray-900">${{ number_format($room_freight, 2) }}</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="room-labour-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Labour Total</p>
            <p class="room-labour-value text-lg font-semibold text-gray-900">${{ number_format($room_labour, 2) }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="room-total-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Total</p>
            <p class="room-total-value text-lg font-bold text-gray-900">${{ number_format($room_total_calc, 2) }}</p>
        </div>
    </div>
</div>

                        </div>
                    @empty
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 text-sm text-gray-600">
        No rooms yet.
    </div>
@endforelse

                </div>
                {{-- Add Room --}}
                <div class="flex justify-start mt-6">
                    <button id="add-room-btn" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                        + Add Room
                    </button>
                </div>
                {{-- Template used by estimate_mock.js --}}
                <template id="room-template">
                    <div class="room-card bg-white border border-gray-200 rounded-lg shadow-sm p-6" data-room-index="__ROOM_INDEX__">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <span class="room-title">Room</span>
                                </label>
                                <input type="text" name="rooms[__ROOM_INDEX__][room_name]" value="" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Room name (e.g. Living Room)">
                                <input type="hidden" class="room-delete-flag" name="rooms[__ROOM_INDEX__][_delete]" value="0">
                                <input type="hidden" class="room-subtotal-materials-input" name="rooms[__ROOM_INDEX__][subtotal_materials]" value="0.00">
                                <input type="hidden" class="room-subtotal-freight-input" name="rooms[__ROOM_INDEX__][subtotal_freight]" value="0.00">
                                <input type="hidden" class="room-subtotal-labour-input" name="rooms[__ROOM_INDEX__][subtotal_labour]" value="0.00">
                                <input type="hidden" class="room-total-input" name="rooms[__ROOM_INDEX__][room_total]" value="0.00">
                            </div>
                            <div class="flex flex-col items-end gap-3 pt-1">
                                <div class="text-sm text-gray-600 whitespace-nowrap">
                                    Total:
                                    <span class="room-total-value font-semibold">$0.00</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="move-up inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">↑</button>
                                    <button type="button" class="move-down inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">↓</button>
                                    <button type="button" class="delete-room inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete Room</button>
                                </div>
                            </div>
                        </div>
                        {{-- MATERIALS --}}
                        <div class="mt-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-gray-900">Materials</h4>
                                <button type="button" class="add-material-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                    + Add Material Row
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                    <tr class="text-left text-gray-700 border-b">
                                        <th class="py-2 pr-4">Product Type</th>
                                        <th class="py-2 pr-4">Notes</th>
                                        <th class="py-2 pr-4">Qty</th>
                                        <th class="py-2 pr-4">Unit</th>
                                        <th class="py-2 pr-4">Sell Price</th>
                                        <th class="py-2 pr-4">Line Total</th>
                                        <th class="py-2 pr-4">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody class="materials-tbody"></tbody>
                                </table>
                            </div>
                            <template class="material-row-template">
  <tr class="border-b">
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][product_type]"
             class="material-type-input w-40 bg-gray-50 border border-gray-300 rounded-lg p-2"
             placeholder="Product Type" value="">
    </td>
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][notes]"
             class="material-notes-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="number" step="0.01"
             name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][quantity]"
             class="material-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][unit]"
             class="material-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="number" step="0.01"
             name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][sell_price]"
             class="material-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <span class="material-line-total-text font-medium">$0.00</span>
      <input type="hidden"
             class="material-line-total-input"
             name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][line_total]"
             value="0.00">
    </td>
    <td class="py-2 pr-4">
      <button type="button" class="delete-material-row text-sm text-red-700 underline">Delete</button>
    </td>
  </tr>
</template>
                        </div>
                        {{-- FREIGHT --}}
                        <div class="mt-8">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-gray-900">Freight</h4>
                                <button type="button"
                                        class="add-freight-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                    + Add Freight Row
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                    <tr class="text-left text-gray-700 border-b">
                                        <th class="py-2 pr-4">Description</th>
                                        <th class="py-2 pr-4">Qty</th>
                                        <th class="py-2 pr-4">Unit</th>
                                        <th class="py-2 pr-4">Sell Price</th>
                                        <th class="py-2 pr-4">Line Total</th>
                                        <th class="py-2 pr-4">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody class="freight-tbody"></tbody>
                                </table>
                            </div>
                            <template class="freight-row-template">
  <tr class="border-b freight-row">
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][freight_description]"
             class="freight-desc-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="number" step="0.01"
             name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][quantity]"
             class="freight-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][unit]"
             class="freight-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="number" step="0.01"
             name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][sell_price]"
             class="freight-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <span class="freight-line-total-text font-medium">$0.00</span>
      <input type="hidden"
             class="freight-line-total-input"
             name="rooms[__ROOM_INDEX__][freight][__ITEM_INDEX__][line_total]"
             value="0.00">
    </td>
    <td class="py-2 pr-4">
      <button type="button" class="delete-freight-row text-sm text-red-700 underline">Delete</button>
    </td>
  </tr>
</template>
                        </div>
                        {{-- LABOUR --}}
                        <div class="mt-8">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-gray-900">Labour</h4>
                                <button type="button"
                                        class="add-labour-row inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                    + Add Labour Row
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                    <tr class="text-left text-gray-700 border-b">
                                        <th class="py-2 pr-4">Labour Type</th>
                                        <th class="py-2 pr-4">Description</th>
                                        <th class="py-2 pr-4">Qty</th>
                                        <th class="py-2 pr-4">Unit</th>
                                        <th class="py-2 pr-4">Sell Price</th>
                                        <th class="py-2 pr-4">Line Total</th>
                                        <th class="py-2 pr-4">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody class="labour-tbody"></tbody>
                                </table>
                            </div>
                            <template class="labour-row-template">
  <tr class="border-b">
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][labour_type]"
             class="labour-type-input w-40 bg-gray-50 border border-gray-300 rounded-lg p-2"
             placeholder="Labour Type" value="">
    </td>
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][description]"
             class="labour-desc-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="number" step="0.01"
             name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][quantity]"
             class="labour-qty-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="text"
             name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][unit]"
             class="labour-unit-input w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <input type="number" step="0.01"
             name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][sell_price]"
             class="labour-price-input w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
             value="">
    </td>
    <td class="py-2 pr-4">
      <span class="labour-line-total-text font-medium">$0.00</span>
      <input type="hidden"
             class="labour-line-total-input"
             name="rooms[__ROOM_INDEX__][labour][__ITEM_INDEX__][line_total]"
             value="0.00">
    </td>
    <td class="py-2 pr-4">
      <button type="button" class="delete-labour-row text-sm text-red-700 underline">Delete</button>
    </td>
  </tr>
</template>
                        </div>
						{{-- Room Summary --}}
<div class="border-t pt-4 mt-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="room-material-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Material Total</p>
            <p class="room-material-value text-lg font-semibold text-gray-900">${{ number_format($room_materials, 2) }}</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="room-freight-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Freight Total</p>
            <p class="room-freight-value text-lg font-semibold text-gray-900">${{ number_format($room_freight, 2) }}</p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="room-labour-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Labour Total</p>
            <p class="room-labour-value text-lg font-semibold text-gray-900">${{ number_format($room_labour, 2) }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <p class="room-total-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Total</p>
            <p class="room-total-value text-lg font-bold text-gray-900">${{ number_format($room_total_calc, 2) }}</p>
        </div>
    </div>
</div>
                    </div>
					   </div>
</div>
                </template>
                {{-- Estimate Summary --}}
                @php
                    $subtotal_materials = 0.0;
                    $subtotal_labour = 0.0;
                    $subtotal_freight = 0.0;
                    foreach ($estimate->rooms as $r) {
                        foreach ($r->items as $it) {
                            $lt = (float) ($it->line_total ?? 0);
                            if ($it->item_type === 'material') $subtotal_materials += $lt;
                            elseif ($it->item_type === 'labour') $subtotal_labour += $lt;
                            elseif ($it->item_type === 'freight') $subtotal_freight += $lt;
                        }
                    }
                    $pretax_total = $subtotal_materials + $subtotal_labour + $subtotal_freight;
                    $tax_amount = 0;
                    $grand_total = $pretax_total + $tax_amount;
                @endphp
                <div class="mt-10 bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Estimate Summary</h2>
                        <button type="button"
                                class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100"
                                disabled>
                            Select Tax Group
                        </button>
                    </div>
                    <div class="flex items-center justify-between border-b pb-2">
                        <span class="text-sm text-gray-700">Subtotal (Materials)</span>
                        <span class="estimate-materials-value text-sm font-semibold text-gray-900">${{ number_format($subtotal_materials, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-2">
                        <span class="text-sm text-gray-700">Subtotal (Labour)</span>
                        <span class="estimate-labour-value text-sm font-semibold text-gray-900">${{ number_format($subtotal_labour, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-2">
                        <span class="text-sm text-gray-700">Total Freight / Trip</span>
                        <span class="estimate-freight-value text-sm font-semibold text-gray-900">${{ number_format($subtotal_freight, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-2">
                        <span class="text-sm text-gray-700">Pre-tax Total</span>
                        <span class="estimate-pretax-value text-sm font-semibold text-gray-900">${{ number_format($pretax_total, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-2">
                        <span class="text-sm text-gray-700">Tax</span>
                        <span class="estimate-tax-value text-sm font-semibold text-gray-900">${{ number_format($tax_amount, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2">
                        <span class="text-base font-semibold text-gray-900">Grand Total</span>
                        <span class="estimate-grand-total-value text-base font-bold text-gray-900">${{ number_format($grand_total, 2) }}</span>
                    </div>
                    <p class="mt-4 text-xs text-gray-500">
                        Totals are calculated from line items. Tax will calculate after tax groups are wired.
                    </p>
                    <!-- Hidden inputs for estimate totals (used on save) -->
                    <input type="hidden" id="subtotal_materials_input" name="subtotal_materials" value="{{ number_format($subtotal_materials, 2, '.', '') }}">
                    <input type="hidden" id="subtotal_labour_input" name="subtotal_labour" value="{{ number_format($subtotal_labour, 2, '.', '') }}">
                    <input type="hidden" id="subtotal_freight_input" name="subtotal_freight" value="{{ number_format($subtotal_freight, 2, '.', '') }}">
                    <input type="hidden" id="pretax_total_input" name="pretax_total" value="{{ number_format($pretax_total, 2, '.', '') }}">
                    <input type="hidden" id="tax_amount_input" name="tax_amount" value="{{ number_format($tax_amount, 2, '.', '') }}">
                    <input type="hidden" id="grand_total_input" name="grand_total" value="{{ number_format($grand_total, 2, '.', '') }}">
                </div>
                {{-- Bottom Action Bar --}}
                <div class="mt-10 border-t pt-6">
                    <div class="flex items-center justify-between max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div class="text-sm text-gray-600">
                            Status:
                            <span class="font-semibold capitalize">{{ $estimate->status ?? 'draft' }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ url()->previous() }}"
                               class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Save Estimate
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script src="{{ asset('assets/js/estimates/estimate_mock.js') }}" defer></script>
</x-admin-layout>