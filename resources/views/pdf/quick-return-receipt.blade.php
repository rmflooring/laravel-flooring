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

    .divider       { border: none; border-top: 1px dashed #aaa; margin: 8px 0; }
    .divider-solid { border: none; border-top: 1px solid #ccc; margin: 6px 0; }

    .label {
        font-size: 8px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    table { width: 100%; border-collapse: collapse; }
    th { font-size: 8px; color: #666; text-transform: uppercase; padding-bottom: 4px; border-bottom: 1px solid #ddd; }
    td { padding: 3px 0; vertical-align: top; }
    .item-name { font-size: 9.5px; }

    .totals-row { display: flex; justify-content: space-between; padding: 2px 0; }
    .totals-row.grand { font-weight: bold; font-size: 12px; padding-top: 4px; color: #be123c; }

    .refund-badge {
        text-align: center;
        border: 2px solid #be123c;
        border-radius: 4px;
        color: #be123c;
        font-weight: bold;
        font-size: 12px;
        letter-spacing: 0.1em;
        padding: 4px 0;
        margin: 8px 0;
    }

    .refund-row {
        background: #fff1f2;
        border: 1px solid #fecdd3;
        border-radius: 4px;
        padding: 5px 7px;
        margin: 6px 0;
    }
    .refund-method { font-weight: bold; font-size: 10px; color: #9f1239; }
    .refund-amount { font-weight: bold; font-size: 11px; color: #9f1239; }

    .footer { text-align: center; font-size: 8px; color: #999; margin-top: 10px; }
</style>
</head>
<body>

    @if (!empty($logoData))
        <div class="center" style="margin-bottom: 6px;">
            <img src="{{ $logoData }}" style="height: 40px; max-width: 140px; object-fit: contain;">
        </div>
    @endif

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

    <p class="center" style="font-size: 11px; font-weight: bold; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 6px;">RETURN RECEIPT</p>

    <table>
        <tr>
            <td style="width: 55%">
                <p class="label">Customer</p>
                <p style="font-size: 10px; font-weight: bold;">{{ $quickReturn->customer_name }}</p>
                @if ($quickReturn->customer?->phone)
                    <p style="font-size: 8.5px; color:#555;">{{ $quickReturn->customer->phone }}</p>
                @endif
            </td>
            <td class="right">
                <p class="label">Date</p>
                <p style="font-size: 10px;">{{ $quickReturn->created_at->format('M j, Y') }}</p>
                <p style="font-size: 8.5px; color:#555;">{{ $quickReturn->return_number }}</p>
                @if ($quickReturn->original_sale_number)
                    <p style="font-size: 8.5px; color:#555;">Re: Sale #{{ $quickReturn->original_sale_number }}</p>
                @endif
            </td>
        </tr>
    </table>

    <hr class="divider">

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
            @foreach ($quickReturn->items as $item)
            <tr>
                <td><p class="item-name">{{ $item->description }}</p></td>
                <td style="text-align:center;">
                    {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}
                    @if($item->unit) <span style="font-size:8px;color:#888;">{{ $item->unit }}</span> @endif
                </td>
                <td style="text-align:right;">${{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align:right; font-weight:bold;">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <hr class="divider-solid">

    <div class="totals-row"><span>Subtotal</span><span>${{ number_format($quickReturn->subtotal, 2) }}</span></div>
    @if ($quickReturn->tax_rate_percent > 0)
        <div class="totals-row"><span>Tax ({{ number_format($quickReturn->tax_rate_percent, 3) }}%)</span><span>${{ number_format($quickReturn->tax_amount, 2) }}</span></div>
    @endif
    <hr class="divider-solid">
    <div class="totals-row grand"><span>TOTAL REFUND</span><span>${{ number_format($quickReturn->grand_total, 2) }}</span></div>

    <div class="refund-row">
        <table>
            <tr>
                <td>
                    <p class="refund-method">Refund — {{ \App\Models\InvoicePayment::PAYMENT_METHODS[$quickReturn->refund_method] ?? ucfirst($quickReturn->refund_method) }}</p>
                    @if ($quickReturn->reference_number)
                        <p style="font-size:8px; color:#9f1239;">Ref: {{ $quickReturn->reference_number }}</p>
                    @endif
                </td>
                <td class="right">
                    <p class="refund-amount">${{ number_format($quickReturn->grand_total, 2) }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="refund-badge">REFUNDED</div>

    @if ($quickReturn->notes)
        <p style="font-size: 8.5px; color: #666; margin: 4px 0;">{{ $quickReturn->notes }}</p>
    @endif

    <p class="footer">Thank you.<br>{{ $settings['branding_company_name'] }}</p>

</body>
</html>
