<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1800px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Reports</a>
                            <h1 class="text-3xl font-bold">Accounts Receivable</h1>
                        </div>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                           class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Export CSV
                        </a>
                    </div>

                    {{-- AR Aging Summary --}}
                    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Outstanding</p>
                            <p class="text-xl font-bold text-gray-800">${{ number_format($aging->total_outstanding ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-xs text-green-600 uppercase font-semibold mb-1">Current</p>
                            <p class="text-xl font-bold text-green-800">${{ number_format($aging->current_amount ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-xs text-yellow-600 uppercase font-semibold mb-1">1–30 Days</p>
                            <p class="text-xl font-bold text-yellow-800">${{ number_format($aging->days_1_30 ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <p class="text-xs text-orange-600 uppercase font-semibold mb-1">31–60 Days</p>
                            <p class="text-xl font-bold text-orange-800">${{ number_format($aging->days_31_60 ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <p class="text-xs text-red-600 uppercase font-semibold mb-1">61–90 Days</p>
                            <p class="text-xl font-bold text-red-800">${{ number_format($aging->days_61_90 ?? 0, 2) }}</p>
                        </div>
                        <div class="bg-red-100 border border-red-300 rounded-lg p-4">
                            <p class="text-xs text-red-700 uppercase font-semibold mb-1">90+ Days</p>
                            <p class="text-xl font-bold text-red-900">${{ number_format($aging->days_90_plus ?? 0, 2) }}</p>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.reports.invoices') }}" class="mb-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="Invoice #, job, customer..."
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
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date From</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date To</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div class="flex items-end pb-1">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="overdue_only" value="1"
                                               @checked(request()->boolean('overdue_only'))
                                               class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500">
                                        <span class="text-sm font-medium text-gray-900">Overdue Only</span>
                                    </label>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Per Page</label>
                                    <select name="perPage" onchange="this.form.submit()"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        @foreach([25, 50, 100] as $n)
                                            <option value="{{ $n }}" @selected(request('perPage', 25) == $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            <div class="flex items-center gap-3 mt-4">
                                <button type="submit"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2">
                                    Apply Filters
                                </button>
                                <a href="{{ route('admin.reports.invoices') }}"
                                   class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Invoice #</th>
                                    <th class="px-4 py-3">Sale #</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Job</th>
                                    <th class="px-4 py-3">Invoice Date</th>
                                    <th class="px-4 py-3">Due Date</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Total</th>
                                    <th class="px-4 py-3 text-right">Paid</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($invoices as $invoice)
                                    @php
                                        $isOverdue = $invoice->due_date && $invoice->due_date->isPast() && !in_array($invoice->status, ['paid', 'voided']);
                                        $statusColors = [
                                            'draft'          => 'bg-gray-100 text-gray-700',
                                            'sent'           => 'bg-blue-100 text-blue-800',
                                            'partially_paid' => 'bg-yellow-100 text-yellow-800',
                                            'paid'           => 'bg-green-100 text-green-800',
                                            'overdue'        => 'bg-red-100 text-red-800',
                                            'voided'         => 'bg-gray-200 text-gray-500',
                                        ];
                                        $statusColor = $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-700';
                                    @endphp
                                    <tr class="bg-white border-b hover:bg-gray-50 {{ $isOverdue ? 'bg-red-50' : '' }}">
                                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                            @if($invoice->sale)
                                                <a href="{{ route('pages.sales.invoices.show', [$invoice->sale, $invoice]) }}" class="text-blue-600 hover:underline">{{ $invoice->invoice_number }}</a>
                                            @else
                                                {{ $invoice->invoice_number }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($invoice->sale)
                                                <a href="{{ route('pages.sales.show', $invoice->sale) }}" class="text-blue-600 hover:underline">#{{ $invoice->sale->sale_number }}</a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">{{ $invoice->sale?->customer_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $invoice->sale?->job_name ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $invoice->created_at->format('M j, Y') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap {{ $isOverdue ? 'text-red-600 font-medium' : '' }}">
                                            {{ $invoice->due_date?->format('M j, Y') ?? '-' }}
                                            @if($isOverdue)
                                                <span class="text-xs text-red-500">({{ $invoice->due_date->diffForHumans() }})</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColor }}">
                                                {{ ucwords(str_replace('_', ' ', $invoice->status)) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($invoice->grand_total, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-green-700">${{ number_format($invoice->amount_paid, 2) }}</td>
                                        <td class="px-4 py-3 text-right font-bold {{ $invoice->balance_due > 0 ? 'text-orange-600' : 'text-gray-400' }}">
                                            ${{ number_format($invoice->balance_due, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-4 py-8 text-center text-gray-400">No invoices found matching your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $invoices->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
