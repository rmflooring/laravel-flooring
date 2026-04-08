<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Sample</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Search the product catalog to add a showroom sample.</p>
                </div>
                <a href="{{ route('pages.samples.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    ← Back
                </a>
            </div>

            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200 dark:bg-red-900/20 dark:text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.samples.store') }}"
                  x-data="sampleCreate()" @submit.prevent="submit">

                @csrf

                {{-- Product Style Search --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Product</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Search Product Catalog <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.300ms="search"
                                   @focus="if(searchQuery.length > 0) showResults = true"
                                   @click.outside="showResults = false"
                                   placeholder="Type product name, SKU, colour, manufacturer..."
                                   class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600"
                                   autocomplete="off">

                            {{-- Dropdown results --}}
                            <div x-show="showResults && results.length > 0"
                                 x-cloak
                                 class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-64 overflow-y-auto">
                                <template x-for="style in results" :key="style.id">
                                    <button type="button"
                                            @click="selectStyle(style)"
                                            class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="style.name"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="style.manufacturer || ''"></span>
                                            <span x-show="style.line_name"> · <span x-text="style.line_name"></span></span>
                                            <span x-show="style.color"> · <span x-text="style.color"></span></span>
                                            <span x-show="style.sku"> · SKU: <span x-text="style.sku"></span></span>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <div x-show="loading" x-cloak class="absolute right-3 top-3">
                                <svg class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        </div>
                        <input type="hidden" name="product_style_id" x-model="selectedId">
                        @error('product_style_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Selected style preview --}}
                    <div x-show="selected" x-cloak
                         class="rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-700 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-0.5">Selected Product</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="selected?.name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span x-text="selected?.manufacturer || ''"></span>
                                    <span x-show="selected?.line_name"> · <span x-text="selected?.line_name"></span></span>
                                    <span x-show="selected?.color"> · <span x-text="selected?.color"></span></span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-show="selected?.sell_price">
                                    Catalog price: $<span x-text="parseFloat(selected?.sell_price || 0).toFixed(2)"></span>
                                </p>
                            </div>
                            <button type="button" @click="clearSelection"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Sample Details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-5 mt-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sample Details</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Quantity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1"
                                   class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            @error('quantity')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Display Price Override
                                <span class="text-gray-400 font-normal">(leave blank to use catalog price)</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">$</span>
                                <input type="number" name="display_price" value="{{ old('display_price') }}"
                                       step="0.01" min="0" placeholder="0.00"
                                       class="block w-full pl-7 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location in Showroom</label>
                        <input type="text" name="location" value="{{ old('location') }}"
                               placeholder="e.g. Showroom – Hardwood Wall, Display Rack B"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Received</label>
                        <input type="date" name="received_at" value="{{ old('received_at', date('Y-m-d')) }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="3"
                                  placeholder="Any notes about this sample..."
                                  class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('pages.samples.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        Cancel
                    </a>
                    <button type="submit" :disabled="!selectedId"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                        Create Sample
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sampleCreate', () => ({
            searchQuery: '',
            results: [],
            loading: false,
            showResults: false,
            selected: null,
            selectedId: '',

            async search() {
                if (this.searchQuery.length < 2) {
                    this.results = [];
                    this.showResults = false;
                    return;
                }
                this.loading = true;
                try {
                    const res = await fetch(`{{ route('pages.samples.styles.search') }}?q=${encodeURIComponent(this.searchQuery)}`);
                    this.results = await res.json();
                    this.showResults = true;
                } finally {
                    this.loading = false;
                }
            },

            selectStyle(style) {
                this.selected    = style;
                this.selectedId  = style.id;
                this.searchQuery = style.name + (style.color ? ' – ' + style.color : '');
                this.showResults = false;
            },

            clearSelection() {
                this.selected    = null;
                this.selectedId  = '';
                this.searchQuery = '';
                this.results     = [];
            },

            submit() {
                if (!this.selectedId) return;
                this.$el.submit();
            }
        }));
    });
    </script>
</x-app-layout>
