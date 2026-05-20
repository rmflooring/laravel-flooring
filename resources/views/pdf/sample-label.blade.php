<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: {{ $format === '5388' ? '3in 5in' : ($format === 'ql700' ? '62mm 90mm' : '3.5in 2in') }};
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
            height: {{ $format === '5388' ? '340pt' : ($format === 'ql700' ? '243pt' : '132pt') }};
            padding: {{ $format === '5388' ? '10pt' : ($format === 'ql700' ? '6pt' : '6pt') }};
            overflow: hidden;
            page-break-after: avoid;
        }

        /* Shared */
        .logo img { max-height: {{ $format === '5388' ? '36px' : ($format === 'ql700' ? '28px' : '22px') }}; max-width: 110px; }
        .company-name {
            font-size: {{ $format === '5388' ? '10pt' : ($format === 'ql700' ? '9pt' : '7.5pt') }};
            font-weight: bold;
            color: #1d4ed8;
        }

        .product-name {
            font-size: {{ $format === '5388' ? '13pt' : ($format === 'ql700' ? '11pt' : '9pt') }};
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
            margin-top: {{ $format === '5388' ? '6px' : ($format === 'ql700' ? '5px' : '3px') }};
        }

        .meta {
            color: #6b7280;
            font-size: {{ $format === '5388' ? '8pt' : ($format === 'ql700' ? '7pt' : '6pt') }};
            margin-top: 2px;
            line-height: 1.5;
        }

        .price-line {
            margin-top: {{ $format === '5388' ? '10px' : ($format === 'ql700' ? '6px' : '4px') }};
        }
        .price-label { font-size: {{ $format === '5388' ? '7pt' : ($format === 'ql700' ? '6pt' : '5.5pt') }}; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; }
        .price-value { font-size: {{ $format === '5388' ? '16pt' : ($format === 'ql700' ? '18pt' : '11pt') }}; font-weight: bold; color: #1d4ed8; line-height: 1.1; }

        .sample-id {
            font-size: {{ $format === '5388' ? '7pt' : ($format === 'ql700' ? '6.5pt' : '5.5pt') }};
            color: #9ca3af;
            font-family: 'DejaVu Sans Mono', monospace;
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
            margin: {{ $format === '5388' ? '8px 0' : ($format === 'ql700' ? '6px 0' : '4px 0') }};
        }
    </style>
</head>
<body>

@if ($format === 'ql700')
{{-- ── Brother QL-700 DK-2205: 62mm × 90mm ─────────── --}}
<div class="label">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="58%" valign="top" style="padding-right:4pt;">
                <div class="logo">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
                    @else
                        <div class="company-name">{{ $companyName }}</div>
                    @endif
                </div>
            </td>
            <td width="42%" valign="top" align="center">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="68" height="68" alt="QR">
                <div class="scan-hint" style="margin-top:2pt;">Scan for details</div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    <div class="product-name" style="margin-top:0;">{{ $sample->productStyle->name }}</div>

    <div class="meta" style="margin-top:4pt;">
        @if ($sample->productStyle->productLine?->manufacturer)
            <strong>{{ $sample->productStyle->productLine->manufacturer }}</strong><br>
        @endif
        @if ($sample->productStyle->productLine?->name)
            {{ $sample->productStyle->productLine->name }}<br>
        @endif
        @if ($sample->productStyle->color)
            {{ $sample->productStyle->color }}<br>
        @endif
        @if ($sample->productStyle->sku)
            SKU: {{ $sample->productStyle->sku }}<br>
        @endif
    </div>

    <hr class="divider">

    @if ($sample->effective_price)
    <div class="price-line" style="margin-top:0;">
        <div class="price-label">Price / Unit</div>
        <div class="price-value">${{ number_format($sample->effective_price, 2) }}</div>
    </div>
    @endif

    <div class="sample-id" style="margin-top:6pt;">
        {{ $sample->sample_id }}
        @if ($sample->location) &middot; {{ $sample->location }}@endif
    </div>
</div>

@elseif ($format === '5371')
{{-- ── Avery 5371: 3.5" × 2" business card ─────────── --}}
<div class="label">
    <table width="100%" style="height:120pt;" cellpadding="0" cellspacing="0">
        <tr>
            {{-- Left: content --}}
            <td width="72%" valign="top" style="padding-right:4pt;">
                <div class="logo">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
                    @else
                        <div class="company-name">{{ $companyName }}</div>
                    @endif
                </div>

                <div class="product-name">{{ $sample->productStyle->name }}</div>
                <div class="meta">
                    @if ($sample->productStyle->productLine?->manufacturer){{ $sample->productStyle->productLine->manufacturer }}@endif
                    @if ($sample->productStyle->color) &middot; {{ $sample->productStyle->color }}@endif
                </div>

                @if ($sample->effective_price)
                <div class="price-line">
                    <div class="price-label">Price / Unit</div>
                    <div class="price-value">${{ number_format($sample->effective_price, 2) }}</div>
                </div>
                @endif

                <div class="sample-id" style="margin-top:4pt;">{{ $sample->sample_id }}</div>
            </td>

            {{-- Right: QR --}}
            <td width="28%" valign="middle" align="center">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="62" height="62" alt="QR">
                <div class="scan-hint" style="margin-top:2pt;">Scan for<br>details</div>
            </td>
        </tr>
    </table>
</div>

@else
{{-- ── Avery 5388: 3" × 5" index card ──────────────── --}}
<div class="label">

    {{-- Top: logo + QR --}}
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="68%" valign="top" style="padding-right:6pt;">
                <div class="logo">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="{{ $companyName }}">
                    @else
                        <div class="company-name">{{ $companyName }}</div>
                    @endif
                </div>
                <div class="product-name">{{ $sample->productStyle->name }}</div>
            </td>
            <td width="32%" valign="top" align="center">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="86" height="86" alt="QR">
                <div class="scan-hint" style="margin-top:2pt;">Scan to view<br>details &amp; pricing</div>
            </td>
        </tr>
    </table>

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
