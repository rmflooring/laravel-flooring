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
            display: block;
            width: 100%;
        }
        .header-inner {
            width: 100%;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
        }
        .doc-meta {
            text-align: right;
            font-size: 10px;
            color: #555;
            margin-top: 3px;
        }

        .body-content {
            line-height: 1.6;
        }

        .body-content h1, .body-content h2, .body-content h3 {
            margin-bottom: 8px;
        }
        .body-content p {
            margin-bottom: 8px;
        }
        .body-content table {
            border-collapse: collapse;
        }

        /* Flooring items table */
        .flooring-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 32px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; text-align: left; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>

@php
    use App\Models\Setting;
    use Illuminate\Support\Facades\Storage;

    $brandName   = Setting::get('branding_company_name', 'RM Flooring');
    $brandTagline = Setting::get('branding_tagline', '');
    $brandPhone  = Setting::get('branding_phone', '');
    $brandEmail  = Setting::get('branding_email', '');
    $brandWebsite = Setting::get('branding_website', '');
    $logoPath    = Setting::get('branding_logo_path', '');

    $logoDataUri = null;
    if ($logoPath && Storage::disk('public')->exists($logoPath)) {
        $logoRaw    = Storage::disk('public')->get($logoPath);
        $logoMime   = Storage::disk('public')->mimeType($logoPath);
        $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoRaw);
    }
@endphp

{{-- Header --}}
<div class="header">
    <table class="header-inner" width="100%">
        <tr>
            <td style="vertical-align:top; width:50%;">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" style="height:60px; max-width:200px; object-fit:contain; display:block; margin-bottom:4px;">
                @else
                    <div class="company-name">{{ $brandName }}</div>
                    @if ($brandTagline)
                        <div class="company-sub">{{ $brandTagline }}</div>
                    @endif
                @endif
            </td>
            <td style="vertical-align:top; text-align:right;">
                <div class="doc-title">{{ $template->name }}</div>
                <div class="doc-meta">
                    Generated {{ now()->format('M j, Y') }}
                    @if ($opportunity->job_no) &nbsp;&middot;&nbsp; Job #{{ $opportunity->job_no }} @endif
                </div>
                @if ($sale)
                    <div class="doc-meta">Sale #{{ $sale->sale_number }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- Body --}}
<div class="body-content">
    {!! $body !!}
</div>

{{-- Footer --}}
<div class="footer">
    <div class="footer-left">
        {{ $brandName }}
        @if ($brandPhone) &nbsp;&middot;&nbsp; {{ $brandPhone }} @endif
        @if ($brandEmail) &nbsp;&middot;&nbsp; {{ $brandEmail }} @endif
    </div>
    <div class="footer-right">{{ $template->name }}</div>
</div>

</body>
</html>
