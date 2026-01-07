@props([
    'label' => '',
    'field' => '',
])

@php
    $currentSort = request('sort');
    $currentDir  = request('dir', 'asc');

    $isActive = ($currentSort === $field);

    $nextDir = 'asc';
    if ($isActive && $currentDir === 'asc') {
        $nextDir = 'desc';
    }

    $query = request()->query();
    $query['sort'] = $field;
    $query['dir']  = $nextDir;

    $url = url()->current() . '?' . http_build_query($query);

    $arrow = '';
    if ($isActive) {
        $arrow = $currentDir === 'asc' ? ' ▲' : ' ▼';
    }
@endphp

<a href="{{ $url }}" class="inline-flex items-center gap-1 hover:underline">
    <span>{{ $label }}</span>
    <span class="{{ $isActive ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
        {{ $arrow }}
    </span>
</a>
