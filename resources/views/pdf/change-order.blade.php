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

        .room-header { background: #1d4ed8; color: #fff; padding: 7px 10px; font-weight: bold; font-size: 11px; margin-bottom: 0; }
        .room-header.added   { background: #15803d; }
        .room-header.removed { background: #b91c1c; }
        .room-header.changed { background: #b45309; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        thead th { background: #f1f5f9; color: #374151; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        thead th.right { text-align: right; }
        tbody tr.added   td { background: #f0fdf4; }
        tbody tr.removed td { background: #fff5f5; }
        tbody tr.changed td { background: #fffbeb; }
        tbody td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; font-size: 10.5px; vertical-align: top; }
        tbody td.right { text-align: right; }
        .tag { font-size: 9px; font-weight: bold; padding: 1px 5px; border-radius: 3px; display: inline-block; margin-right: 4px; }
        .tag-added   { background: #dcfce7; color: #166534; }
        .tag-removed { background: #fee2e2; color: #991b1b; text-decoration: line-through; }
        .tag-changed { background: #fef9c3; color: #92400e; }

        .room-footer { display: table; width: 100%; padding: 5px 8px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 11px; margin-bottom: 16px; }
        .room-footer-cell { display: table-cell; }
        .room-footer-cell.right { text-align: right; font-weight: bold; }
        .delta-pos { color: #15803d; font-weight: bold; }
        .delta-neg { color: #b91c1c; font-weight: bold; }

        .summary-box { border: 2px solid #1d4ed8; border-radius: 4px; padding: 14px 18px; margin-top: 20px; }
        .summary-row { display: table; width: 100%; padding: 4px 0; }
        .summary-label { display: table-cell; font-size: 12px; }
        .summary-value { display: table-cell; text-align: right; font-size: 12px; font-weight: bold; }
        .summary-divider { border-top: 1px solid #e2e8f0; margin: 6px 0; }
        .summary-total .summary-label,
        .summary-total .summary-value { font-size: 14px; font-weight: bold; color: #1d4ed8; }

        .approval-block { margin-top: 32px; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        .approval-line { display: table; width: 100%; margin-top: 16px; }
        .approval-cell { display: table-cell; width: 50%; padding-right: 24px; }
        .sig-line { border-bottom: 1px solid #374151; margin-top: 32px; margin-bottom: 4px; }
        .sig-label { font-size: 10px; color: #555; }

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
    $logoPath    = $settings['branding_logo_path'] ?? null;
    $grandDelta  = $delta['grand_delta'];
@endphp

{{-- Header --}}
<div class="header">
    <div style="display:table; width:100%">
        <div style="display:table-cell; vertical-align:top; width:60%">
            @if($logoPath && file_exists(storage_path('app/public/' . ltrim(str_replace('/storage/', '', $logoPath), '/'))))
                @php
                    $fullPath  = storage_path('app/public/' . ltrim(str_replace('/storage/', '', $logoPath), '/'));
                    $mime      = mime_content_type($fullPath);
                    $logoData  = base64_encode(file_get_contents($fullPath));
                @endphp
                <img src="data:{{ $mime }};base64,{{ $logoData }}" style="height:70px; max-width:260px; object-fit:contain;">
            @else
                <div class="company-name">{{ $companyName }}</div>
                @if($tagline)<div class="company-sub">{{ $tagline }}</div>@endif
            @endif
        </div>
        <div style="display:table-cell; vertical-align:top; text-align:right;">
            <div class="doc-title">CHANGE ORDER</div>
            <div class="doc-meta">{{ $changeOrder->co_number }}</div>
            <div class="doc-meta">Date: {{ $changeOrder->created_at->format('F j, Y') }}</div>
            @if($changeOrder->status === 'approved')
                <div class="doc-meta" style="color:#15803d; font-weight:bold;">APPROVED {{ $changeOrder->approved_at?->format('M j, Y') }}</div>
            @else
                <div class="doc-meta" style="color:#b45309; font-weight:bold;">{{ strtoupper($changeOrder->status) }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Info Grid --}}
<div class="info-grid">
    <div class="info-col">
        <div class="info-label">Sale Information</div>
        <div class="info-row"><span class="info-key">Sale #:</span> {{ $sale->sale_number }}</div>
        @if($sale->customer_name)
            <div class="info-row"><span class="info-key">Customer:</span> {{ $sale->customer_name }}</div>
        @endif
        @if($sale->job_name)
            <div class="info-row"><span class="info-key">Job Name:</span> {{ $sale->job_name }}</div>
        @endif
        @if($sale->job_no)
            <div class="info-row"><span class="info-key">Job #:</span> {{ $sale->job_no }}</div>
        @endif
    </div>
    <div class="info-col">
        <div class="info-label">Job Site</div>
        @if($sale->homeowner_name)
            <div class="info-row">{{ $sale->homeowner_name }}</div>
        @endif
        @if($sale->job_address)
            <div class="info-row" style="white-space: pre-line;">{{ $sale->job_address }}</div>
        @endif
        @if($sale->job_phone)
            <div class="info-row"><span class="info-key">Phone:</span> {{ $sale->job_phone }}</div>
        @endif
    </div>
</div>

@if($changeOrder->title || $changeOrder->reason)
    <div style="border:1px solid #e2e8f0; border-radius:4px; padding:10px 14px; margin-bottom:16px; background:#f8fafc;">
        @if($changeOrder->title)
            <div style="font-weight:bold; font-size:12px; margin-bottom:4px;">{{ $changeOrder->title }}</div>
        @endif
        @if($changeOrder->reason)
            <div style="font-size:10.5px; color:#555;">{{ $changeOrder->reason }}</div>
        @endif
    </div>
@endif

{{-- Delta by Room --}}
@foreach($delta['rooms'] as $room)
    @php
        $roomClass = match($room['status']) {
            'added'   => 'added',
            'removed' => 'removed',
            'changed' => 'changed',
            default   => '',
        };
        $roomLabel = match($room['status']) {
            'added'   => '+ NEW ROOM',
            'removed' => '− REMOVED',
            default   => '',
        };
    @endphp

    <div class="room-header {{ $roomClass }}">
        {{ $room['room_name'] ?: 'Unnamed Room' }}
        @if($roomLabel) — {{ $roomLabel }} @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:35%">Item</th>
                <th class="right">Orig Qty</th>
                <th class="right">Orig Price</th>
                <th class="right">Orig Total</th>
                <th class="right">New Qty</th>
                <th class="right">New Price</th>
                <th class="right">New Total</th>
                <th class="right">Delta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($room['rows'] as $row)
                @php
                    $rowClass = match($row['status']) { 'added' => 'added', 'removed' => 'removed', 'changed' => 'changed', default => '' };
                    $tagClass = match($row['status']) { 'added' => 'tag-added', 'removed' => 'tag-removed', 'changed' => 'tag-changed', default => '' };
                    $tagText  = match($row['status']) { 'added' => '+Added', 'removed' => '−Removed', 'changed' => 'Changed', default => '' };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td>
                        @if($tagText)<span class="tag {{ $tagClass }}">{{ $tagText }}</span>@endif
                        {{ $row['label'] ?: ucfirst($row['item_type']) }}
                        @if($row['status'] === 'changed' && !empty($row['orig_label']) && $row['orig_label'] !== $row['label'])
                            <div style="font-size:9px; color:#999; text-decoration:line-through;">was: {{ $row['orig_label'] }}</div>
                        @endif
                    </td>
                    <td class="right">{{ $row['orig_qty'] !== null ? number_format($row['orig_qty'], 2) : '—' }}</td>
                    <td class="right">{{ $row['orig_price'] !== null ? '$'.number_format($row['orig_price'], 2) : '—' }}</td>
                    <td class="right">{{ $row['orig_total'] !== null ? '$'.number_format($row['orig_total'], 2) : '—' }}</td>
                    <td class="right">{{ $row['new_qty'] !== null ? number_format($row['new_qty'], 2) : '—' }}</td>
                    <td class="right">{{ $row['new_price'] !== null ? '$'.number_format($row['new_price'], 2) : '—' }}</td>
                    <td class="right">{{ $row['new_total'] !== null ? '$'.number_format($row['new_total'], 2) : '—' }}</td>
                    <td class="right">
                        @if(abs($row['delta']) < 0.01)
                            —
                        @elseif($row['delta'] >= 0)
                            <span class="delta-pos">+${{ number_format($row['delta'], 2) }}</span>
                        @else
                            <span class="delta-neg">−${{ number_format(abs($row['delta']), 2) }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

{{-- Summary --}}
<div class="summary-box">
    <div class="summary-row">
        <div class="summary-label">Original Contract Total</div>
        <div class="summary-value">${{ number_format($delta['orig_grand_total'], 2) }}</div>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-row">
        <div class="summary-label {{ $grandDelta >= 0 ? 'delta-pos' : 'delta-neg' }}">
            Change Order {{ $grandDelta >= 0 ? 'Addition' : 'Credit' }}
        </div>
        <div class="summary-value {{ $grandDelta >= 0 ? 'delta-pos' : 'delta-neg' }}">
            {{ $grandDelta >= 0 ? '+' : '−' }}${{ number_format(abs($grandDelta), 2) }}
        </div>
    </div>
    <div class="summary-divider"></div>
    <div class="summary-row summary-total">
        <div class="summary-label">Revised Contract Total</div>
        <div class="summary-value">${{ number_format($delta['new_grand_total'], 2) }}</div>
    </div>
</div>

{{-- Approval Block --}}
<div class="approval-block">
    <div style="font-size:10.5px; color:#555;">
        By signing below, the homeowner/client acknowledges and approves the changes described in this Change Order.
        The Revised Contract Total above supersedes the original contract amount.
    </div>
    <div class="approval-line">
        <div class="approval-cell">
            <div class="sig-line"></div>
            <div class="sig-label">Homeowner / Client Signature</div>
        </div>
        <div class="approval-cell">
            <div class="sig-line"></div>
            <div class="sig-label">Date</div>
        </div>
    </div>
    <div class="approval-line" style="margin-top:8px;">
        <div class="approval-cell">
            <div class="sig-line"></div>
            <div class="sig-label">Print Name</div>
        </div>
        <div class="approval-cell">
            <div class="sig-line"></div>
            <div class="sig-label">{{ $companyName }} Representative</div>
        </div>
    </div>
</div>

<div class="footer">
    {{ $companyName }}{{ $phone ? ' · ' . $phone : '' }}{{ $email ? ' · ' . $email : '' }}
    — Generated {{ now()->format('M j, Y g:i A') }}
</div>

</body>
</html>
