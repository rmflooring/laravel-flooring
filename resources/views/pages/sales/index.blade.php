<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Sales</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        View, search, and filter all sales.
                    </p>
                </div>

                {{-- Optional: add a "Create Sale" later if/when you support it --}}
                <div class="flex items-center gap-2">
                    {{-- <a href="{{ route('pages.sales.create') }}" class="...">+ Create Sale</a> --}}
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters / Search --}}
            <form method="GET" action="{{ route('pages.sales.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                    {{-- Search --}}
                    <div class="md:col-span-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" value="{{ $q }}"
                                   placeholder="Sale #, estimate #, customer, job, PM..."
                                   class="block w-full pl-10 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All</option>
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt }}" @selected($status === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    {{-- Date To --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    {{-- Buttons --}}
                    <div class="md:col-span-12 flex flex-wrap items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                            Apply Filters
                        </button>

                        <a href="{{ route('pages.sales.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            Reset
                        </a>

                        <div class="ml-auto text-sm text-gray-600">
                            Showing <span class="font-semibold">{{ $sales->firstItem() ?? 0 }}</span>
                            to <span class="font-semibold">{{ $sales->lastItem() ?? 0 }}</span>
                            of <span class="font-semibold">{{ $sales->total() }}</span>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
							<tr>
								<th class="px-6 py-3">Sale #</th>
								<th class="px-6 py-3">Source Estimate</th>
								<th class="px-6 py-3">Customer</th>
								<th class="px-6 py-3">Job</th>
								<th class="px-6 py-3">Status</th>
								<th class="px-6 py-3">Locked</th>
								<th class="px-6 py-3 text-right">Revised Contract</th>
								<th class="px-6 py-3 text-right">Invoiced</th>
								<th class="px-6 py-3">Created</th>
								<th class="px-6 py-3 text-right">Action</th>
							</tr>
						</thead>
                        <tbody>
    @forelse ($sales as $sale)
        @php
            $statusVal = $sale->status ?? 'open';
            $badge = match ($statusVal) {
                'completed', 'invoiced' => 'bg-green-100 text-green-800',
                'in_progress'           => 'bg-blue-100 text-blue-800',
                'on_hold'               => 'bg-yellow-100 text-yellow-800',
                'cancelled'             => 'bg-red-100 text-red-800',
                default                 => 'bg-gray-100 text-gray-800',
            };

            $isLocked = !empty($sale->locked_at);
            $lockedBadge = $isLocked ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800';

            $revised = (float) ($sale->revised_contract_total ?? $sale->locked_grand_total ?? $sale->grand_total ?? 0);
            $invoiced = (float) ($sale->invoiced_total ?? 0);
        @endphp

        <tr class="bg-white border-b hover:bg-gray-50">
            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                {{ $sale->sale_number ?? $sale->id }}
            </td>

            <td class="px-6 py-4">
                {{ $sale->source_estimate_number ?? '—' }}
            </td>

            <td class="px-6 py-4">
                {{ $sale->customer_name ?? '—' }}
            </td>

            <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ $sale->job_name ?? '—' }}</div>
                <div class="text-xs text-gray-500">{{ $sale->job_no ?? '' }}</div>
            </td>

            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">
                    {{ $statusVal }}
                </span>

                @if (!empty($sale->has_changes))
                    <span class="ml-2 inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Changed
                    </span>
                @endif
            </td>

            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $lockedBadge }}">
                    {{ $isLocked ? 'Locked' : 'Open' }}
                </span>
            </td>

            <td class="px-6 py-4 text-right font-medium text-gray-900">
                {{ number_format($revised, 2) }}
                @if ((float) ($sale->approved_co_total ?? 0) != 0.0)
                    <div class="text-xs text-gray-500">
                        CO: {{ number_format((float) $sale->approved_co_total, 2) }}
                    </div>
                @endif
            </td>

            <td class="px-6 py-4 text-right font-medium text-gray-900">
                {{ number_format($invoiced, 2) }}

                @if (!empty($sale->is_fully_invoiced))
                    <div class="text-xs text-green-700 font-medium">Fully invoiced</div>
                @elseif ($invoiced > 0)
                    <div class="text-xs text-blue-700 font-medium">Partially invoiced</div>
                @endif
            </td>

            <td class="px-6 py-4 text-gray-600">
                {{ optional($sale->created_at)->format('Y-m-d') }}
            </td>

            <td class="px-6 py-4 text-right">
                <div class="inline-flex items-center gap-2 justify-end">
    <a href="{{ route('pages.sales.show', $sale) }}"
       class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
        View
    </a>

    <a href="{{ route('pages.sales.edit', $sale) }}"
       class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
        Edit
    </a>
</div>

            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="px-6 py-10 text-center text-gray-500">
                No sales found.
            </td>
        </tr>
    @endforelse
</tbody>

                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t bg-white">
                    {{ $sales->links() }}
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
