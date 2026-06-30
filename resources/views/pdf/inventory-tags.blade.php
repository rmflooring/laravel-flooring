<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
@php
    $isZebra    = ($format ?? 'standard') === 'zebra';
    $bodyW      = $isZebra ? '432pt' : '226.77pt';
    $itemNameSz = $isZebra ? '14pt' : '11pt';
    $qtyNumSz   = $isZebra ? '22pt' : '16pt';
    $qrSize     = $isZebra ? 90 : 60;
    $qrColW     = $isZebra ? '100pt' : '68pt';
    $qrImgSz    = $isZebra ? '90pt' : '60pt';
    $infoColW   = $isZebra ? '312pt' : '140pt';
@endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1a1a1a; width: {{ $bodyW }}; padding: 6pt; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
    </style>
</head>
<body>

@foreach ($receipts as $receipt)
@php
    $mobileUrl       = route('mobile.inventory.show', $receipt);
    $qrSvg           = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize)->margin(1)->generate($mobileUrl);
    $allocSale       = $receipt->allocations->first()?->sale;
    $qtyDisplay      = rtrim(rtrim(number_format((float)$receipt->quantity_received, 2), '0'), '.');
    $manufacturer    = $receipt->productStyle?->productLine?->manufacturer;
    $lineName        = $receipt->productStyle?->productLine?->name;
    $styleSku        = $receipt->productStyle?->sku;
    $lineNameDisplay = $lineName
        ? ($isZebra ? $lineName : \Illuminate\Support\Str::limit($lineName, 40))
        : null;
    $unitsPer        = $receipt->productStyle?->units_per;
    $unitLabel       = $receipt->productStyle?->productLine?->unit?->label;
@endphp

<div class="page">

    {{-- Outer border --}}
    <div style="border: 1.5pt solid #1d4ed8; border-radius: 3pt; padding: 6pt;">

        {{-- Header --}}
        <div style="border-bottom: 1pt solid #dde4f8; padding-bottom: 4pt; margin-bottom: 5pt;">
            <div style="font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; color: #1d4ed8; margin-bottom: 1pt;">
                Inventory Tag &mdash; {{ $purchaseOrder->po_number }}
            </div>
            <div style="font-size: {{ $itemNameSz }}; font-weight: bold; color: #111; line-height: 1.2;">{{ $receipt->item_name }}</div>
        </div>

        {{-- Body: two columns via display:table --}}
        <div style="display: table; width: 100%;">

            {{-- Left: info column --}}
            <div style="display: table-cell; vertical-align: top; width: {{ $infoColW }};">

                {{-- Qty box --}}
                <div style="background: #eff6ff; border: 1pt solid #bfdbfe; border-radius: 3pt; padding: 3pt 5pt; text-align: center; margin-bottom: 4pt;">
                    @if ($unitsPer && $unitLabel)
                        <div style="font-size: {{ $qtyNumSz }}; font-weight: bold; color: #1d4ed8; line-height: 1;">{{ rtrim(rtrim(number_format((float) $unitsPer, 2), '0'), '.') }}</div>
                        <div style="font-size: 6.5pt; color: #555; margin-top: 1pt;">{{ $unitLabel }}</div>
                    @else
                        <div style="font-size: {{ $qtyNumSz }}; font-weight: bold; color: #1d4ed8; line-height: 1;">{{ $qtyDisplay }}</div>
                        <div style="font-size: 6.5pt; color: #555; margin-top: 1pt;">{{ $receipt->unit ?: 'units' }}</div>
                    @endif
                </div>

                {{-- Allocation badge --}}
                @if ($allocSale)
                    <div style="background: #f0fdf4; border: 1pt solid #bbf7d0; border-radius: 3pt; padding: 3pt 5pt; font-size: 7pt; font-weight: bold; color: #166534; text-align: center; line-height: 1.3; margin-bottom: 4pt;">
                        Sale #{{ $allocSale->sale_number }}<br>{{ $allocSale->customer_name ?? $allocSale->job_name ?? '' }}
                    </div>
                @else
                    <div style="background: #fefce8; border: 1pt solid #fde68a; border-radius: 3pt; padding: 3pt 5pt; font-size: 6.5pt; font-weight: bold; color: #854d0e; text-align: center; margin-bottom: 4pt;">
                        Stock / Unallocated
                    </div>
                @endif

                {{-- Info rows --}}
                @if ($manufacturer)
                    <div style="margin-bottom: 2pt;">
                        <div style="font-size: 5.5pt; color: #777; text-transform: uppercase; letter-spacing: 0.3pt;">Manufacturer</div>
                        <div style="font-size: 7.5pt; font-weight: bold; color: #111;">{{ $manufacturer }}</div>
                    </div>
                @endif
                @if ($lineNameDisplay)
                    <div style="margin-bottom: 2pt;">
                        <div style="font-size: 5.5pt; color: #777; text-transform: uppercase; letter-spacing: 0.3pt;">Product Line</div>
                        <div style="font-size: 7pt; font-weight: bold; color: #111;">{{ $lineNameDisplay }}</div>
                    </div>
                @endif
                @if ($styleSku)
                    <div style="margin-bottom: 2pt;">
                        <div style="font-size: 5.5pt; color: #777; text-transform: uppercase; letter-spacing: 0.3pt;">SKU</div>
                        <div style="font-size: 7.5pt; font-weight: bold; color: #111;">{{ $styleSku }}</div>
                    </div>
                @endif
                <div style="margin-bottom: 2pt;">
                    <div style="font-size: 5.5pt; color: #777; text-transform: uppercase; letter-spacing: 0.3pt;">PO</div>
                    <div style="font-size: 7.5pt; font-weight: bold; color: #111;">{{ $purchaseOrder->po_number }}</div>
                </div>
                <div style="margin-bottom: 2pt;">
                    <div style="font-size: 5.5pt; color: #777; text-transform: uppercase; letter-spacing: 0.3pt;">Received</div>
                    <div style="font-size: 7.5pt; font-weight: bold; color: #111;">{{ $receipt->received_date->format('M j, Y') }}</div>
                </div>

            </div>

            {{-- Right: QR column --}}
            <div style="display: table-cell; vertical-align: bottom; text-align: center; width: {{ $qrColW }};">
                <div style="width: {{ $qrImgSz }}; height: {{ $qrImgSz }};">{!! $qrSvg !!}</div>
                <div style="font-size: 5pt; color: #aaa; margin-top: 2pt;">Scan for details</div>
            </div>

        </div>

        {{-- Footer --}}
        <div style="font-size: 5.5pt; color: #bbb; text-align: right; padding-top: 3pt; border-top: 0.5pt solid #eee; margin-top: 3pt;">
            Receipt #{{ $receipt->id }}
        </div>

    </div>

</div>

@endforeach

</body>
</html>
