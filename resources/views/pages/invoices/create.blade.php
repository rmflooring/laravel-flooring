<x-app-layout>
<div class="py-8">
<div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('pages.sales.show', $sale) }}"
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Sale #{{ $sale->sale_number }}</a>
    </div>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Invoice</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $sale->job_name ?? $sale->opportunity?->customer?->company_name }} &mdash; Sale #{{ $sale->sale_number }}
        </p>
    </div>

    @if ($errors->any())
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pages.sales.invoices.store', $sale) }}" method="POST" id="invoice-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: Invoice details --}}
            <div class="space-y-5 lg:col-span-1">
                <div class="p-5 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Invoice Details</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Payment Terms</label>
                            <select name="payment_term_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">— None —</option>
                                @foreach ($paymentTerms as $term)
                                    <option value="{{ $term->id }}" {{ old('payment_term_id') == $term->id ? 'selected' : '' }}>
                                        {{ $term->name }}{{ $term->days ? ' (Net ' . $term->days . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                            <input type="date" name="due_date" value="{{ old('due_date') }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Customer PO #</label>
                            <input type="text" name="customer_po_number" value="{{ old('customer_po_number') }}"
                                placeholder="Customer's PO reference..."
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea name="notes" rows="3"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Notes printed on invoice...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Running total --}}
                <div class="p-5 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Invoice Total</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span class="font-medium text-gray-900 dark:text-white" id="summary-subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Tax ({{ number_format((float)($sale->tax_rate_percent ?? 0), 2) }}%)</span>
                            <span class="font-medium text-gray-900 dark:text-white" id="summary-tax">$0.00</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-2 mt-2">
                            <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                            <span class="font-bold text-gray-900 dark:text-white text-base" id="summary-total">$0.00</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 pt-1">
                            <span>Sale Total</span>
                            <span>${{ number_format((float)($sale->locked_grand_total ?: ($sale->revised_contract_total ?: $sale->grand_total)), 2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>Previously Invoiced</span>
                            <span>${{ number_format((float)$sale->invoiced_total, 2) }}</span>
                        </div>
                    </div>
                </div>

                <button type="submit"
                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Create Invoice
                </button>
            </div>

            {{-- Right: Item selector --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Select Items to Invoice</h2>
                    <div class="flex gap-2">
                        <button type="button" id="select-all"
                            class="text-xs text-blue-600 hover:underline dark:text-blue-400">Select all available</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" id="deselect-all"
                            class="text-xs text-gray-500 hover:underline dark:text-gray-400">Deselect all</button>
                    </div>
                </div>

                @foreach ($sale->rooms as $room)
                    @php
                        $hasAvailable = $room->items->filter(function($item) use ($invoicedQty) {
                            $remaining = (float)$item->quantity - ($invoicedQty[$item->id] ?? 0);
                            return $remaining > 0.001;
                        })->isNotEmpty();
                    @endphp

                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                        {{-- Room header --}}
                        <div class="flex items-center gap-3 px-5 py-3 bg-blue-700 text-white">
                            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9.5L12 4l9 5.5V20H3V9.5z"/>
                            </svg>
                            <span class="font-semibold text-sm">{{ $room->room_name }}</span>
                        </div>

                        {{-- Items --}}
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs uppercase text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <th class="px-4 py-2 w-8"></th>
                                    <th class="px-4 py-2 text-left">Item</th>
                                    <th class="px-4 py-2 text-center w-24">Total Qty</th>
                                    <th class="px-4 py-2 text-center w-24">Invoiced</th>
                                    <th class="px-4 py-2 text-center w-28">Invoice Qty</th>
                                    <th class="px-4 py-2 text-right w-28">Unit Price</th>
                                    <th class="px-4 py-2 text-right w-28">Line Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($room->items as $item)
                                    @php
                                        $alreadyInvoiced = $invoicedQty[$item->id] ?? 0;
                                        $remaining       = round((float)$item->quantity - $alreadyInvoiced, 2);
                                        $fullyInvoiced   = $remaining <= 0.001;
                                        $labelParts = match($item->item_type) {
                                            'material' => array_filter([$item->product_type, $item->manufacturer, $item->style, $item->color_item_number]),
                                            'labour'   => [$item->labour_type ?? 'Labour'],
                                            'freight'  => [$item->freight_description ?? 'Freight'],
                                            default    => ['Item'],
                                        };
                                        $label = implode(' — ', $labelParts);
                                    @endphp
                                    <tr class="{{ $fullyInvoiced ? 'opacity-40 bg-gray-50 dark:bg-gray-900' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                                        data-item-id="{{ $item->id }}"
                                        data-sell-price="{{ (float)$item->sell_price }}"
                                        data-tax-rate="{{ (float)($sale->tax_rate_percent ?? 0) / 100 }}">

                                        <td class="px-4 py-3 text-center">
                                            @if(! $fullyInvoiced)
                                                <input type="checkbox" class="item-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded"
                                                    data-item-id="{{ $item->id }}"
                                                    @if(old("items.{$item->id}")) checked @endif>
                                            @else
                                                <svg class="h-4 w-4 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $label }}</div>
                                            @if($fullyInvoiced)
                                                <div class="text-xs text-green-600 dark:text-green-400">Fully invoiced</div>
                                            @elseif($alreadyInvoiced > 0)
                                                <div class="text-xs text-amber-600 dark:text-amber-400">Partially invoiced</div>
                                            @endif
                                            <div class="text-xs text-gray-400">
                                                {{ ucfirst($item->item_type) }}{{ $item->unit ? ' · ' . strtoupper($item->unit) : '' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                            {{ number_format((float)$item->quantity, 2) }}
                                        </td>

                                        <td class="px-4 py-3 text-center text-gray-500">
                                            {{ $alreadyInvoiced > 0 ? number_format($alreadyInvoiced, 2) : '—' }}
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            @if(! $fullyInvoiced)
                                                <input type="number"
                                                    name="items[{{ $item->id }}]"
                                                    class="invoice-qty-input w-24 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg text-center p-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                    min="0" max="{{ $remaining }}" step="0.01"
                                                    value="{{ old("items.{$item->id}", 0) }}"
                                                    data-item-id="{{ $item->id }}"
                                                    data-max="{{ $remaining }}"
                                                    {{ $fullyInvoiced ? 'disabled' : '' }}>
                                                <div class="text-xs text-gray-400 mt-0.5">of {{ number_format($remaining, 2) }} avail.</div>
                                            @else
                                                <input type="hidden" name="items[{{ $item->id }}]" value="0">
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                                            ${{ number_format((float)$item->sell_price, 2) }}
                                        </td>

                                        <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white item-line-total" data-item-id="{{ $item->id }}">
                                            $0.00
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

        </div>
    </form>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const taxRate = {{ (float)($sale->tax_rate_percent ?? 0) / 100 }};

    function formatMoney(v) {
        return '$' + parseFloat(v).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function recalculate() {
        let subtotal = 0;

        document.querySelectorAll('.invoice-qty-input').forEach(function (input) {
            const row      = input.closest('tr');
            const price    = parseFloat(row.dataset.sellPrice) || 0;
            const qty      = parseFloat(input.value) || 0;
            const total    = Math.round(qty * price * 100) / 100;
            const itemId   = input.dataset.itemId;
            const lineCell = document.querySelector('.item-line-total[data-item-id="' + itemId + '"]');
            if (lineCell) lineCell.textContent = formatMoney(total);
            subtotal += total;
        });

        const tax   = Math.round(subtotal * taxRate * 100) / 100;
        const grand = Math.round((subtotal + tax) * 100) / 100;

        document.getElementById('summary-subtotal').textContent = formatMoney(subtotal);
        document.getElementById('summary-tax').textContent      = formatMoney(tax);
        document.getElementById('summary-total').textContent    = formatMoney(grand);
    }

    // Checkbox toggles qty between 0 and remaining max
    document.querySelectorAll('.item-checkbox').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const itemId = this.dataset.itemId;
            const input  = document.querySelector('.invoice-qty-input[data-item-id="' + itemId + '"]');
            if (!input) return;
            input.value = this.checked ? input.dataset.max : '0';
            recalculate();
        });
    });

    // Qty input updates checkbox state
    document.querySelectorAll('.invoice-qty-input').forEach(function (input) {
        input.addEventListener('input', function () {
            const itemId = this.dataset.itemId;
            const cb     = document.querySelector('.item-checkbox[data-item-id="' + itemId + '"]');
            if (cb) cb.checked = parseFloat(this.value) > 0;
            recalculate();
        });
    });

    // Select all available
    document.getElementById('select-all').addEventListener('click', function () {
        document.querySelectorAll('.invoice-qty-input').forEach(function (input) {
            input.value = input.dataset.max;
            const cb = document.querySelector('.item-checkbox[data-item-id="' + input.dataset.itemId + '"]');
            if (cb) cb.checked = true;
        });
        recalculate();
    });

    document.getElementById('deselect-all').addEventListener('click', function () {
        document.querySelectorAll('.invoice-qty-input').forEach(function (input) {
            input.value = '0';
            const cb = document.querySelector('.item-checkbox[data-item-id="' + input.dataset.itemId + '"]');
            if (cb) cb.checked = false;
        });
        recalculate();
    });

    recalculate();
});
</script>
</x-app-layout>
