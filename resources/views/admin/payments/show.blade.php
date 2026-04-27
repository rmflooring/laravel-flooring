<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back + Header --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.payments.index') }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Payments
                </a>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Payment Detail</h1>
                    <p class="text-sm text-gray-500 mt-1">Recorded {{ $payment->created_at?->format('Y-m-d g:i A') }}</p>
                </div>
                @php
                    $methodBadge = match ($payment->payment_method) {
                        'cash'        => 'bg-green-100 text-green-800',
                        'cheque'      => 'bg-blue-100 text-blue-800',
                        'e-transfer'  => 'bg-purple-100 text-purple-800',
                        'credit_card' => 'bg-amber-100 text-amber-800',
                        default       => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $methodBadge }}">
                    {{ $payment->method_label }}
                </span>
            </div>

            {{-- Payment Details Card --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-base font-semibold text-gray-900">Payment Details</h2>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($payment->amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $payment->payment_date?->format('F j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</dt>
                            <dd class="mt-1 text-gray-900">{{ $payment->method_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</dt>
                            <dd class="mt-1 text-gray-900">{{ $payment->reference_number ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</dt>
                            <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $payment->notes ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</dt>
                            <dd class="mt-1 text-gray-900">{{ $payment->recordedBy?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded At</dt>
                            <dd class="mt-1 text-gray-900">{{ $payment->created_at?->format('Y-m-d g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Invoice Context --}}
            @php $invoice = $payment->invoice; @endphp
            @if ($invoice)
                @php
                    $invoiceStatusBadge = match ($invoice->status) {
                        'paid'           => 'bg-green-100 text-green-800',
                        'partially_paid' => 'bg-blue-100 text-blue-800',
                        'overdue'        => 'bg-red-100 text-red-800',
                        'voided'         => 'bg-gray-100 text-gray-500',
                        'sent'           => 'bg-sky-100 text-sky-800',
                        default          => 'bg-gray-100 text-gray-700',
                    };
                    $sale = $invoice->sale;
                @endphp
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Invoice</h2>
                        @if ($sale)
                            <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
                               class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                View Invoice
                            </a>
                        @endif
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-8 gap-y-4">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $invoice->invoice_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $invoiceStatusBadge }}">
                                        {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</dt>
                                <dd class="mt-1 text-gray-900">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Total</dt>
                                <dd class="mt-1 font-semibold text-gray-900">${{ number_format($invoice->grand_total, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Paid</dt>
                                <dd class="mt-1 font-semibold text-green-700">${{ number_format($invoice->amount_paid, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Balance Due</dt>
                                @php $balance = (float) $invoice->grand_total - (float) $invoice->amount_paid; @endphp
                                <dd class="mt-1 font-semibold {{ $balance > 0 ? 'text-red-700' : 'text-green-700' }}">
                                    ${{ number_format($balance, 2) }}
                                </dd>
                            </div>
                            @if ($invoice->paymentTerm)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Terms</dt>
                                    <dd class="mt-1 text-gray-900">{{ $invoice->paymentTerm->name }}</dd>
                                </div>
                            @endif
                            @if ($invoice->customer_po_number)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Customer PO #</dt>
                                    <dd class="mt-1 text-gray-900">{{ $invoice->customer_po_number }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Sale Context --}}
                @if ($sale)
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-900">Sale</h2>
                            <a href="{{ route('pages.sales.show', $sale) }}"
                               class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                View Sale
                            </a>
                        </div>
                        <div class="px-6 py-5">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sale #</dt>
                                    <dd class="mt-1 font-semibold text-gray-900">{{ $sale->sale_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Job</dt>
                                    <dd class="mt-1 text-gray-900">{{ $sale->job_name ?: '—' }}</dd>
                                </div>
                                @if ($sale->homeowner_name)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / Homeowner</dt>
                                        <dd class="mt-1 text-gray-900">{{ $sale->homeowner_name }}</dd>
                                    </div>
                                @endif
                                @if ($sale->job_address)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Job Address</dt>
                                        <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $sale->job_address }}</dd>
                                    </div>
                                @endif
                                @if ($sale->opportunity?->customer)
                                    <div>
                                        <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Account</dt>
                                        <dd class="mt-1 text-gray-900">{{ $sale->opportunity->customer->name }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endif
            @endif

        </div>
    </div>
</x-app-layout>
