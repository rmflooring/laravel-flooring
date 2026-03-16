{{-- resources/views/pages/warehouse/pick-tickets/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pick Tickets</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Warehouse fulfilment queue.</p>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filter bar --}}
            <form method="GET" action="{{ route('pages.warehouse.pick-tickets.index') }}" class="flex flex-wrap gap-2 items-end">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="PT number…"
                       class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <select name="status"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    Filter
                </button>
                @if (request()->hasAny(['search', 'status']))
                    <a href="{{ route('pages.warehouse.pick-tickets.index') }}"
                       class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Clear
                    </a>
                @endif
            </form>

            {{-- Table --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PT #</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sale</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Work Order</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Items</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Created</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($pickTickets as $pt)
                            @php $isTerminal = in_array($pt->status, ['delivered', 'returned', 'cancelled']); @endphp
                            <tr class="{{ $isTerminal ? 'opacity-60' : '' }} hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                    <a href="{{ route('pages.warehouse.pick-tickets.show', $pt) }}"
                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        {{ $pt->pt_number }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    @if ($pt->sale)
                                        <a href="{{ route('pages.sales.status', $pt->sale) }}"
                                           class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            Sale #{{ $pt->sale->sale_number }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    @if ($pt->workOrder)
                                        WO #{{ $pt->workOrder->wo_number }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $pt->items->count() }} {{ Str::plural('item', $pt->items->count()) }}
                                </td>
                                <td class="px-4 py-3">
                                    @include('pages.warehouse.pick-tickets._status-badge', ['status' => $pt->status])
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $pt->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('pages.warehouse.pick-tickets.show', $pt) }}"
                                       class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                        View →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">
                                    No pick tickets found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($pickTickets->hasPages())
                    <div class="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                        {{ $pickTickets->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
