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

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #1d4ed8; color: #fff; padding: 7px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: top; }
        tbody td.right { text-align: right; font-weight: bold; }

        .room-header { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 6px 12px; margin-bottom: 4px; font-size: 11px; font-weight: bold; color: #1d4ed8; }

        .notes-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 14px; margin-bottom: 20px; }
        .notes-box .label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #555; margin-bottom: 4px; }

        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>

@php
    use Illuminate\Support\Facades\DB;
    $settings    = DB::table('app_settings')->pluck('value', 'key');
    $companyName = $settings['branding_company_name'] ?? 'RM Flooring';
    $tagline     = $settings['branding_tagline'] ?? '';
    $phone       = $settings['branding_phone'] ?? '';
    $email       = $settings['branding_email'] ?? '';
    $website     = $settings['branding_website'] ?? '';
    $logoPath    = $settings['branding_logo_path'] ?? null;

    $sale      = $pickTicket->sale;
    $workOrder = $pickTicket->workOrder;
    $installer = $workOrder?->installer;
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
        </div>
        <div style="display:table-cell; vertical-align:top; text-align:right; width:40%">
            <div class="doc-title">PICK TICKET</div>
            <div class="doc-meta">PT #{{ $pickTicket->pt_number }}</div>
            <div class="doc-meta">Date: {{ $pickTicket->created_at->format('M j, Y') }}</div>
            <div class="doc-meta">Status: <strong>{{ $pickTicket->status_label }}</strong></div>
        </div>
    </div>
</div>

{{-- Info Grid --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-label">Job Details</div>
        @if($sale)
            <div class="info-row"><span class="info-key">Sale #:</span> {{ $sale->sale_number }}</div>
            @if($sale->customer_name)
                <div class="info-row"><span class="info-key">Customer:</span> {{ $sale->customer_name }}</div>
            @endif
            @if($sale->homeowner_name)
                <div class="info-row"><span class="info-key">Homeowner:</span> {{ $sale->homeowner_name }}</div>
            @endif
            @if($sale->job_name)
                <div class="info-row"><span class="info-key">Job:</span> {{ $sale->job_name }}</div>
            @endif
            @if($sale->job_address)
                <div class="info-row"><span class="info-key">Address:</span> {{ $sale->job_address }}</div>
            @endif
        @else
            <div class="info-row">—</div>
        @endif
    </div>
    <div class="info-col">
        @if($workOrder)
            <div class="info-label">Work Order</div>
            <div class="info-row"><span class="info-key">WO #:</span> {{ $workOrder->wo_number }}</div>
            @if($installer)
                <div class="info-row"><span class="info-key">Installer:</span> {{ $installer->company_name }}</div>
                @if($installer->contact_name)
                    <div class="info-row">{{ $installer->contact_name }}</div>
                @endif
                @if($installer->phone)
                    <div class="info-row"><span class="info-key">Ph:</span> {{ $installer->phone }}</div>
                @endif
            @endif
            @if($workOrder->scheduled_date)
                <div class="info-row" style="margin-top:6px">
                    <span class="info-key">Install date:</span>
                    {{ $workOrder->scheduled_date->format('M j, Y') }}
                    @if($workOrder->scheduled_time)
                        at {{ \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') }}
                    @endif
                </div>
            @endif
        @else
            <div class="info-label">Prepared by</div>
            <div class="info-row">{{ $pickTicket->creator?->name ?? '—' }}</div>
        @endif
    </div>
</div>

{{-- Items grouped by Room --}}
@php
    $itemsByRoom = $pickTicket->items->groupBy(fn($item) => $item->saleItem?->sale_room_id ?? 0);
@endphp

@foreach($itemsByRoom as $roomId => $roomItems)
    @php $roomName = $roomItems->first()->saleItem?->room?->room_name ?? null; @endphp

    @if($roomName)
        <div class="room-header">&#x2302; {{ $roomName }}</div>
    @endif

    <table style="margin-bottom:{{ $roomName ? '16px' : '20px' }}">
        <thead>
            <tr>
                <th style="width:55%">Item</th>
                @if(!$roomName)<th style="width:25%">Room</th>@endif
                <th class="right" style="width:{{ $roomName ? '25%' : '10%' }}; text-align:right">Qty</th>
                <th class="right" style="width:20%; text-align:right">Unit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roomItems as $ptItem)
                <tr>
                    <td>{{ $ptItem->item_name }}</td>
                    @if(!$roomName)
                        <td class="info-key">—</td>
                    @endif
                    <td class="right">{{ number_format((float) $ptItem->quantity, 2) }}</td>
                    <td class="right" style="font-weight:normal; color:#555">{{ $ptItem->unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

{{-- Notes --}}
@if($pickTicket->notes)
<div class="notes-box">
    <div class="label">Notes</div>
    <div style="margin-top:4px; white-space:pre-wrap">{{ $pickTicket->notes }}</div>
</div>
@endif

<div class="footer">
    {{ $companyName }}
    @if($phone) · {{ $phone }} @endif
    @if($email) · {{ $email }} @endif
    @if($website) · {{ $website }} @endif
</div>

</body>
</html>
