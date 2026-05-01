<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px;
        color: #1a1a1a;
        padding: 48px 56px;
        line-height: 1.5;
    }

    /* ── Header ── */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
    }
    .company-name {
        font-size: 20px;
        font-weight: bold;
        color: #111827;
        margin-bottom: 4px;
    }
    .company-sub {
        font-size: 10px;
        color: #6b7280;
        margin-bottom: 2px;
    }
    .receipt-label {
        font-size: 24px;
        font-weight: bold;
        color: #4f46e5;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        text-align: right;
    }
    .receipt-meta {
        font-size: 10px;
        color: #6b7280;
        text-align: right;
        margin-top: 4px;
    }

    /* ── Customer / info row ── */
    .info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 28px;
    }
    .info-block label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #9ca3af;
        font-weight: bold;
        display: block;
        margin-bottom: 3px;
    }
    .info-block p {
        font-size: 11px;
        color: #111827;
    }
    .info-block p.name {
        font-size: 13px;
        font-weight: bold;
    }

    /* ── Items table ── */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    thead th {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #6b7280;
        font-weight: bold;
        padding: 8px 10px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
    }
    tbody td {
        padding: 10px 10px;
        border-bottom: 1px solid #f3f4f6;
        font-size: 11px;
        color: #374151;
        vertical-align: top;
    }
    tbody tr:last-child td {
        border-bottom: none;
    }
    .item-sub {
        font-size: 9px;
        color: #9ca3af;
        margin-top: 2px;
    }

    /* ── Totals ── */
    .totals-wrap {
        margin-top: 16px;
        display: flex;
        justify-content: flex-end;
    }
    .totals-table {
        width: 260px;
    }
    .totals-table td {
        padding: 4px 10px;
        font-size: 11px;
    }
    .totals-table .label { color: #6b7280; }
    .totals-table .value { text-align: right; color: #374151; }
    .totals-table .grand-row td {
        font-size: 14px;
        font-weight: bold;
        color: #111827;
        padding-top: 10px;
        border-top: 2px solid #e5e7eb;
    }

    /* ── Payment box ── */
    .payment-box {
        margin-top: 28px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 6px;
        padding: 14px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .payment-method { font-size: 13px; font-weight: bold; color: #166534; }
    .payment-ref    { font-size: 10px; color: #15803d; margin-top: 2px; }
    .payment-amount { font-size: 18px; font-weight: bold; color: #166534; text-align: right; }
    .payment-change { font-size: 10px; color: #15803d; text-align: right; margin-top: 2px; }

    /* ── Paid stamp ── */
    .paid-stamp {
        margin-top: 20px;
        text-align: center;
    }
    .paid-stamp span {
        display: inline-block;
        border: 3px solid #16a34a;
        border-radius: 6px;
        color: #16a34a;
        font-size: 20px;
        font-weight: bold;
        letter-spacing: 0.15em;
        padding: 6px 28px;
    }

    /* ── Notes + footer ── */
    .notes {
        margin-top: 24px;
        padding: 12px 14px;
        background: #f9fafb;
        border-radius: 4px;
        font-size: 10px;
        color: #6b7280;
    }
    .footer {
        margin-top: 40px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        font-size: 10px;
        color: #9ca3af;
    }
</style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div>
            @if (!empty($logoData))
                <img src="{{ $logoData }}" style="height: 50px; max-width: 180px; object-fit: contain; margin-bottom: 8px; display: block;">
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
        </div>
        <div>
            <p class="receipt-label">Receipt</p>
            <p class="receipt-meta">Sale #{{ $sale->sale_number }}</p>
            <p class="receipt-meta">{{ $sale->created_at->format('F j, Y') }}</p>
            <p class="receipt-meta">{{ $sale->created_at->format('g:i A') }}</p>
        </div>
    </div>

    {{-- Customer info --}}
    <div class="info-row">
        <div class="info-block">
            <label>Bill To</label>
            <p class="name">{{ $sale->customer?->company_name ?? 'Walk-in Customer' }}</p>
            @if ($sale->customer?->phone)
                <p>{{ $sale->customer->phone }}</p>
            @endif
            @if ($sale->customer?->email)
                <p>{{ $sale->customer->email }}</p>
            @endif
        </div>
        @if ($payment)
        <div class="info-block" style="text-align: right;">
            <label>Payment Method</label>
            <p class="name">{{ \App\Models\InvoicePayment::PAYMENT_METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</p>
            @if ($payment->reference_number)
                <p>Ref: {{ $payment->reference_number }}</p>
            @endif
        </div>
        @endif
    </div>

    {{-- Items table --}}
    <table>
        <thead>
            <tr>
                <th style="text-align:left;">Description</th>
                <th style="text-align:center; width:70px;">Qty</th>
                <th style="text-align:right; width:90px;">Unit Price</th>
                <th style="text-align:right; width:90px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->rooms->first()?->items ?? [] as $item)
            <tr>
                <td>
                    {{ $item->style ?? $item->description ?? '—' }}
                    @if ($item->color_item_number)
                        <div class="item-sub">{{ $item->color_item_number }}</div>
                    @endif
                    @if ($item->manufacturer)
                        <div class="item-sub">{{ $item->manufacturer }}</div>
                    @endif
                </td>
                <td style="text-align:center;">
                    {{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}
                    @if($item->unit) {{ $item->unit }} @endif
                </td>
                <td style="text-align:right;">${{ number_format($item->sell_price, 2) }}</td>
                <td style="text-align:right;">${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-wrap">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal</td>
                <td class="value">${{ number_format($invoice?->subtotal ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Tax ({{ number_format($sale->tax_rate_percent, 3) }}%)</td>
                <td class="value">${{ number_format($invoice?->tax_amount ?? 0, 2) }}</td>
            </tr>
            <tr class="grand-row">
                <td>Total</td>
                <td style="text-align:right;">${{ number_format($grandTotal, 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Payment --}}
    @if ($payment)
    <div class="payment-box">
        <div>
            <p class="payment-method">{{ \App\Models\InvoicePayment::PAYMENT_METHODS[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</p>
            @if ($payment->reference_number)
                <p class="payment-ref">Ref: {{ $payment->reference_number }}</p>
            @endif
        </div>
        <div>
            <p class="payment-amount">${{ number_format($amountTendered, 2) }}</p>
            @if ($changeDue > 0)
                <p class="payment-change">Change: ${{ number_format($changeDue, 2) }}</p>
            @endif
        </div>
    </div>
    @endif

    <div class="paid-stamp">
        <span>PAID</span>
    </div>

    @if ($sale->notes)
        <div class="notes">{{ $sale->notes }}</div>
    @endif

    <div class="footer">
        Thank you for your business! &mdash; {{ $settings['branding_company_name'] ?: config('app.name') }}
    </div>

</body>
</html>
