<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
@php
    $isZebra    = ($format ?? 'standard') === 'zebra';
    $bodyW      = $isZebra ? '432pt' : '226.77pt';
    $bodyH      = $isZebra ? '288pt' : '170.08pt';
    $itemNameSz = $isZebra ? '14pt' : '11pt';
    $qtyNumSz   = $isZebra ? '24pt' : '18pt';
    $qrSize     = $isZebra ? 90 : 64;
    $qrColW     = $isZebra ? '100pt' : '70pt';
    $qrImgSz    = $isZebra ? '90pt' : '64pt';
    $bodyGap    = $isZebra ? '10pt' : '6pt';

    $mobileUrl  = route('mobile.inventory.show', $receipt);
    $qrSvg      = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize)->margin(1)->generate($mobileUrl);
    $allocSale  = $receipt->allocations->first()?->sale;
    $qtyDisplay = rtrim(rtrim(number_format((float)$receipt->quantity_received, 2), '0'), '.');
@endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            width: {{ $bodyW }};
            height: {{ $bodyH }};
            padding: 8pt;
        }

        .tag {
            width: 100%;
            height: 100%;
            border: 1.5pt solid #1d4ed8;
            border-radius: 4pt;
            padding: 7pt;
            display: flex;
            flex-direction: column;
        }

        .tag-header {
            border-bottom: 1pt solid #dde4f8;
            padding-bottom: 5pt;
            margin-bottom: 5pt;
        }

        .tag-label {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
            color: #1d4ed8;
            margin-bottom: 1pt;
        }

        .item-name {
            font-size: {{ $itemNameSz }};
            font-weight: bold;
            color: #111;
            line-height: 1.2;
        }

        .tag-body {
            display: flex;
            gap: {{ $bodyGap }};
            flex: 1;
        }

        .tag-info { flex: 1; }

        .info-row { margin-bottom: 3pt; }

        .info-label {
            font-size: 6.5pt;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
        }

        .info-value {
            font-size: 9pt;
            font-weight: bold;
            color: #111;
        }

        .qty-box {
            background: #eff6ff;
            border: 1pt solid #bfdbfe;
            border-radius: 3pt;
            padding: 4pt 6pt;
            text-align: center;
            margin-bottom: 4pt;
        }

        .qty-number {
            font-size: {{ $qtyNumSz }};
            font-weight: bold;
            color: #1d4ed8;
            line-height: 1;
        }

        .qty-unit {
            font-size: 7pt;
            color: #555;
            margin-top: 1pt;
        }

        .alloc-badge {
            background: #f0fdf4;
            border: 1pt solid #bbf7d0;
            border-radius: 3pt;
            padding: 3pt 5pt;
            font-size: 7.5pt;
            font-weight: bold;
            color: #166534;
            text-align: center;
            line-height: 1.3;
        }

        .stock-badge {
            background: #fefce8;
            border: 1pt solid #fde68a;
            border-radius: 3pt;
            padding: 3pt 5pt;
            font-size: 7pt;
            font-weight: bold;
            color: #854d0e;
            text-align: center;
        }

        .qr-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            width: {{ $qrColW }};
        }

        .qr-img {
            width: {{ $qrImgSz }};
            height: {{ $qrImgSz }};
        }

        .qr-caption {
            font-size: 5.5pt;
            color: #aaa;
            margin-top: 2pt;
            text-align: center;
        }

        .receipt-id {
            margin-top: auto;
            font-size: 6pt;
            color: #bbb;
            text-align: right;
            padding-top: 4pt;
        }
    </style>
</head>
<body>

<div class="tag">

    <div class="tag-header">
        <div class="tag-label">Inventory Tag</div>
        <div class="item-name">{{ $receipt->item_name }}</div>
    </div>

    <div class="tag-body">

        <div class="tag-info">

            <div class="qty-box">
                <div class="qty-number">{{ $qtyDisplay }}</div>
                <div class="qty-unit">{{ $receipt->unit ?: 'units' }}</div>
            </div>

            @if ($allocSale)
                <div class="alloc-badge">
                    Sale #{{ $allocSale->sale_number }}<br>
                    {{ $allocSale->customer_name ?? $allocSale->job_name ?? '' }}
                </div>
            @else
                <div class="stock-badge">Stock / Unallocated</div>
            @endif

            <div style="margin-top: 4pt;">
                @if ($receipt->purchaseOrder)
                    <div class="info-row">
                        <div class="info-label">PO</div>
                        <div class="info-value">{{ $receipt->purchaseOrder->po_number }}</div>
                    </div>
                @endif
                <div class="info-row">
                    <div class="info-label">Received</div>
                    <div class="info-value">{{ $receipt->received_date->format('M j, Y') }}</div>
                </div>
            </div>
        </div>

        <div class="qr-col">
            <div class="qr-img">{!! $qrSvg !!}</div>
            <div class="qr-caption">Scan for details</div>
        </div>

    </div>

    <div class="receipt-id">Receipt #{{ $receipt->id }}</div>

</div>
</body>
</html>
