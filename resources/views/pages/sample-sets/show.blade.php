<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php $statusColors = \App\Models\SampleSet::STATUS_COLORS; @endphp

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sampleSet->set_id }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sampleSet->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $sampleSet->status_label }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">Set</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $sampleSet->name ?? $sampleSet->productLine->name }}
                        · {{ $sampleSet->productLine->manufacturer }}
                        · {{ $sampleSet->items->count() }} {{ Str::plural('style', $sampleSet->items->count()) }}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Label dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                            </svg>
                            Print Label
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-1 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-10">
                            <a href="{{ route('pages.sample-sets.label', [$sampleSet, 'format' => '5371']) }}" target="_blank"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-t-lg">
                                Avery 5371 (3.5" × 2")
                            </a>
                            <a href="{{ route('pages.sample-sets.label', [$sampleSet, 'format' => '5388']) }}" target="_blank"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-b-lg border-t border-gray-100 dark:border-gray-700">
                                Avery 5388 (3" × 5")
                            </a>
                        </div>
                    </div>

                    @can('edit samples')
                    <a href="{{ route('pages.sample-sets.edit', $sampleSet) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                        Edit
                    </a>
                    @endcan

                    <a href="{{ route('pages.samples.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        ← Back
                    </a>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Left: styles + checkouts --}}
                <div class="md:col-span-2 space-y-6">

                    {{-- Set Details --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Set Details</h2>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Product Line</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $sampleSet->productLine->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Manufacturer</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $sampleSet->productLine->manufacturer }}</dd>
                            </div>
                            @if ($sampleSet->name)
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Set Name</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $sampleSet->name }}</dd>
                            </div>
                            @endif
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Location</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $sampleSet->location ?? '—' }}</dd>
                            </div>
                            @if ($sampleSet->notes)
                            <div class="col-span-2">
                                <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
                                <dd class="text-gray-900 dark:text-white whitespace-pre-line">{{ $sampleSet->notes }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Styles in Set --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                Styles in This Set <span class="ml-1 text-xs font-normal text-gray-400">({{ $sampleSet->items->count() }})</span>
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th class="px-6 py-3">Style</th>
                                        <th class="px-6 py-3">SKU</th>
                                        <th class="px-6 py-3">Colour</th>
                                        <th class="px-6 py-3 text-right">Display Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sampleSet->items as $item)
                                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $item->productStyle->name }}</td>
                                            <td class="px-6 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $item->productStyle->sku ?? '—' }}</td>
                                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $item->productStyle->color ?? '—' }}</td>
                                            <td class="px-6 py-3 text-right">
                                                @if ($item->display_price)
                                                    <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($item->display_price, 2) }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">No styles in this set.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Active Checkout --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                Active Checkout
                                @if ($sampleSet->activeCheckout)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">1</span>
                                @endif
                            </h2>
                        </div>

                        @if (! $sampleSet->activeCheckout)
                            <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No active checkout. Use the mobile page to check this set out.
                            </div>
                        @else
                            @php $co = $sampleSet->activeCheckout; @endphp
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th class="px-6 py-3">Borrower</th>
                                            <th class="px-6 py-3">Type</th>
                                            <th class="px-6 py-3">Checked Out</th>
                                            <th class="px-6 py-3">Due Back</th>
                                            <th class="px-6 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-gray-100 dark:border-gray-700 {{ $co->is_overdue ? 'bg-red-50 dark:bg-red-900/10' : 'bg-white dark:bg-gray-800' }}">
                                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $co->borrower_name }}</td>
                                            <td class="px-6 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $co->checkout_type === 'staff' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                                                    {{ ucfirst($co->checkout_type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $co->checked_out_at->format('M j, Y') }}</td>
                                            <td class="px-6 py-3 {{ $co->is_overdue ? 'text-red-600 font-semibold' : 'text-gray-600 dark:text-gray-400' }}">
                                                {{ $co->due_back_at ? $co->due_back_at->format('M j, Y') : '—' }}
                                                @if ($co->is_overdue)
                                                    <div class="text-xs font-normal">{{ $co->days_overdue }}d overdue</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 text-right">
                                                @can('manage sample checkouts')
                                                <form method="POST"
                                                      action="{{ route('pages.sample-sets.checkouts.return', [$sampleSet, $co]) }}"
                                                      onsubmit="return confirm('Mark this set as returned?')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="text-xs px-3 py-1.5 font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 border border-green-200">
                                                        Mark Returned
                                                    </button>
                                                </form>
                                                @endcan
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Checkout History --}}
                    @php $history = $sampleSet->checkouts->whereNotNull('returned_at'); @endphp
                    @if ($history->isNotEmpty())
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Checkout History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th class="px-6 py-3">Borrower</th>
                                        <th class="px-6 py-3">Checked Out</th>
                                        <th class="px-6 py-3">Returned</th>
                                        <th class="px-6 py-3">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($history->sortByDesc('checked_out_at') as $co)
                                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $co->borrower_name }}</td>
                                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $co->checked_out_at->format('M j, Y') }}</td>
                                        <td class="px-6 py-3 text-green-700">{{ $co->returned_at->format('M j, Y') }}</td>
                                        <td class="px-6 py-3 text-gray-500">{{ $co->return_notes ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>

                {{-- Right: status + mobile + record + delete --}}
                <div class="space-y-6">

                    {{-- Status card --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Status</h2>
                        <div class="text-center py-2">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $statusColors[$sampleSet->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $sampleSet->status_label }}
                            </span>
                        </div>
                        <dl class="text-sm space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Styles</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $sampleSet->items->count() }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Location</dt>
                                <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $sampleSet->location ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Mobile / QR --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 text-center space-y-3">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Mobile / QR</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Scan to view and check out this set on mobile.</p>
                        <a href="{{ route('mobile.samples.show', $sampleSet->set_id) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 9h3"/>
                            </svg>
                            Open Mobile Page
                        </a>
                        <p class="text-xs text-gray-400 font-mono">{{ $sampleSet->set_id }}</p>
                    </div>

                    {{-- Record info --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Record</h2>
                        <dl class="text-xs space-y-1.5 text-gray-500">
                            <div class="flex justify-between"><dt>Created by</dt><dd>{{ $sampleSet->creator?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt>Created</dt><dd>{{ $sampleSet->created_at->format('M j, Y') }}</dd></div>
                            <div class="flex justify-between"><dt>Updated by</dt><dd>{{ $sampleSet->updater?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt>Updated</dt><dd>{{ $sampleSet->updated_at->format('M j, Y') }}</dd></div>
                        </dl>
                    </div>

                    {{-- Delete --}}
                    @can('delete samples')
                    @if (! $sampleSet->activeCheckout)
                    <form method="POST" action="{{ route('pages.sample-sets.destroy', $sampleSet) }}"
                          onsubmit="return confirm('Delete {{ $sampleSet->set_id }}? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100">
                            Delete Set
                        </button>
                    </form>
                    @endif
                    @endcan

                </div>

            </div>

        </div>
    </div>
</x-app-layout>
