@php
    $colors = [
        'pending'   => ['bg' => '#6b7280', 'text' => '#fff'],   // gray
        'ready'     => ['bg' => '#2563eb', 'text' => '#fff'],   // blue
        'picked'    => ['bg' => '#7c3aed', 'text' => '#fff'],   // purple
        'staged'              => ['bg' => '#ea580c', 'text' => '#fff'],   // orange
        'partially_delivered' => ['bg' => '#ca8a04', 'text' => '#fff'],   // yellow-600
        'delivered'           => ['bg' => '#16a34a', 'text' => '#fff'],   // green
        'returned'  => ['bg' => '#d97706', 'text' => '#fff'],   // amber
        'cancelled' => ['bg' => '#9ca3af', 'text' => '#fff'],   // light gray
    ];
    $c = $colors[$status] ?? ['bg' => '#6b7280', 'text' => '#fff'];
    $label = \App\Models\PickTicket::STATUS_LABELS[$status] ?? ucfirst($status);
@endphp
<span class="badge"
      style="background-color: {{ $c['bg'] }}; color: {{ $c['text'] }}; font-size: .75rem; font-weight: 500; padding: .3em .65em;">
    {{ $label }}
</span>
