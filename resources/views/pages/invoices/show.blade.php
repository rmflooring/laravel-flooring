<x-app-layout>
<div class="py-8">
<div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                {{-- Send email button --}}
                <button type="button" onclick="document.getElementById('send-modal').classList.remove('hidden')"
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
                    @if($invoice->sent_at)
                        <div>Sent:
                            <button type="button"
                                    @click="window.dispatchEvent(new Event('open-sent-email-modal'))"
                                    class="inline-flex items-center gap-1 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 underline decoration-dotted underline-offset-2 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $invoice->sent_at->format('M j, Y') }}
                            </button>
                        </div>
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
                <div class="text-sm text-gray-500">Tax: <span class="text-gray-900 dark:text-white font-medium">${{ number_format((float)$invoice->tax_amount, 2) }}</span></div>
                <div class="text-lg font-bold text-gray-900 dark:text-white">Total: ${{ number_format((float)$invoice->grand_total, 2) }}</div>
                <div class="text-sm text-gray-500">Paid: <span class="text-green-600 dark:text-green-400 font-medium">${{ number_format((float)$invoice->amount_paid, 2) }}</span></div>
                @if($invoice->balance_due > 0 && $invoice->status !== 'voided')
                    <div class="text-sm font-semibold text-red-600 dark:text-red-400">Balance Due: ${{ number_format($invoice->balance_due, 2) }}</div>
                @endif
            </div>
        </div>

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
            <div class="flex items-center gap-3 px-5 py-2.5 bg-blue-700 text-white text-sm font-semibold">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.5L12 4l9 5.5V20H3V9.5z"/>
                </svg>
                {{ $room->name }}
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
            <div class="flex gap-8 text-sm text-gray-600 dark:text-gray-300">
                <span>Tax</span>
                <span class="w-28 text-right">${{ number_format((float)$invoice->tax_amount, 2) }}</span>
            </div>
            <div class="flex gap-8 text-base font-bold text-gray-900 dark:text-white border-t border-gray-300 dark:border-gray-500 pt-2 mt-1">
                <span>Total</span>
                <span class="w-28 text-right">${{ number_format((float)$invoice->grand_total, 2) }}</span>
            </div>
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
                    <input type="number" name="amount" step="0.01" min="0.01"
                        value="{{ number_format($invoice->balance_due, 2) }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
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
<div id="send-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Send Invoice</h3>
            <button type="button" onclick="document.getElementById('send-modal').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('pages.sales.invoices.send-email', [$sale, $invoice]) }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">To <span class="text-red-500">*</span></label>
                @php $homeownerEmail = $sale->job_email ?? $sale->sourceEstimate?->homeowner_email; @endphp
                <div class="flex gap-2 mb-2 flex-wrap">
                    @if($homeownerEmail)
                        <button type="button" onclick="document.getElementById('email-to').value = '{{ $homeownerEmail }}'"
                            class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 rounded px-2 py-1 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-300">
                            Job Site: {{ $homeownerEmail }}
                        </button>
                    @endif
                </div>
                <input type="email" id="email-to" name="to" value="{{ $homeownerEmail }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    required>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                <input type="text" name="subject"
                    value="Invoice {{ $invoice->invoice_number }} — {{ $sale->job_name ?? 'Job #' . $sale->sale_number }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    required>
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                <textarea name="body" rows="5"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">Please find your invoice {{ $invoice->invoice_number }} attached.{{ $invoice->due_date ? "\n\nPayment due: " . $invoice->due_date->format('M j, Y') : '' }}

Thank you for your business.</textarea>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">The invoice PDF will be attached automatically.</p>
            <div class="flex gap-3 pt-1">
                <button type="submit"
                    class="flex-1 text-white bg-emerald-600 hover:bg-emerald-700 font-medium rounded-lg text-sm px-5 py-2.5">
                    Send Invoice
                </button>
                <button type="button" onclick="document.getElementById('send-modal').classList.add('hidden')"
                    class="flex-1 py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>


<x-modals.sent-email-modal type="invoice" :related-id="$invoice->id" />
</x-app-layout>
