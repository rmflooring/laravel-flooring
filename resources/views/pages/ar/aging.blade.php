<x-app-layout>
<div class="py-6">
<div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AR Aging Report</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Unpaid invoices as of {{ $today->format('F j, Y') }}
            </p>
        </div>
        <a href="{{ route('pages.ar.index') }}"
           class="inline-flex items-center gap-2 py-2 px-4 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
            &larr; All Invoices
        </a>
    </div>

    {{-- Summary bar --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        @php
            $bucketColors = [
                'current' => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'text' => '#16a34a'],
                '1_30'    => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'text' => '#ca8a04'],
                '31_60'   => ['bg' => 'bg-orange-50 dark:bg-orange-900/20', 'text' => '#ea580c'],
                '61_90'   => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'text' => '#dc2626'],
                '90_plus' => ['bg' => 'bg-red-100 dark:bg-red-900/40', 'text' => '#991b1b'],
            ];
        @endphp
        @foreach ($buckets as $key => $bucket)
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow-sm {{ $bucketColors[$key]['bg'] }}">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $bucket['label'] }}</p>
                <p class="mt-1 text-xl font-bold" style="color: {{ $bucketColors[$key]['text'] }};">
                    ${{ number_format($bucket['total'], 2) }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $bucket['invoices']->count() }} invoice{{ $bucket['invoices']->count() !== 1 ? 's' : '' }}</p>
            </div>
        @endforeach
    </div>

    {{-- Grand total --}}
    <div class="text-right text-sm text-gray-600 dark:text-gray-400">
        Total outstanding: <span class="font-bold text-gray-900 dark:text-white">${{ number_format($grandTotal, 2) }}</span>
    </div>

    {{-- Aging buckets --}}
    @foreach ($buckets as $key => $bucket)
        @if ($bucket['invoices']->count())
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ $bucket['label'] }}</h2>
                    <span class="text-sm font-medium" style="color: {{ $bucketColors[$key]['text'] }};">
                        ${{ number_format($bucket['total'], 2) }}
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Invoice #</th>
                                <th class="px-4 py-3">Sale #</th>
                                <th class="px-4 py-3">Customer / Homeowner</th>
                                <th class="px-4 py-3">Due Date</th>
                                <th class="px-4 py-3">Days Overdue</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-right">Paid</th>
                                <th class="px-4 py-3 text-right">Balance Due</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($bucket['invoices'] as $invoice)
                                @php
                                    $sale      = $invoice->sale;
                                    $customer  = $sale?->opportunity?->parentCustomer?->company_name ?? '—';
                                    $homeowner = $sale?->homeowner_name;
                                    $daysOver  = $today->diffInDays($invoice->due_date, false) * -1;
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
                                        {{ $invoice->due_date->format('M j, Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($daysOver > 0)
                                            <span style="color: {{ $bucketColors[$key]['text'] }}; font-weight: 600;">
                                                {{ $daysOver }}d
                                            </span>
                                        @else
                                            <span class="text-green-600">On time</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($invoice->grand_total, 2) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">${{ number_format($invoice->amount_paid, 2) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap font-semibold"
                                        style="color: {{ $daysOver > 0 ? $bucketColors[$key]['text'] : 'inherit' }};">
                                        ${{ number_format($invoice->balance_due, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach

    @if ($grandTotal == 0)
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-8 text-center text-gray-500 dark:text-gray-400 shadow-sm">
            No outstanding invoices. You're all caught up!
        </div>
    @endif

</div>
</div>
</x-app-layout>
