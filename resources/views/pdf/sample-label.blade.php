<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0;
            size: {{ $format === '5388' ? '3in 5in' : ($format === 'ql700' ? '90mm 62mm' : '3.5in 2in') }};
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
            height: {{ $format === '5388' ? '340pt' : ($format === 'ql700' ? '164pt' : '132pt') }};
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
{{-- ── Brother QL-700 DK-2205: landscape PDF 90mm×62mm ────────────────── --}}
{{-- Brother driver rotates this 90° so it prints portrait on the 62mm tape --}}
<div class="label">
    <table width="100%" style="height:164pt;" cellpadding="0" cellspacing="0">
        <tr>
            {{-- Logo --}}
            <td width="22%" valign="middle" align="center" style="padding-right:5pt; border-right:1pt solid #e5e7eb;">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="{{ $companyName }}" style="max-width:46pt; max-height:46pt;">
                @else
                    <div style="font-size:8pt; font-weight:bold; color:#1d4ed8; text-align:center;">{{ $companyName }}</div>
                @endif
            </td>

            {{-- Product info --}}
            <td width="42%" valign="middle" style="padding:0 8pt; border-right:1pt solid #e5e7eb;">
                <div style="font-size:10pt; font-weight:bold; color:#111827; line-height:1.2;">{{ $sample->productStyle->name }}</div>
                <div style="font-size:7pt; color:#6b7280; margin-top:5pt; line-height:1.5;">
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
            </td>

            {{-- Price --}}
            <td width="18%" valign="middle" align="center" style="padding:0 4pt; border-right:1pt solid #e5e7eb;">
                @if ($showPrice && $sample->effective_price)
                    <div style="font-size:5.5pt; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Price / Unit</div>
                    <div style="font-size:14pt; font-weight:bold; color:#1d4ed8; line-height:1.1;">${{ number_format($sample->effective_price, 2) }}</div>
                @endif
            </td>

            {{-- QR + Sample ID --}}
            <td width="18%" valign="middle" align="center" style="padding-left:5pt;">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="52" height="52" alt="QR">
                <div style="font-size:5pt; color:#9ca3af; font-family:'DejaVu Sans Mono',monospace; text-align:center; margin-top:3pt;">{{ $sample->sample_id }}</div>
            </td>
        </tr>
    </table>
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
                    @if ($sample->productStyle->productLine?->name) &middot; {{ $sample->productStyle->productLine->name }}@endif
                    @if ($sample->productStyle->color) &middot; {{ $sample->productStyle->color }}@endif
                </div>

                @if ($showPrice && $sample->effective_price)
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
    @if ($showPrice && $sample->effective_price)
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
