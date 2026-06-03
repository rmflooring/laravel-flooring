@php
    $isSample = isset($sample);
    $isSet    = isset($set);

    if ($isSample) {
        $style       = $sample->productStyle;
        $line        = $style->productLine;
        $primary     = $style->photos->firstWhere('is_primary', true) ?? $style->photos->first();
        $available   = $sample->available_qty;
        $statusColor = \App\Models\Sample::STATUS_COLORS[$sample->status] ?? 'bg-gray-100 text-gray-700';
        $title       = $sample->sample_id . ' – ' . $style->name;
    } else {
        $line        = $set->productLine;
        $statusColor = \App\Models\SampleSet::STATUS_COLORS[$set->status] ?? 'bg-gray-100 text-gray-700';
        $title       = $set->set_id . ' – ' . ($set->name ?? $line->name);
    }
@endphp
<x-public-scan-layout :title="$title">

    {{-- Sample: photo --}}
    @if ($isSample && isset($primary))
        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-video">
            <img src="{{ $primary->url }}" alt="{{ $style->name }}" class="w-full h-full object-cover">
        </div>
    @endif

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                @if ($isSample)
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-0.5">Sample</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sample->sample_id }}</h1>
                @else
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-0.5">Sample Set</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $set->set_id }}</h1>
                    @if ($set->name)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $set->name }}</p>
                    @endif
                @endif
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">
                {{ $isSample ? $sample->status_label : $set->status_label }}
            </span>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-2">
            @if ($isSample)
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide">Product</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $style->name }}</p>
                </div>
            @endif
            @if ($line)
            <div class="flex gap-4 text-sm">
                @if ($line->manufacturer)
                <div>
                    <p class="text-xs text-gray-400">Manufacturer</p>
                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $line->manufacturer }}</p>
                </div>
                @endif
                @if ($line->name)
                <div>
                    <p class="text-xs text-gray-400">Product Line</p>
                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $line->name }}</p>
                </div>
                @endif
            </div>
            @endif
            @if ($isSample && $style->color)
            <div class="text-sm">
                <p class="text-xs text-gray-400">Colour</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $style->color }}</p>
            </div>
            @endif
            @if ($isSample && $style->sku)
            <div class="text-sm">
                <p class="text-xs text-gray-400">SKU</p>
                <p class="font-medium text-gray-800 dark:text-gray-200 font-mono">{{ $style->sku }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Sample: pricing + availability --}}
    @if ($isSample)
    <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-4 text-center">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Price</p>
            @if ($sample->effective_price)
                <p class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($sample->effective_price, 2) }}</p>
                <p class="text-xs text-gray-500 mt-0.5">per unit</p>
            @else
                <p class="text-gray-400 text-sm">Not listed</p>
            @endif
        </div>
        <div class="rounded-xl border shadow-sm p-4 text-center {{ $available === 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' }}">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Available</p>
            <p class="text-2xl font-bold {{ $available === 0 ? 'text-red-600' : 'text-green-700 dark:text-green-400' }}">{{ $available }}</p>
            <p class="text-xs text-gray-500 mt-0.5">of {{ $sample->quantity }} total</p>
        </div>
    </div>
    @endif

    {{-- Set: location + style count --}}
    @if ($isSet)
    <div class="grid grid-cols-2 gap-3">
        @if ($set->location)
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Location</p>
            <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $set->location }}</p>
        </div>
        @endif
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-4 text-center {{ $set->location ? '' : 'col-span-2' }}">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Styles</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $set->items->count() }}</p>
        </div>
    </div>
    @endif

    {{-- Sample: location --}}
    @if ($isSample && $sample->location)
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
        </svg>
        <div>
            <p class="text-xs text-gray-400">Showroom Location</p>
            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $sample->location }}</p>
        </div>
    </div>
    @endif

    {{-- Set: styles list --}}
    @if ($isSet && $set->items->count() > 0)
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Styles Included</p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($set->items as $item)
                <div class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->productStyle->name }}</p>
                    <p class="text-xs text-gray-500">
                        @if ($item->productStyle->sku){{ $item->productStyle->sku }}@endif
                        @if ($item->productStyle->color) · {{ $item->productStyle->color }}@endif
                    </p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Staff button --}}
    <div class="pt-2">
        @auth
            @can('manage sample checkouts')
                <a href="{{ $checkoutUrl }}"
                   class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    Check In / Out
                </a>
            @endcan
        @else
            <a href="{{ $checkoutUrl }}"
               class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                </svg>
                Staff — Check In / Out
            </a>
        @endauth
    </div>

</x-public-scan-layout>
