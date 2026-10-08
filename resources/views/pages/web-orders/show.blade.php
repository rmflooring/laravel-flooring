@php
    $data = $sale->web_order_data ?? [];
    $actions = [
        'new'       => [['confirmed', 'Confirm — items available', '#4f46e5'], ['ready', 'Ready for pickup', '#16a34a']],
        'confirmed' => [['ready', 'Ready for pickup', '#16a34a']],
        'ready'     => [['picked_up', 'Picked up', '#16a34a']],
    ][$sale->web_status] ?? [];
    $canRefund = in_array($sale->web_status, ['new', 'confirmed', 'ready'], true);
@endphp
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <a href="{{ route('pages.web-orders.index') }}" class="text-sm font-medium text-indigo-600">← Web Orders</a>

            @foreach (['success' => 'green', 'error' => 'red'] as $key => $c)
                @if (session($key))
                    <div class="p-4 rounded-lg border bg-{{ $c }}-100 border-{{ $c }}-200 text-{{ $c }}-800">{{ session($key) }}</div>
                @endif
            @endforeach

            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Web order {{ $sale->web_order_number }}</h1>
                    <p class="text-sm text-gray-500">Placed {{ $sale->created_at->timezone('America/Vancouver')->format('M j, Y g:i A') }} · <a href="{{ route('pages.sales.show', $sale) }}" class="text-indigo-600">Sale #{{ $sale->sale_number }}</a>
                        @if ($sale->opportunity_id) · <a href="{{ route('pages.opportunities.show', $sale->opportunity_id) }}" class="text-indigo-600">Opportunity</a>@endif</p>
                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Status: {{ \App\Models\Sale::WEB_STATUSES[$sale->web_status] ?? $sale->web_status }}
                        <span class="font-normal text-gray-500">since {{ $sale->web_status_at?->timezone('America/Vancouver')->format('M j, g:i A') }}</span></p>
                </div>
                @can('edit sales')
                    <div class="flex flex-wrap gap-2">
                        @foreach ($actions as [$to, $label, $color])
                            <form method="POST" action="{{ route('pages.web-orders.update', $sale) }}">@csrf
                                <input type="hidden" name="status" value="{{ $to }}">
                                <button class="rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:{{ $color }}">{{ $label }}</button>
                            </form>
                        @endforeach
                        @if ($canRefund)
                            <form method="POST" action="{{ route('pages.web-orders.update', $sale) }}"
                                  onsubmit="return confirm('Refund ${{ number_format((float) $sale->grand_total, 2) }} to the customer’s card and cancel this order? This can’t be undone.')">@csrf
                                <input type="hidden" name="status" value="refunded">
                                <button class="rounded-lg px-4 py-2 text-sm font-semibold border" style="border-color:#dc2626;color:#dc2626">Can’t fulfil — refund</button>
                            </form>
                        @endif
                    </div>
                @endcan
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="md:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-left text-xs uppercase tracking-wider text-gray-500"><tr><th class="px-4 py-2">Item</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2 text-right">Total</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($data['items'] ?? [] as $item)
                                <tr>
                                    <td class="px-4 py-2 text-gray-900 dark:text-white">{{ $item['label'] }}
                                        @if (! empty($item['return_policy']))<span class="block text-xs text-gray-500">Returns: {{ $item['return_policy'] }}</span>@endif</td>
                                    <td class="px-4 py-2 text-right text-gray-700 dark:text-gray-300">{{ ! empty($item['boxes']) ? $item['boxes'] . ' boxes (' . rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') . ' ' . $item['unit'] . ')' : rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') . ' ' . ($item['unit'] ?? '') }}</td>
                                    <td class="px-4 py-2 text-right text-gray-900 dark:text-white">${{ number_format(round($item['quantity'] * $item['unit_price'], 2), 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="text-sm">
                            <tr><td colspan="2" class="px-4 py-1 text-right text-gray-500">Subtotal</td><td class="px-4 py-1 text-right">${{ number_format((float) $sale->pretax_total, 2) }}</td></tr>
                            <tr><td colspan="2" class="px-4 py-1 text-right text-gray-500">GST + PST ({{ rtrim(rtrim(number_format((float) $sale->tax_rate_percent, 2), '0'), '.') }}%)</td><td class="px-4 py-1 text-right">${{ number_format((float) $sale->tax_amount, 2) }}</td></tr>
                            <tr><td colspan="2" class="px-4 py-2 text-right font-semibold">Paid online</td><td class="px-4 py-2 text-right font-bold">${{ number_format((float) $sale->grand_total, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-4 text-sm space-y-1">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $sale->customer_name }}</p>
                        @if ($sale->job_phone)<p><a href="tel:{{ $sale->job_phone }}" class="text-indigo-600">{{ $sale->job_phone }}</a></p>@endif
                        @if ($sale->job_email)<p><a href="mailto:{{ $sale->job_email }}" class="text-indigo-600 break-all">{{ $sale->job_email }}</a></p>@endif
                        @if ($sale->customer)<p class="pt-1"><a href="{{ route('admin.customers.show', $sale->customer) }}" class="text-indigo-600">Customer record →</a></p>@endif
                    </div>
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-4 text-sm space-y-1">
                        <p class="font-semibold text-gray-900 dark:text-white">Payment</p>
                        <p class="text-gray-600 dark:text-gray-300">Card via Stripe</p>
                        <p class="text-xs text-gray-500 break-all">{{ $sale->web_payment_reference }}</p>
                    </div>
                    @if (! empty($data['notes']))
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-4 text-sm">
                            <p class="font-semibold text-gray-900 dark:text-white">Customer note</p>
                            <p class="mt-1 text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $data['notes'] }}</p>
                        </div>
                    @endif
                    @if ($sale->web_ready_at)
                        <p class="text-xs text-gray-500">Ready since {{ $sale->web_ready_at->timezone('America/Vancouver')->format('M j') }}{{ $sale->web_reminder_sent_at ? ' · pickup reminder sent ' . $sale->web_reminder_sent_at->timezone('America/Vancouver')->format('M j') : '' }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
