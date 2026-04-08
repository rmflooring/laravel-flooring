<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: {{ $format === '5388' ? '3in 5in' : '3.5in 2in' }};
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: {{ $format === '5388' ? '9pt' : '7pt' }};
            color: #1f2937;
        }

        .label {
            width: 100%;
            height: {{ $format === '5388' ? '354pt' : '140pt' }};
            padding: {{ $format === '5388' ? '10pt' : '6pt' }};
            overflow: hidden;
            page-break-after: avoid;
            display: flex;
            flex-direction: {{ $format === '5388' ? 'column' : 'row' }};
            gap: {{ $format === '5388' ? '8pt' : '6pt' }};
        }

        /* ── 5371 layout (3.5" × 2") ─────────────────── */
        .layout-5371 .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }
        .layout-5371 .qr-block {
            width: 70px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
        }
        .layout-5371 .qr-block img {
            width: 66px;
            height: 66px;
        }

        /* ── 5388 layout (3" × 5") ─────────────────── */
        .layout-5388 .top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 8px;
        }
        .layout-5388 .content { flex: 1; min-width: 0; }
        .layout-5388 .qr-block {
            width: 90px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .layout-5388 .qr-block img {
            width: 86px;
            height: 86px;
        }

        /* Shared */
        .logo img { max-height: {{ $format === '5388' ? '36px' : '22px' }}; max-width: 120px; }
        .company-name {
            font-size: {{ $format === '5388' ? '10pt' : '7.5pt' }};
            font-weight: bold;
            color: #1d4ed8;
        }

        .product-name {
            font-size: {{ $format === '5388' ? '13pt' : '9pt' }};
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
            margin-top: {{ $format === '5388' ? '6px' : '3px' }};
        }

        .meta {
            color: #6b7280;
            font-size: {{ $format === '5388' ? '8pt' : '6pt' }};
            margin-top: 2px;
            line-height: 1.4;
        }

        .price-line {
            margin-top: {{ $format === '5388' ? '10px' : '4px' }};
        }
        .price-label { font-size: {{ $format === '5388' ? '7pt' : '5.5pt' }}; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
        .price-value { font-size: {{ $format === '5388' ? '16pt' : '11pt' }}; font-weight: bold; color: #1d4ed8; line-height: 1.1; }

        .sample-id {
            font-size: {{ $format === '5388' ? '7pt' : '5.5pt' }};
            color: #9ca3af;
            font-family: 'DejaVu Sans Mono', monospace;
            margin-top: auto;
        }

        .scan-hint {
            font-size: 5.5pt;
            color: #9ca3af;
            text-align: center;
            line-height: 1.3;
        }

        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: {{ $format === '5388' ? '8px 0' : '4px 0' }};
        }
    </style>
</head>
<body>

@if ($format === '5371')
{{-- ── Avery 5371: 3.5" × 2" business card ─────────── --}}
<div class="label layout-5371">

    <div class="content">
        {{-- Logo / company --}}
        <div class="logo">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
            @else
                <div class="company-name">{{ $companyName }}</div>
            @endif
        </div>

        {{-- Product name + meta --}}
        <div class="product-name">{{ $sample->productStyle->name }}</div>
        <div class="meta">
            @if ($sample->productStyle->productLine?->manufacturer){{ $sample->productStyle->productLine->manufacturer }}@endif
            @if ($sample->productStyle->color) &middot; {{ $sample->productStyle->color }}@endif
        </div>

        {{-- Price --}}
        @if ($sample->effective_price)
        <div class="price-line">
            <div class="price-label">Price / Unit</div>
            <div class="price-value">${{ number_format($sample->effective_price, 2) }}</div>
        </div>
        @endif

        {{-- Sample ID --}}
        <div class="sample-id">{{ $sample->sample_id }}</div>
    </div>

    {{-- QR --}}
    <div class="qr-block">
        <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR">
        <div class="scan-hint">Scan for<br>details</div>
    </div>
</div>

@else
{{-- ── Avery 5388: 3" × 5" index card ──────────────── --}}
<div class="label layout-5388">

    {{-- Top: logo + QR --}}
    <div class="top-row">
        <div class="content">
            <div class="logo">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
                @else
                    <div class="company-name">{{ $companyName }}</div>
                @endif
            </div>
            <div class="product-name">{{ $sample->productStyle->name }}</div>
        </div>
        <div class="qr-block">
            <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR">
            <div class="scan-hint">Scan to view<br>details &amp; pricing</div>
        </div>
    </div>

    <hr class="divider">

    {{-- Meta --}}
    <div class="meta">
        @if ($sample->productStyle->productLine?->manufacturer)
            <strong>Manufacturer:</strong> {{ $sample->productStyle->productLine->manufacturer }}<br>
        @endif
        @if ($sample->productStyle->productLine?->name)
            <strong>Line:</strong> {{ $sample->productStyle->productLine->name }}<br>
        @endif
        @if ($sample->productStyle->color)
            <strong>Colour:</strong> {{ $sample->productStyle->color }}<br>
        @endif
        @if ($sample->productStyle->sku)
            <strong>SKU:</strong> {{ $sample->productStyle->sku }}<br>
        @endif
        @if ($sample->productStyle->style_number)
            <strong>Style #:</strong> {{ $sample->productStyle->style_number }}<br>
        @endif
    </div>

    <hr class="divider">

    {{-- Price --}}
    @if ($sample->effective_price)
    <div class="price-line">
        <div class="price-label">Material Price / Unit</div>
        <div class="price-value">${{ number_format($sample->effective_price, 2) }}</div>
    </div>
    @endif

    {{-- Sample ID footer --}}
    <div class="sample-id" style="margin-top: auto; padding-top: 8px;">
        Sample ID: {{ $sample->sample_id }}
        @if ($sample->location)
         &middot; {{ $sample->location }}
        @endif
    </div>

</div>
@endif

</body>
</html>
