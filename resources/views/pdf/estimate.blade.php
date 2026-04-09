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
            padding: 32px;
        }

        /* ── Header ── */
        .header {
            border-bottom: 2px solid #1d4ed8;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
            margin-top: -28px;
        }
        .doc-meta {
            text-align: right;
            font-size: 11px;
            color: #555;
            margin-top: 4px;
        }

        /* ── Info grid ── */
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-grid td {
            vertical-align: top;
            width: 50%;
            padding-right: 16px;
        }
        .info-section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .info-row {
            margin-bottom: 3px;
        }
        .info-label {
            color: #666;
            display: inline;
        }
        .info-value {
            font-weight: bold;
            display: inline;
        }

        /* ── Room ── */
        .room {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .room-header {
            background-color: #1d4ed8;
            color: #fff;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11px;
        }
        .room-header-right {
            float: right;
            font-weight: normal;
        }

        /* ── Item tables ── */
        .section-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            padding: 4px 10px 2px;
            background: #f5f5f5;
            border-bottom: 1px solid #e0e0e0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            padding: 4px 8px;
            border-bottom: 1px solid #ddd;
            background: #fafafa;
            text-align: left;
        }
        .items-table th.right { text-align: right; }
        .items-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        .items-table td.right { text-align: right; }
        .items-table .note-row td {
            color: #888;
            font-style: italic;
            font-size: 10px;
            padding-top: 0;
        }
        .items-table tr:last-child td { border-bottom: none; }

        /* ── Totals ── */
        .totals-wrapper {
            margin-top: 20px;
            text-align: right;
        }
        .totals-table {
            display: inline-table;
            width: 260px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 3px 8px;
            font-size: 11px;
        }
        .totals-table .label { color: #555; text-align: left; }
        .totals-table .amount { text-align: right; }
        .totals-table .divider td {
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        .totals-table .grand td {
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #1d4ed8;
            padding-top: 5px;
        }

        /* ── Notes ── */
        .notes-section {
            margin-top: 20px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
        }
        .notes-section .label {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 10px;
            color: #888;
            text-align: center;
        }

        .clearfix::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>

    @php $format = $format ?? 'detailed'; @endphp

    {{-- Branding --}}
    @php
        use App\Models\Setting;
        $brandName    = Setting::get('branding_company_name', 'RM Flooring');
        $brandTagline = Setting::get('branding_tagline', '');
        $brandStreet   = Setting::get('branding_address', '');
        $brandCity     = Setting::get('branding_city', '');
        $brandProvince = Setting::get('branding_province', '');
        $brandPostal   = Setting::get('branding_postal', '');
        $brandAddressLine1 = trim(implode(', ', array_filter([$brandStreet, $brandCity])));
        $brandAddressLine2 = trim(implode(' ', array_filter([$brandProvince, $brandPostal])));
        $brandPhone   = Setting::get('branding_phone', '');
        $brandEmail   = Setting::get('branding_email', '');
        $brandWebsite = Setting::get('branding_website', '');
        $logoPath     = Setting::get('branding_logo_path', '');
        $logoData     = null;
        $logoMime     = 'image/png';
        if ($logoPath) {
            $absPath = storage_path('app/public/' . $logoPath);
            if (file_exists($absPath)) {
                $logoData = base64_encode(file_get_contents($absPath));
                $logoMime = mime_content_type($absPath) ?: 'image/png';
            }
        }
    @endphp

    {{-- Header --}}
    <div class="header clearfix">
        <div>
            @if ($logoData)
                <img src="data:{{ $logoMime }};base64,{{ $logoData }}"
                     style="height: 110px; max-width: 352px; object-fit: contain;">
            @else
                <div class="company-name">{{ $brandName }}</div>
                @if ($brandTagline)
                    <div class="company-sub">{{ $brandTagline }}</div>
                @endif
            @endif
            @if ($brandAddressLine1 || $brandAddressLine2 || $brandPhone || $brandWebsite)
                <div class="company-sub" style="margin-top: 4px;">
                    @if ($brandAddressLine1) {{ $brandAddressLine1 }}<br> @endif
                    @if ($brandAddressLine2) {{ $brandAddressLine2 }}<br> @endif
                    @if ($brandPhone) {{ $brandPhone }} @endif
                    @if ($brandPhone && $brandWebsite)  &nbsp;|&nbsp;  @endif
                    @if ($brandWebsite) {{ $brandWebsite }} @endif
                </div>
            @endif
        </div>
        <div class="doc-title">ESTIMATE</div>
        <div class="doc-meta">
            #{{ $estimate->estimate_number ?? '—' }}<br>
            {{ $estimate->created_at?->format('F j, Y') }}
        </div>
    </div>

    {{-- Info grid --}}
    @php
        $parentCustomerName  = $estimate->opportunity?->parentCustomer?->name;
        $jobSiteCustomerName = $estimate->opportunity?->jobSiteCustomer?->name;

        // Parse stored job_address ("street\ncity, province, postal") into two display lines
        $jobAddrLine1 = '';
        $jobAddrLine2 = '';
        if ($estimate->job_address) {
            $addrParts    = explode("\n", $estimate->job_address, 2);
            $street       = trim($addrParts[0] ?? '');
            $cityLineParts = array_map('trim', explode(',', $addrParts[1] ?? ''));
            $city         = $cityLineParts[0] ?? '';
            $province     = $cityLineParts[1] ?? '';
            $postal       = $cityLineParts[2] ?? '';
            $jobAddrLine1 = trim(implode(', ', array_filter([$street, $city])));
            $jobAddrLine2 = trim(implode(' ', array_filter([$province, $postal])));
        }
    @endphp
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-section-title">Prepared For</div>
                @php
                    $preparedForName = $parentCustomerName
                        ?? $estimate->homeowner_name
                        ?? $estimate->customer_name;
                @endphp
                @if ($preparedForName)
                    <div class="info-row"><span class="info-value">{{ $preparedForName }}</span></div>
                @endif
                @if ($estimate->homeowner_name && $parentCustomerName && $estimate->homeowner_name !== $parentCustomerName)
                    <div class="info-row">{{ $estimate->homeowner_name }}</div>
                @endif
                @if ($estimate->homeowner_email)
                    <div class="info-row">{{ $estimate->homeowner_email }}</div>
                @endif
                @if ($estimate->homeowner_phone)
                    <div class="info-row">{{ $estimate->homeowner_phone }}</div>
                @endif
                @if ($estimate->pm_name)
                    <div class="info-row" style="margin-top: 6px;"><span class="info-label">PM: </span>{{ $estimate->pm_name }}</div>
                @endif
            </td>
            <td>
                <div class="info-section-title">Job Details</div>
                @if ($jobSiteCustomerName)
                    <div class="info-row"><span class="info-value">{{ $jobSiteCustomerName }}</span></div>
                @endif
                @if ($jobAddrLine1)
                    <div class="info-row">{{ $jobAddrLine1 }}</div>
                @endif
                @if ($jobAddrLine2)
                    <div class="info-row">{{ $jobAddrLine2 }}</div>
                @endif
                @if ($estimate->job_name)
                    <div class="info-row"><span class="info-label">Job: </span><span class="info-value">{{ $estimate->job_name }}</span></div>
                @endif
                @if ($estimate->estimate_number)
                    <div class="info-row"><span class="info-label">Estimate #: </span><span class="info-value">{{ $estimate->estimate_number }}</span></div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Rooms --}}
    @foreach ($estimate->rooms as $room)
        @php
            $materials       = $room->items->where('item_type', 'material');
            $labour          = $room->items->where('item_type', 'labour');
            $freight         = $room->items->where('item_type', 'freight');
            $roomTotal       = $room->items->sum('line_total');
            $showRoomTotal   = in_array($format, ['detailed', 'room_totals']);
            $showLinePricing = $format === 'detailed';
        @endphp

        <div class="room">
            <div class="room-header clearfix">
                {{ $room->room_name ?: 'Unnamed Room' }}
                @if ($showRoomTotal)
                    <span class="room-header-right">${{ number_format($roomTotal, 2) }}</span>
                @endif
            </div>

            {{-- Materials --}}
            @if ($materials->isNotEmpty())
                <div class="section-label">Materials</div>
                @if ($showLinePricing)
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product Type</th>
                                <th>Manufacturer</th>
                                <th>Style</th>
                                <th>Colour / Item #</th>
                                <th class="right">Qty</th>
                                <th>Unit</th>
                                <th class="right">Price</th>
                                <th class="right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materials as $item)
                                <tr>
                                    <td>{{ $item->product_type ?: '—' }}</td>
                                    <td>{{ $item->manufacturer ?: '—' }}</td>
                                    <td>{{ $item->style ?: '—' }}</td>
                                    <td>{{ $item->color_item_number ?: '—' }}</td>
                                    <td class="right">{{ $item->quantity }}</td>
                                    <td>{{ $item->unit ?: '—' }}</td>
                                    <td class="right">${{ number_format($item->sell_price, 2) }}</td>
                                    <td class="right">${{ number_format($item->line_total, 2) }}</td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="note-row"><td colspan="8">{{ $item->notes }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product Type</th>
                                <th>Manufacturer</th>
                                <th>Style</th>
                                <th>Colour / Item #</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materials as $item)
                                <tr>
                                    <td>{{ $item->product_type ?: '—' }}</td>
                                    <td>{{ $item->manufacturer ?: '—' }}</td>
                                    <td>{{ $item->style ?: '—' }}</td>
                                    <td>{{ $item->color_item_number ?: '—' }}</td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="note-row"><td colspan="4">{{ $item->notes }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif

            {{-- Labour --}}
            @if ($labour->isNotEmpty())
                <div class="section-label">Labour</div>
                @if ($showLinePricing)
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="right">Qty</th>
                                <th>Unit</th>
                                <th class="right">Price</th>
                                <th class="right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labour as $item)
                                <tr>
                                    <td>{{ $item->labour_type ?: '—' }}</td>
                                    <td>{{ $item->description ?: '—' }}</td>
                                    <td class="right">{{ $item->quantity }}</td>
                                    <td>{{ $item->unit ?: '—' }}</td>
                                    <td class="right">${{ number_format($item->sell_price, 2) }}</td>
                                    <td class="right">${{ number_format($item->line_total, 2) }}</td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="note-row"><td colspan="6">{{ $item->notes }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($labour as $item)
                                <tr>
                                    <td>{{ $item->labour_type ?: '—' }}</td>
                                    <td>{{ $item->description ?: '—' }}</td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="note-row"><td colspan="2">{{ $item->notes }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif

            {{-- Freight --}}
            @if ($freight->isNotEmpty())
                <div class="section-label">Freight</div>
                @if ($showLinePricing)
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th class="right">Qty</th>
                                <th>Unit</th>
                                <th class="right">Price</th>
                                <th class="right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($freight as $item)
                                <tr>
                                    <td>{{ $item->freight_description ?: '—' }}</td>
                                    <td class="right">{{ $item->quantity }}</td>
                                    <td>{{ $item->unit ?: '—' }}</td>
                                    <td class="right">${{ number_format($item->sell_price, 2) }}</td>
                                    <td class="right">${{ number_format($item->line_total, 2) }}</td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="note-row"><td colspan="5">{{ $item->notes }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($freight as $item)
                                <tr>
                                    <td>{{ $item->freight_description ?: '—' }}</td>
                                </tr>
                                @if ($item->notes)
                                    <tr class="note-row"><td>{{ $item->notes }}</td></tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endif

        </div>
    @endforeach

    {{-- Notes --}}
    @if ($estimate->notes)
        <div class="notes-section">
            <div class="label">Notes</div>
            <div>{{ $estimate->notes }}</div>
        </div>
    @endif

    {{-- Totals --}}
    <div class="totals-wrapper">
        <table class="totals-table">
            @if ($format === 'detailed')
                <tr>
                    <td class="label">Materials</td>
                    <td class="amount">${{ number_format($estimate->subtotal_materials, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Labour</td>
                    <td class="amount">${{ number_format($estimate->subtotal_labour, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Freight</td>
                    <td class="amount">${{ number_format($estimate->subtotal_freight, 2) }}</td>
                </tr>
            @endif
            <tr class="{{ $format === 'detailed' ? 'divider' : '' }}">
                <td class="label">Subtotal</td>
                <td class="amount">${{ number_format($estimate->pretax_total, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Tax ({{ $estimate->tax_rate_percent }}%)</td>
                <td class="amount">${{ number_format($estimate->tax_amount, 2) }}</td>
            </tr>
            <tr class="grand">
                <td class="label">Grand Total</td>
                <td class="amount">${{ number_format($estimate->grand_total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        This estimate is valid for 30 days. Thank you for choosing RM Flooring.
    </div>

</body>
</html>
