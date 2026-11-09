<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#1f2937;">
    @if ($kind === 'cancelled')
        <p><strong>{{ $customer_name }} cancelled their showroom visit</strong> using their appointment link.</p>
    @else
        <p><strong>{{ $customer_name }} has asked to change their showroom visit.</strong> Please reschedule it in Floor Manager — the customer will get a new confirmation once you save the new time.</p>
    @endif

    <p>
        <strong>Booked for:</strong> {{ $visit_date }} at {{ $visit_time }}<br>
        @if ($job_number)<strong>Job #:</strong> {{ $job_number }}<br>@endif
        @if ($host_name)<strong>Host:</strong> {{ $host_name }}<br>@endif
        @if ($visit->contact_phone)<strong>Phone:</strong> {{ $visit->contact_phone }}<br>@endif
        @if ($visit->contact_email)<strong>Email:</strong> {{ $visit->contact_email }}<br>@endif
    </p>

    @if ($kind === 'cancelled')
        @if ($visit->cancellation_reason)
            <p><strong>Reason given:</strong><br>{!! nl2br(e($visit->cancellation_reason)) !!}</p>
        @endif
    @else
        @if ($visit->change_request_preferred)
            <p><strong>Preferred times:</strong><br>{!! nl2br(e($visit->change_request_preferred)) !!}</p>
        @endif
        @if ($visit->change_request_note)
            <p><strong>Note:</strong><br>{!! nl2br(e($visit->change_request_note)) !!}</p>
        @endif
    @endif

    <p><a href="{{ $fm_link }}">Open the visit in Floor Manager</a></p>
</div>
