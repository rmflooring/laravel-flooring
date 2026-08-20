<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; padding: 32px; }

        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 14px; margin-bottom: 20px; display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; }
        .header-right { display: table-cell; vertical-align: top; text-align: right; }
        .logo img { max-height: 40px; max-width: 160px; margin-bottom: 6px; }
        .company-name { font-size: 18px; font-weight: bold; }
        .doc-title { font-size: 18px; font-weight: bold; color: #1d4ed8; }
        .doc-number { font-size: 22px; font-weight: bold; font-family: monospace; margin-top: 2px; }
        .doc-date { font-size: 11px; color: #555; margin-top: 4px; }

        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 16px; }
        .info-section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #555; border-bottom: 1px solid #ddd; padding-bottom: 3px; margin-bottom: 6px; }
        .info-row { margin-bottom: 3px; }

        .due-back-banner { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 4px; padding: 10px 14px; margin-bottom: 20px; font-size: 12px; }
        .due-back-banner strong { color: #92400e; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #666; padding: 6px 8px; border-bottom: 1px solid #ddd; background: #fafafa; text-align: left; }
        table.items th.right { text-align: right; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #f0f0f0; }
        table.items td.right { text-align: right; }

        .footer-note { margin-top: 24px; padding-top: 14px; border-top: 1px solid #ddd; font-size: 10.5px; color: #444; }
        .footer-note .ref { font-weight: bold; font-family: monospace; }
    </style>
</head>
<body>

    <div class="header">
        <div class="header-left">
            <div class="logo">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}">
                @endif
            </div>
            <div class="company-name">{{ $companyName }}</div>
        </div>
        <div class="header-right">
            <div class="doc-title">SAMPLE CHECKOUT RECEIPT</div>
            <div class="doc-number">{{ $checkoutNumber }}</div>
            <div class="doc-date">{{ $first->checked_out_at?->format('F j, Y') }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-col">
            <div class="info-section-title">Checked Out To</div>
            <div class="info-row"><strong>{{ $first->borrower_name }}</strong></div>
            @if ($first->checkout_type === 'customer')
                @if ($first->customer_phone)<div class="info-row">{{ $first->customer_phone }}</div>@endif
                @if ($first->customer_email)<div class="info-row">{{ $first->customer_email }}</div>@endif
            @else
                @if ($first->destination)<div class="info-row">{{ $first->destination }}</div>@endif
            @endif
        </div>
        <div class="info-col">
            <div class="info-section-title">Checkout Details</div>
            <div class="info-row">Checked out: {{ $first->checked_out_at?->format('M j, Y') }}</div>
            <div class="info-row">Due back: {{ $first->due_back_at?->format('M j, Y') ?? '—' }}</div>
            <div class="info-row">Items: {{ $items->count() }}</div>
        </div>
    </div>

    @if ($first->due_back_at)
        <div class="due-back-banner">
            <strong>Please return by {{ $first->due_back_at->format('F j, Y') }}.</strong>
            Reference # <span class="ref">{{ $checkoutNumber }}</span> when returning.
        </div>
    @endif

    <table class="items">
        <thead>
            <tr>
                <th>Sample #</th>
                <th>Style</th>
                <th class="right">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php $style = $item->sample?->productStyle; @endphp
                <tr>
                    <td>{{ $item->sample?->sample_id ?? '—' }}</td>
                    <td>{{ $style?->productLine?->manufacturer }} {{ $style?->name }}</td>
                    <td class="right">{{ $item->qty_checked_out }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer-note">
        Please bring this receipt (or reference # <span class="ref">{{ $checkoutNumber }}</span>) when returning samples,
        so staff can look up and check them back in quickly.
    </div>

</body>
</html>
