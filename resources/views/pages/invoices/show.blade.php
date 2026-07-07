<x-admin-layout>
<div class="py-6">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('pages.sales.show', $sale) }}"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Sale #{{ $sale->sale_number }}</a>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('pages.sales.invoices.pdf', [$sale, $invoice]) }}" target="_blank"
                class="inline-flex items-center gap-1.5 py-2 px-4 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2 4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V5a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4h10z"/>
                </svg>
                Print / PDF
            </a>
            @if($invoice->status !== 'voided')
                <a href="{{ route('pages.sales.invoices.edit', [$sale, $invoice]) }}"
                    class="inline-flex items-center gap-1.5 py-2 px-4 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Edit Invoice
                </a>
                {{-- Push to QBO --}}
                @if (app(\App\Services\QuickBooksService::class)->isConnected())
                    <form method="POST" action="{{ route('pages.sales.invoices.push-to-qbo', [$sale, $invoice]) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 py-2 px-4 text-sm font-medium rounded-lg border transition
                                    {{ $invoice->qbo_id
                                        ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            {{ $invoice->qbo_id ? 'Re-sync to QBO' : 'Push to QBO' }}
                        </button>
                    </form>
                @endif
                {{-- Send email button --}}
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-send-invoice-modal'))"
                    class="inline-flex items-center gap-1.5 py-2 px-4 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Send Email
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
    @endif

    {{-- Invoice header card --}}
    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-wrap gap-6 justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoice {{ $invoice->invoice_number }}</h1>
                    @php
                        $badgeClass = match($invoice->status) {
                            'draft'          => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            'sent'           => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300',
                            'paid'           => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'overdue'        => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            'partially_paid' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
                            'voided'         => 'bg-red-200 text-red-900 dark:bg-red-900 dark:text-red-200',
                            default          => 'bg-gray-100 text-gray-700',
                        };
                        $statusLabel = match($invoice->status) {
                            'draft'          => 'Draft',
                            'sent'           => 'Sent',
                            'paid'           => 'Paid',
                            'overdue'        => 'Overdue',
                            'partially_paid' => 'Partially Paid',
                            'voided'         => 'Voided',
                            default          => ucfirst($invoice->status),
                        };
                    @endphp
                    <span class="text-xs font-semibold px-2.5 py-1 rounded {{ $badgeClass }}">{{ $statusLabel }}</span>
                    @if($invoice->sent_at)
                        <button type="button"
                                onclick="window.dispatchEvent(new Event('open-sent-email-modal'))"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 underline decoration-dotted underline-offset-2 cursor-pointer hover:opacity-80">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Emailed {{ $invoice->sent_at->format('M j') }}
                        </button>
                    @endif
                    @if(!empty($openedAt))
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                              title="Recipient opened the email on {{ \Carbon\Carbon::parse($openedAt)->format('M j, Y g:i a') }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Opened {{ \Carbon\Carbon::parse($openedAt)->format('M j') }}
                        </span>
                    @endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400 space-y-0.5">
                    <div>Sale #{{ $sale->sale_number }}
                        @if($sale->job_name) &mdash; {{ $sale->job_name }}@endif
                    </div>
                    @if($invoice->paymentTerm)
                        <div>Payment Terms: <span class="text-gray-700 dark:text-gray-300">{{ $invoice->paymentTerm->name }}</span></div>
                    @endif
                    @if($invoice->due_date)
                        <div>Due: <span class="text-gray-700 dark:text-gray-300 {{ $invoice->due_date->isPast() && !in_array($invoice->status, ['paid','voided']) ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                            {{ $invoice->due_date->format('M j, Y') }}
                        </span></div>
                    @endif
                    @if($invoice->customer_po_number)
                        <div>Customer PO#: <span class="text-gray-700 dark:text-gray-300">{{ $invoice->customer_po_number }}</span></div>
                    @endif
                </div>
                @if($invoice->voided_at)
                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">
                        Voided {{ $invoice->voided_at->format('M j, Y') }}
                        @if($invoice->void_reason) &mdash; {{ $invoice->void_reason }}@endif
                    </div>
                @endif
            </div>
            {{-- Financial summary --}}
            <div class="text-right space-y-1">
                <div class="text-sm text-gray-500">Subtotal: <span class="text-gray-900 dark:text-white font-medium">${{ number_format((float)$invoice->subtotal, 2) }}</span></div>
                @if($taxRates->isNotEmpty())
                    @foreach($taxRates as $taxRate)
                        @php $lineAmt = round((float)$invoice->subtotal * ((float)$taxRate->sales_rate / 100), 2); @endphp
                        <div class="text-sm text-gray-500">{{ $taxRate->name }} ({{ number_format((float)$taxRate->sales_rate, 0) }}%): <span class="text-gray-900 dark:text-white font-medium">${{ number_format($lineAmt, 2) }}</span></div>
                    @endforeach
                @else
                    <div class="text-sm text-gray-500">Tax: <span class="text-gray-900 dark:text-white font-medium">${{ number_format((float)$invoice->tax_amount, 2) }}</span></div>
                @endif
                <div class="text-lg font-bold text-gray-900 dark:text-white">Total: ${{ number_format((float)$invoice->grand_total, 2) }}</div>
                <div class="text-sm text-gray-500">Paid: <span class="text-green-600 dark:text-green-400 font-medium">${{ number_format((float)$invoice->amount_paid, 2) }}</span></div>
                @if($invoice->balance_due > 0 && $invoice->status !== 'voided')
                    <div class="text-sm font-semibold text-red-600 dark:text-red-400">Balance Due: ${{ number_format($invoice->balance_due, 2) }}</div>
                @endif
            </div>
        </div>

        {{-- Customer info --}}
        @php
            $parentCustomer  = $sale->opportunity?->parentCustomer;
            $jobSiteCustomer = $sale->opportunity?->jobSiteCustomer;
        @endphp
        @if($parentCustomer || $jobSiteCustomer || $sale->opportunity)
            <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-600 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                @if($invoice->bill_to_name || $parentCustomer)
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">
                            Bill To
                            @if($invoice->bill_to_name)
                                <span class="ml-1 normal-case font-normal text-amber-600 dark:text-amber-400">(overridden)</span>
                            @endif
                        </div>
                        @if($invoice->bill_to_name)
                            <div class="font-medium text-gray-900 dark:text-white">{{ $invoice->bill_to_name }}</div>
                            @if($invoice->bill_to_address)
                                <div class="text-gray-500 dark:text-gray-400">{{ $invoice->bill_to_address }}</div>
                            @endif
                            @if($invoice->bill_to_email)
                                <div class="text-gray-500 dark:text-gray-400">{{ $invoice->bill_to_email }}</div>
                            @endif
                        @elseif($parentCustomer)
                            <div class="font-medium text-gray-900 dark:text-white">{{ $parentCustomer->company_name ?: $parentCustomer->name }}</div>
                            @if($parentCustomer->company_name && $parentCustomer->name !== $parentCustomer->company_name)
                                <div class="text-gray-500 dark:text-gray-400">{{ $parentCustomer->name }}</div>
                            @endif
                            @if($parentCustomer->email)
                                <div class="text-gray-500 dark:text-gray-400">{{ $parentCustomer->email }}</div>
                            @endif
                            @if($parentCustomer->phone)
                                <div class="text-gray-500 dark:text-gray-400">{{ $parentCustomer->phone }}</div>
                            @endif
                        @endif
                    </div>
                @endif
                @if($jobSiteCustomer)
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Job Site</div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $jobSiteCustomer->name }}</div>
                        @if($sale->job_address)
                            <div class="text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $sale->job_address }}</div>
                        @endif
                        @if($sale->job_phone)
                            <div class="text-gray-500 dark:text-gray-400">{{ $sale->job_phone }}</div>
                        @endif
                        @if($sale->job_email)
                            <div class="text-gray-500 dark:text-gray-400">{{ $sale->job_email }}</div>
                        @endif
                    </div>
                @endif
                @if($sale->opportunity)
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-1">Job Info</div>
                        @if($sale->opportunity->job_no)
                            <div class="font-medium text-gray-900 dark:text-white">Job #{{ $sale->opportunity->job_no }}</div>
                        @endif
                        @if($sale->job_name)
                            <div class="text-gray-500 dark:text-gray-400">{{ $sale->job_name }}</div>
                        @endif
                        @if($sale->opportunity->projectManager)
                            <div class="text-gray-500 dark:text-gray-400">PM: {{ $sale->opportunity->projectManager->name }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if($invoice->notes)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600 text-sm text-gray-600 dark:text-gray-400">
                {{ $invoice->notes }}
            </div>
        @endif
    </div>

    {{-- Line items --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Line Items</h2>
        </div>
        @foreach ($invoice->rooms as $room)
            {{-- Room header --}}
            <div class="flex items-center justify-between px-5 py-2.5 bg-blue-700 text-white text-sm font-semibold">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.5L12 4l9 5.5V20H3V9.5z"/>
                    </svg>
                    {{ $room->name }}
                </div>
                <span class="font-normal text-blue-100">${{ number_format($room->subtotal, 2) }}</span>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <th class="px-5 py-2 text-left">Item</th>
                        <th class="px-5 py-2 text-center w-24">Qty</th>
                        <th class="px-5 py-2 text-right w-28">Unit Price</th>
                        <th class="px-5 py-2 text-right w-28">Tax</th>
                        <th class="px-5 py-2 text-right w-28">Line Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($room->items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $item->label }}</div>
                                <div class="text-xs text-gray-400">{{ ucfirst($item->item_type) }}{{ $item->unit ? ' · ' . strtoupper($item->unit) : '' }}</div>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-700 dark:text-gray-300">{{ number_format((float)$item->quantity, 2) }}</td>
                            <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">${{ number_format((float)$item->sell_price, 2) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">${{ number_format((float)$item->tax_amount, 2) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-white">${{ number_format((float)$item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                    {{-- Room subtotal --}}
                    <tr class="bg-gray-50 dark:bg-gray-700 text-sm">
                        <td colspan="4" class="px-5 py-2 text-right font-medium text-gray-600 dark:text-gray-300">Room Subtotal</td>
                        <td class="px-5 py-2 text-right font-bold text-gray-900 dark:text-white">${{ number_format($room->subtotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        {{-- Grand total footer --}}
        <div class="px-5 py-4 border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 flex flex-col items-end gap-1">
            <div class="flex gap-8 text-sm text-gray-600 dark:text-gray-300">
                <span>Subtotal</span>
                <span class="w-28 text-right">${{ number_format((float)$invoice->subtotal, 2) }}</span>
            </div>
            @if($taxRates->isNotEmpty())
                @foreach($taxRates as $taxRate)
                    @php $lineAmt = round((float)$invoice->subtotal * ((float)$taxRate->sales_rate / 100), 2); @endphp
                    <div class="flex gap-8 text-sm text-gray-600 dark:text-gray-300">
                        <span>{{ $taxRate->name }} ({{ number_format((float)$taxRate->sales_rate, 0) }}%)</span>
                        <span class="w-28 text-right">${{ number_format($lineAmt, 2) }}</span>
                    </div>
                @endforeach
            @else
            <div class="flex gap-8 text-sm text-gray-600 dark:text-gray-300">
                <span>Tax</span>
                <span class="w-28 text-right">${{ number_format((float)$invoice->tax_amount, 2) }}</span>
            </div>
            @endif
            <div class="flex gap-8 text-base font-bold text-gray-900 dark:text-white border-t border-gray-300 dark:border-gray-500 pt-2 mt-1">
                <span>Total</span>
                <span class="w-28 text-right">${{ number_format((float)$invoice->grand_total, 2) }}</span>
            </div>
            @if((float)$invoice->amount_paid > 0)
            <div class="flex gap-8 text-sm text-green-700 dark:text-green-400 border-t border-dashed border-gray-300 dark:border-gray-500 pt-2 mt-1">
                <span>Amount Paid</span>
                <span class="w-28 text-right font-semibold">−${{ number_format((float)$invoice->amount_paid, 2) }}</span>
            </div>
            <div class="flex gap-8 text-sm font-bold border-t border-gray-300 dark:border-gray-500 pt-1 {{ $invoice->balance_due > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}">
                <span>Balance Due</span>
                <span class="w-28 text-right">${{ number_format(max(0, (float)$invoice->balance_due), 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Payments --}}
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Payments</h2>
            @if($invoice->status !== 'voided' && $invoice->status !== 'paid')
                <button type="button" onclick="document.getElementById('add-payment-modal').classList.remove('hidden')"
                    class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">+ Add Payment</button>
            @endif
        </div>

        @if($invoice->payments->isEmpty())
            <div class="px-5 py-6 text-sm text-gray-400">No payments recorded yet.</div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs uppercase text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <th class="px-5 py-2 text-left">Date</th>
                        <th class="px-5 py-2 text-left">Method</th>
                        <th class="px-5 py-2 text-left">Reference</th>
                        <th class="px-5 py-2 text-left">Notes</th>
                        <th class="px-5 py-2 text-right">Amount</th>
                        <th class="px-5 py-2 w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($invoice->payments as $payment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $payment->payment_date->format('M j, Y') }}</td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                {{ $payment->method_label }}
                                @if($payment->sale_payment_id)
                                    <span class="ml-1.5 inline-flex items-center text-xs font-medium px-1.5 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Deposit</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $payment->reference_number ?: '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $payment->notes ?: '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-green-700 dark:text-green-400">${{ number_format((float)$payment->amount, 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if(app(\App\Services\QuickBooksService::class)->isConnected())
                                        @if($payment->qbo_id)
                                            <span class="text-xs text-green-600 dark:text-green-400" title="Synced to QBO {{ $payment->qbo_synced_at?->format('M j, Y') }}">QBO ✓</span>
                                        @else
                                            <form method="POST" action="{{ route('pages.sales.invoices.payments.push-to-qbo', [$sale, $invoice, $payment]) }}">
                                                @csrf
                                                <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 underline">Push to QBO</button>
                                            </form>
                                        @endif
                                    @endif
                                    @role('admin')
                                        <a href="{{ route('admin.payments.show', $payment) }}"
                                           class="text-xs text-blue-600 hover:underline">View</a>
                                    @endrole
                                    @if(! $payment->sale_payment_id)
                                        <form action="{{ route('pages.sales.invoices.payments.destroy', [$sale, $invoice, $payment]) }}" method="POST"
                                            onsubmit="return confirm('Remove this payment?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:underline">Remove</button>
                                        </form>
                                    @else
                                        <a href="{{ route('pages.sales.show', $sale) }}#deposits"
                                           class="text-xs text-gray-400 hover:underline" title="Manage from the sale page">From sale</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 dark:bg-gray-700 font-semibold text-sm">
                        <td colspan="4" class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">Total Paid</td>
                        <td class="px-5 py-3 text-right text-green-700 dark:text-green-400">${{ number_format((float)$invoice->amount_paid, 2) }}</td>
                        <td></td>
                    </tr>
                    @if($invoice->balance_due > 0 && $invoice->status !== 'voided')
                        <tr class="bg-red-50 dark:bg-red-900/20 font-semibold text-sm">
                            <td colspan="4" class="px-5 py-3 text-right text-red-700 dark:text-red-400">Balance Due</td>
                            <td class="px-5 py-3 text-right text-red-700 dark:text-red-400">${{ number_format($invoice->balance_due, 2) }}</td>
                            <td></td>
                        </tr>
                    @endif
                </tfoot>
            </table>
        @endif
    </div>

    {{-- QuickBooks status --}}
    @if (app(\App\Services\QuickBooksService::class)->isConnected())
    <div class="bg-white border border-gray-200 rounded-lg p-4 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">QuickBooks Online</h3>
        @if ($invoice->qbo_id)
            <div class="flex items-center gap-3 text-sm">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Synced</span>
                <span class="text-gray-500">QBO ID: <span class="font-mono">{{ $invoice->qbo_id }}</span></span>
                <span class="text-gray-400">· {{ $invoice->qbo_synced_at?->format('M j, Y g:i a') }}</span>
            </div>
        @else
            <p class="text-sm text-gray-500">Not yet synced to QuickBooks. Use the "Push to QBO" button above.</p>
        @endif
    </div>
    @endif

    {{-- Void section --}}
    @if($invoice->status !== 'voided')
        <div class="p-4 border border-red-200 rounded-lg bg-red-50 dark:bg-gray-800 dark:border-red-900 flex items-center justify-between gap-4">
            <div class="text-sm text-red-700 dark:text-red-400">
                <span class="font-semibold">Void Invoice</span> — This cannot be undone. The invoice will be removed from the sale's invoiced total.
            </div>
            <button type="button" onclick="document.getElementById('void-modal').classList.remove('hidden')"
                class="text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg px-4 py-2 flex-shrink-0">
                Void Invoice
            </button>
        </div>
    @endif

</div>
</div>

{{-- Add Payment Modal --}}
<div id="add-payment-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Record Payment</h3>
            <button type="button" onclick="document.getElementById('add-payment-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('pages.sales.invoices.payments.store', [$sale, $invoice]) }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Amount <span class="text-red-500">*</span></label>
                    <input type="text" inputmode="decimal" name="amount"
                        value="{{ number_format($invoice->balance_due, 2) }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        onblur="if(this.value!==''&&!isNaN(parseFloat(this.value)))this.value=parseFloat(this.value).toFixed(2)"
                        required>
                </div>
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Date <span class="text-red-500">*</span></label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        required>
                </div>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Payment Method <span class="text-red-500">*</span></label>
                <select name="payment_method"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    required>
                    @foreach($paymentMethods as $value => $label)
                        <option value="{{ $value }}" {{ $value === 'e-transfer' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Reference # <span class="text-gray-400 font-normal">(cheque, transaction ID...)</span></label>
                <input type="text" name="reference_number"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                <textarea name="notes" rows="2"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="flex-1 text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Record Payment
                </button>
                <button type="button" onclick="document.getElementById('add-payment-modal').classList.add('hidden')"
                    class="flex-1 py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Void Modal --}}
<div id="void-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Void Invoice {{ $invoice->invoice_number }}</h3>
            <button type="button" onclick="document.getElementById('void-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('pages.sales.invoices.void', [$sale, $invoice]) }}" method="POST" class="p-5 space-y-4">
            @csrf
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This invoice will be marked as voided and removed from the sale's invoiced total. This cannot be undone.
            </p>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Reason (optional)</label>
                <textarea name="void_reason" rows="2"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="flex-1 text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-5 py-2.5">
                    Void Invoice
                </button>
                <button type="button" onclick="document.getElementById('void-modal').classList.add('hidden')"
                    class="flex-1 py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Send Email Modal --}}
<div x-data="{
        open: false,
        toEmail: '{{ $homeownerEmail }}',
        customTo: '',
        selected: '{{ $homeownerEmail ? 'jobsite' : 'custom' }}',
        get finalTo() { return this.selected === 'custom' ? this.customTo : this.toEmail; },
        select(val, email) { this.selected = val; this.toEmail = email; }
     }"
     @open-send-invoice-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.5)">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" @click.outside="open = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h5 class="text-base font-semibold text-gray-800">Send Invoice</h5>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('pages.sales.invoices.send-email', [$sale, $invoice]) }}" enctype="multipart/form-data">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                {{-- To --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @if($homeownerEmail)
                            <button type="button"
                                    @click="select('jobsite', '{{ $homeownerEmail }}')"
                                    :class="selected === 'jobsite' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Job Site — {{ $homeownerEmail }}
                            </button>
                        @endif
                        @if(!empty($pmEmail))
                            <button type="button"
                                    @click="select('pm', '{{ $pmEmail }}')"
                                    :class="selected === 'pm' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                PM — {{ $pmEmail }}
                            </button>
                        @endif
                        <button type="button"
                                @click="select('custom', ''); $nextTick(() => $refs.customToInput.focus())"
                                :class="selected === 'custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Custom
                        </button>
                    </div>
                    <template x-if="selected !== 'custom'">
                        <div class="w-full bg-gray-100 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-700" x-text="toEmail"></div>
                    </template>
                    <template x-if="selected === 'custom'">
                        <input type="email" x-ref="customToInput" x-model="customTo"
                               placeholder="Enter email address"
                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                    </template>
                    <input type="hidden" name="to" :value="finalTo">
                    @if(!$homeownerEmail && empty($pmEmail))
                        <p class="mt-1.5 text-xs text-yellow-700">No job site or PM email on this sale. Use Custom to enter a recipient.</p>
                    @endif
                </div>

                {{-- CC --}}
                <div x-data="{ ccEmails: [], ccInput: '' }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CC <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                    @if($customerContacts->isNotEmpty())
                    <div class="mb-2">
                        <p class="text-xs text-gray-500 mb-1.5">Quick-add from contacts:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($customerContacts->filter(fn($c) => $c->email) as $contact)
                            <button type="button"
                                    @click="if(!ccEmails.includes('{{ $contact->email }}')) { ccEmails.push('{{ $contact->email }}') }"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full border border-gray-300 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors">
                                {{ $contact->name }}@if($contact->title) <span class="text-gray-400">&middot; {{ $contact->title }}</span>@endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="ccEmails.length > 0">
                        <template x-for="(email, i) in ccEmails" :key="i">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">
                                <span x-text="email"></span>
                                <input type="hidden" name="cc[]" :value="email">
                                <button type="button" @click="ccEmails.splice(i, 1)" class="text-blue-400 hover:text-blue-600 leading-none ml-1">&times;</button>
                            </span>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="email" x-model="ccInput"
                               @keydown.enter.prevent="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                               placeholder="cc@example.com"
                               class="flex-1 bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <button type="button"
                                @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                            Add
                        </button>
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" value="{{ $emailSubject }}"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>

                {{-- Body --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="body" rows="10"
                              class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-mono">{{ $emailBody }}</textarea>
                </div>

                {{-- Attachment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <a href="{{ route('pages.sales.invoices.pdf', [$sale, $invoice]) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-colors">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        <span>Invoice-{{ $invoice->invoice_number }}.pdf</span>
                        <span class="text-xs text-gray-400 ml-1">— click to preview</span>
                    </a>
                </div>

                {{-- Extra Attachments --}}
                <div x-data="{
                        extraFiles: [],
                        onSelect(event) {
                            this.extraFiles = [...this.extraFiles, ...Array.from(event.target.files)];
                            const dt = new DataTransfer();
                            this.extraFiles.forEach(f => dt.items.add(f));
                            this.$refs.fileInput.files = dt.files;
                            event.target.value = '';
                        },
                        remove(idx) {
                            this.extraFiles.splice(idx, 1);
                            const dt = new DataTransfer();
                            this.extraFiles.forEach(f => dt.items.add(f));
                            this.$refs.fileInput.files = dt.files;
                        },
                        formatSize(bytes) {
                            return bytes < 1048576 ? (bytes / 1024).toFixed(0) + ' KB' : (bytes / 1048576).toFixed(1) + ' MB';
                        }
                    }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Additional Attachments <span class="text-xs text-gray-400 font-normal">(optional, max 3 MB each)</span>
                    </label>
                    <input type="file" x-ref="fileInput" name="attachments[]" multiple class="hidden" @change="onSelect($event)">
                    <button type="button" @click="$refs.fileInput.click()"
                            class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-100 hover:border-gray-400 transition-colors">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        Attach files...
                    </button>
                    <div x-show="extraFiles.length > 0" class="mt-2 space-y-1.5">
                        <template x-for="(file, idx) in extraFiles" :key="idx">
                            <div class="flex items-center gap-2 px-2.5 py-1.5 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-700">
                                <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="flex-1 truncate" x-text="file.name"></span>
                                <span class="text-gray-400 flex-shrink-0" x-text="formatSize(file.size)"></span>
                                <button type="button" @click="remove(idx)" class="text-gray-400 hover:text-red-500 transition-colors leading-none ml-1">&times;</button>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Read receipt --}}
                <div class="flex items-center gap-2 py-1">
                    <input type="checkbox" name="request_read_receipt" id="rr_invoice" value="1" checked
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="rr_invoice" class="text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                        Request read receipt
                        <span class="text-xs text-gray-400">(tracking pixel + Outlook read request)</span>
                    </label>
                </div>

                <p class="text-xs text-gray-400">
                    @if(auth()->user()->microsoftAccount?->mail_connected)
                        Sending from <strong>{{ auth()->user()->microsoftAccount->email }}</strong> via your personal MS365 account (Track 2).
                    @else
                        Sending from the shared mailbox via Track 1.
                    @endif
                </p>

            </div>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">
                    Send Invoice
                </button>
            </div>
        </form>
    </div>
</div>


<x-modals.sent-email-modal type="invoice" :related-id="$invoice->id" />
</x-admin-layout>
