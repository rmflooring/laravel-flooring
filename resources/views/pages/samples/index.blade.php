<x-app-layout>
    <div class="py-6" x-data="{
        selectedSamples: [],
        selectedSets: [],
        showPrice: true,
        get totalSelected() { return this.selectedSamples.length + this.selectedSets.length; },
        toggleSample(id) {
            const idx = this.selectedSamples.indexOf(id);
            idx === -1 ? this.selectedSamples.push(id) : this.selectedSamples.splice(idx, 1);
        },
        toggleSet(id) {
            const idx = this.selectedSets.indexOf(id);
            idx === -1 ? this.selectedSets.push(id) : this.selectedSets.splice(idx, 1);
        },
        selectAllSamples(ids) {
            this.selectedSamples = ids.every(id => this.selectedSamples.includes(id)) ? [] : [...ids];
        },
        selectAllSets(ids) {
            this.selectedSets = ids.every(id => this.selectedSets.includes(id)) ? [] : [...ids];
        },
        buildUrl() {
            const p = new URLSearchParams();
            this.selectedSamples.forEach(id => p.append('samples[]', id));
            this.selectedSets.forEach(id => p.append('sets[]', id));
            if (this.showPrice) p.append('show_price', '1');
            return '{{ route('pages.samples.batch-label.form') }}?' + p;
        },
        clearAll() { this.selectedSamples = []; this.selectedSets = []; }
    }">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Samples</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Showroom sample inventory and checkout tracking.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    @can('view samples')
                    <a href="{{ route('pages.samples.product-line-labels.form') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L9.568 3Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                        </svg>
                        Product Line Labels
                    </a>
                    @endcan
                    @can('create samples')
                    <a href="{{ route('pages.samples.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Sample
                    </a>
                    <a href="{{ route('pages.sample-sets.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Add Sample Set
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200 dark:bg-green-900/20 dark:text-green-300">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200 dark:bg-red-900/20 dark:text-red-300">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('pages.samples.index') }}"
                  class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                   placeholder="ID, product name, SKU, colour..."
                                   class="block w-full pl-10 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            <option value="all"        @selected(($filters['type'] ?? 'all') === 'all')>All Types</option>
                            <option value="individual" @selected(($filters['type'] ?? '') === 'individual')>Individual</option>
                            <option value="set"        @selected(($filters['type'] ?? '') === 'set')>Sample Sets</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            <option value="">All Statuses</option>
                            @foreach ($statuses as $val)
                                <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ ucfirst(str_replace('_', ' ', $val)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location</label>
                        <select name="location"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            <option value="">All Locations</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc }}" @selected(($filters['location'] ?? '') === $loc)>{{ $loc }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300 pb-2.5">
                            <input type="checkbox" name="overdue" value="1" @checked(!empty($filters['overdue']))
                                   class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500">
                            <span>Overdue only</span>
                        </label>
                    </div>

                    <div class="md:col-span-1 flex items-end gap-2">
                        <button type="submit"
                                class="w-full px-3 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            Filter
                        </button>
                    </div>

                    @if (array_filter($filters))
                    <div class="md:col-span-12">
                        <a href="{{ route('pages.samples.index') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">Clear filters</a>
                    </div>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        @php
                            $allSampleIds = $type !== 'set' ? $samples->pluck('id')->toArray() : [];
                            $allSetIds    = $type !== 'individual' ? $sampleSets->pluck('id')->toArray() : [];
                        @endphp
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600">
                            <tr>
                                <th class="px-4 py-3 w-10">
                                    @if ($type === 'individual')
                                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                               @click="selectAllSamples({{ json_encode($allSampleIds) }})"
                                               :checked="selectedSamples.length > 0 && {{ json_encode($allSampleIds) }}.every(id => selectedSamples.includes(id))">
                                    @elseif ($type === 'set')
                                        <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                               @click="selectAllSets({{ json_encode($allSetIds) }})"
                                               :checked="selectedSets.length > 0 && {{ json_encode($allSetIds) }}.every(id => selectedSets.includes(id))">
                                    @endif
                                </th>
                                <th class="px-6 py-3">ID</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Product / Line</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Location</th>
                                <th class="px-6 py-3 text-center">Qty / Styles</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                            {{-- Individual Samples --}}
                            @if ($type !== 'set')
                                @forelse ($samples as $sample)
                                    @php
                                        $overdue = $sample->activeCheckouts->contains(fn($c) =>
                                            $c->due_back_at && $c->due_back_at->isPast()
                                        );
                                        $statusColors = \App\Models\Sample::STATUS_COLORS;
                                    @endphp
                                    <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                        :class="selectedSamples.includes({{ $sample->id }}) ? 'ring-1 ring-inset ring-blue-400' : ''">
                                        <td class="px-4 py-4">
                                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                                   :checked="selectedSamples.includes({{ $sample->id }})"
                                                   @click="toggleSample({{ $sample->id }})">
                                        </td>
                                        <td class="px-6 py-4 font-mono font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $sample->sample_id }}
                                            @if ($overdue)
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">Individual</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $sample->productStyle->name }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $sample->productStyle->productLine?->manufacturer }}
                                                @if ($sample->productStyle->color)· {{ $sample->productStyle->color }}@endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sample->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $sample->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $sample->location ?? '—' }}</td>
                                        <td class="px-6 py-4 text-center">
                                            @php $avail = $sample->available_qty; @endphp
                                            <span class="font-semibold {{ $avail === 0 ? 'text-red-600' : 'text-green-700 dark:text-green-400' }}">{{ $avail }}</span>
                                            <span class="text-gray-400">/{{ $sample->quantity }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('pages.samples.show', $sample) }}"
                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    @if ($type === 'individual')
                                        <tr>
                                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                                No samples found.
                                                @can('create samples')
                                                    <a href="{{ route('pages.samples.create') }}" class="text-blue-600 hover:underline ml-1">Add the first one.</a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            @endif

                            {{-- Sample Sets --}}
                            @if ($type !== 'individual')
                                @forelse ($sampleSets as $set)
                                    @php
                                        $setOverdue = $set->activeCheckout && $set->activeCheckout->due_back_at && $set->activeCheckout->due_back_at->isPast();
                                        $statusColors = \App\Models\SampleSet::STATUS_COLORS;
                                    @endphp
                                    <tr class="bg-indigo-50/30 dark:bg-indigo-900/10 border-b dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                        :class="selectedSets.includes({{ $set->id }}) ? 'ring-1 ring-inset ring-indigo-400' : ''">
                                        <td class="px-4 py-4">
                                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                   :checked="selectedSets.includes({{ $set->id }})"
                                                   @click="toggleSet({{ $set->id }})">
                                        </td>
                                        <td class="px-6 py-4 font-mono font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $set->set_id }}
                                            @if ($setOverdue)
                                                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Overdue</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">Set</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $set->name ?? $set->productLine->name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $set->productLine->manufacturer }} · {{ $set->productLine->name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$set->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $set->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $set->location ?? '—' }}</td>
                                        <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">
                                            {{ $set->items_count }} {{ Str::plural('style', $set->items_count) }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('pages.sample-sets.show', $set) }}"
                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-700 bg-indigo-50 rounded-lg hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    @if ($type === 'set')
                                        <tr>
                                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                                No sample sets found.
                                                @can('create samples')
                                                    <a href="{{ route('pages.sample-sets.create') }}" class="text-indigo-600 hover:underline ml-1">Add the first one.</a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endif
                                @endforelse
                            @endif

                            @if ($type === 'all' && $samples->isEmpty() && $sampleSets->isEmpty())
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No samples or sets found.
                                    </td>
                                </tr>
                            @endif

                        </tbody>
                    </table>
                </div>

                {{-- Pagination (only when filtered to one type) --}}
                @if ($type === 'individual' && $samples instanceof \Illuminate\Pagination\LengthAwarePaginator && $samples->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $samples->links() }}
                    </div>
                @elseif ($type === 'set' && $sampleSets instanceof \Illuminate\Pagination\LengthAwarePaginator && $sampleSets->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $sampleSets->links() }}
                    </div>
                @endif
            </div>

        </div>

    {{-- Sticky batch-label action bar --}}
    <div x-show="totalSelected > 0"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-0 inset-x-0 z-50 pb-safe"
         style="display:none;">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 pb-4">
            <div class="bg-gray-900 dark:bg-gray-800 border border-gray-700 rounded-xl shadow-2xl px-5 py-3 flex flex-wrap items-center gap-3">

                {{-- Count --}}
                <span class="text-sm font-medium text-white">
                    <span x-text="totalSelected"></span> selected
                </span>

                <div class="h-4 w-px bg-gray-600"></div>

                {{-- Show price toggle --}}
                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-300">
                    <input type="checkbox" x-model="showPrice" class="w-4 h-4 rounded border-gray-500 text-blue-600 focus:ring-blue-500 bg-gray-700">
                    Show prices on labels
                </label>

                <div class="flex-1"></div>

                {{-- Clear --}}
                <button type="button" @click="clearAll()"
                        class="px-3 py-1.5 text-sm font-medium text-gray-300 hover:text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Clear
                </button>

                {{-- Print --}}
                <a :href="buildUrl()"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                    </svg>
                    Print Labels
                </a>

            </div>
        </div>
    </div>

    </div>{{-- /x-data --}}
</x-app-layout>
