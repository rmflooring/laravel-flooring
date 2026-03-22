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

        .info-grid { width: 100%; margin-bottom: 20px; }
        .info-grid td { vertical-align: top; width: 50%; padding-right: 16px; }
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
        .info-row { margin-bottom: 3px; }
        .info-label { color: #666; display: inline; }
        .info-value { font-weight: bold; display: inline; }

        .delivery-box {
            background: #f0f4ff;
            border: 1px solid #c7d7f8;
            border-radius: 3px;
            padding: 10px 14px;
            margin-bottom: 20px;
        }
        .delivery-box .delivery-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1d4ed8;
            margin-bottom: 5px;
        }
        .delivery-box .delivery-address {
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
            background: #1d4ed8;
            padding: 6px 10px;
            text-align: left;
        }
        .items-table th.right { text-align: right; }
        .items-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }
        .items-table td.right { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #fafafa; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table tfoot td {
            border-top: 2px solid #1d4ed8;
            padding-top: 8px;
            font-weight: bold;
            font-size: 12px;
        }
        .items-table tfoot td.right { text-align: right; }

        .notes-section {
            margin-top: 6px;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        .notes-section .label {
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 4px;
        }

        .ordered-by {
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }

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

    {{-- Branding --}}
    @php
        use App\Models\Setting;
        $brandName    = Setting::get('branding_company_name', 'RM Flooring');
        $brandTagline = Setting::get('branding_tagline', '');
        $brandStreet   = Setting::get('branding_address', '');
        $brandCity     = Setting::get('branding_city', '');
        $brandProvince = Setting::get('branding_province', '');
        $brandPostal   = Setting::get('branding_postal', '');
        $brandAddress  = trim(implode(', ', array_filter([$brandStreet, $brandCity, $brandProvince, $brandPostal])));
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
                     style="height: 100px; max-width: 320px; object-fit: contain;">
            @else
                <div class="company-name">{{ $brandName }}</div>
                @if ($brandTagline)
                    <div class="company-sub">{{ $brandTagline }}</div>
                @endif
            @endif
            @if ($brandAddress || $brandPhone || $brandWebsite)
                <div class="company-sub" style="margin-top: 4px;">
                    @if ($brandAddress) {{ $brandAddress }}<br> @endif
                    @if ($brandPhone) {{ $brandPhone }} @endif
                    @if ($brandPhone && $brandWebsite) &nbsp;|&nbsp; @endif
                    @if ($brandWebsite) {{ $brandWebsite }} @endif
                </div>
            @endif
        </div>
        <div class="doc-title">PURCHASE ORDER</div>
        <div class="doc-meta">
            {{ $purchaseOrder->po_number }}<br>
            Date: {{ $purchaseOrder->created_at->format('F j, Y') }}<br>
            @if ($purchaseOrder->expected_delivery_date)
                Expected: {{ $purchaseOrder->expected_delivery_date->format('F j, Y') }}
            @endif
        </div>
    </div>

    {{-- Info grid: Vendor + PO Details --}}
    <table class="info-grid">
        <tr>
            <td>
                <div class="info-section-title">Vendor</div>
                <div class="info-row"><span class="info-value">{{ $purchaseOrder->vendor->company_name }}</span></div>
                @if ($purchaseOrder->vendor->contact_name)
                    <div class="info-row">{{ $purchaseOrder->vendor->contact_name }}</div>
                @endif
                @php
                    $vendorAddr = trim(implode(', ', array_filter([
                        $purchaseOrder->vendor->address,
                        $purchaseOrder->vendor->address2,
                        $purchaseOrder->vendor->city,
                        $purchaseOrder->vendor->province,
                        $purchaseOrder->vendor->postal_code,
                    ])));
                @endphp
                @if ($vendorAddr)
                    <div class="info-row" style="margin-top: 4px;">{{ $vendorAddr }}</div>
                @endif
                @if ($purchaseOrder->vendor->email)
                    <div class="info-row" style="margin-top: 4px;">{{ $purchaseOrder->vendor->email }}</div>
                @endif
                @if ($purchaseOrder->vendor->phone)
                    <div class="info-row">{{ $purchaseOrder->vendor->phone }}</div>
                @endif
            </td>
            <td>
                <div class="info-section-title">PO Details</div>
                <div class="info-row">
                    <span class="info-label">PO Number: </span>
                    <span class="info-value">{{ $purchaseOrder->po_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date: </span>
                    {{ $purchaseOrder->created_at->format('F j, Y') }}
                </div>
                @if ($purchaseOrder->expected_delivery_date)
                    <div class="info-row">
                        <span class="info-label">Expected Delivery: </span>
                        <span class="info-value">{{ $purchaseOrder->expected_delivery_date->format('F j, Y') }}</span>
                    </div>
                @endif
                <div class="info-row" style="margin-top: 4px;">
                    <span class="info-label">Fulfillment: </span>
                    {{ $purchaseOrder->fulfillment_label }}
                </div>
                @if ($purchaseOrder->vendor_order_number)
                    <div class="info-row">
                        <span class="info-label">Vendor Order #: </span>
                        <span class="info-value">{{ $purchaseOrder->vendor_order_number }}</span>
                    </div>
                @endif
                @if ($purchaseOrder->sale)
                <div class="info-row" style="margin-top: 4px;">
                    <span class="info-label">Sale Ref: </span>
                    {{ $purchaseOrder->sale->sale_number }}
                </div>
                @if ($purchaseOrder->sale->job_name)
                    <div class="info-row">
                        <span class="info-label">Job: </span>
                        {{ $purchaseOrder->sale->job_name }}
                    </div>
                @endif
                @else
                <div class="info-row" style="margin-top: 4px;">
                    <span class="info-label">Type: </span>
                    Stock PO
                </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Delivery Address (only for delivery methods) --}}
    @if ($purchaseOrder->fulfillment_method !== 'pickup' && $purchaseOrder->delivery_address)
        <div class="delivery-box">
            <div class="delivery-title">Delivery Address</div>
            <div class="delivery-address">{{ $purchaseOrder->delivery_address }}</div>
        </div>
    @elseif ($purchaseOrder->fulfillment_method === 'pickup')
        <div class="delivery-box">
            <div class="delivery-title">Pickup</div>
            <div class="delivery-address">We will pick up this order from your location.</div>
        </div>
    @endif

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th class="right">Qty</th>
                <th>Unit</th>
                <th class="right">Unit Cost</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseOrder->items as $item)
                <tr>
                    <td>
                        {{ $item->item_name }}
                        @if ($item->po_notes)
                            <div style="margin-top: 3px; font-size: 10px; color: #555;">{{ $item->po_notes }}</div>
                        @endif
                    </td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td>{{ $item->unit ?: '—' }}</td>
                    <td class="right">${{ number_format($item->cost_price, 2) }}</td>
                    <td class="right">${{ number_format($item->cost_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Grand Total</td>
                <td class="right">${{ number_format($purchaseOrder->items->sum('cost_total'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Special Instructions --}}
    @if ($purchaseOrder->special_instructions)
        <div class="notes-section">
            <div class="label">Special Instructions</div>
            <div>{{ $purchaseOrder->special_instructions }}</div>
        </div>
    @endif

    {{-- Ordered By --}}
    <div class="ordered-by">
        Ordered by: <strong>{{ $purchaseOrder->orderedBy->name ?? '—' }}</strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Issued: {{ $purchaseOrder->created_at->format('F j, Y') }}
    </div>

    @php
        $mobileUrl = route('mobile.purchase-orders.show', $purchaseOrder);
        $qrPng     = base64_encode((new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(new \BaconQrCode\Renderer\RendererStyle\RendererStyle(72), new \BaconQrCode\Renderer\Image\GdImageBackEnd())))->writeString($mobileUrl));
    @endphp
    <table style="width:100%; margin-top:24px; border-top:1px solid #ddd; padding-top:10px;">
        <tr>
            <td style="font-size:10px; color:#888; vertical-align:middle;">
                {{ $brandName }} &mdash; Purchase Order {{ $purchaseOrder->po_number }}
                @if ($brandEmail) &nbsp;|&nbsp; {{ $brandEmail }} @endif
            </td>
            <td style="text-align:right; vertical-align:middle; width:90px;">
                <img src="data:image/png;base64,{{ $qrPng }}" style="width:72px; height:72px;">
                <div style="font-size:8px; color:#aaa; margin-top:2px; text-align:center;">Scan for mobile</div>
            </td>
        </tr>
    </table>

</body>
</html>
