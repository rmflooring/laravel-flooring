@php
    $headings = [
        'booked'              => 'Your showroom visit is booked',
        'updated'             => 'Your showroom visit has been updated',
        'reminder_day_before' => 'See you tomorrow!',
        'reminder_morning'    => 'See you today!',
        'cancelled'           => 'Your showroom visit has been cancelled',
    ];
    $intros = [
        'booked'              => "Thanks for booking a visit to our showroom to choose your flooring. Here are the details:",
        'updated'             => "Your showroom visit has been changed. Here are the updated details:",
        'reminder_day_before' => "This is a friendly reminder that your showroom visit is tomorrow.",
        'reminder_morning'    => "Just a reminder that we're looking forward to seeing you at the showroom today.",
        'cancelled'           => "Your showroom visit listed below has been cancelled. If you'd like to rebook, just give us a call or reply to this email.",
    ];
    $isCancelled = $kind === 'cancelled';
@endphp
<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#1f2937;max-width:560px;margin:0 auto;">
    <h2 style="font-size:20px;color:#111827;margin:0 0 12px;">{{ $headings[$kind] ?? 'Your showroom visit' }}</h2>

    <p style="margin:0 0 16px;">Hi {{ $customer_first_name }},</p>
    <p style="margin:0 0 16px;">{{ $intros[$kind] ?? '' }}</p>

    <table cellpadding="0" cellspacing="0" style="width:100%;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;margin:0 0 20px;">
        <tr><td style="padding:16px 20px;">
            <p style="margin:0 0 6px;"><strong>Date:</strong> {{ $visit_date }}</p>
            <p style="margin:0 0 6px;"><strong>Time:</strong> {{ $visit_time }} – {{ $visit_end_time }}</p>
            @if ($host_name && ! $isCancelled)
                <p style="margin:0 0 6px;"><strong>You'll be meeting with:</strong> {{ $host_name }}</p>
            @endif
            @if ($showroom_address)
                <p style="margin:0;"><strong>Where:</strong> {{ $company_name }} Showroom, {{ $showroom_address }}</p>
            @endif
        </td></tr>
    </table>

    @if (! $isCancelled)
        <p style="margin:0 0 12px;">
            To help us make the most of your visit, it's handy to bring room measurements, photos of the space,
            and anything you're trying to match (cabinet doors, paint chips, etc.).
        </p>

        <p style="margin:0 0 8px;">Need to cancel or change your appointment?</p>
        <p style="margin:0 0 20px;">
            <a href="{{ $manage_link }}"
               style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-weight:bold;">
                Manage my appointment
            </a>
        </p>
    @endif

    <p style="margin:0 0 4px;">Questions? We're happy to help:</p>
    <p style="margin:0 0 20px;">
        @if ($showroom_phone) Phone: {{ $showroom_phone }}<br>@endif
        @if ($showroom_email) Email: {{ $showroom_email }}@endif
    </p>

    <p style="margin:0;">Thank you,<br>{{ $company_name }}</p>
</div>
