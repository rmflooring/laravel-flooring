<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
@php
    // Mirrors the "Print Tag" layout on the inventory record page (pages/inventory/show):
    // Standard = 4" × 6" portrait  |  Zebra = 4"×6" stock, auto-rotated → 6" × 4" landscape
    $isZebra = ($format ?? 'standard') === 'zebra';
    // Content area inside a 0.2in margin (dompdf adds padding outside a fixed width/height,
    // and the * reset below overrides @page margins, so the margin lives on .page).
    // Height is a hair under the full area so the page break doesn't spill onto a blank page.
    $pageW   = $isZebra ? '5.6in' : '3.6in';
    $pageH   = $isZebra ? '3.55in' : '5.55in';
@endphp
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #111; }
        .page { position: relative; width: {{ $pageW }}; height: {{ $pageH }}; margin: 0.2in; overflow: hidden; page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .tag-header       { background: #1d4ed8; color: #fff; padding: {{ $isZebra ? '4.5pt 7.5pt' : '5pt 7.5pt' }}; border-radius: 3pt; margin-bottom: 7.5pt; }
        .tag-header table { width: 100%; border-collapse: collapse; }
        .tag-header-label { font-size: 10pt; font-weight: bold; letter-spacing: 0.5pt; text-transform: uppercase; }
        .tag-header-id    { font-size: {{ $isZebra ? '13pt' : '14pt' }}; font-weight: bold; font-family: Courier, monospace; text-align: right; }

        .tag-item-name    { font-size: {{ $isZebra ? '15pt' : '16pt' }}; font-weight: bold; color: #111; line-height: 1.2; margin-bottom: {{ $isZebra ? '4pt' : '4.5pt' }}; }
        .tag-detail       { font-size: 10pt; color: #555; margin-bottom: {{ $isZebra ? '2.25pt' : '3pt' }}; }
        .tag-date         { font-size: 9pt; color: #777; margin-bottom: {{ $isZebra ? '6pt' : '7.5pt' }}; }

        .tag-job          { background: #f0fdf4; border: 0.75pt solid #86efac; border-radius: 3pt; padding: {{ $isZebra ? '4pt 6pt' : '4.5pt 7.5pt' }}; margin-bottom: {{ $isZebra ? '6pt' : '7.5pt' }}; }
        .tag-job-label    { font-size: {{ $isZebra ? '7pt' : '8pt' }}; color: #166534; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; margin-bottom: 1pt; }
        .tag-job-number   { font-size: {{ $isZebra ? '17pt' : '18pt' }}; font-weight: bold; color: #15803d; font-family: Courier, monospace; }
        .tag-job-customer { font-size: {{ $isZebra ? '9pt' : '10pt' }}; font-weight: bold; color: #166534; margin-top: 1pt; }

        .tag-qty          { background: #eff6ff; border: 0.75pt solid #93c5fd; border-radius: 3pt; padding: {{ $isZebra ? '4pt 6pt' : '4.5pt 7.5pt' }}; margin-bottom: {{ $isZebra ? '6pt' : '7.5pt' }}; }
        .tag-qty-label    { font-size: {{ $isZebra ? '7pt' : '8pt' }}; color: #1e40af; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; margin-bottom: 1pt; }
        .tag-qty-number   { font-size: {{ $isZebra ? '20pt' : '22pt' }}; font-weight: bold; color: #1d4ed8; }
        .tag-qty-unit     { font-size: {{ $isZebra ? '11pt' : '12pt' }}; font-weight: bold; color: #1e40af; }

        .tag-cards        { width: 100%; border-collapse: collapse; }
        .tag-cards td     { vertical-align: top; }

        .tag-footer-text  { font-size: 7pt; color: #9ca3af; }
        .tag-qr-caption   { font-size: 6pt; color: #c0c0c0; margin-top: 2pt; text-align: center; }

        /* Standard: footer pinned to the bottom of the page, QR on the right */
        .tag-footer       { position: absolute; left: 0; right: 0; bottom: 0; padding-top: 4.5pt; border-top: 0.75pt solid #e5e7eb; }
        .tag-footer table { width: 100%; border-collapse: collapse; }

        /* Zebra: QR column pinned bottom-right beside the details */
        .tag-left         { margin-right: 1.3in; }
        .tag-right        { position: absolute; right: 0; bottom: 0; width: 1.1in; text-align: center; }
    </style>
</head>
<body>

@foreach ($receipts as $receipt)
@php
    $qrSize = $isZebra ? 88 : 72;
    $qrB64  = base64_encode((string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size($qrSize * 2)->margin(1)->generate(route('mobile.inventory.show', $receipt)));

    // Job #: the PO's sale plus any sales this stock is allocated to (same as the inventory page)
    $tagSales = collect([$receipt->purchaseOrder?->sale])
        ->merge($receipt->allocations->map(fn ($alloc) => $alloc->sale))
        ->filter()
        ->unique('id')
        ->values();
    // Jobsite customer under the job #, not the parent (billing) customer
    $tagJobSites = $tagSales->map(function ($sale) {
        $jobSite = $sale->opportunity?->jobSiteCustomer;
        return $jobSite?->company_name ?: ($jobSite?->name ?: ($sale->customer_name ?? $sale->job_name ?? ''));
    })->filter()->unique()->values();

    $manufacturer = $receipt->productStyle?->productLine?->manufacturer;
    $lineName     = $receipt->productStyle?->productLine?->name;
    $styleSku     = $receipt->productStyle?->sku;
    $unitsPer     = $receipt->productStyle?->units_per;
    $unitLabel    = $receipt->productStyle?->productLine?->unit?->label;
    $po           = $receipt->purchaseOrder;
@endphp

<div class="page">

    <div class="tag-header">
        <table><tr>
            <td class="tag-header-label">Inventory Record</td>
            <td class="tag-header-id">#{{ $receipt->id }}</td>
        </tr></table>
    </div>

    <div class="{{ $isZebra ? 'tag-left' : '' }}">
        <div class="tag-item-name">{{ $receipt->item_name }}</div>
        @if ($manufacturer)
            <div class="tag-detail"><strong>Manufacturer:</strong> {{ $manufacturer }}</div>
        @endif
        @if ($lineName)
            <div class="tag-detail"><strong>Product Line:</strong> {{ $lineName }}</div>
        @endif
        @if ($styleSku)
            <div class="tag-detail"><strong>SKU:</strong> {{ $styleSku }}</div>
        @endif
        <div class="tag-date">Received: {{ $receipt->received_date?->format('M j, Y') ?? '—' }}</div>
        @if ($po)
            <div class="tag-detail"><strong>PO:</strong> {{ $po->po_number }}{{ $po->vendor ? ' — ' . $po->vendor->company_name : '' }}</div>
        @endif
        {{-- Zebra is only 4" tall, so the Job # and Qty cards sit side by side there --}}
        @if ($isZebra)<table class="tag-cards"><tr>@endif
        @if ($tagSales->isNotEmpty())
            @if ($isZebra)<td style="width: 60%; padding-right: 6pt;">@endif
            <div class="tag-job">
                <div class="tag-job-label">Job #</div>
                <div class="tag-job-number">{{ $tagSales->pluck('sale_number')->implode(' · ') }}</div>
                @if ($tagJobSites->isNotEmpty())
                    <div class="tag-job-customer">{{ $tagJobSites->implode(' · ') }}</div>
                @endif
            </div>
            @if ($isZebra)</td>@endif
        @endif
        @if ($isZebra)<td>@endif
        <div class="tag-qty">
            @if ($unitsPer && $unitLabel)
                <span class="tag-qty-number">{{ rtrim(rtrim(number_format((float) $unitsPer, 2), '0'), '.') }}</span>
                <span class="tag-qty-unit">{{ $unitLabel }}</span>
            @else
                <div class="tag-qty-label">Qty</div>
                <span class="tag-qty-number">{{ rtrim(rtrim(number_format((float) $receipt->quantity_received, 2), '0'), '.') }}</span>
                <span class="tag-qty-unit">{{ $receipt->unit }}</span>
            @endif
        </div>
        @if ($isZebra)</td></tr></table>@endif
    </div>

    @if ($isZebra)
        <div class="tag-right">
            <img src="data:image/svg+xml;base64,{{ $qrB64 }}" style="width: {{ $qrSize }}pt; height: {{ $qrSize }}pt; display: block; margin: 0 auto;">
            <div class="tag-qr-caption">Scan for details</div>
            <div class="tag-footer-text" style="text-align: center;">RM Flooring<br>{{ now()->format('M j, Y') }}</div>
        </div>
    @else
        <div class="tag-footer">
            <table><tr>
                <td class="tag-footer-text" style="vertical-align: bottom;">RM Flooring<br>{{ now()->format('M j, Y') }}</td>
                <td style="vertical-align: bottom; width: {{ $qrSize }}pt;">
                    <img src="data:image/svg+xml;base64,{{ $qrB64 }}" style="width: {{ $qrSize }}pt; height: {{ $qrSize }}pt; display: block;">
                    <div class="tag-qr-caption">Scan for details</div>
                </td>
            </tr></table>
        </div>
    @endif

</div>

@endforeach

</body>
</html>
