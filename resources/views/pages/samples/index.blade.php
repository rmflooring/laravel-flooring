<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Samples</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Showroom sample inventory and checkout tracking.</p>
                </div>
                @can('create samples')
                <div class="flex items-center gap-2">
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
                </div>
                @endcan
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
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600">
                            <tr>
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
                                    <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
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
                                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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
                                    <tr class="bg-indigo-50/30 dark:bg-indigo-900/10 border-b dark:border-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
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
                                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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
    </div>
</x-app-layout>
