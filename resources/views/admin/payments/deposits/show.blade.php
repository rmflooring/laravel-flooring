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

            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Deposit Detail</h1>
                    <p class="text-sm text-gray-500 mt-1">Recorded {{ $deposit->created_at?->format('Y-m-d g:i A') }}</p>
                </div>
                @if (! $deposit->is_applied)
                    <a href="{{ route('admin.payments.deposits.edit', $deposit) }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        Edit Deposit
                    </a>
                @endif
                @php
                    $methodBadge = match ($deposit->payment_method) {
                        'cash'        => 'bg-green-100 text-green-800',
                        'cheque'      => 'bg-blue-100 text-blue-800',
                        'e-transfer'  => 'bg-purple-100 text-purple-800',
                        default       => 'bg-gray-100 text-gray-800',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold {{ $methodBadge }}">
                    {{ $deposit->method_label }}
                </span>
            </div>

            @if ($deposit->is_applied)
                <div class="p-4 text-blue-800 bg-blue-50 border border-blue-200 rounded-lg text-sm">
                    This deposit has been applied to an invoice and can no longer be edited.
                </div>
            @endif

            {{-- Deposit Details Card --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-base font-semibold text-gray-900">Deposit Details</h2>
                </div>
                <div class="px-6 py-5">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</dt>
                            <dd class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($deposit->amount, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $deposit->payment_date?->format('F j, Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</dt>
                            <dd class="mt-1 text-gray-900">{{ $deposit->method_label }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Reference Number</dt>
                            <dd class="mt-1 text-gray-900">{{ $deposit->reference_number ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Payer</dt>
                            <dd class="mt-1 text-gray-900">
                                {{ $deposit->payerCustomer?->company_name ?: $deposit->payerCustomer?->name ?: '—' }}
                                @if ($deposit->payer_type)
                                    <span class="ml-1 text-xs text-gray-400">({{ $deposit->payer_label }})</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</dt>
                            <dd class="mt-1">
                                @if ($deposit->appliedInvoice)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                        Applied to
                                        @if ($deposit->sale)
                                            <a href="{{ route('pages.sales.invoices.show', [$deposit->sale, $deposit->appliedInvoice]) }}"
                                               class="underline">{{ $deposit->appliedInvoice->invoice_number }}</a>
                                        @else
                                            {{ $deposit->appliedInvoice->invoice_number }}
                                        @endif
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                        Pending
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</dt>
                            <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $deposit->notes ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</dt>
                            <dd class="mt-1 text-gray-900">{{ $deposit->recordedBy?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded At</dt>
                            <dd class="mt-1 text-gray-900">{{ $deposit->created_at?->format('Y-m-d g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Sale Context --}}
            @if ($deposit->sale)
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b bg-gray-50 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900">Sale</h2>
                        <a href="{{ route('pages.sales.show', $deposit->sale) }}"
                           class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            View Sale
                        </a>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Sale #</dt>
                                <dd class="mt-1 font-semibold text-gray-900">{{ $deposit->sale->sale_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Job</dt>
                                <dd class="mt-1 text-gray-900">{{ $deposit->sale->job_name ?: '—' }}</dd>
                            </div>
                            @if ($deposit->sale->homeowner_name)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Customer / Homeowner</dt>
                                    <dd class="mt-1 text-gray-900">{{ $deposit->sale->homeowner_name }}</dd>
                                </div>
                            @endif
                            @if ($deposit->sale->job_address)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Job Address</dt>
                                    <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $deposit->sale->job_address }}</dd>
                                </div>
                            @endif
                            @if ($deposit->sale->opportunity?->customer)
                                <div>
                                    <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Account</dt>
                                    <dd class="mt-1 text-gray-900">{{ $deposit->sale->opportunity->customer->name }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
