<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; padding: 32px; }

        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 14px; margin-bottom: 20px; }
        .company-name { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
        .company-sub { font-size: 11px; color: #555; margin-top: 2px; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; margin-top: -28px; color: #1d4ed8; }
        .doc-meta { text-align: right; font-size: 11px; color: #555; margin-top: 4px; }

        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-col:last-child { padding-left: 24px; }
        .info-label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1d4ed8; margin-bottom: 6px; }
        .info-row { margin-bottom: 3px; }
        .info-key { color: #555; }

        .schedule-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 10px 14px; margin-bottom: 20px; }
        .schedule-box .label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1d4ed8; margin-bottom: 4px; }
        .schedule-box .value { font-size: 13px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #1d4ed8; color: #fff; padding: 7px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        thead th:last-child { text-align: right; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: top; }
        tbody td:last-child { text-align: right; font-weight: bold; }
        tbody td.right { text-align: right; }
        tfoot td { padding: 8px 10px; font-weight: bold; font-size: 12px; }
        tfoot td:last-child { text-align: right; color: #1d4ed8; }
        .unit { font-size: 10px; color: #777; }

        .notes-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 14px; margin-bottom: 20px; }
        .notes-box .label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 4px; }

        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>

@php
    $settings = DB::table('app_settings')->pluck('value', 'key');
    $companyName = $settings['branding_company_name'] ?? 'RM Flooring';
    $tagline     = $settings['branding_tagline'] ?? '';
    $phone       = $settings['branding_phone'] ?? '';
    $email       = $settings['branding_email'] ?? '';
    $website     = $settings['branding_website'] ?? '';
    $street      = $settings['branding_address'] ?? '';
    $city        = $settings['branding_city'] ?? '';
    $province    = $settings['branding_province'] ?? '';
    $postal      = $settings['branding_postal'] ?? '';
    $logoPath    = $settings['branding_logo_path'] ?? null;

    $installer = $workOrder->installer;
    $sale      = $workOrder->sale;
@endphp

{{-- Header --}}
<div class="header">
    <div style="display:table; width:100%">
        <div style="display:table-cell; vertical-align:top; width:60%">
            @if($logoPath && file_exists(storage_path('app/public/' . ltrim(str_replace('/storage/', '', $logoPath), '/'))))
                @php
                    $fullPath = storage_path('app/public/' . ltrim(str_replace('/storage/', '', $logoPath), '/'));
                    $mime     = mime_content_type($fullPath);
                    $b64      = base64_encode(file_get_contents($fullPath));
                @endphp
                <img src="data:{{ $mime }};base64,{{ $b64 }}" style="height:60px; max-width:220px; object-fit:contain;">
            @else
                <div class="company-name">{{ $companyName }}</div>
                @if($tagline) <div class="company-sub">{{ $tagline }}</div> @endif
            @endif
            @php $brandAddress = implode(', ', array_filter([$street, $city, $province, $postal])); @endphp
            @if($brandAddress)
                <div style="margin-top:4px; font-size:10px; color:#555;">{{ $brandAddress }}</div>
            @endif
            @if($phone || $email)
                <div style="margin-top:2px; font-size:10px; color:#555;">{{ $phone }}{{ $phone && $email ? ' · ' : '' }}{{ $email }}</div>
            @endif
        </div>
        <div style="display:table-cell; vertical-align:top; text-align:right; width:40%">
            <div class="doc-title">WORK ORDER</div>
            <div class="doc-meta">{{ $workOrder->wo_number }}</div>
            <div class="doc-meta">Date: {{ $workOrder->created_at->format('M j, Y') }}</div>
            <div class="doc-meta">
                Status: <strong>{{ $workOrder->status_label }}</strong>
            </div>
        </div>
    </div>
</div>

{{-- Info Grid --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-label">Installer</div>
        @if($installer)
            <div class="info-row"><strong>{{ $installer->company_name }}</strong></div>
            @if($installer->contact_name) <div class="info-row">{{ $installer->contact_name }}</div> @endif
            @if($installer->address)      <div class="info-row">{{ $installer->address }}</div> @endif
            @if($installer->address2)     <div class="info-row">{{ $installer->address2 }}</div> @endif
            @if($installer->city || $installer->province)
                <div class="info-row">{{ implode(', ', array_filter([$installer->city, $installer->province])) }} {{ $installer->postal_code }}</div>
            @endif
            @if($installer->phone)  <div class="info-row"><span class="info-key">Ph:</span> {{ $installer->phone }}</div> @endif
            @if($installer->mobile) <div class="info-row"><span class="info-key">M:</span>  {{ $installer->mobile }}</div> @endif
            @if($installer->email)  <div class="info-row"><span class="info-key">Email:</span> {{ $installer->email }}</div> @endif
        @else
            <div class="info-row">—</div>
        @endif
    </div>
    <div class="info-col">
        <div class="info-label">Job Details</div>
        <div class="info-row"><span class="info-key">Sale:</span> {{ $sale->sale_number }}</div>
        @if($sale->customer_name)
            <div class="info-row"><span class="info-key">Customer:</span> {{ $sale->customer_name }}</div>
        @endif
        @if($sale->job_name)
            <div class="info-row"><span class="info-key">Job:</span> {{ $sale->job_name }}</div>
        @endif
        @if($sale->job_address)
            <div class="info-row"><span class="info-key">Address:</span> {{ $sale->job_address }}</div>
        @endif
        <div class="info-row" style="margin-top:6px"><span class="info-key">Created by:</span> {{ $workOrder->creator?->name ?? '—' }}</div>
    </div>
</div>

{{-- Schedule Box --}}
@if($workOrder->scheduled_date)
<div class="schedule-box">
    <div class="label">Scheduled Date &amp; Time</div>
    <div class="value">
        {{ $workOrder->scheduled_date->format('l, F j, Y') }}
        @if($workOrder->scheduled_time)
            at {{ \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') }}
        @endif
    </div>
    @if($sale->job_address)
        <div style="margin-top:4px; color:#555">{{ $sale->job_address }}</div>
    @endif
</div>
@endif

{{-- Items grouped by Room --}}
@php
    $itemsByRoom = $workOrder->items->groupBy(fn($item) => $item->saleItem?->sale_room_id ?? 0);
@endphp

@foreach($itemsByRoom as $roomId => $roomItems)
    @php $roomName = $roomItems->first()->saleItem?->room?->room_name ?? 'Uncategorized'; @endphp

    {{-- Room header --}}
    <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:4px; padding:6px 12px; margin-bottom:4px; font-size:11px; font-weight:bold; color:#1d4ed8;">
        &#x2302; {{ $roomName }}
    </div>

    <table style="margin-bottom:16px;">
        <thead>
            <tr>
                <th style="width:50%">Item</th>
                <th class="right" style="width:15%; text-align:right">Qty</th>
                <th class="right" style="width:15%; text-align:right">Unit</th>
                <th class="right" style="width:10%; text-align:right">Unit Cost</th>
                <th style="width:10%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roomItems as $item)
                <tr>
                    <td>
                        @if($item->relatedMaterials->isNotEmpty())
                            @foreach($item->relatedMaterials as $mat)
                                @php
                                    $si = $mat->saleItem;
                                    $matName = $si ? implode(' — ', array_filter([$si->product_type, $si->manufacturer, $si->style, $si->color_item_number])) : 'Material';
                                @endphp
                                <div style="font-size:9px; color:#555; margin-bottom:2px;">
                                    &#x25B8; {{ $matName }}@if($si) — {{ number_format((float)$si->quantity, 2) }} {{ $si->unit }}@endif
                                </div>
                            @endforeach
                        @endif
                        {{ $item->item_name }}
                        @if ($item->wo_notes)
                            <div style="margin-top: 3px; font-size: 10px; color: #555;">{{ $item->wo_notes }}</div>
                        @endif
                    </td>
                    <td class="right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="right">{{ $item->unit }}</td>
                    <td class="right">${{ number_format($item->cost_price, 2) }}</td>
                    <td>${{ number_format($item->cost_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

{{-- Grand Total --}}
<table>
    <tfoot>
        <tr>
            <td colspan="4" style="text-align:right; border-top:2px solid #1d4ed8; padding-top:8px">Grand Total</td>
            <td style="border-top:2px solid #1d4ed8; padding-top:8px">${{ number_format($workOrder->grand_total, 2) }}</td>
        </tr>
    </tfoot>
</table>

{{-- Notes --}}
@if($workOrder->notes)
<div class="notes-box">
    <div class="label">Notes / Special Instructions</div>
    <div style="margin-top:4px; white-space:pre-wrap">{{ $workOrder->notes }}</div>
</div>
@endif

@php
    $mobileUrl = route('mobile.work-orders.show', $workOrder);
    $qrSvg     = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(72)->margin(1)->generate($mobileUrl);
    $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
@endphp
<div style="display:table; width:100%; margin-top:24px; border-top:1px solid #e5e7eb; padding-top:10px;">
    <div style="display:table-cell; vertical-align:middle; font-size:10px; color:#888;">
        {{ $companyName }}
        @if($phone) &mdash; {{ $phone }} @endif
        @if($email) &nbsp;|&nbsp; {{ $email }} @endif
        @if($website) &nbsp;|&nbsp; {{ $website }} @endif
    </div>
    <div style="display:table-cell; vertical-align:middle; text-align:right; width:90pt;">
        <img src="{{ $qrDataUri }}" style="width:72pt; height:72pt;">
        <div style="font-size:8px; color:#aaa; margin-top:2px; text-align:center;">Scan for mobile</div>
    </div>
</div>

</body>
</html>
