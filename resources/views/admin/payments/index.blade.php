<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Payments Received</h1>
                    <p class="text-sm text-gray-600 mt-1">All invoice payments recorded across all sales.</p>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.payments.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                    {{-- Search --}}
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" value="{{ $q }}"
                                   placeholder="Invoice #, sale #, customer, job, reference #..."
                                   class="block w-full pl-10 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Method</label>
                        <select name="method"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Methods</option>
                            @foreach ($paymentMethods as $key => $label)
                                <option value="{{ $key }}" @selected($method === $key)>{{ $label }}</option>
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
                    <div class="md:col-span-2 flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                            Filter
                        </button>
                        <a href="{{ route('admin.payments.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            Reset
                        </a>
                    </div>

                    {{-- Results summary --}}
                    <div class="md:col-span-12 flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-gray-100">
                        <span class="text-sm text-gray-600">
                            Showing <span class="font-semibold">{{ $payments->firstItem() ?? 0 }}</span>
                            to <span class="font-semibold">{{ $payments->lastItem() ?? 0 }}</span>
                            of <span class="font-semibold">{{ $payments->total() }}</span> payments
                        </span>
                        <span class="text-sm font-semibold text-gray-800">
                            Filtered Total: ${{ number_format($totalAmount, 2) }}
                        </span>
                    </div>

                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3">
                                    @include('admin.partials.sort-link', ['label' => 'Date', 'field' => 'payment_date'])
                                </th>
                                <th class="px-6 py-3">Invoice</th>
                                <th class="px-6 py-3">Sale</th>
                                <th class="px-6 py-3">Customer / Job</th>
                                <th class="px-6 py-3 text-right">
                                    @include('admin.partials.sort-link', ['label' => 'Amount', 'field' => 'amount'])
                                </th>
                                <th class="px-6 py-3">
                                    @include('admin.partials.sort-link', ['label' => 'Method', 'field' => 'payment_method'])
                                </th>
                                <th class="px-6 py-3">Reference #</th>
                                <th class="px-6 py-3">Recorded By</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $payment)
                                @php
                                    $invoice = $payment->invoice;
                                    $sale    = $invoice?->sale;

                                    $methodBadge = match ($payment->payment_method) {
                                        'cash'        => 'bg-green-100 text-green-800',
                                        'cheque'      => 'bg-blue-100 text-blue-800',
                                        'e-transfer'  => 'bg-purple-100 text-purple-800',
                                        'credit_card' => 'bg-amber-100 text-amber-800',
                                        default       => 'bg-gray-100 text-gray-800',
                                    };
                                @endphp
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        {{ $payment->payment_date?->format('Y-m-d') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($invoice)
                                            <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
                                               class="text-blue-600 hover:underline font-medium">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                            @php
                                                $statusBadge = match ($invoice->status) {
                                                    'paid'           => 'bg-green-100 text-green-800',
                                                    'partially_paid' => 'bg-blue-100 text-blue-800',
                                                    'overdue'        => 'bg-red-100 text-red-800',
                                                    'voided'         => 'bg-gray-100 text-gray-500',
                                                    default          => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $statusBadge }}">
                                                {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($sale)
                                            <a href="{{ route('pages.sales.show', $sale) }}"
                                               class="text-blue-600 hover:underline font-medium">
                                                {{ $sale->sale_number }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($sale)
                                            <div class="font-medium text-gray-900">{{ $sale->homeowner_name ?: $sale->job_name }}</div>
                                            @if ($sale->homeowner_name && $sale->job_name)
                                                <div class="text-xs text-gray-500">{{ $sale->job_name }}</div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                        ${{ number_format($payment->amount, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $methodBadge }}">
                                            {{ $payment->method_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $payment->reference_number ?: '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $payment->recordedBy?->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.payments.show', $payment) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                        No payments found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t bg-white">
                    {{ $payments->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
