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
        .layout-5371 .qr-block img { width: 66px; height: 66px; }

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
        .layout-5388 .qr-block img { width: 86px; height: 86px; }

        /* Shared */
        .logo img { max-height: {{ $format === '5388' ? '36px' : '22px' }}; max-width: 120px; }
        .company-name { font-size: {{ $format === '5388' ? '10pt' : '7.5pt' }}; font-weight: bold; color: #1d4ed8; }

        .set-badge {
            display: inline-block;
            font-size: {{ $format === '5388' ? '6.5pt' : '5pt' }};
            font-weight: bold;
            color: #4f46e5;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 3px;
            padding: 1px 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .product-name {
            font-size: {{ $format === '5388' ? '13pt' : '9pt' }};
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
            margin-top: 2px;
        }

        .meta {
            color: #6b7280;
            font-size: {{ $format === '5388' ? '8pt' : '6pt' }};
            margin-top: 2px;
            line-height: 1.4;
        }

        .set-id {
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
            margin: {{ $format === '5388' ? '6px 0' : '4px 0' }};
        }

        .styles-heading {
            font-size: {{ $format === '5388' ? '7pt' : '5.5pt' }};
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .style-row {
            font-size: {{ $format === '5388' ? '8pt' : '6pt' }};
            color: #374151;
            line-height: 1.5;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 1px;
            margin-bottom: 1px;
        }

        .style-color {
            color: #9ca3af;
            font-size: {{ $format === '5388' ? '7pt' : '5.5pt' }};
        }

        .style-price {
            float: right;
            font-weight: bold;
            color: #1d4ed8;
            font-size: {{ $format === '5388' ? '8pt' : '6pt' }};
        }

        .price-range-label { font-size: 5.5pt; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
        .price-range-value { font-size: 11pt; font-weight: bold; color: #1d4ed8; line-height: 1.1; }
    </style>
</head>
<body>

@if ($format === '5371')
{{-- ── Avery 5371: 3.5" × 2" ─────────────────────────── --}}
<div class="label layout-5371">

    <div class="content">
        <div class="logo">
            @if ($logoDataUri)
                <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
            @else
                <div class="company-name">{{ $companyName }}</div>
            @endif
        </div>

        <div class="set-badge">Sample Set</div>
        <div class="product-name">{{ $sampleSet->name ?? $sampleSet->productLine->name }}</div>
        <div class="meta">
            {{ $sampleSet->productLine->manufacturer }}
            &middot; {{ $sampleSet->items->count() }} {{ $sampleSet->items->count() === 1 ? 'style' : 'styles' }}
        </div>

        @php
            $prices = $sampleSet->items->pluck('display_price')->filter()->map(fn($p) => (float)$p);
        @endphp
        @if ($prices->isNotEmpty())
        <div class="price-line" style="margin-top: 4px;">
            <div class="price-range-label">From</div>
            <div class="price-range-value">${{ number_format($prices->min(), 2) }}</div>
        </div>
        @endif

        <div class="set-id">{{ $sampleSet->set_id }}</div>
    </div>

    <div class="qr-block">
        <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR">
        <div class="scan-hint">Scan for<br>details</div>
    </div>
</div>

@else
{{-- ── Avery 5388: 3" × 5" ────────────────────────────── --}}
<div class="label layout-5388">

    <div class="top-row">
        <div class="content">
            <div class="logo">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
                @else
                    <div class="company-name">{{ $companyName }}</div>
                @endif
            </div>
            <div class="set-badge">Sample Set</div>
            <div class="product-name">{{ $sampleSet->name ?? $sampleSet->productLine->name }}</div>
        </div>
        <div class="qr-block">
            <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR">
            <div class="scan-hint">Scan to view<br>styles &amp; details</div>
        </div>
    </div>

    <hr class="divider">

    <div class="meta">
        <strong>Manufacturer:</strong> {{ $sampleSet->productLine->manufacturer }}<br>
        <strong>Line:</strong> {{ $sampleSet->productLine->name }}<br>
        @if ($sampleSet->location)
            <strong>Location:</strong> {{ $sampleSet->location }}<br>
        @endif
    </div>

    <hr class="divider">

    <div class="styles-heading">Styles in this set ({{ $sampleSet->items->count() }})</div>
    @foreach ($sampleSet->items as $item)
        <div class="style-row">
            @if ($item->display_price)
                <span class="style-price">${{ number_format($item->display_price, 2) }}</span>
            @endif
            {{ $item->productStyle->name }}
            @if ($item->productStyle->color)
                <span class="style-color">&middot; {{ $item->productStyle->color }}</span>
            @endif
        </div>
    @endforeach

    <div class="set-id" style="margin-top: auto; padding-top: 6px;">
        Set ID: {{ $sampleSet->set_id }}
    </div>

</div>
@endif

</body>
</html>
