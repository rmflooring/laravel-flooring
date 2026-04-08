Hi {{ $customer_name }},

This is a friendly reminder that a flooring sample you borrowed from our showroom is now overdue for return.

Sample Details
--------------
Sample ID:    {{ $sample_id }}
Product:      {{ $product_name }}
Checked Out:  {{ $checked_out_date }}
Due Back:     {{ $due_back_date }}
Days Overdue: {{ $days_overdue }} day(s)

Please return the sample to our showroom at your earliest convenience.

If you have any questions or would like to arrange a drop-off, please don't hesitate to contact us:
@if ($showroom_phone)
  Phone: {{ $showroom_phone }}
@endif
@if ($showroom_email)
  Email: {{ $showroom_email }}
@endif

Thank you for your cooperation.

{{ $company_name }}
