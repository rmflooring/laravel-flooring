<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('pages.sample-sets.show', $sampleSet) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit {{ $sampleSet->set_id }}</h1>
            </div>

            {{-- Flash --}}
            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('pages.sample-sets.update', $sampleSet) }}" method="POST"
                  x-data="sampleSetEdit()">
                @csrf
                @method('PUT')

                {{-- Set Details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Set Details</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Product Line <span class="text-red-500">*</span>
                            </label>
                            <select name="product_line_id" x-model="selectedLineId" @change="loadStyles"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                <option value="">— Select a Product Line —</option>
                                @foreach ($productLines as $line)
                                    <option value="{{ $line->id }}" @selected($sampleSet->product_line_id == $line->id)>
                                        {{ $line->manufacturer ? $line->manufacturer . ' — ' : '' }}{{ $line->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                @foreach (\App\Models\SampleSet::STATUSES as $val)
                                    <option value="{{ $val }}" @selected($sampleSet->status === $val)>{{ ucfirst(str_replace('_', ' ', $val)) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Set Name</label>
                            <input type="text" name="name" value="{{ old('name', $sampleSet->name) }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                            <input type="text" name="location" value="{{ old('location', $sampleSet->location) }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <textarea name="notes" rows="2"
                                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">{{ old('notes', $sampleSet->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Styles Picker --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mt-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Styles in This Set
                            <span x-show="styles.length > 0" class="ml-2 text-sm font-normal text-gray-500">
                                (<span x-text="selectedStyleIds.length"></span> of <span x-text="styles.length"></span> selected)
                            </span>
                        </h2>
                        <div x-show="styles.length > 0" class="flex items-center gap-2">
                            <button type="button" @click="selectAll"
                                    class="text-sm text-blue-600 hover:underline dark:text-blue-400">Select All</button>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <button type="button" @click="deselectAll"
                                    class="text-sm text-gray-500 hover:underline dark:text-gray-400">Clear</button>
                        </div>
                    </div>

                    <div x-show="selectedLineId && loading" class="py-8 text-center text-gray-400 text-sm">Loading styles…</div>
                    <div x-show="selectedLineId && !loading && styles.length === 0" class="py-8 text-center text-gray-400 text-sm">No active styles for this product line.</div>

                    <div x-show="styles.length > 0 && !loading" class="space-y-2">
                        <template x-for="style in styles" :key="style.id">
                            <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
                                   :class="selectedStyleIds.includes(style.id)
                                       ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-500'
                                       : 'border-gray-200 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700/30 dark:hover:bg-gray-700'">
                                <input type="checkbox"
                                       :value="style.id"
                                       :name="`style_ids[]`"
                                       :checked="selectedStyleIds.includes(style.id)"
                                       @change="toggleStyle(style.id)"
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="style.name"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-show="style.sku" x-text="style.sku"></span>
                                        <span x-show="style.sku && style.color"> · </span>
                                        <span x-show="style.color" x-text="style.color"></span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-400 mb-1">Display price</div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-400 text-xs">$</span>
                                        <input type="number" step="0.01" min="0"
                                               :name="`display_prices[${style.id}]`"
                                               :value="existingPrices[style.id] ?? null"
                                               :placeholder="style.sell_price ? parseFloat(style.sell_price).toFixed(2) : ''"
                                               @click.stop
                                               class="w-24 text-xs text-right border border-gray-300 rounded px-2 py-1 bg-white dark:bg-gray-700 dark:border-gray-500 dark:text-white focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>

                    <p x-show="submitAttempted && selectedStyleIds.length === 0"
                       class="text-sm text-red-600 mt-2">Please select at least one style.</p>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3 mt-4">
                    <a href="{{ route('pages.sample-sets.show', $sampleSet) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            @click="submitAttempted = true; if (selectedStyleIds.length === 0) $event.preventDefault()"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                        Save Changes
                    </button>
                </div>

            </form>

        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('sampleSetEdit', () => ({
                selectedLineId: '{{ $sampleSet->product_line_id }}',
                styles: @json($styles),
                selectedStyleIds: @json($sampleSet->items->pluck('product_style_id')),
                existingPrices: @json($sampleSet->items->pluck('display_price', 'product_style_id')),
                loading: false,
                submitAttempted: false,

                async loadStyles() {
                    if (!this.selectedLineId) {
                        this.styles = [];
                        this.selectedStyleIds = [];
                        return;
                    }
                    this.loading = true;
                    // Keep selected IDs that were already there, clear on line change
                    this.selectedStyleIds = [];
                    this.existingPrices = {};
                    try {
                        const url = `{{ route('pages.sample-sets.styles-by-line', ':id') }}`.replace(':id', this.selectedLineId);
                        const resp = await fetch(url);
                        this.styles = await resp.json();
                    } catch (e) {
                        this.styles = [];
                    } finally {
                        this.loading = false;
                    }
                },

                toggleStyle(id) {
                    if (this.selectedStyleIds.includes(id)) {
                        this.selectedStyleIds = this.selectedStyleIds.filter(s => s !== id);
                    } else {
                        this.selectedStyleIds.push(id);
                    }
                },

                selectAll() {
                    this.selectedStyleIds = this.styles.map(s => s.id);
                },

                deselectAll() {
                    this.selectedStyleIds = [];
                },
            }));
        });
    </script>
</x-app-layout>
