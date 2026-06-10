<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1800px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Reports</a>
                            <h1 class="text-3xl font-bold">Sales Report</h1>
                        </div>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                           class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Export CSV
                        </a>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.reports.sales') }}" class="mb-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="Sale #, job, customer..."
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Status</label>
                                    <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All Statuses</option>
                                        @foreach($statuses as $s)
                                            <option value="{{ $s }}" @selected(request('status') === $s)>
                                                {{ ucwords(str_replace('_', ' ', $s)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Invoiced</label>
                                    <select name="invoiced" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All</option>
                                        <option value="uninvoiced" @selected(request('invoiced') === 'uninvoiced')>Not Yet Invoiced</option>
                                        <option value="invoiced"   @selected(request('invoiced') === 'invoiced')>Fully Invoiced</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Salesperson</label>
                                    <select name="salesperson_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All</option>
                                        @foreach($salespeople as $emp)
                                            <option value="{{ $emp->id }}" @selected(request('salesperson_id') == $emp->id)>{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date From</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date To</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                            </div>
                            <div class="flex items-center gap-3 mt-4">
                                <button type="submit"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2">
                                    Apply Filters
                                </button>
                                <a href="{{ route('admin.reports.sales') }}"
                                   class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2">
                                    Reset
                                </a>
                                <div class="ml-auto flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Per page:</label>
                                    <select name="perPage" onchange="this.form.submit()"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2">
                                        @foreach([25, 50, 100] as $n)
                                            <option value="{{ $n }}" @selected(request('perPage', 25) == $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Sales</p>
                            <p class="text-2xl font-bold text-gray-800">{{ number_format($totals->total_count) }}</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-blue-600 uppercase font-semibold mb-1">Total Contract Value</p>
                            <p class="text-2xl font-bold text-blue-800">${{ number_format($totals->total_value, 2) }}</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-xs text-green-600 uppercase font-semibold mb-1">Invoiced</p>
                            <p class="text-2xl font-bold text-green-800">${{ number_format($totals->total_invoiced, 2) }}</p>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <p class="text-xs text-orange-600 uppercase font-semibold mb-1">Uninvoiced Balance</p>
                            <p class="text-2xl font-bold text-orange-800">${{ number_format(max(0, $totals->total_balance), 2) }}</p>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Sale #</th>
                                    <th class="px-4 py-3">Job Name</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">PM</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Grand Total</th>
                                    <th class="px-4 py-3 text-right">Invoiced</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                    <th class="px-4 py-3">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                            <a href="{{ route('pages.sales.show', $sale) }}" class="text-blue-600 hover:underline">#{{ $sale->sale_number }}</a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-900">{{ $sale->job_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $sale->customer_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $sale->pm_name ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $statusColors = [
                                                    'open'              => 'bg-blue-100 text-blue-800',
                                                    'scheduled'         => 'bg-indigo-100 text-indigo-800',
                                                    'in_progress'       => 'bg-yellow-100 text-yellow-800',
                                                    'on_hold'           => 'bg-gray-100 text-gray-700',
                                                    'completed'         => 'bg-green-100 text-green-800',
                                                    'partially_invoiced'=> 'bg-orange-100 text-orange-800',
                                                    'invoiced'          => 'bg-teal-100 text-teal-800',
                                                    'cancelled'         => 'bg-red-100 text-red-800',
                                                ];
                                                $color = $statusColors[$sale->status] ?? 'bg-gray-100 text-gray-700';
                                            @endphp
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                                {{ ucwords(str_replace('_', ' ', $sale->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($sale->grand_total, 2) }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format($sale->invoiced_total, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-medium {{ max(0, $sale->grand_total - $sale->invoiced_total) > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                                            ${{ number_format(max(0, $sale->grand_total - $sale->invoiced_total), 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $sale->created_at->format('M j, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-400">No sales found matching your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $sales->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
