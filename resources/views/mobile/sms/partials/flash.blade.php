{{-- Flash messages + validation errors for the mobile SMS pages --}}
@foreach (['success' => ['#f0fdf4', '#bbf7d0', '#166534'], 'warning' => ['#fffbeb', '#fde68a', '#92400e'], 'error' => ['#fef2f2', '#fecaca', '#991b1b']] as $key => [$bg, $border, $fg])
    @if (session($key))
        <div class="rounded-xl px-4 py-3 flex items-start justify-between gap-3"
             style="background:{{ $bg }};border:1px solid {{ $border }};color:{{ $fg }};">
            <span class="text-sm font-medium">{{ session($key) }}</span>
            <button type="button" onclick="this.closest('div').remove()" class="text-lg leading-none">&times;</button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div class="rounded-xl px-4 py-3" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
        @foreach ($errors->all() as $error)
            <p class="text-sm">{{ $error }}</p>
        @endforeach
    </div>
@endif
