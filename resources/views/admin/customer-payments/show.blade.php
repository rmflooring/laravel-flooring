<x-app-layout>
    @php
        $customerName = $payment->customer?->company_name ?: $payment->customer?->name;
        $onInvoices   = $payment->invoicePayments->filter(fn ($ip) => ! $ip->sale_payment_id);
        $credit       = $payment->credit;
        $money        = fn ($v) => '$' . number_format((float) $v, 2);
    @endphp

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center gap-4">
                <a href="{{ route('admin.customers.show', $payment->customer) }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ $customerName }}
                </a>
            </div>

            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Payment {{ $payment->payment_number }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        From {{ $customerName }} · recorded {{ $payment->created_at?->format('Y-m-d g:i A') }}
                        @if ($payment->recordedBy) by {{ $payment->recordedBy->name }} @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @can('edit invoices')
                        @if ($qboConnected)
                            <form method="POST" action="{{ route('admin.customer-payments.push-to-qbo', $payment) }}">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                    {{ $payment->qbo_id ? 'Re-sync to QuickBooks' : 'Push to QuickBooks' }}
                                </button>
                            </form>
                        @endif
                    @endcan
                    @can('delete invoices')
                        @if ($payment->can_be_deleted)
                            <form method="POST" action="{{ route('admin.customer-payments.destroy', $payment) }}"
                                  onsubmit="return confirm('Delete payment {{ $payment->payment_number }}? This removes every invoice payment, deposit and credit it created{{ $payment->qbo_id ? ', and deletes it in QuickBooks' : '' }}.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-200 rounded-lg hover:bg-red-50">
                                    Delete Payment
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>

            {{-- Details --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-base font-semibold text-gray-900">Payment Details</h2>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">{{ $money($payment->amount) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $payment->payment_date?->format('F j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Method</dt>
                            <dd class="mt-1 text-gray-900">{{ $payment->method_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</dt>
                            <dd class="mt-1 text-gray-900">{{ $payment->reference_number ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">QuickBooks</dt>
                            <dd class="mt-1 text-sm">
                                @if ($payment->qbo_last_error)
                                    <span class="text-red-700">Not synced: {{ $payment->qbo_last_error }}</span>
                                @elseif ($payment->qbo_id)
                                    <span class="text-green-700">Synced as one payment (QBO #{{ $payment->qbo_id }}) · {{ $payment->qbo_synced_at?->format('Y-m-d g:i A') }}</span>
                                @else
                                    <span class="text-gray-500">Not sent to QuickBooks yet.</span>
                                @endif
                            </dd>
                        </div>
                        @if ($payment->notes)
                            <div class="sm:col-span-2">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</dt>
                                <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $payment->notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Where the money went --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-base font-semibold text-gray-900">How it was applied</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Applied to</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($onInvoices as $ip)
                            <tr>
                                <td class="px-6 py-3">
                                    @if ($ip->invoice?->sale)
                                        <a href="{{ route('pages.sales.invoices.show', [$ip->invoice->sale, $ip->invoice]) }}" class="text-blue-700 hover:underline">Invoice {{ $ip->invoice->invoice_number }}</a>
                                        <span class="text-xs text-gray-400">(Sale #{{ $ip->invoice->sale->sale_number }})</span>
                                    @else
                                        Invoice {{ $ip->invoice?->invoice_number ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-600">
                                    {{ $ip->invoice?->status === 'voided' ? 'Invoice voided' : 'Invoice payment' }}
                                </td>
                                <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $money($ip->amount) }}</td>
                            </tr>
                        @endforeach

                        @foreach ($payment->deposits as $deposit)
                            @php $appliedTo = $deposit->invoicePayments->first(fn ($ip) => $ip->invoice && $ip->invoice->status !== 'voided')?->invoice; @endphp
                            <tr>
                                <td class="px-6 py-3">
                                    @if ($deposit->sale)
                                        <a href="{{ route('pages.sales.show', $deposit->sale) }}" class="text-blue-700 hover:underline">Sale #{{ $deposit->sale->sale_number }}</a>
                                    @else
                                        Sale —
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-600">
                                    @if ($appliedTo)
                                        Deposit · now on invoice {{ $appliedTo->invoice_number }}
                                    @else
                                        Deposit · not invoiced yet
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $money($deposit->amount) }}</td>
                            </tr>
                        @endforeach

                        @if ($credit)
                            <tr>
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.customer-credits.index', ['customer_id' => $payment->customer_id]) }}" class="text-blue-700 hover:underline">Credit {{ $credit->credit_number }}</a>
                                    <span class="text-xs text-gray-400">(left over)</span>
                                </td>
                                <td class="px-6 py-3 text-gray-600">
                                    {{ $money($credit->remaining_balance) }} still available
                                    @foreach ($credit->applications as $app)
                                        <div class="text-xs text-gray-500">
                                            {{ $money($app->amount) }}
                                            @if ($app->type === 'refund')
                                                refunded ({{ $app->method_label }})
                                            @elseif ($app->invoice)
                                                used on invoice {{ $app->invoice->invoice_number }}
                                            @elseif ($app->sale)
                                                used on sale #{{ $app->sale->sale_number }}
                                            @endif
                                            · {{ \Illuminate\Support\Carbon::parse($app->applied_date)->format('M j, Y') }}
                                        </div>
                                    @endforeach
                                </td>
                                <td class="px-6 py-3 text-right font-medium text-gray-900">{{ $money($credit->amount) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</td>
                            <td class="px-6 py-3 text-right font-bold text-gray-900">
                                {{ $money($onInvoices->sum('amount') + $payment->deposits->sum('amount') + ($credit?->amount ?? 0)) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @unless ($payment->can_be_deleted)
                <p class="text-xs text-gray-500">
                    This payment can't be deleted because part of it has already moved on (a deposit was invoiced, or the leftover credit was used).
                </p>
            @endunless
        </div>
    </div>
</x-app-layout>
