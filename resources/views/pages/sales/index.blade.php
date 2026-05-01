<x-admin-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    {{ session('error') }}
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
                                   placeholder="Sale #, estimate #, customer, job, PM, CO #..."
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
                                <option value="{{ $opt }}" @selected($status === $opt)>{{ $statusLabels[$opt] ?? $opt }}</option>
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
                    <div class="md:col-span-12 flex flex-wrap items-center gap-4">
                        {{-- Has CO checkbox --}}
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                            <input type="checkbox" name="has_co" value="1" @checked($hasCo)
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            Has Change Orders
                        </label>

                        @role('admin')
                        <label class="flex items-center gap-2 text-sm text-red-700 cursor-pointer select-none">
                            <input type="checkbox" name="trashed" value="1" @checked($trashed)
                                   class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                            Show Deleted
                        </label>
                        @endrole

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
            @php
                $statusLabels = [
                    'open'                => 'Open',
                    'sent'                => 'Sent',
                    'approved'            => 'Approved',
                    'change_in_progress'  => 'Change In Progress',
                    'scheduled'           => 'Scheduled',
                    'in_progress'         => 'In Progress',
                    'on_hold'             => 'On Hold',
                    'completed'           => 'Completed',
                    'partially_invoiced'  => 'Partially Invoiced',
                    'invoiced'            => 'Invoiced',
                    'cancelled'           => 'Cancelled',
                ];
            @endphp
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden" x-data="{ showDelete: false }">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
							<tr>
								<th class="px-6 py-3">
									@include('admin.partials.sort-link', ['label' => 'Sale #', 'field' => 'sale_number'])
								</th>
								<th class="px-6 py-3">Source Estimate</th>
								<th class="px-6 py-3">
									@include('admin.partials.sort-link', ['label' => 'Customer', 'field' => 'customer_name'])
								</th>
								<th class="px-6 py-3">
									@include('admin.partials.sort-link', ['label' => 'Job', 'field' => 'job_name'])
								</th>
								<th class="px-6 py-3 min-w-[200px]">Site Info</th>
								<th class="px-6 py-3">
									@include('admin.partials.sort-link', ['label' => 'Status', 'field' => 'status'])
								</th>
								<th class="px-6 py-3">Locked</th>
								<th class="px-6 py-3 text-right">Revised Contract</th>
								<th class="px-6 py-3 text-right">Invoiced</th>
								<th class="px-6 py-3">
									@include('admin.partials.sort-link', ['label' => 'Created', 'field' => 'created_at'])
								</th>
								<th class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <span>Action</span>
                                        @can('delete sales')
                                        <button type="button" @click="showDelete = !showDelete"
                                                :title="showDelete ? 'Hide delete buttons' : 'Show delete buttons'"
                                                :class="showDelete ? 'text-red-600 bg-red-50 border-red-200' : 'text-gray-400 bg-white border-gray-200'"
                                                class="inline-flex items-center justify-center w-6 h-6 rounded border transition-colors hover:border-red-300 hover:text-red-500">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                        @endcan
                                    </div>
                                </th>
							</tr>
						</thead>
                        <tbody>
    @forelse ($sales as $sale)
        @php
            $statusVal = $sale->status ?? 'open';
            $badge = match ($statusVal) {
                'completed', 'invoiced'  => 'bg-green-100 text-green-800',
                'in_progress'            => 'bg-blue-100 text-blue-800',
                'on_hold'                => 'bg-yellow-100 text-yellow-800',
                'cancelled'              => 'bg-red-100 text-red-800',
                'approved'               => 'bg-emerald-100 text-emerald-800',
                'sent'                   => 'bg-sky-100 text-sky-800',
                'change_in_progress'     => 'bg-amber-100 text-amber-800',
                default                  => 'bg-gray-100 text-gray-800',
            };
            $statusLabel = $statusLabels[$statusVal] ?? $statusVal;
            $activeCo    = $sale->changeOrders->first(); // eager-loaded active CO (draft/sent only)

            $isLocked = !empty($sale->locked_at);
            $lockedBadge = $isLocked ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800';

            $revisedContract = (float) ($sale->revised_contract_total ?? 0);
			$lockedGrand     = (float) ($sale->locked_grand_total ?? 0);
			$grandTotal      = (float) ($sale->grand_total ?? 0);

			$revised = $revisedContract != 0.0
				? $revisedContract
				: ($lockedGrand != 0.0 ? $lockedGrand : $grandTotal);
						$invoiced = (float) ($sale->invoiced_total ?? 0);
        @endphp

        <tr class="{{ $sale->trashed() ? 'bg-red-50 border-b opacity-75' : 'bg-white border-b hover:bg-gray-50' }}">
            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                {{ $sale->sale_number ?? $sale->id }}
                @if ($sale->is_quick_sale)
                    <div class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 mt-0.5">Quick</div>
                @endif
                @if ($sale->trashed())
                    <div class="text-xs text-red-600 font-medium mt-0.5">Deleted {{ optional($sale->deleted_at)->format('Y-m-d') }}</div>
                @endif
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
                @if ($sale->homeowner_name)
                    <div class="font-medium text-gray-900">{{ $sale->homeowner_name }}</div>
                @endif
                @if ($sale->job_address)
                    <div class="text-xs text-gray-500 whitespace-pre-line">{{ $sale->job_address }}</div>
                @endif
                @if (!$sale->homeowner_name && !$sale->job_address)
                    <span class="text-gray-400">—</span>
                @endif
            </td>

            <td class="px-6 py-4">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $badge }}">
                    {{ $statusLabel }}
                </span>

                @if ($sale->change_orders_count > 0)
                    <div class="mt-1">
                        @if($activeCo)
                            <a href="{{ route('pages.sales.change-orders.show', [$sale, $activeCo]) }}"
                               class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 hover:bg-amber-200">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                {{ $activeCo->co_number }} — Active
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                {{ $sale->change_orders_count }} {{ Str::plural('CO', $sale->change_orders_count) }}
                            </span>
                        @endif
                    </div>
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
    @if ($sale->trashed())
        @role('admin')
        <form method="POST" action="{{ route('pages.sales.restore', $sale) }}"
              onsubmit="return confirm('Restore Sale #{{ $sale->sale_number }}?')">
            @csrf
            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-green-700 bg-white border border-green-300 rounded-lg hover:bg-green-50 focus:outline-none focus:ring-4 focus:ring-green-200">
                Restore
            </button>
        </form>
        <form method="POST" action="{{ route('pages.sales.force-destroy', $sale) }}"
              onsubmit="return confirm('Permanently delete Sale #{{ $sale->sale_number }}? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
                Delete Forever
            </button>
        </form>
        @endrole
    @else
    <a href="{{ route('pages.sales.show', $sale) }}"
       class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
        View
    </a>

    <a href="{{ route('pages.sales.edit', $sale) }}"
       class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
        Edit
    </a>

    @can('delete sales')
    @if ($sale->all_purchase_orders_count === 0 && $sale->all_work_orders_count === 0 && $sale->draft_rfcs_count === 0)
        <form x-show="showDelete" x-cloak method="POST"
              action="{{ route('pages.sales.destroy', $sale) }}"
              onsubmit="return confirm('Delete Sale #{{ $sale->sale_number }}?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-200">
                Delete
            </button>
        </form>
    @endif
    @endcan
    @endif
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
