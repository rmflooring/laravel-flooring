<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $first         = $items->first();
                $openItems     = $items->filter(fn ($i) => $i->returned_at === null);
                $allReturned   = $openItems->isEmpty();
                $anyOverdue    = $openItems->contains(fn ($i) => $i->is_overdue);
            @endphp

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $checkoutNumber }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Checked out to <span class="font-medium text-gray-700 dark:text-gray-300">{{ $first->borrower_name }}</span>
                        on {{ $first->checked_out_at?->format('M j, Y') }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.samples.checkouts.receipt', $checkoutNumber) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                        </svg>
                        Print Receipt
                    </a>
                    <a href="{{ route('pages.samples.checkouts.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        ← All Checkouts
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">{{ session('error') }}</div>
            @endif

            {{-- Summary --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Type</div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $first->checkout_type === 'staff' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                            {{ ucfirst($first->checkout_type) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Due Back</div>
                        <div class="{{ $anyOverdue ? 'text-red-600 font-semibold' : 'text-gray-900 dark:text-white' }}">
                            {{ $first->due_back_at?->format('M j, Y') ?? '—' }}
                            @if ($anyOverdue) <span class="text-xs font-normal">(overdue)</span> @endif
                        </div>
                    </div>
                    @if ($first->checkout_type === 'customer')
                        <div>
                            <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Phone</div>
                            <div class="text-gray-900 dark:text-white">{{ $first->customer_phone ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Email</div>
                            <div class="text-gray-900 dark:text-white">{{ $first->customer_email ?: '—' }}</div>
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <div class="text-xs text-gray-400 uppercase tracking-wide mb-1">Destination / Notes</div>
                            <div class="text-gray-900 dark:text-white">{{ $first->destination ?: '—' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Items --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                        Samples
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $items->count() }}
                        </span>
                    </h2>
                    @can('manage sample checkouts')
                        @if (! $allReturned)
                            <form method="POST" action="{{ route('pages.samples.checkouts.return-all', $checkoutNumber) }}"
                                  onsubmit="return confirm('Mark every sample in this checkout as returned?')">
                                @csrf
                                <button type="submit"
                                        class="text-sm font-medium text-green-700 bg-green-50 px-3 py-1.5 rounded-lg hover:bg-green-100 border border-green-200">
                                    Return All
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <th class="px-6 py-2 text-left">Sample</th>
                            <th class="px-6 py-2 text-left">Style</th>
                            <th class="px-6 py-2 text-right">Qty</th>
                            <th class="px-6 py-2 text-left">Status</th>
                            <th class="px-6 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($items as $item)
                            @php $style = $item->sample?->productStyle; @endphp
                            <tr class="{{ $item->is_overdue ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                    @if ($item->sample)
                                        <a href="{{ route('pages.samples.show', $item->sample) }}" class="hover:underline">{{ $item->sample->sample_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">
                                    {{ $style?->productLine?->manufacturer }} {{ $style?->name }}
                                </td>
                                <td class="px-6 py-3 text-right">{{ $item->qty_checked_out }}</td>
                                <td class="px-6 py-3">
                                    @if ($item->returned_at)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">
                                            Returned {{ $item->returned_at->format('M j') }}
                                        </span>
                                    @elseif ($item->is_overdue)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                            Overdue {{ $item->days_overdue }}d
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
                                            Out
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    @can('manage sample checkouts')
                                        @if (! $item->returned_at && $item->sample)
                                            <form method="POST" action="{{ route('pages.samples.checkouts.return', [$item->sample, $item]) }}"
                                                  onsubmit="return confirm('Mark this sample as returned?')">
                                                @csrf
                                                <button type="submit" class="text-xs px-3 py-1.5 font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 border border-green-200">
                                                    Mark Returned
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
