<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            padding: 28px 32px;
        }

        /* ── Header ─────────────────────────────────────────────── */
        .header {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 12px;
            margin-bottom: 18px;
            width: 100%;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-left { vertical-align: top; }
        .header-right { vertical-align: top; text-align: right; }
        .company-name { font-size: 18px; font-weight: bold; color: #1e3a8a; }
        .company-tagline { font-size: 9px; color: #555; margin-top: 2px; }
        .company-address { font-size: 9px; color: #555; margin-top: 4px; line-height: 1.5; }
        .doc-title { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .doc-date  { font-size: 10px; color: #444; margin-top: 4px; }

        /* ── Job Info ────────────────────────────────────────────── */
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta-table td { padding: 3px 6px; font-size: 10px; vertical-align: top; }
        .meta-label { font-weight: bold; color: #333; width: 110px; }
        .meta-value { color: #1a1a1a; }

        /* ── Section heading ─────────────────────────────────────── */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            background: #1d4ed8;
            color: #fff;
            padding: 4px 8px;
            margin-bottom: 0;
        }

        /* ── Items table ─────────────────────────────────────────── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table th {
            background: #eff6ff;
            font-size: 10px;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        .items-table td {
            padding: 5px 8px;
            border: 1px solid #ddd;
            font-size: 10px;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) td { background: #f8fafc; }
        .col-desc  { }
        .col-color { width: 28%; }

        /* ── Conditions ──────────────────────────────────────────── */
        .conditions-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 10px 12px;
            margin-bottom: 20px;
            background: #fafafa;
        }
        .conditions-box p { font-size: 10px; line-height: 1.6; color: #333; }

        /* ── Signatures ──────────────────────────────────────────── */
        .sig-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .sig-table td { width: 50%; padding: 0 12px 0 0; vertical-align: top; }
        .sig-table td:last-child { padding-left: 12px; padding-right: 0; }
        .sig-label { font-size: 10px; font-weight: bold; color: #333; margin-bottom: 24px; }
        .sig-line { border-bottom: 1px solid #555; margin-bottom: 4px; height: 30px; }
        .sig-caption { font-size: 9px; color: #888; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" style="max-height:48px; max-width:160px; margin-bottom:4px;">
                    @endif
                    <div class="company-name">{{ $branding['company_name'] }}</div>
                    @if ($branding['tagline'])
                        <div class="company-tagline">{{ $branding['tagline'] }}</div>
                    @endif
                    <div class="company-address">
                        @if ($branding['street']){{ $branding['street'] }}<br>@endif
                        {{ implode('  ', array_filter([$branding['city'], $branding['province'], $branding['postal']])) }}
                        @if ($branding['phone'] || $branding['email'])
                            <br>
                            {{ implode('   ', array_filter([$branding['phone'], $branding['email']])) }}
                        @endif
                        @if ($branding['website'])<br>{{ $branding['website'] }}@endif
                    </div>
                </td>
                <td class="header-right">
                    <div class="doc-title">Flooring Selection Sign-Off</div>
                    <div class="doc-date">Date: {{ $signOff->date?->format('F j, Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Job Info --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Customer:</td>
            <td class="meta-value">{{ $signOff->customer_name }}</td>
            <td class="meta-label">Job No.:</td>
            <td class="meta-value">{{ $signOff->job_no }}</td>
        </tr>
        <tr>
            <td class="meta-label">Job Site:</td>
            <td class="meta-value">{{ $signOff->job_site_name }}</td>
            <td class="meta-label">Project Manager:</td>
            <td class="meta-value">{{ $signOff->pm_name }}</td>
        </tr>
        @if ($signOff->job_site_address)
        <tr>
            <td class="meta-label">Address:</td>
            <td class="meta-value" colspan="3">{{ $signOff->job_site_address }}</td>
        </tr>
        @endif
        @if ($signOff->job_site_phone || $signOff->job_site_email)
        <tr>
            @if ($signOff->job_site_phone)
            <td class="meta-label">Phone:</td>
            <td class="meta-value">{{ $signOff->job_site_phone }}</td>
            @else
            <td colspan="2"></td>
            @endif
            @if ($signOff->job_site_email)
            <td class="meta-label">Email:</td>
            <td class="meta-value">{{ $signOff->job_site_email }}</td>
            @else
            <td colspan="2"></td>
            @endif
        </tr>
        @endif
    </table>

    {{-- Items grouped by room --}}
    <div class="section-title">Flooring Selection</div>
    @php $grouped = $signOff->items->groupBy('room_name'); @endphp
    @if ($grouped->isEmpty())
        <table class="items-table"><tbody>
            <tr><td colspan="2" style="text-align:center;color:#888;padding:10px;">No items.</td></tr>
        </tbody></table>
    @else
        @foreach ($grouped as $roomName => $roomItems)
        <table class="items-table" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th style="background:#1d4ed8;color:#fff;font-size:10px;padding:4px 8px;text-align:left;">
                        {{ $roomName }}
                    </th>
                </tr>
                <tr>
                    <th class="col-desc">Product / Description</th>
                    <th class="col-color">Colour / Item #</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roomItems as $item)
                <tr>
                    <td class="col-desc">{{ $item->product_description }}</td>
                    <td class="col-color">{{ $item->color_item_number }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="height:6px;"></div>
        @endforeach
    @endif

    {{-- Conditions --}}
    @if ($signOff->condition_text)
    <div class="section-title">Conditions</div>
    <div class="conditions-box" style="margin-top:0;">
        <p>{!! nl2br(e($signOff->condition_text)) !!}</p>
    </div>
    @endif

    {{-- Signatures --}}
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-label">Customer Signature</div>
                <div class="sig-line"></div>
                <div class="sig-caption">Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</div>
            </td>
            <td>
                <div class="sig-label">{{ $branding['company_name'] }} Representative</div>
                <div class="sig-line"></div>
                <div class="sig-caption">Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</div>
            </td>
        </tr>
    </table>

</body>
</html>
