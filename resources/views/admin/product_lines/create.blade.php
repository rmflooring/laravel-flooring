<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Add New Product Line
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- Error Messages -->
                    @if ($errors->any())
                        <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.product_lines.store') }}" method="POST">
                        @csrf

                        <!-- Product Type Dropdown -->
                        <div class="mb-6">
                            <label for="product_type_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Product Type</label>
                            <select name="product_type_id" id="product_type_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">-- Select Product Type --</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}"
                                            data-sold-by-unit="{{ $type->sold_by_unit_id }}"
                                            {{ old('product_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_type_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Name -->
                        <div class="mb-6">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Vendor Dropdown (NEW) -->
                        <div class="mb-6">
                            <label for="vendor_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Vendor</label>
                            <select name="vendor_id" id="vendor_id"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
    <option value="">-- Select a Vendor --</option>
    @foreach($vendors as $vendor)
        <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
            {{ $vendor->company_name }}  <!-- ← Changed from name to company_name -->
        </option>
    @endforeach
</select>
                            @error('vendor_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Manufacturer -->
                        <div class="mb-6">
                            <label for="manufacturer" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Manufacturer</label>
                            <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer') }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('manufacturer')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Model -->
                        <div class="mb-6">
                            <label for="model" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Model</label>
                            <input type="text" name="model" id="model" value="{{ old('model') }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('model')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Collection -->
                        <div class="mb-6">
                            <label for="collection" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Collection</label>
                            <input type="text" name="collection" id="collection" value="{{ old('collection') }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('collection')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Default Cost Price & Default Sell Price -->
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="default_cost_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Default Cost Price</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">$</span>
                                    <input type="number" step="0.0001" min="0" name="default_cost_price" id="default_cost_price"
                                           value="{{ old('default_cost_price') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           placeholder="0.0000">
                                </div>
                                @error('default_cost_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="default_sell_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Default Sell Price</label>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">$</span>
                                    <input type="number" step="0.01" min="0" name="default_sell_price" id="default_sell_price"
                                           value="{{ old('default_sell_price') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           placeholder="0.00">
                                </div>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Apply GPM:</span>
                                    <select id="gpm_selector" class="flex-1 text-xs bg-gray-50 border border-gray-300 text-gray-700 rounded-lg p-1.5 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500">
                                        <option value="">— select margin —</option>
                                        <option value="0.05">5%</option>
                                        <option value="0.10">10%</option>
                                        <option value="0.15">15%</option>
                                        <option value="0.20">20%</option>
                                        <option value="0.25">25%</option>
                                        <option value="0.30">30%</option>
                                        <option value="0.35">35%</option>
                                        <option value="0.40">40%</option>
                                        <option value="0.45">45%</option>
                                        <option value="0.50">50%</option>
                                        <option value="0.55">55%</option>
                                        <option value="0.60">60%</option>
                                        <option value="0.65">65%</option>
                                        <option value="0.70">70%</option>
                                    </select>
                                </div>
                                @error('default_sell_price')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Unit -->
                        <div class="mb-6">
                            <label for="unit_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Unit</label>
                            <select name="unit_id" id="unit_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">-- Select Unit --</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->label }} ({{ $unit->code }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pre-fills from product type; override as needed.</p>
                            @error('unit_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Width & Length -->
                        <div class="grid grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="width" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Width</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.01" min="0" name="width" id="width"
                                           value="{{ old('width') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           placeholder="e.g. 12">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">inches</span>
                                </div>
                                @error('width')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="length" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Length</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.01" min="0" name="length" id="length"
                                           value="{{ old('length') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                           placeholder="e.g. 24">
                                    <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">inches</span>
                                </div>
                                @error('length')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-6">
                            <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
                            <select name="status" id="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('admin.product_lines.index') }}"
                               class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                                Save Product Line
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('product_type_id');
    const unitSelect = document.getElementById('unit_id');

    typeSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const soldByUnitId = selected.dataset.soldByUnit;

        if (soldByUnitId && unitSelect.value === '') {
            unitSelect.value = soldByUnitId;
        }
    });

    document.getElementById('gpm_selector').addEventListener('change', function () {
        const margin = parseFloat(this.value);
        const cost = parseFloat(document.getElementById('default_cost_price').value);
        if (!margin || isNaN(cost) || cost <= 0) { this.value = ''; return; }
        document.getElementById('default_sell_price').value = (cost / (1 - margin)).toFixed(2);
        this.value = '';
    });
});
</script>
</x-app-layout>