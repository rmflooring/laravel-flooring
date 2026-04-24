<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; padding: 32px; }

        .header { border-bottom: 2px solid #1d4ed8; padding-bottom: 14px; margin-bottom: 20px; }
        .company-name { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
        .company-sub  { font-size: 11px; color: #555; margin-top: 2px; }
        .doc-title    { font-size: 18px; font-weight: bold; text-align: right; margin-top: -28px; color: #1d4ed8; }
        .doc-meta     { text-align: right; font-size: 11px; color: #555; margin-top: 4px; }

        .section { margin-bottom: 20px; }
        .section-label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1d4ed8; margin-bottom: 8px; border-bottom: 1px solid #bfdbfe; padding-bottom: 3px; }

        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-col  { display: table-cell; width: 50%; vertical-align: top; }
        .info-col:last-child { padding-left: 24px; }

        .info-block { margin-bottom: 14px; }
        .info-label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: #888; margin-bottom: 3px; }
        .info-value { font-size: 11px; color: #1a1a1a; }

        .schedule-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 12px 16px; margin-bottom: 20px; }
        .schedule-box .label { font-weight: bold; font-size: 10px; text-transform: uppercase; color: #1d4ed8; margin-bottom: 4px; }
        .schedule-box .value { font-size: 14px; font-weight: bold; color: #1a1a1a; }

        .status-badge { display: inline-block; font-size: 11px; font-weight: bold; padding: 3px 10px; border-radius: 999px; }
        .status-pending   { background: #fef9c3; color: #854d0e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .flooring-tag { display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 999px; background: #f1f5f9; color: #334155; margin-right: 4px; margin-bottom: 3px; }

        .contact-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 12px; }
        .contact-row { margin-bottom: 2px; }
        .contact-key { color: #777; }

        .instructions-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 10px 14px; margin-bottom: 20px; white-space: pre-wrap; font-size: 11px; }

        .footer { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 10px; font-size: 10px; color: #888; }
    </style>
</head>
<body>

@php
    $settings    = DB::table('app_settings')->pluck('value', 'key');
    $companyName = $settings['branding_company_name'] ?? 'RM Flooring';
    $tagline     = $settings['branding_tagline'] ?? '';
    $phone       = $settings['branding_phone'] ?? '';
    $email       = $settings['branding_email'] ?? '';
    $website     = $settings['branding_website'] ?? '';
    $logoPath    = $settings['branding_logo_path'] ?? null;
    $street      = $settings['branding_address'] ?? '';
    $city        = $settings['branding_city'] ?? '';
    $province    = $settings['branding_province'] ?? '';
    $postal      = $settings['branding_postal'] ?? '';

    $opportunity = $rfm->opportunity;
    $estimator   = $rfm->estimator;
    $jobSite     = $rfm->jobSiteCustomer ?? $opportunity->jobSiteCustomer;
    $pm          = $opportunity->projectManager;
    $customer    = $opportunity->parentCustomer;

    $statusClass = [
        'pending'   => 'status-pending',
        'confirmed' => 'status-confirmed',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled',
    ][$rfm->status] ?? 'status-pending';

    $addressLines = array_filter([$rfm->site_address, $rfm->site_address2]);
    $cityLine     = implode(', ', array_filter([$rfm->site_city, $rfm->site_province, $rfm->site_postal_code]));
    $address      = implode(', ', array_filter([$rfm->site_address, $rfm->site_address2, $rfm->site_city, $rfm->site_province, $rfm->site_postal_code]));
@endphp

{{-- ── Header ──────────────────────────────────────────────────────── --}}
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
            <div class="doc-title">REQUEST FOR MEASURE</div>
            <div class="doc-meta">
                <span class="status-badge {{ $statusClass }}">{{ ucfirst($rfm->status) }}</span>
            </div>
            <div class="doc-meta" style="margin-top:6px">Scheduled: {{ $rfm->scheduled_at->format('M j, Y \a\t g:i A') }}</div>
            <div class="doc-meta">Printed: {{ now()->format('M j, Y') }}</div>
        </div>
    </div>
</div>

{{-- ── Info Grid ────────────────────────────────────────────────────── --}}
<div class="info-grid">

    {{-- Left: Job + Customer + PM --}}
    <div class="info-col">
        <div class="section-label">Job Information</div>

        @if($opportunity->job_no)
        <div class="info-block">
            <div class="info-label">Job #</div>
            <div class="info-value" style="font-weight:bold">{{ $opportunity->job_no }}</div>
        </div>
        @endif

        @if($opportunity->job_name)
        <div class="info-block">
            <div class="info-label">Job Name</div>
            <div class="info-value">{{ $opportunity->job_name }}</div>
        </div>
        @endif

        <div class="info-block">
            <div class="info-label">Customer</div>
            <div class="info-value">{{ $customer?->company_name ?: ($customer?->name ?? '—') }}</div>
        </div>

        @if($pm)
        <div class="info-block">
            <div class="info-label">Project Manager</div>
            <div class="contact-box">
                <div class="contact-row" style="font-weight:bold">{{ $pm->name }}</div>
                @if($pm->phone) <div class="contact-row"><span class="contact-key">Ph:</span> {{ $pm->phone }}</div> @endif
                @if($pm->email) <div class="contact-row"><span class="contact-key">Email:</span> {{ $pm->email }}</div> @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Right: Site + Measure Details --}}
    <div class="info-col">
        <div class="section-label">Site &amp; Measure Details</div>

        <div class="info-block">
            <div class="info-label">Site Address</div>
            @if($address)
                @foreach($addressLines as $line)
                    <div class="info-value">{{ $line }}</div>
                @endforeach
                @if($cityLine)
                    <div class="info-value">{{ $cityLine }}</div>
                @endif
            @else
                <div class="info-value">—</div>
            @endif
        </div>

        @if($jobSite)
        <div class="info-block">
            <div class="info-label">Site Contact</div>
            <div class="contact-box">
                <div class="contact-row" style="font-weight:bold">{{ $jobSite->name ?: $jobSite->company_name }}</div>
                @if($jobSite->phone)  <div class="contact-row"><span class="contact-key">Ph:</span> {{ $jobSite->phone }}</div>  @endif
                @if($jobSite->mobile) <div class="contact-row"><span class="contact-key">M:</span>  {{ $jobSite->mobile }}</div>  @endif
                @if($jobSite->email)  <div class="contact-row"><span class="contact-key">Email:</span> {{ $jobSite->email }}</div> @endif
            </div>
        </div>
        @endif

        <div class="info-block">
            <div class="info-label">Estimator</div>
            <div class="info-value">
                @if($estimator)
                    {{ trim($estimator->first_name . ' ' . $estimator->last_name) }}
                    @if($estimator->phone) <br><span style="color:#555">{{ $estimator->phone }}</span> @endif
                @else
                    —
                @endif
            </div>
        </div>

        <div class="info-block">
            <div class="info-label">Flooring Type</div>
            <div style="margin-top:2px">
                @forelse((array) $rfm->flooring_type as $type)
                    <span class="flooring-tag">{{ $type }}</span>
                @empty
                    <span style="color:#888">—</span>
                @endforelse
            </div>
        </div>
    </div>

</div>

{{-- ── Scheduled Date Highlight Box ────────────────────────────────── --}}
<div class="schedule-box">
    <div class="label">Scheduled Date &amp; Time</div>
    <div class="value">{{ $rfm->scheduled_at->format('l, F j, Y \a\t g:i A') }}</div>
    @if($address)
        <div style="margin-top:5px; font-size:11px; color:#555">{{ $address }}</div>
    @endif
</div>

{{-- ── Special Instructions ─────────────────────────────────────────── --}}
@if(filled($rfm->special_instructions))
<div class="section">
    <div class="section-label">Special Instructions</div>
    <div class="instructions-box">{{ $rfm->special_instructions }}</div>
</div>
@endif

{{-- ── Footer ───────────────────────────────────────────────────────── --}}
@php
    $mobileUrl = route('mobile.rfms.show', $rfm->id);
    $qrSvg     = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(64)->margin(1)->generate($mobileUrl);
    $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
@endphp
<div class="footer" style="display:table; width:100%;">
    <div style="display:table-cell; vertical-align:middle;">
        {{ $companyName }}
        @if($phone) &mdash; {{ $phone }} @endif
        @if($email) &nbsp;|&nbsp; {{ $email }} @endif
        @if($website) &nbsp;|&nbsp; {{ $website }} @endif
    </div>
    <div style="display:table-cell; vertical-align:middle; text-align:right; width:72pt;">
        <img src="{{ $qrDataUri }}" style="width:64pt; height:64pt;">
        <div style="font-size:8px; color:#aaa; margin-top:2px; text-align:center;">Mobile view</div>
    </div>
</div>

</body>
</html>
