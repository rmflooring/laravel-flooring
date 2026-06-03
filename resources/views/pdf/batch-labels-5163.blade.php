@php
    $mmToPt      = 2.8346;
    $topPadPt    = round($topOffsetMm  * $mmToPt, 2);
    $leftPadPt   = round($leftOffsetMm * $mmToPt, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 36pt 11pt;
            size: letter;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            padding-top: {{ $topPadPt }}pt;
            padding-left: {{ $leftPadPt }}pt;
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
        <td width="288" height="144" style="padding:6pt; vertical-align:top; overflow:hidden;">
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
        <td width="288" height="144" style="padding:6pt; vertical-align:top; overflow:hidden;">
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
