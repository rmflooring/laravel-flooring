@php
    $mmToPt     = 2.8346;
    $baseTopPt  = 36;
    $baseLeftPt = 11;
    // The shift needs three different approaches ruled out before landing here, all
    // confirmed live 2026-10-20 by actually rendering and visually/pixel-comparing
    // the output (not just by reading the CSS):
    //   1. padding-top/left on <body> (original code): CSS padding can never go
    //      negative — it silently clamps to 0 — so a negative offset (needed to
    //      shift UP/LEFT, per the form's own "positive = move down/right" labeling)
    //      did nothing. top_offset_mm=-10 rendered byte-identical to 0.
    //   2. margin-top/left on <body>: margin IS valid negative, and positive values
    //      shifted content correctly — but dompdf clips body content at the @page
    //      margin's inner box edge, so a negative margin big enough to matter just
    //      pushed the logo/product-name block off the top-left edge entirely rather
    //      than sliding it inward.
    //   3. Moving the @page margin's own top/left values dynamically: in theory the
    //      cleanest fix (no inner box to clip against) — but dompdf did not visibly
    //      respond to this at all; re-rendering with top/left both at +10mm produced
    //      pixel-identical output to 0mm, even after clearing the compiled view
    //      cache and confirming the computed values were correct in the template.
    // What actually works: @page margin is zeroed out for top/left (kept nonzero for
    // bottom/right, which aren't being offset and still need real physical margin so
    // the last row/column isn't flush against the sheet edge), and the FULL desired
    // position — base position plus the user's offset — is applied as body margin
    // instead of just the offset. There's no longer an inner @page-margin box
    // narrower than body's margin for content to clip against, since @page no longer
    // reserves any of its own space on those two sides.
    $topMarginPt  = max(0, round($baseTopPt  + ($topOffsetMm  * $mmToPt), 2));
    $leftMarginPt = max(0, round($baseLeftPt + ($leftOffsetMm * $mmToPt), 2));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0 11pt 36pt 0;
            size: letter;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-weight: bold; }

        html, body {
            margin: 0;
            padding: 0;
            margin-top: {{ $topMarginPt }}pt;
            margin-left: {{ $leftMarginPt }}pt;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 7pt;
            color: #1f2937;
        }

        .company-name {
            font-size: 7.5pt;
            font-weight: bold;
            color: #1d4ed8;
        }

        .product-name {
            font-size: 9pt;
            font-weight: bold;
            color: #111827;
            line-height: 1.2;
            margin-top: 3pt;
        }

        .set-label {
            font-size: 5.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6366f1;
            margin-top: 3pt;
        }

        .meta {
            color: #6b7280;
            font-size: 6pt;
            margin-top: 2pt;
            line-height: 1.5;
        }

        .price-label {
            font-size: 5.5pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .price-value {
            font-size: 11pt;
            font-weight: bold;
            color: #1d4ed8;
            line-height: 1.1;
        }

        .item-id {
            font-size: 5.5pt;
            color: #9ca3af;
            font-family: 'DejaVu Sans Mono', monospace;
            margin-top: 3pt;
        }

        .scan-hint {
            font-size: 5pt;
            color: #9ca3af;
            text-align: center;
            line-height: 1.3;
            margin-top: 2pt;
        }
    </style>
</head>
<body>

<table width="590" cellpadding="0" cellspacing="0">
    @foreach ($rows as $row)
    <tr>
        {{-- Left label --}}
        <td width="288" height="132" style="padding:6pt; vertical-align:top; overflow:hidden;">
            @if (isset($row[0]))
                @php $item = $row[0]; $model = $item['model']; @endphp
                <table width="100%" style="height:132pt;" cellpadding="0" cellspacing="0">
                    <tr>
                        {{-- Info (72%) --}}
                        <td width="72%" valign="top" style="padding-right:4pt;">
                            @if ($logoDataUri)
                                <img src="{{ $logoDataUri }}" alt="{{ $companyName }}" style="max-height:18pt; max-width:90pt;">
                            @else
                                <div class="company-name">{{ $companyName }}</div>
                            @endif

                            @if ($item['type'] === 'set')
                                <div class="set-label">Sample Set</div>
                                <div class="product-name">{{ $model->name ?? $model->productLine->name }}</div>
                                <div class="meta">
                                    @if ($model->productLine?->manufacturer){{ $model->productLine->manufacturer }}@endif
                                    @if ($model->productLine?->name) &middot; {{ $model->productLine->name }}@endif
                                </div>
                            @else
                                <div class="product-name">{{ $model->productStyle->name }}</div>
                                <div class="meta">
                                    @if ($model->productStyle->productLine?->manufacturer){{ $model->productStyle->productLine->manufacturer }}@endif
                                    @if ($model->productStyle->productLine?->name) &middot; {{ $model->productStyle->productLine->name }}@endif
                                    @if ($model->productStyle->color) &middot; {{ $model->productStyle->color }}@endif
                                </div>
                                @if ($showPrice && $model->effective_price)
                                <div style="margin-top:4pt;">
                                    <div class="price-label">Price / Unit</div>
                                    <div class="price-value">${{ number_format($model->effective_price, 2) }}</div>
                                </div>
                                @endif
                            @endif

                            <div class="item-id">{{ $item['type'] === 'set' ? $model->set_id : $model->sample_id }}</div>
                        </td>

                        {{-- QR (28%) --}}
                        <td width="28%" valign="middle" align="center">
                            <img src="data:image/svg+xml;base64,{{ $item['qrSvg'] }}" width="60" height="60" alt="QR">
                            <div class="scan-hint">Scan for<br>details</div>
                        </td>
                    </tr>
                </table>
            @endif
        </td>

        {{-- Gutter --}}
        <td width="14"></td>

        {{-- Right label --}}
        <td width="288" height="132" style="padding:6pt; vertical-align:top; overflow:hidden;">
            @if (isset($row[1]))
                @php $item = $row[1]; $model = $item['model']; @endphp
                <table width="100%" style="height:132pt;" cellpadding="0" cellspacing="0">
                    <tr>
                        {{-- Info (72%) --}}
                        <td width="72%" valign="top" style="padding-right:4pt;">
                            @if ($logoDataUri)
                                <img src="{{ $logoDataUri }}" alt="{{ $companyName }}" style="max-height:18pt; max-width:90pt;">
                            @else
                                <div class="company-name">{{ $companyName }}</div>
                            @endif

                            @if ($item['type'] === 'set')
                                <div class="set-label">Sample Set</div>
                                <div class="product-name">{{ $model->name ?? $model->productLine->name }}</div>
                                <div class="meta">
                                    @if ($model->productLine?->manufacturer){{ $model->productLine->manufacturer }}@endif
                                    @if ($model->productLine?->name) &middot; {{ $model->productLine->name }}@endif
                                </div>
                            @else
                                <div class="product-name">{{ $model->productStyle->name }}</div>
                                <div class="meta">
                                    @if ($model->productStyle->productLine?->manufacturer){{ $model->productStyle->productLine->manufacturer }}@endif
                                    @if ($model->productStyle->productLine?->name) &middot; {{ $model->productStyle->productLine->name }}@endif
                                    @if ($model->productStyle->color) &middot; {{ $model->productStyle->color }}@endif
                                </div>
                                @if ($showPrice && $model->effective_price)
                                <div style="margin-top:4pt;">
                                    <div class="price-label">Price / Unit</div>
                                    <div class="price-value">${{ number_format($model->effective_price, 2) }}</div>
                                </div>
                                @endif
                            @endif

                            <div class="item-id">{{ $item['type'] === 'set' ? $model->set_id : $model->sample_id }}</div>
                        </td>

                        {{-- QR (28%) --}}
                        <td width="28%" valign="middle" align="center">
                            <img src="data:image/svg+xml;base64,{{ $item['qrSvg'] }}" width="60" height="60" alt="QR">
                            <div class="scan-hint">Scan for<br>details</div>
                        </td>
                    </tr>
                </table>
            @endif
        </td>
    </tr>
    @endforeach
</table>

</body>
</html>
