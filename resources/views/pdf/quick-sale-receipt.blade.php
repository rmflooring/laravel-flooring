<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10px;
        color: #111;
        width: 100%;
        padding: 10px 12px;
        line-height: 1.4;
    }

    .center { text-align: center; }
    .right   { text-align: right; }

    .company-name {
        font-size: 14px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 2px;
    }
    .company-sub {
        font-size: 9px;
        color: #555;
        text-align: center;
        margin-bottom: 2px;
    }

    .divider {
        border: none;
        border-top: 1px dashed #aaa;
        margin: 8px 0;
    }
    .divider-solid {
        border: none;
        border-top: 1px solid #ccc;
        margin: 6px 0;
    }

    .label {
        font-size: 8px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }
    th {
        font-size: 8px;
        color: #666;
        text-transform: uppercase;
        padding-bottom: 4px;
        border-bottom: 1px solid #ddd;
    }
    td { padding: 3px 0; vertical-align: top; }
    .item-name { font-size: 9.5px; }
    .item-sub  { font-size: 8px; color: #777; }

    .totals-row { display: flex; justify-content: space-between; padding: 2px 0; }
    .totals-row.grand { font-weight: bold; font-size: 12px; padding-top: 4px; }

    .paid-badge {
        text-align: center;
        border: 2px solid #16a34a;
        border-radius: 4px;
        color: #16a34a;
        font-weight: bold;
        font-size: 12px;
        letter-spacing: 0.1em;
        padding: 4px 0;
        margin: 8px 0;
    }

    .payment-row {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 4px;
        padding: 5px 7px;
        margin: 6px 0;
    }
    .payment-method { font-weight: bold; font-size: 10px; color: #166534; }
    .payment-amount { font-weight: bold; font-size: 11px; color: #166534; }
    .payment-change { font-size: 9px; color: #15803d; }

    .footer {
        text-align: center;
        font-size: 8px;
        color: #999;
        margin-top: 10px;
    }
</style>
</head>
<body>

    {{-- Logo --}}
    @if (!empty($logoData))
        <div class="center" style="margin-bottom: 6px;">
            <img src="{{ $logoData }}" style="height: 40px; max-width: 140px; object-fit: contain;">
        </div>
    @endif

    {{-- Company info --}}
    <p class="company-name">{{ $settings['branding_company_name'] ?: config('app.name') }}</p>
    @if ($settings['branding_street'])
        <p class="company-sub">{{ $settings['branding_street'] }}, {{ $settings['branding_city'] }} {{ $settings['branding_province'] }} {{ $settings['branding_postal'] }}</p>
    @endif
    @if ($settings['branding_phone'])
        <p class="company-sub">{{ $settings['branding_phone'] }}</p>
    @endif
    @if ($settings['branding_email'])
        <p class="company-sub">{{ $settings['branding_email'] }}</p>
    @endif

    <hr class="divider">

    {{-- Receipt header --}}
    <p class="center" style="font-size: 11px; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 6px;">RECEIPT</p>

    {{-- Customer + Date --}}
    <table>
        <tr>
            <td style="width: 55%">
                <p class="label">Customer</p>
                <p style="font-size: 10px; font-weight: bold;">{{ $sale->customer?->company_name ?? 'Walk-in' }}</p>
                @if ($sale->customer?->phone)
                    <p style="font-size: 8.5px; color:#555;">{{ $sale->customer->phone }}</p>
                @endif
            </td>
            <td class="right">
                <p class="label">Date</p>
                <p style="font-size: 10px;">{{ $sale->created_at->format('M j, Y') }}</p>
                <p style="font-size: 8.5px; color:#555;">Sale #{{ $sale->sale_number }}</p>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Items --}}
    <table>
        <thead>
            <tr>
                <th style="text-align:left; width:50%">Item</th>
                <th style="text-align:center; width:12%">Qty</th>
                <th style="text-align:right; width:18%">Price</th>
                <th style="text-align:right; width:20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->rooms->first()?->items ?? [] as $item)
            <tr>
                <td>
                    <p class="item-name">{{ $item->style ?? $item->description ?? '—' }}</p>
                    @if ($item->color_item_number)
                        <p class="item-sub">{{ $item->color_item_number }}</p>
                    @endif
                </td>
                <td style="text-align:center;">
                    {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}
                    @if($item->unit) <span style="font-size:8px;color:#888;">{{ $item->unit }}</span> @endif
                </td>
                <td style="text-align:right;">${{ number_format($item->sell_price, 2) }}</td>
                <td style="text-align:right; font-weight:bold;">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="divider-solid">

    {{-- Totals --}}
    <div class="totals-row"><span>Subtotal</span><span>${{ number_format($invoice?->subtotal ?? 0, 2) }}</span></div>
    <div class="totals-row"><span>Tax ({{ number_format($sale->tax_rate_percent, 3) }}%)</span><span>${{ number_format($invoice?->tax_amount ?? 0, 2) }}</span></div>
    <hr class="divider-solid">
    <div class="totals-row grand"><span>TOTAL</span><span>${{ number_format($grandTotal, 2) }}</span></div>

    {{-- Payment --}}
    @if ($payment)
        <div class="payment-row">
            <table>
                <tr>
                    <td>
                        <p class="payment-method">{{ \App\Models\InvoicePayment::PAYMENT_METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</p>
                        @if ($payment->reference_number)
                            <p style="font-size:8px; color:#166534;">Ref: {{ $payment->reference_number }}</p>
                        @endif
                    </td>
                    <td class="right">
                        <p class="payment-amount">${{ number_format($amountTendered, 2) }}</p>
                        @if ($changeDue > 0)
                            <p class="payment-change">Change: ${{ number_format($changeDue, 2) }}</p>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="paid-badge">PAID</div>

    @if ($sale->notes)
        <p style="font-size: 8.5px; color: #666; margin: 4px 0;">{{ $sale->notes }}</p>
    @endif

    <p class="footer">Thank you for your business!<br>{{ $settings['branding_company_name'] }}</p>

</body>
</html>
