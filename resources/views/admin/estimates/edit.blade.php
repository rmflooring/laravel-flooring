<x-admin-layout>

    <div class="py-6">
	<form method="POST" action="{{ route('admin.estimates.update', $estimate) }}">
	  @csrf
	  @method('PUT')

		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 estimate-normal-container">

            {{-- Page Header --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Estimate</h1>
                    <p class="text-sm text-gray-600">Status: <span class="font-semibold">Draft</span></p>
                </div>
@if (session('success'))
    <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg rounded-lg">
        {{ session('success') }}
    </div>
@endif

                <div class="flex items-center gap-2">
                    <button type="submit"
    class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
    Save Estimate
</button>
					<button id="toggle-wide-mode" type="button"
  class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
  Wide Mode
</button>


                </div>
            </div>

            {{-- Estimate Header Card --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Customer & Job Information</h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

{{-- Left Column --}}
<div class="space-y-4">
    <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Parent Customer</label>
        <input type="text" name="parent_customer_name"
               value="{{ old('parent_customer_name', $estimate->customer_name) }}"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
               placeholder="Restoration Company Name">
    </div>

    <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Project Manager (PM)</label>
        <input type="text" name="pm_name"
               value="{{ old('pm_name', $estimate->pm_name) }}"
               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
               placeholder="PM Name">
    </div>

{{-- Salespersons row --}}
{{-- Salespersons row --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Salesperson 1</label>
        <select name="salesperson_1_employee_id"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
            <option value="">Select</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}"
                  @selected(old('salesperson_1_employee_id', $estimate->salesperson_1_employee_id) == $emp->id)>
                    {{ $emp->first_name }} {{ $emp->last_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block mb-1 text-sm font-medium text-gray-700">Salesperson 2</label>
        <select name="salesperson_2_employee_id"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
            <option value="">Select</option>
            @foreach($employees as $emp)
                <option value="{{ $emp->id }}"
                  @selected(old('salesperson_2_employee_id', $estimate->salesperson_2_employee_id) == $emp->id)>
                    {{ $emp->first_name }} {{ $emp->last_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
	</div>

                    {{-- Right Column --}}
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700">Job Number</label>
                                <input type="text" name="job_number"
                                        value="{{ old('job_number', $estimate->job_no) }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    placeholder="e.g. 12345">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block mb-1 text-sm font-medium text-gray-700">Job Name</label>
                                <input type="text" name="job_name"
							<input type="text" name="job_name"
								value="{{ old('job_name', $estimate->job_name) }}"
								class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
								placeholder="e.g. Smith - Water Damage Repair">
                            </div>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700">Estimate Number</label>
							<input type="text" name="estimate_number" value="{{ old('estimate_number', $estimate->estimate_number) }}"
								class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
								/>

							<p class="mt-1 text-xs text-gray-500">
								Automatically assigned when the estimate is saved.
							</p>
                        </div>

                        <div class="border-t pt-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Homeowner</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Name</label>
                                    <input type="text" name="homeowner_name"
                                            value="{{ old('job_name', $estimate->job_name) }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                        placeholder="Homeowner Name">
                                </div>

                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Phone</label>
                                    <input type="text" name="homeowner_phone"
                                            value="{{ old('job_phone', $estimate->job_phone ?? '') }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                        placeholder="Phone Number">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" name="homeowner_email"
                                            value="{{ old('job_email', $estimate->job_email ?? '') }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                        placeholder="email@example.com">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block mb-1 text-sm font-medium text-gray-700">Job Address</label>
                                    <textarea name="job_address" rows="3"    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Full job address">{{ old('job_address', $estimate->job_address) }}</textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
</div> {{-- end max-w-7xl container --}}
		   {{-- Full-width Estimate Builder --}}
<div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-16">
	
{{-- Rooms --}}
	@if(app()->environment('local'))
  <div class="mb-2 text-xs text-gray-500">
    Rooms loaded: {{ $estimate->rooms?->count() ?? 0 }}
  </div>
@endif
	
<div id="rooms-container" class="mt-6 space-y-6">
  @foreach($estimate->rooms as $roomIndex => $room)
	<div class="text-xs text-red-600 mb-2">

		<div class="text-xs text-blue-600 mb-2">
  item_types:
  {{ $room->items->pluck('item_type')->unique()->implode(', ') }}
</div>
</div>
    @php
      $materials = $room->items->where('item_type', 'material')->values();
      $freight   = $room->items->where('item_type', 'freight')->values();
      $labour    = $room->items->where('item_type', 'labour')->values();
    @endphp

    <div class="room-card bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden" data-room-index="{{ $roomIndex }}">
      {{-- Room Header --}}
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <div class="flex items-center gap-3">
          <h2 class="room-title text-lg font-semibold text-gray-900">Room {{ $roomIndex + 1 }}</h2>
        </div>

        <div class="flex items-center gap-2">
          <button type="button"
            class="move-up inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            ↑
          </button>

          <button type="button"
            class="move-down inline-flex items-center justify-center w-9 h-9 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            ↓
          </button>

          <button type="button"
            class="delete-room inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
            Delete Room
          </button>
        </div>
      </div>

      <div class="p-6 space-y-8">
        {{-- Room Name --}}
        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Room Name</label>

          <input type="hidden" name="rooms[{{ $roomIndex }}][id]" value="{{ $room->id }}">
          <input type="hidden" class="room-delete-flag" name="rooms[{{ $roomIndex }}][_delete]" value="0">

          <input type="text"
            name="rooms[{{ $roomIndex }}][room_name]"
            value="{{ old("rooms.$roomIndex.room_name", $room->room_name) }}"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
            placeholder="e.g. Living Room">

          <input type="hidden" class="room-subtotal-materials-input" name="rooms[{{ $roomIndex }}][subtotal_materials]" value="{{ number_format((float)($room->subtotal_materials ?? 0), 2, '.', '') }}">
          <input type="hidden" class="room-subtotal-freight-input" name="rooms[{{ $roomIndex }}][subtotal_freight]" value="{{ number_format((float)($room->subtotal_freight ?? 0), 2, '.', '') }}">
          <input type="hidden" class="room-subtotal-labour-input" name="rooms[{{ $roomIndex }}][subtotal_labour]" value="{{ number_format((float)($room->subtotal_labour ?? 0), 2, '.', '') }}">
          <input type="hidden" class="room-total-input" name="rooms[{{ $roomIndex }}][room_total]" value="{{ number_format((float)($room->room_total ?? 0), 2, '.', '') }}">
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

  <div class="border border-gray-200 rounded-lg overflow-hidden">
  <div class="w-full overflow-x-auto">
    <table class="min-w-max text-sm text-left text-gray-700">
      <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
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
  @foreach($materials as $i => $item)
    <tr class="bg-white border-t">
      <td class="px-3 py-2 relative">
        <input type="hidden" name="rooms[{{ $roomIndex }}][materials][{{ $i }}][id]" value="{{ $item->id }}">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][product_type]"
          value="{{ old("rooms.$roomIndex.materials.$i.product_type", $item->product_type) }}"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
          autocomplete="off"
          data-product-type-input>
        <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
          data-product-type-dropdown>
          <ul class="py-1 text-sm text-gray-700" data-product-type-options></ul>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][quantity]"
          value="{{ old("rooms.$roomIndex.materials.$i.quantity", $item->quantity) }}"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][unit]"
          value="{{ old("rooms.$roomIndex.materials.$i.unit", $item->unit) }}"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2 relative">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][manufacturer]"
          value="{{ old("rooms.$roomIndex.materials.$i.manufacturer", $item->manufacturer) }}"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
          autocomplete="off"
          data-manufacturer-input>
        <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
          data-manufacturer-dropdown>
          <ul class="py-1 text-sm text-gray-700" data-manufacturer-options></ul>
        </div>
      </td>

      <td class="px-3 py-2 relative">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][style]"
          value="{{ old("rooms.$roomIndex.materials.$i.style", $item->style) }}"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
          autocomplete="off"
          data-style-input>
        <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
          data-style-dropdown>
          <ul class="py-1 text-sm text-gray-700" data-style-options></ul>
        </div>
      </td>

      <td class="px-3 py-2 relative">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][color_item_number]"
          value="{{ old("rooms.$roomIndex.materials.$i.color_item_number", $item->color_item_number) }}"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
          autocomplete="off"
          data-color-input>
        <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg hidden"
          data-color-dropdown>
          <ul class="py-1 max-h-56 overflow-auto" data-color-options></ul>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][po_notes]"
          value="{{ old("rooms.$roomIndex.materials.$i.po_notes", $item->po_notes) }}"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][sell_price]"
          value="{{ old("rooms.$roomIndex.materials.$i.sell_price", $item->sell_price) }}"
          class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <span class="material-line-total inline-block w-28 text-right font-medium">
          ${{ number_format((float)($item->line_total ?? ((float)$item->quantity * (float)$item->sell_price)), 2) }}
        </span>
        <input type="hidden"
          name="rooms[{{ $roomIndex }}][materials][{{ $i }}][line_total]"
          class="material-line-total-input"
          value="{{ number_format((float)($item->line_total ?? ((float)$item->quantity * (float)$item->sell_price)), 2, '.', '') }}">
      </td>

      <td class="px-3 py-2">
        <button type="button" class="delete-material-row text-red-600 hover:underline">Delete</button>
      </td>
    </tr>
  @endforeach
</tbody>

    </table>
  </div> 
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
          data-product-type-input />

        <div class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
          data-product-type-dropdown>
          <ul class="py-1 text-sm text-gray-700" data-product-type-options></ul>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][quantity]"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="0">
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][unit]"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="Unit">
      </td>

      <td class="px-3 py-2 relative">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][manufacturer]"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="Manufacturer"
          autocomplete="off"
          data-manufacturer-input />

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
          data-style-input />

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
          data-color-input />

        <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg hidden"
          data-color-dropdown>
          <ul class="py-1 max-h-56 overflow-auto" data-color-options></ul>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][po_notes]"
          class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="PO Notes">
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][sell_price]"
          class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="0.00">
      </td>

      <td class="px-3 py-2">
        <span class="material-line-total inline-block w-28 text-right font-medium">$0.00</span>
        <input type="hidden"
          name="rooms[{{ $roomIndex }}][materials][__ITEM_INDEX__][line_total]"
          class="material-line-total-input" value="0">
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

  <div class="border border-gray-200 rounded-lg overflow-visible">
    <table class="min-w-full text-sm text-left text-gray-700">
      <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
          <th class="px-3 py-3">Description</th>
          <th class="px-3 py-3">Qty</th>
          <th class="px-3 py-3">Sell</th>
          <th class="px-3 py-3">Total</th>
          <th class="px-3 py-3">Action</th>
        </tr>
      </thead>

      <tbody class="freight-tbody">
  @foreach($freight as $i => $item)
    @php
      $qty  = (float)($item->quantity ?? 0);
      $sell = (float)($item->sell_price ?? $item->unit_price ?? 0);
      $line = (float)($item->line_total ?? $item->total_price ?? ($qty * $sell));
    @endphp

    <tr class="bg-white border-t">
      <td class="px-3 py-2">
        <div class="relative">
          <input type="hidden" name="rooms[{{ $roomIndex }}][freight][{{ $i }}][id]" value="{{ $item->id }}">

          <input type="text"
            name="rooms[{{ $roomIndex }}][freight][{{ $i }}][freight_description]"
            value="{{ old("rooms.$roomIndex.freight.$i.freight_description", $item->freight_description ?? $item->item_description ?? '') }}"
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
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][freight][{{ $i }}][quantity]"
          value="{{ old("rooms.$roomIndex.freight.$i.quantity", $qty) }}"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][freight][{{ $i }}][sell_price]"
          value="{{ old("rooms.$roomIndex.freight.$i.sell_price", number_format($sell, 2, '.', '')) }}"
          class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <span class="freight-line-total inline-block w-28 text-right font-medium">
          ${{ number_format($line, 2) }}
        </span>
        <input type="hidden"
          name="rooms[{{ $roomIndex }}][freight][{{ $i }}][line_total]"
          class="freight-line-total-input"
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
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][quantity]"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="0">
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][sell_price]"
          class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="0.00">
      </td>

      <td class="px-3 py-2">
        <span class="freight-line-total inline-block w-28 text-right font-medium">$0.00</span>
        <input type="hidden"
          name="rooms[{{ $roomIndex }}][freight][__ITEM_INDEX__][line_total]"
          class="freight-line-total-input" value="0">
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

  <div class="border border-gray-200 rounded-lg overflow-visible">
    <table class="min-w-full text-sm text-left text-gray-700">
      <thead class="text-xs text-gray-700 uppercase bg-gray-50">
        <tr>
          <th class="px-3 py-3">Labour Type</th>
          <th class="px-3 py-3">Qty</th>
          <th class="px-3 py-3">Unit</th>
          <th class="px-3 py-3">Description</th>
          <th class="px-3 py-3">Notes</th>
          <th class="px-3 py-3">Sell</th>
          <th class="px-3 py-3">Total</th>
          <th class="px-3 py-3">Action</th>
        </tr>
      </thead>

     <tbody class="labour-tbody">
  @foreach($labour as $i => $item)
    @php
      $qty  = (float)($item->quantity ?? 0);
      $sell = (float)($item->sell_price ?? $item->unit_price ?? 0);
      $line = (float)($item->line_total ?? $item->total_price ?? ($qty * $sell));
    @endphp

    <tr class="bg-white border-t">
      <td class="px-3 py-2 overflow-visible">
        <div class="relative">
          <input type="hidden" name="rooms[{{ $roomIndex }}][labour][{{ $i }}][id]" value="{{ $item->id }}">

          <input type="text"
            name="rooms[{{ $roomIndex }}][labour][{{ $i }}][labour_type]"
            value="{{ old("rooms.$roomIndex.labour.$i.labour_type", $item->labour_type ?? $item->product_type ?? '') }}"
            class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
            autocomplete="off"
            data-labour-type-input />

          <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
            data-labour-type-dropdown>
            <ul class="py-1 text-sm text-gray-700" data-labour-type-options></ul>
          </div>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][labour][{{ $i }}][quantity]"
          value="{{ old("rooms.$roomIndex.labour.$i.quantity", $qty) }}"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][labour][{{ $i }}][unit]"
          value="{{ old("rooms.$roomIndex.labour.$i.unit", $item->unit ?? '') }}"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
          data-labour-unit-input>
      </td>

      <td class="px-3 py-2 overflow-visible">
        <div class="relative">
          <input type="text"
            name="rooms[{{ $roomIndex }}][labour][{{ $i }}][description]"
            value="{{ old("rooms.$roomIndex.labour.$i.description", $item->description ?? $item->item_description ?? '') }}"
            class="w-64 bg-gray-50 border border-gray-300 rounded-lg p-2"
            autocomplete="off"
            data-labour-desc-input />

          <div class="hidden absolute left-0 top-full z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
            data-labour-desc-dropdown>
            <ul class="py-1 text-sm text-gray-700" data-labour-desc-options></ul>
          </div>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][labour][{{ $i }}][notes]"
          value="{{ old("rooms.$roomIndex.labour.$i.notes", $item->notes ?? $item->item_notes ?? '') }}"
          class="w-56 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][labour][{{ $i }}][sell_price]"
          value="{{ old("rooms.$roomIndex.labour.$i.sell_price", number_format($sell, 2, '.', '')) }}"
          class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
      </td>

      <td class="px-3 py-2">
        <span class="labour-line-total inline-block w-28 text-right font-medium">
          ${{ number_format($line, 2) }}
        </span>
        <input type="hidden"
          name="rooms[{{ $roomIndex }}][labour][{{ $i }}][line_total]"
          class="labour-line-total-input"
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

	{{-- Room Summary --}}
<div class="border-t pt-4 mt-6">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
      <p class="room-material-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Material Total</p>
      <p class="room-material-value text-lg font-semibold text-gray-900">
        ${{ number_format((float)($room->subtotal_materials ?? 0), 2) }}
      </p>
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
      <p class="room-freight-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Freight Total</p>
      <p class="room-freight-value text-lg font-semibold text-gray-900">
        ${{ number_format((float)($room->subtotal_freight ?? 0), 2) }}
      </p>
    </div>

    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
      <p class="room-labour-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Labour Total</p>
      <p class="room-labour-value text-lg font-semibold text-gray-900">
        ${{ number_format((float)($room->subtotal_labour ?? 0), 2) }}
      </p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4">
      <p class="room-total-label text-xs text-gray-500">Room {{ $roomIndex + 1 }} Total</p>
      <p class="room-total-value text-lg font-bold text-gray-900">
        ${{ number_format((float)($room->room_total ?? 0), 2) }}
      </p>
    </div>
  </div>
</div>
	
  <template class="labour-row-template">
    <tr class="bg-white border-t">
      <td class="px-3 py-2 overflow-visible">
        <div class="relative">
          <input type="text"
            name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][labour_type]"
            class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
            placeholder="Labour Type"
            autocomplete="off"
            data-labour-type-input />

          <div class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
            data-labour-type-dropdown>
            <ul class="py-1 text-sm text-gray-700" data-labour-type-options></ul>
          </div>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][quantity]"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="0">
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][unit]"
          class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="Unit"
          data-labour-unit-input>
      </td>

      <td class="px-3 py-2 overflow-visible">
        <div class="relative">
          <input type="text"
            name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][description]"
            class="w-64 bg-gray-50 border border-gray-300 rounded-lg p-2"
            placeholder="Description"
            autocomplete="off"
            data-labour-desc-input />

          <div class="hidden absolute left-0 top-full z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
            data-labour-desc-dropdown>
            <ul class="py-1 text-sm text-gray-700" data-labour-desc-options></ul>
          </div>
        </div>
      </td>

      <td class="px-3 py-2">
        <input type="text"
          name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][notes]"
          class="w-56 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="Notes">
      </td>

      <td class="px-3 py-2">
        <input type="number" step="0.01"
          name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][sell_price]"
          class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="0.00">
      </td>

      <td class="px-3 py-2">
        <span class="labour-line-total inline-block w-28 text-right font-medium">$0.00</span>
        <input type="hidden"
          name="rooms[{{ $roomIndex }}][labour][__ITEM_INDEX__][line_total]"
          class="labour-line-total-input" value="0">
      </td>

      <td class="px-3 py-2">
        <button type="button" class="delete-labour-row text-red-600 hover:underline">Delete</button>
      </td>
    </tr>
  </template>
</div>
      </div>
    </div>
  @endforeach
</div>

<template id="room-template">
    <div class="room-card bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden" data-room-index="{{ $roomIndex }}">
        {{-- Room Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <div class="flex items-center gap-3">
                <h2 class="room-title text-lg font-semibold text-gray-900">Room</h2>
            </div>

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

        <div class="p-6 space-y-8">
            {{-- Room Name --}}
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700">Room Name</label>
<div class="text-xs text-gray-500 mb-2">
  Room ID: {{ $room->id }} —
  Items loaded: {{ $room->items->count() }}
</div>
                <input type="text" name="rooms[__ROOM_INDEX__][room_name]"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    placeholder="e.g. Living Room">
				<input type="hidden" class="room-delete-flag" name="rooms[__ROOM_INDEX__][_delete]" value="0">

				<input type="hidden" class="room-subtotal-materials-input" name="rooms[__ROOM_INDEX__][subtotal_materials]" value="0.00">
				<input type="hidden" class="room-subtotal-freight-input" name="rooms[__ROOM_INDEX__][subtotal_freight]" value="0.00">
				<input type="hidden" class="room-subtotal-labour-input" name="rooms[__ROOM_INDEX__][subtotal_labour]" value="0.00">
				<input type="hidden" class="room-total-input" name="rooms[__ROOM_INDEX__][room_total]" value="0.00">
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

                <div class="border border-gray-200 rounded-lg">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
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

                        <tbody class="materials-tbody"></tbody>
                    </table>
                </div>

                {{-- Material row template (outside the table) --}}
                <template class="material-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2 relative">
  <input
    type="text"
    name="rooms[0][materials][0][product_type]"
    class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
    placeholder="Product Type"
    autocomplete="off"
    data-product-type-input
  />

  <div
  class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
  data-product-type-dropdown
>
    <ul class="py-1 text-sm text-gray-700" data-product-type-options></ul>
  </div>
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
                        <td class="px-3 py-2 relative">
						  <input
							type="text"
							name="rooms[0][materials][0][manufacturer]"
							class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
							placeholder="Manufacturer"
							autocomplete="off"
							data-manufacturer-input
						  />

						  <div
							class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
							data-manufacturer-dropdown
						  >
							<ul class="py-1 text-sm text-gray-700" data-manufacturer-options></ul>
						  </div>
						</td>
                        <td class="px-3 py-2 relative">
						  <input
							type="text"
							name="rooms[0][materials][0][style]"
							class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
							placeholder="Style"
							autocomplete="off"
							data-style-input
						  />

						  <div
							class="hidden absolute left-0 top-full z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
							data-style-dropdown
						  >
							<ul class="py-1 text-sm text-gray-700" data-style-options></ul>
						  </div>
						</td>

                        <td class="relative">
  <input
    type="text"
    name="rooms[__ROOM_INDEX__][materials][__ITEM_INDEX__][color_item_number]"
    class="..."
    data-color-input
    autocomplete="off"
  />

  <div
    class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg hidden"
    data-color-dropdown
  >
    <ul class="py-1 max-h-56 overflow-auto" data-color-options></ul>
  </div>
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

				<div class="border border-gray-200 rounded-lg overflow-visible">
				<table class="min-w-full text-sm text-left text-gray-700">
					<table class="min-w-full text-sm text-left text-gray-700">
						<thead class="text-xs text-gray-700 uppercase bg-gray-50">
							<tr>
								<th class="px-3 py-3">Description</th>
								<th class="px-3 py-3">Qty</th>
								<th class="px-3 py-3">Sell</th>
								<th class="px-3 py-3">Total</th>
								<th class="px-3 py-3">Action</th>
							</tr>
						</thead>

						<tbody class="freight-tbody"></tbody>
					</table>
				  </div>

                {{-- Freight row template (outside the table) --}}
<template class="freight-row-template">
  <tr class="bg-white border-t">
    <td class="px-3 py-2">
      <div class="relative">
        <input
          type="text"
          name="rooms[0][freight][0][freight_description]"
          class="w-80 bg-gray-50 border border-gray-300 rounded-lg p-2"
          placeholder="Freight description"
          autocomplete="off"
          data-freight-desc-input
        >

        <div
          class="absolute z-50 mt-1 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden"
          data-freight-desc-dropdown
        >
          <ul class="max-h-56 overflow-auto p-1" data-freight-desc-options></ul>
        </div>
      </div>
    </td>

    <td class="px-3 py-2">
      <input type="number" step="0.01" name="rooms[0][freight][0][quantity]"
        class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
        placeholder="0">
    </td>

    <td class="px-3 py-2">
      <input type="number" step="0.01" name="rooms[0][freight][0][sell_price]"
        class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
        placeholder="0.00">
    </td>

    <td class="px-3 py-2">
      <span class="freight-line-total inline-block w-28 text-right font-medium">$0.00</span>
      <input type="hidden" name="rooms[0][freight][0][line_total]" class="freight-line-total-input" value="0">
    </td>

    <td class="px-3 py-2">
      <button type="button" class="delete-freight-row text-red-600 hover:underline">
        Delete
      </button>
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

                <div class="border border-gray-200 rounded-lg overflow-visible">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-3">Labour Type</th>
                                <th class="px-3 py-3">Qty</th>
                                <th class="px-3 py-3">Unit</th>
                                <th class="px-3 py-3">Description</th>
                                <th class="px-3 py-3">Notes</th>
                                <th class="px-3 py-3">Sell</th>
                                <th class="px-3 py-3">Total</th>
                                <th class="px-3 py-3">Action</th>
                            </tr>
                        </thead>

                        <tbody class="labour-tbody"></tbody>
                    </table>
                </div>

                {{-- Labour row template (outside the table) --}}
                <template class="labour-row-template">
                    <tr class="bg-white border-t">
                        <td class="px-3 py-2 overflow-visible">
						  <div class="relative">
							<input
							  type="text"
							  name="rooms[0][labour][0][labour_type]"
							  class="w-44 bg-gray-50 border border-gray-300 rounded-lg p-2"
							  placeholder="Labour Type"
							  autocomplete="off"
							  data-labour-type-input
							/>

							<div
							  class="hidden absolute left-0 top-full z-50 mt-1 w-44 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
							  data-labour-type-dropdown
							>
							  <ul class="py-1 text-sm text-gray-700" data-labour-type-options></ul>
							</div>
						  </div>
						</td>
                        <td class="px-3 py-2">
                            <input type="number" step="0.01" name="rooms[0][labour][0][quantity]"
                                class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0">
                        </td>
                        <td class="px-3 py-2">
                            <input type="text" name="rooms[0][labour][0][unit]"
							  class="w-24 bg-gray-50 border border-gray-300 rounded-lg p-2"
							  placeholder="Unit"
							  data-labour-unit-input>
                        </td>
                        <td class="px-3 py-2 overflow-visible">
						  <div class="relative">
							<input
							  type="text"
							  name="rooms[0][labour][0][description]"
							  class="w-64 bg-gray-50 border border-gray-300 rounded-lg p-2"
							  placeholder="Description"
							  autocomplete="off"
							  data-labour-desc-input
							/>

							<div
							  class="hidden absolute left-0 top-full z-50 mt-1 w-64 bg-white border border-gray-200 rounded-lg shadow-lg max-h-56 overflow-auto"
							  data-labour-desc-dropdown
							>
							  <ul class="py-1 text-sm text-gray-700" data-labour-desc-options></ul>
							</div>
						  </div>
						</td>
                        <td class="px-3 py-2">
                            <input type="text" name="rooms[0][labour][0][notes]"
                                class="w-56 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="Notes">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" step="0.01" name="rooms[0][labour][0][sell_price]"
                                class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2"
                                placeholder="0.00">
                        </td>
                        <td class="px-3 py-2">
    <span class="labour-line-total inline-block w-28 text-right font-medium">$0.00</span>
    <input type="hidden" name="rooms[0][labour][0][line_total]" class="labour-line-total-input" value="0">
</td>
                        <td class="px-3 py-2">
                            <button type="button" class="delete-labour-row text-red-600 hover:underline">
                                Delete
                            </button>
                        </td>
                    </tr>
                </template>
            </div>

            {{-- Room Summary --}}
            <div class="border-t pt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-material-label text-xs text-gray-500">Room 1 Material Total</p>
                        <p class="room-material-value text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-freight-label text-xs text-gray-500">Room 1 Freight Total</p>
                        <p class="room-freight-value text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="room-labour-label text-xs text-gray-500">Room 1 Labour Total</p>
                        <p class="room-labour-value text-lg font-semibold text-gray-900">$0.00</p>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <p class="room-total-label text-xs text-gray-500">Room 1 Total</p>
                        <p class="room-total-value text-lg font-bold text-gray-900">$0.00</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</template>

{{-- Add Room Button --}}
<div class="flex justify-start">
    <button id="add-room-btn" type="button"
        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
        + Add Room
    </button>
</div>
</div>
		
{{-- Back to normal width --}}
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 estimate-normal-container">
                {{-- Estimate Summary --}}
                <div class="mt-8 bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Estimate Summary</h2>

<button
  id="select-tax-group-btn"
  type="button"
  data-modal-target="tax-group-modal"
  data-modal-toggle="tax-group-modal"
  class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
  Select Tax Group
</button>
						{{-- Tax Group Modal --}}
<div id="tax-group-modal" tabindex="-1" aria-hidden="true"
  class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
  <div class="relative w-full max-w-lg bg-white rounded-lg shadow">

    {{-- Header --}}
    <div class="flex items-center justify-between p-4 border-b rounded-t">
      <h3 class="text-lg font-semibold text-gray-900">Select Tax Group</h3>
      <button type="button"
        class="text-gray-400 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex items-center justify-center"
        data-modal-hide="tax-group-modal">
        ✕
      </button>
    </div>

    {{-- Body --}}
    <div class="p-4 space-y-2 max-h-[60vh] overflow-auto">
      @foreach($taxGroups as $group)
        <button type="button"
          class="w-full text-left px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50"
          data-tax-group-id="{{ $group->id }}"
          data-tax-group-name="{{ $group->name }}"
          data-modal-hide="tax-group-modal">
          <div class="font-medium text-gray-900">{{ $group->name }}</div>
        </button>
      @endforeach
    </div>

    {{-- Footer --}}
    <div class="flex justify-end gap-2 p-4 border-t rounded-b">
      <button type="button"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
        data-modal-hide="tax-group-modal">
        Cancel
      </button>
    </div>

  </div>
</div>
                    </div>

                    <div class="flex items-center justify-between border-b pb-2">
    <span class="text-sm text-gray-700">Subtotal (Materials)</span>
    <span class="estimate-materials-value text-sm font-semibold text-gray-900">$0.00</span>
</div>

<div class="flex items-center justify-between border-b pb-2">
    <span class="text-sm text-gray-700">Subtotal (Labour)</span>
    <span class="estimate-labour-value text-sm font-semibold text-gray-900">$0.00</span>
</div>

<div class="flex items-center justify-between border-b pb-2">
    <span class="text-sm text-gray-700">Total Freight / Trip</span>
    <span class="estimate-freight-value text-sm font-semibold text-gray-900">$0.00</span>
</div>

<div class="flex items-center justify-between border-b pb-2">
    <span class="text-sm text-gray-700">Pre-tax Total</span>
    <span class="estimate-pretax-value text-sm font-semibold text-gray-900">$0.00</span>
</div>

<div class="flex items-center justify-between border-b pb-2">
    <span class="estimate-tax-label text-sm text-gray-700">Tax (G)</span>
    <span class="estimate-tax-value text-sm font-semibold text-gray-900">$0.00</span>
</div>

<div class="flex items-center justify-between pt-2">
    <span class="text-base font-semibold text-gray-900">Grand Total</span>
    <span class="estimate-grand-total-value text-base font-bold text-gray-900">$0.00</span>
</div>

					<!-- Step 13: Hidden inputs for estimate totals (used on save) -->
<input type="hidden" name="subtotal_materials" id="subtotal_materials_input" value="0">
<input type="hidden" name="subtotal_labour" id="subtotal_labour_input" value="0">
<input type="hidden" name="subtotal_freight" id="subtotal_freight_input" value="0">
<input type="hidden" name="pretax_total" id="pretax_total_input" value="0">
<input type="hidden" name="tax_amount" id="tax_amount_input" value="0">
<input type="hidden" name="grand_total" id="grand_total_input" value="0">
<input type="hidden" name="tax_group_id" id="tax_group_id_input" value="{{ $defaultTaxGroupId ?? '' }}">
<input type="hidden" name="tax_rate_percent" id="tax_rate_percent_input" value="0">

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">

    {{-- Prepared by --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500">Prepared by</p>
        <p class="text-sm font-semibold text-gray-900">
            {{ optional($estimate->creator)->name ?? '—' }}
        </p>
    </div>

    {{-- Last edited by --}}
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p class="text-xs text-gray-500">Last edited by</p>
        <p class="text-sm font-semibold text-gray-900">
            {{ optional($estimate->updater)->name ?? '—' }}
        </p>
    </div>

    {{-- Status --}}
<div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-2">
    <label class="block text-xs text-gray-500">Status</label>

    @php
        $currentStatus = old('status', $estimate->status);

        $statusColors = [
            'draft'    => 'bg-gray-200 text-gray-800',
            'sent'     => 'bg-blue-200 text-blue-800',
            'revised'  => 'bg-yellow-200 text-yellow-800',
            'approved' => 'bg-green-200 text-green-800',
            'rejected' => 'bg-red-200 text-red-800',
        ];
    @endphp

    <div class="flex items-center gap-2">
        <select name="status"
          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5">
            @foreach(array_keys($statusColors) as $s)
                <option value="{{ $s }}" @selected($currentStatus === $s)>
                    {{ ucfirst($s) }}
                </option>
            @endforeach
        </select>

        <span id="status-badge"
          class="px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$currentStatus] ?? 'bg-gray-200 text-gray-800' }}">
            {{ ucfirst($currentStatus) }}
        </span>
    </div>
</div>

</div>

                            <p class="text-xs text-gray-500">
                                Totals are display-only. Tax will recalculate after selecting a tax group (later).
                            </p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- Bottom Action Bar --}}
    <div class="mt-10 border-t pt-6">
        <div class="flex items-center justify-between max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="text-sm text-gray-600">
              
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>

                <button type="submit"
				  class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 						focus:ring-blue-300">
				  Save Estimate
				</button>

            </div>
        </div>
    </div>
</div>
</form>

<script>
  window.FM_ESTIMATE_PRODUCT_TYPES_URL = "{{ route('admin.estimates.api.product-types') }}";
  window.FM_ESTIMATE_MANUFACTURERS_URL = "/estimates/api/manufacturers";
  window.FM_ESTIMATE_PRODUCT_STYLES_URL = "/product-lines";
  window.FM_ESTIMATE_FREIGHT_ITEMS_URL = "{{ route('admin.estimates.api.freight-items') }}";
  window.FM_ESTIMATE_LABOUR_TYPES_URL = "/estimates/api/labour-types";
</script>

<script src="{{ asset('assets/js/estimates/estimate_mock.js') }}" defer></script>
<script src="{{ asset('assets/js/estimates/estimate_edit.js') }}" defer></script>

</x-admin-layout>