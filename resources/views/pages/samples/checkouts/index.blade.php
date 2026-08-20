<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sample Checkouts</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Who has what checked out, by checkout number.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.samples.checkout.form') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                        Check Out Samples
                    </a>
                    <a href="{{ route('pages.samples.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        ← Samples
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">{{ session('error') }}</div>
            @endif

            {{-- Search --}}
            <form method="GET" action="{{ route('pages.samples.checkouts.index') }}"
                  class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex gap-3">
                    <input type="text" name="q" value="{{ $search }}"
                           placeholder="Checkout #, borrower name…"
                           class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800">
                        Search
                    </button>
                    @if ($search)
                        <a href="{{ route('pages.samples.checkouts.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Checkout #</th>
                                <th class="px-6 py-3">Borrower</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3 text-right">Items</th>
                                <th class="px-6 py-3">Checked Out</th>
                                <th class="px-6 py-3">Due Back</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($checkouts as $checkout)
                                @php
                                    $statusStyles = [
                                        'active'   => 'bg-blue-100 text-blue-700',
                                        'overdue'  => 'bg-red-100 text-red-700',
                                        'partial'  => 'bg-amber-100 text-amber-700',
                                        'returned' => 'bg-green-100 text-green-700',
                                    ];
                                    $statusLabels = [
                                        'active'   => 'Active',
                                        'overdue'  => 'Overdue',
                                        'partial'  => 'Partially Returned',
                                        'returned' => 'Returned',
                                    ];
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-3 font-mono font-semibold text-gray-900 dark:text-white">
                                        <a href="{{ route('pages.samples.checkouts.show', $checkout->checkout_number) }}" class="hover:underline">
                                            {{ $checkout->checkout_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-3 text-gray-900 dark:text-white">{{ $checkout->borrower_name }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $checkout->checkout_type === 'staff' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                                            {{ ucfirst($checkout->checkout_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">{{ $checkout->item_count }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $checkout->checked_out_at?->format('M j, Y') }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $checkout->due_back_at?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusStyles[$checkout->status] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $statusLabels[$checkout->status] ?? ucfirst($checkout->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <a href="{{ route('pages.samples.checkouts.show', $checkout->checkout_number) }}"
                                           class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-400">No checkouts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($checkouts->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $checkouts->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
