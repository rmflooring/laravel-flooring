<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; padding: 32px; }

        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 14px; margin-bottom: 20px; }
        .company-name { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
        .company-sub { font-size: 11px; color: #555; margin-top: 2px; }
        .doc-title { font-size: 20px; font-weight: bold; text-align: right; color: #1d4ed8; }
        .doc-meta { text-align: right; font-size: 11px; color: #555; margin-top: 3px; }
        .doc-status { text-align: right; font-size: 11px; font-weight: bold; margin-top: 3px; }

        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-col:last-child { padding-left: 24px; }
        .info-section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #1d4ed8; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 6px; }
        .info-row { margin-bottom: 3px; }
        .info-key { color: #666; }

        .room { margin-bottom: 18px; page-break-inside: avoid; }
        .room-header { background-color: #1d4ed8; color: #fff; padding: 6px 10px; font-weight: bold; font-size: 11px; }

        table { width: 100%; border-collapse: collapse; }
        .items-table th { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #666; padding: 5px 8px; border-bottom: 1px solid #ddd; background: #fafafa; text-align: left; }
        .items-table th.right { text-align: right; }
        .items-table td { padding: 5px 8px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
        .items-table td.right { text-align: right; }
        .items-table tr:last-child td { border-bottom: none; }
        .item-label { font-weight: bold; }
        .item-meta { font-size: 9.5px; color: #888; margin-top: 1px; }

        .room-subtotal { background: #f8f8f8; }
        .room-subtotal td { font-weight: bold; padding: 4px 8px; font-size: 10.5px; }

        .totals-wrapper { margin-top: 20px; text-align: right; }
        .totals-table { display: inline-table; width: 260px; border-collapse: collapse; }
        .totals-table td { padding: 3px 8px; font-size: 11px; }
        .totals-table .t-label { color: #555; text-align: left; }
        .totals-table .t-amount { text-align: right; }
        .totals-table .t-grand td { font-weight: bold; font-size: 13px; border-top: 2px solid #1d4ed8; padding-top: 5px; }

        .payment-section { margin-top: 20px; }
        .payment-section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #555; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 8px; }
        .payment-table td { padding: 4px 8px; font-size: 10.5px; border-bottom: 1px solid #f0f0f0; }
        .payment-table .paid-label { color: #15803d; font-weight: bold; }
        .balance-row td { font-weight: bold; color: #b91c1c; border-top: 1px solid #ddd; padding-top: 5px; }

        .notes-section { margin-top: 16px; padding: 10px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 3px; }
        .notes-label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 4px; }

        .remittance-box { margin-top: 30px; border: 1px dashed #aaa; padding: 14px 18px; }
        .remittance-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #555; margin-bottom: 10px; }
        .sig-line { border-bottom: 1px solid #374151; margin-top: 28px; margin-bottom: 4px; }
        .sig-label { font-size: 10px; color: #555; }

        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>

@php
    use Illuminate\Support\Facades\DB;
    $settings    = DB::table('app_settings')->pluck('value', 'key');
    $companyName = $settings['branding_company_name'] ?? 'RM Flooring';
    $tagline     = $settings['branding_tagline'] ?? '';
    $phone       = $settings['branding_phone'] ?? '';
    $email       = $settings['branding_email'] ?? '';
    $website     = $settings['branding_website'] ?? '';
    $logoPath    = $settings['branding_logo_path'] ?? null;
    $street      = $settings['branding_address'] ?? '';
    $city        = $settings['branding_city'] ?? '';
    $province    = $settings['branding_province'] ?? '';
    $postal      = $settings['branding_postal'] ?? '';

    $statusLabel = match($invoice->status) {
        'draft'          => 'DRAFT',
        'sent'           => 'SENT',
        'paid'           => 'PAID',
        'overdue'        => 'OVERDUE',
        'partially_paid' => 'PARTIALLY PAID',
        'voided'         => 'VOIDED',
        default          => strtoupper($invoice->status),
    };
    $statusColor = match($invoice->status) {
        'paid'           => '#15803d',
        'overdue'        => '#b91c1c',
        'voided'         => '#6b7280',
        'partially_paid' => '#b45309',
        default          => '#1d4ed8',
    };
@endphp

{{-- Header --}}
<div class="header">
    <div style="display:table; width:100%">
        <div style="display:table-cell; vertical-align:top; width:60%">
            @if($logoPath && file_exists(storage_path('app/public/' . ltrim(str_replace('/storage/', '', $logoPath), '/'))))
                @php
                    $fullPath = storage_path('app/public/' . ltrim(str_replace('/storage/', '', $logoPath), '/'));
                    $mime     = mime_content_type($fullPath);
                    $logoData = base64_encode(file_get_contents($fullPath));
                @endphp
                <img src="data:{{ $mime }};base64,{{ $logoData }}" style="height:70px; max-width:260px; object-fit:contain;">
            @else
                <div class="company-name">{{ $companyName }}</div>
                @if($tagline)<div class="company-sub">{{ $tagline }}</div>@endif
            @endif
            @php $brandAddress = implode(', ', array_filter([$street, $city, $province, $postal])); @endphp
            @if($brandAddress)
                <div style="margin-top:4px; font-size:10px; color:#555;">{{ $brandAddress }}</div>
            @endif
            @if($phone || $email)
                <div style="margin-top:2px; font-size:10px; color:#555;">
                    {{ $phone }}{{ $phone && $email ? ' · ' : '' }}{{ $email }}
                </div>
            @endif
        </div>
        <div style="display:table-cell; vertical-align:top; text-align:right;">
            <div class="doc-title">INVOICE</div>
            <div class="doc-meta">{{ $invoice->invoice_number }}</div>
            <div class="doc-meta">Date: {{ $invoice->created_at->format('F j, Y') }}</div>
            @if($invoice->due_date)
                <div class="doc-meta">Due: {{ $invoice->due_date->format('F j, Y') }}</div>
            @endif
            <div class="doc-status" style="color:{{ $statusColor }}">{{ $statusLabel }}</div>
        </div>
    </div>
</div>

{{-- Info Grid --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-section-title">Bill To</div>
        @if($sale->homeowner_name ?? $sale->opportunity?->customer?->company_name)
            <div class="info-row" style="font-weight:bold; font-size:12px;">
                {{ $sale->homeowner_name ?? $sale->opportunity?->customer?->company_name }}
            </div>
        @endif
        @if($sale->job_address)
            <div class="info-row" style="margin-top:3px; white-space:pre-line;">{{ $sale->job_address }}</div>
        @endif
        @if($sale->job_phone)
            <div class="info-row">{{ $sale->job_phone }}</div>
        @endif
        @if($sale->job_email)
            <div class="info-row">{{ $sale->job_email }}</div>
        @endif
    </div>
    <div class="info-col">
        <div class="info-section-title">Invoice Details</div>
        <div class="info-row"><span class="info-key">Invoice #: </span><strong>{{ $invoice->invoice_number }}</strong></div>
        <div class="info-row"><span class="info-key">Sale #: </span>{{ $sale->sale_number }}</div>
        @if($sale->job_name)
            <div class="info-row"><span class="info-key">Job: </span>{{ $sale->job_name }}</div>
        @endif
        @if($sale->job_no)
            <div class="info-row"><span class="info-key">Job #: </span>{{ $sale->job_no }}</div>
        @endif
        @if($invoice->customer_po_number)
            <div class="info-row"><span class="info-key">Customer PO #: </span>{{ $invoice->customer_po_number }}</div>
        @endif
        @if($invoice->paymentTerm)
            <div class="info-row"><span class="info-key">Payment Terms: </span>{{ $invoice->paymentTerm->name }}</div>
        @endif
    </div>
</div>

{{-- Line Items --}}
@foreach($invoice->rooms as $room)
<div class="room">
    <div class="room-header">{{ $room->name }}</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right" style="width:70px">Qty</th>
                <th class="right" style="width:80px">Unit Price</th>
                <th class="right" style="width:80px">Tax</th>
                <th class="right" style="width:90px">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($room->items as $item)
            <tr>
                <td>
                    <div class="item-label">{{ $item->label }}</div>
                    <div class="item-meta">{{ ucfirst($item->item_type) }}{{ $item->unit ? ' · ' . strtoupper($item->unit) : '' }}</div>
                </td>
                <td class="right">{{ number_format((float)$item->quantity, 2) }}</td>
                <td class="right">${{ number_format((float)$item->sell_price, 2) }}</td>
                <td class="right">${{ number_format((float)$item->tax_amount, 2) }}</td>
                <td class="right">${{ number_format((float)$item->line_total, 2) }}</td>
            </tr>
            @endforeach
            <tr class="room-subtotal">
                <td colspan="4" style="text-align:right; color:#555;">Room Subtotal</td>
                <td class="right">${{ number_format($room->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endforeach

{{-- Totals --}}
<div class="totals-wrapper">
    <table class="totals-table">
        <tr>
            <td class="t-label">Subtotal</td>
            <td class="t-amount">${{ number_format((float)$invoice->subtotal, 2) }}</td>
        </tr>
        @if($taxRates->isNotEmpty())
            @foreach($taxRates as $taxRate)
                @php $lineAmt = round((float)$invoice->subtotal * ((float)$taxRate->sales_rate / 100), 2); @endphp
            <tr>
                <td class="t-label">{{ $taxRate->name }} ({{ number_format((float)$taxRate->sales_rate, 0) }}%)</td>
                <td class="t-amount">${{ number_format($lineAmt, 2) }}</td>
            </tr>
            @endforeach
        @else
        <tr>
            <td class="t-label">Tax</td>
            <td class="t-amount">${{ number_format((float)$invoice->tax_amount, 2) }}</td>
        </tr>
        @endif
        <tr class="t-grand">
            <td class="t-label">Total</td>
            <td class="t-amount">${{ number_format((float)$invoice->grand_total, 2) }}</td>
        </tr>
        @if((float)$invoice->amount_paid > 0)
        <tr>
            <td class="t-label" style="color:#15803d; padding-top:8px;">Paid</td>
            <td class="t-amount" style="color:#15803d; padding-top:8px;">(${{ number_format((float)$invoice->amount_paid, 2) }})</td>
        </tr>
        <tr>
            <td class="t-label" style="font-weight:bold; color:{{ $invoice->balance_due <= 0 ? '#15803d' : '#b91c1c' }}; border-top:1px solid #ddd; padding-top:5px;">Balance Due</td>
            <td class="t-amount" style="font-weight:bold; color:{{ $invoice->balance_due <= 0 ? '#15803d' : '#b91c1c' }}; border-top:1px solid #ddd; padding-top:5px;">${{ number_format(max(0, $invoice->balance_due), 2) }}</td>
        </tr>
        @endif
    </table>
</div>

{{-- Payment history --}}
@if($invoice->payments->isNotEmpty())
<div class="payment-section" style="margin-top:24px;">
    <div class="payment-section-title">Payment History</div>
    <table class="payment-table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="text-align:left; padding:4px 8px; font-size:9px; text-transform:uppercase; color:#666; border-bottom:1px solid #ddd;">Date</th>
                <th style="text-align:left; padding:4px 8px; font-size:9px; text-transform:uppercase; color:#666; border-bottom:1px solid #ddd;">Method</th>
                <th style="text-align:left; padding:4px 8px; font-size:9px; text-transform:uppercase; color:#666; border-bottom:1px solid #ddd;">Reference</th>
                <th style="text-align:right; padding:4px 8px; font-size:9px; text-transform:uppercase; color:#666; border-bottom:1px solid #ddd;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->payments as $payment)
            <tr>
                <td>{{ $payment->payment_date->format('M j, Y') }}</td>
                <td>{{ $payment->method_label }}</td>
                <td>{{ $payment->reference_number ?: '—' }}</td>
                <td style="text-align:right; color:#15803d; font-weight:bold;">${{ number_format((float)$payment->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Notes --}}
@if($invoice->notes)
<div class="notes-section" style="margin-top:20px;">
    <div class="notes-label">Notes</div>
    <div>{{ $invoice->notes }}</div>
</div>
@endif

{{-- Remittance stub --}}
@if($invoice->balance_due > 0 && $invoice->status !== 'voided')
<div class="remittance-box">
    <div class="remittance-title">Remittance — Please return with payment</div>
    <div style="display:table; width:100%">
        <div style="display:table-cell; font-size:11px;">
            <div><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</div>
            <div><strong>Sale #:</strong> {{ $sale->sale_number }}</div>
            @if($invoice->due_date)<div><strong>Due Date:</strong> {{ $invoice->due_date->format('F j, Y') }}</div>@endif
        </div>
        <div style="display:table-cell; text-align:right; font-size:13px; font-weight:bold;">
            Amount Due: ${{ number_format(max(0, $invoice->balance_due), 2) }}
        </div>
    </div>
</div>
@endif

<div class="footer">
    {{ $companyName }}{{ $website ? ' · ' . $website : '' }}{{ $phone ? ' · ' . $phone : '' }}
</div>

</body>
</html>
