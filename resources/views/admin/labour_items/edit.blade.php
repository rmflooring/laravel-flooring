<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Edit Labour Item: {{ $labourItem->description }}</h1>

                    <form method="POST" action="{{ route('admin.labour_items.update', $labourItem) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Labour Type Dropdown -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Labour Type *</label>
                                <select name="labour_type_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select Labour Type --</option>
                                    @foreach($labourTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('labour_type_id', $labourItem->labour_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('labour_type_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                                <input type="text" name="description" value="{{ old('description', $labourItem->description) }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="notes" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $labourItem->notes) }}</textarea>
                                @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Cost -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cost *</label>
                                <input type="number" step="0.01" name="cost" id="cost" value="{{ old('cost', $labourItem->cost) }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('cost') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Sell -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sell *</label>
                                <input type="number" step="0.01" name="sell" id="sell" value="{{ old('sell', $labourItem->sell) }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-xs text-gray-500 whitespace-nowrap">Apply GPM:</span>
                                    <select id="gpm_selector" class="flex-1 text-xs bg-gray-50 border border-gray-300 text-gray-700 rounded-lg p-1.5 focus:ring-blue-500 focus:border-blue-500">
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
                                <div class="mt-1">
                                    <span id="margin_display" class="text-xs font-medium" style="color:#6b7280;">Margin: —</span>
                                </div>
                                @error('sell') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Unit Measure Dropdown -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                                <select name="unit_measure_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select Unit --</option>
                                    @foreach($unitMeasures as $unit)
                                        <option value="{{ $unit->id }}" {{ old('unit_measure_id', $labourItem->unit_measure_id) == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unit_measure_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                <select name="status" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="Active" {{ old('status', $labourItem->status) == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ old('status', $labourItem->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="Needs Update" {{ old('status', $labourItem->status) == 'Needs Update' ? 'selected' : '' }}>Needs Update</option>
                                </select>
                                @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('admin.labour_items.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg">
                                Update Labour Item
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script>
function updateLabourMargin() {
    const cost = parseFloat(document.getElementById('cost').value);
    const sell = parseFloat(document.getElementById('sell').value);
    const el = document.getElementById('margin_display');
    if (!cost || !sell || sell <= 0) { el.textContent = 'Margin: —'; el.style.color = '#6b7280'; return; }
    const margin = ((sell - cost) / sell) * 100;
    el.textContent = 'Margin: ' + margin.toFixed(1) + '%';
    el.style.color = margin < 20 ? '#dc2626' : margin < 38 ? '#d97706' : '#16a34a';
}
document.getElementById('cost').addEventListener('input', updateLabourMargin);
document.getElementById('sell').addEventListener('input', updateLabourMargin);
document.getElementById('gpm_selector').addEventListener('change', function () {
    const margin = parseFloat(this.value);
    const cost = parseFloat(document.getElementById('cost').value);
    if (!margin || isNaN(cost) || cost <= 0) { this.value = ''; return; }
    document.getElementById('sell').value = (cost / (1 - margin)).toFixed(2);
    this.value = '';
    updateLabourMargin();
});
updateLabourMargin();
</script>
</x-app-layout>
