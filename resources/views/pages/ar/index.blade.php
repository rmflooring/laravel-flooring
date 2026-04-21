<x-app-layout>
<div class="py-6">
<div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Accounts Receivable</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">All invoices across all sales.</p>
        </div>
        <a href="{{ route('pages.ar.aging') }}"
           class="inline-flex items-center gap-2 py-2 px-4 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            AR Aging Report
        </a>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Outstanding</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalOutstanding, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Overdue</p>
            <p class="mt-1 text-2xl font-bold" style="color: #dc2626;">${{ number_format($totalOverdue, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Due This Week</p>
            <p class="mt-1 text-2xl font-bold" style="color: #d97706;">${{ number_format($dueThisWeek, 2) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Collected (paid)</p>
            <p class="mt-1 text-2xl font-bold" style="color: #16a34a;">${{ number_format($totalPaid, 2) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('pages.ar.index') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

            <div class="md:col-span-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Invoice #, sale #, customer, homeowner…"
                           class="block w-full pl-10 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All</option>
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due From</label>
                <input type="date" name="due_from" value="{{ request('due_from') }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due To</label>
                <input type="date" name="due_to" value="{{ request('due_to') }}"
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div class="md:col-span-1 flex items-end gap-2">
                <button type="submit"
                        class="w-full py-2.5 px-4 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Filter
                </button>
            </div>

            @if (request()->hasAny(['q', 'status', 'due_from', 'due_to']))
                <div class="md:col-span-12">
                    <a href="{{ route('pages.ar.index') }}"
                       class="text-sm text-blue-600 hover:underline dark:text-blue-400">Clear filters</a>
                </div>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Invoice #</th>
                        <th class="px-4 py-3">Sale #</th>
                        <th class="px-4 py-3">Customer / Homeowner</th>
                        <th class="px-4 py-3">Due Date</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Paid</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($invoices as $invoice)
                        @php
                            $sale        = $invoice->sale;
                            $customer    = $sale?->opportunity?->parentCustomer?->company_name ?? '—';
                            $homeowner   = $sale?->homeowner_name;
                            $balanceDue  = $invoice->balance_due;
                            $isOverdue   = $invoice->status === 'overdue';
                            $isPaid      = $invoice->status === 'paid';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 font-medium">
                                @if ($sale)
                                    <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                @else
                                    {{ $invoice->invoice_number }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($sale)
                                    <a href="{{ route('pages.sales.show', $sale) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        #{{ $sale->sale_number }}
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $customer }}</div>
                                @if ($homeowner && $homeowner !== $customer)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $homeowner }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($invoice->due_date)
                                    <span @class(['text-red-600 font-medium' => $isOverdue])>
                                        {{ $invoice->due_date->format('M j, Y') }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($invoice->grand_total, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($invoice->amount_paid, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">
                                @if ($isPaid)
                                    <span style="color: #16a34a;">$0.00</span>
                                @elseif ($isOverdue)
                                    <span style="color: #dc2626;">${{ number_format($balanceDue, 2) }}</span>
                                @else
                                    ${{ number_format($balanceDue, 2) }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'draft'          => 'bg-gray-100 text-gray-700',
                                        'sent'           => 'bg-blue-100 text-blue-700',
                                        'partially_paid' => 'bg-yellow-100 text-yellow-700',
                                        'paid'           => 'bg-green-100 text-green-700',
                                        'overdue'        => 'bg-red-100 text-red-700',
                                    ];
                                    $statusLabels = [
                                        'draft'          => 'Draft',
                                        'sent'           => 'Sent',
                                        'partially_paid' => 'Partial',
                                        'paid'           => 'Paid',
                                        'overdue'        => 'Overdue',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$invoice->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
</div>
</x-app-layout>
