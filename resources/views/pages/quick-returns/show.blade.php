<x-app-layout>
<div class="max-w-2xl mx-auto px-4 py-8">

    @if (session('success'))
        <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm font-medium">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $quickReturn->return_number }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Quick Return — {{ $quickReturn->created_at->format('M j, Y g:i A') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pages.quick-returns.create') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 border border-gray-300 bg-white hover:bg-gray-50 rounded-lg px-3 py-2 transition">
                + New Return
            </a>
            <a href="{{ route('pages.quick-returns.receipt', $quickReturn) }}" target="_blank"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg px-4 py-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </a>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="bg-gray-50 border-b border-gray-200 px-6 py-5 text-center">
            <p class="text-lg font-bold text-gray-900">{{ $settings['branding_company_name'] ?: config('app.name') }}</p>
            @if ($settings['branding_street'])
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ $settings['branding_street'] }}{{ $settings['branding_city'] ? ', ' . $settings['branding_city'] : '' }}
                    {{ $settings['branding_province'] }} {{ $settings['branding_postal'] }}
                </p>
            @endif
            @if ($settings['branding_phone'])
                <p class="text-sm text-gray-500">{{ $settings['branding_phone'] }}</p>
            @endif
        </div>

        <div class="px-6 py-5 space-y-5">

            {{-- Customer + Date --}}
            <div class="flex justify-between text-sm">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-0.5">Customer</p>
                    <p class="font-semibold text-gray-800">{{ $quickReturn->customer_name }}</p>
                    @if ($quickReturn->customer?->phone)
                        <p class="text-gray-500 text-xs">{{ $quickReturn->customer->phone }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-medium mb-0.5">Date</p>
                    <p class="font-semibold text-gray-800">{{ $quickReturn->created_at->format('M j, Y') }}</p>
                    <p class="text-gray-500 text-xs">{{ $quickReturn->return_number }}</p>
                    @if ($quickReturn->sale)
                        <p class="text-gray-500 text-xs">Re: Sale #{{ $quickReturn->sale->sale_number }}</p>
                    @endif
                </div>
            </div>

            {{-- Items table --}}
            <div>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide pb-2">Item</th>
                            <th class="text-center text-xs font-semibold text-gray-500 uppercase tracking-wide pb-2 w-16">Qty</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide pb-2 w-20">Price</th>
                            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide pb-2 w-20">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($quickReturn->items as $item)
                            <tr>
                                <td class="py-2 pr-3 text-gray-800">{{ $item->description }}</td>
                                <td class="py-2 text-center text-gray-600">
                                    {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}
                                    @if($item->unit) <span class="text-gray-400 text-xs">{{ $item->unit }}</span> @endif
                                </td>
                                <td class="py-2 text-right text-gray-600">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-2 text-right font-medium text-gray-800">${{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="border-t border-gray-200 pt-4 space-y-1.5 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span>${{ number_format($quickReturn->subtotal, 2) }}</span>
                </div>
                @if ($quickReturn->tax_rate_percent > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>Tax ({{ number_format($quickReturn->tax_rate_percent, 3) }}%)</span>
                        <span>${{ number_format($quickReturn->tax_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold text-base text-gray-900 pt-1 border-t border-gray-200">
                    <span>Total Refund</span>
                    <span class="text-rose-600">${{ number_format($quickReturn->grand_total, 2) }}</span>
                </div>
            </div>

            {{-- Refund method --}}
            <div class="bg-rose-50 border border-rose-200 rounded-lg px-4 py-3 text-sm">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="font-semibold text-rose-800">
                            Refund — {{ \App\Models\InvoicePayment::PAYMENT_METHODS[$quickReturn->refund_method] ?? ucfirst($quickReturn->refund_method) }}
                        </p>
                        @if ($quickReturn->reference_number)
                            <p class="text-rose-700 text-xs">Ref: {{ $quickReturn->reference_number }}</p>
                        @endif
                    </div>
                    <p class="text-rose-800 font-bold">${{ number_format($quickReturn->grand_total, 2) }}</p>
                </div>
            </div>

            {{-- Refunded stamp --}}
            <div class="text-center">
                <span class="inline-block bg-rose-100 text-rose-700 text-xs font-bold uppercase tracking-widest px-4 py-1.5 rounded-full border border-rose-300">
                    Refunded
                </span>
            </div>

            @if ($quickReturn->notes)
                <div class="text-xs text-gray-400 text-center">{{ $quickReturn->notes }}</div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
