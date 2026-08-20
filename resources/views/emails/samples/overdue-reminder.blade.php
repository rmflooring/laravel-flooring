Hi {{ $customer_name }},

This is a friendly reminder that {{ $item_count > 1 ? 'flooring samples you borrowed' : 'a flooring sample you borrowed' }} from our showroom {{ $item_count > 1 ? 'are' : 'is' }} now overdue for return.

@if ($checkout_number)
Checkout #:   {{ $checkout_number }}
@endif
Checked Out:  {{ $checked_out_date }}
Due Back:     {{ $due_back_date }}
Days Overdue: {{ $days_overdue }} day(s)

Samples
-------
@foreach ($item_lines as $line)
- {{ $line }}
@endforeach

Please return {{ $item_count > 1 ? 'these samples' : 'the sample' }} to our showroom at your earliest convenience.

If you have any questions or would like to arrange a drop-off, please don't hesitate to contact us:
@if ($showroom_phone)
  Phone: {{ $showroom_phone }}
@endif
@if ($showroom_email)
  Email: {{ $showroom_email }}
@endif

Thank you for your cooperation.

{{ $company_name }}
