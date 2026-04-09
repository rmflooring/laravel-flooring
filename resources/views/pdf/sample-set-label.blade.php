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
            color: #1f2937;
        }

        .label {
            width: {{ $format === '5388' ? '216pt' : '252pt' }};
            max-height: {{ $format === '5388' ? '348pt' : '136pt' }};
            padding: {{ $format === '5388' ? '8pt' : '5pt' }};
            overflow: hidden;
            page-break-inside: avoid;
            page-break-after: avoid;
        }

        /* ── Shared ─────────────────────────────────────── */
        .logo img    { max-height: {{ $format === '5388' ? '30px' : '20px' }}; max-width: 110px; }
        .company-name { font-size: {{ $format === '5388' ? '9pt' : '7pt' }}; font-weight: bold; color: #1d4ed8; }

        .set-badge {
            font-size: 5.5pt;
            font-weight: bold;
            color: #4f46e5;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 2px;
            padding: 1px 3px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .product-name {
            font-size: {{ $format === '5388' ? '12pt' : '9pt' }};
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
            margin-top: 2pt;
        }

        .meta {
            color: #6b7280;
            font-size: {{ $format === '5388' ? '7.5pt' : '6pt' }};
            margin-top: 2pt;
            line-height: 1.4;
        }

        .set-id {
            font-size: {{ $format === '5388' ? '6.5pt' : '5.5pt' }};
            color: #9ca3af;
            font-family: 'DejaVu Sans Mono', monospace;
        }

        .scan-hint {
            font-size: 5pt;
            color: #9ca3af;
            text-align: center;
            line-height: 1.3;
        }

        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: {{ $format === '5388' ? '5pt 0' : '3pt 0' }};
        }

        /* ── Styles table ───────────────────────────────── */
        .styles-heading {
            font-size: 6.5pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2pt;
        }

        .styles-table {
            width: 100%;
            border-collapse: collapse;
        }

        .styles-table td {
            font-size: 7.5pt;
            color: #374151;
            line-height: 1.5;
            border-bottom: 1px solid #f3f4f6;
            padding: 1pt 0;
            vertical-align: middle;
        }

        .td-name  { width: 100%; }

        .style-color { color: #9ca3af; font-size: 6.5pt; }

        /* ── Price range (5371) ─────────────────────────── */
        .price-range-label { font-size: 5pt; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.4px; }
        .price-range-value { font-size: 10pt; font-weight: bold; color: #1d4ed8; line-height: 1.1; }
    </style>
</head>
<body>

@if ($format === '5371')
{{-- ── Avery 5371: 3.5" × 2" ─────────────────────────── --}}
<div class="label">
    <table width="100%" style="height:126pt;" cellpadding="0" cellspacing="0">
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

                <div style="margin-top:3pt;">
                    <span class="set-badge">Sample Set</span>
                </div>
                <div class="product-name">{{ $sampleSet->name ?? $sampleSet->productLine->name }}</div>
                <div class="meta">
                    {{ $sampleSet->productLine->manufacturer }}
                    &middot; {{ $sampleSet->items->count() }} {{ $sampleSet->items->count() === 1 ? 'style' : 'styles' }}
                </div>

                @php
                    $prices = $sampleSet->items->pluck('display_price')->filter()->map(fn($p) => (float)$p);
                @endphp
                @if ($prices->isNotEmpty())
                <div style="margin-top:4pt;">
                    <div class="price-range-label">From</div>
                    <div class="price-range-value">${{ number_format($prices->min(), 2) }}</div>
                </div>
                @endif

                <div class="set-id" style="margin-top:4pt;">{{ $sampleSet->set_id }}</div>
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
{{-- ── Avery 5388: 3" × 5" ────────────────────────────── --}}
<div class="label">

    {{-- Top row: logo+name | QR --}}
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
                <div style="margin-top:3pt;"><span class="set-badge">Sample Set</span></div>
                <div class="product-name">{{ $sampleSet->name ?? $sampleSet->productLine->name }}</div>
            </td>
            <td width="32%" valign="top" align="center">
                <img src="data:image/svg+xml;base64,{{ $qrSvg }}" width="80" height="80" alt="QR">
                <div class="scan-hint" style="margin-top:2pt;">Scan to view<br>styles &amp; details</div>
            </td>
        </tr>
    </table>

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
    <table class="styles-table">
        @foreach ($sampleSet->items as $item)
        <tr>
            <td class="td-name">
                {{ $item->productStyle->name }}
                @if ($item->productStyle->color)
                    <span class="style-color">&middot; {{ $item->productStyle->color }}</span>
                @endif
                @if ($item->display_price)
                    <br><span style="font-weight:bold;color:#1d4ed8;font-size:7pt;">${{ number_format($item->display_price, 2) }}</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>

    <div class="set-id" style="margin-top:4pt;">Set ID: {{ $sampleSet->set_id }}</div>

</div>
@endif

</body>
</html>
