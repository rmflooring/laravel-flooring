<x-mobile-layout :title="$sample->sample_id . ' – ' . $sample->productStyle->name">

    @php
        $style     = $sample->productStyle;
        $line      = $style->productLine;
        $primary   = $style->photos->firstWhere('is_primary', true) ?? $style->photos->first();
        $available = $sample->available_qty;

        $statusColors = [
            'active'       => 'bg-green-100 text-green-800',
            'checked_out'  => 'bg-blue-100 text-blue-800',
            'discontinued' => 'bg-gray-100 text-gray-600',
            'retired'      => 'bg-yellow-100 text-yellow-800',
            'lost'         => 'bg-red-100 text-red-800',
        ];
    @endphp

    {{-- Flash --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-sm font-medium text-green-800">{{ session('success') }}</span>
            <button type="button" onclick="this.closest('div').remove()" class="text-green-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-sm font-medium text-red-800">{{ session('error') }}</span>
            <button type="button" onclick="this.closest('div').remove()" class="text-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Product photo --}}
    @if ($primary)
        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 aspect-video">
            <img src="{{ $primary->url }}" alt="{{ $style->name }}" class="w-full h-full object-cover">
        </div>
    @endif

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-0.5">Sample</p>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sample->sample_id }}</h1>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sample->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $sample->status_label }}
            </span>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 space-y-2">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Product</p>
                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $style->name }}</p>
            </div>
            @if ($line?->manufacturer)
            <div class="flex gap-4 text-sm">
                <div>
                    <p class="text-xs text-gray-400">Manufacturer</p>
                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $line->manufacturer }}</p>
                </div>
                @if ($line->name)
                <div>
                    <p class="text-xs text-gray-400">Product Line</p>
                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $line->name }}</p>
                </div>
                @endif
            </div>
            @endif
            @if ($style->color)
            <div class="text-sm">
                <p class="text-xs text-gray-400">Colour</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $style->color }}</p>
            </div>
            @endif
            @if ($style->sku)
            <div class="text-sm">
                <p class="text-xs text-gray-400">SKU</p>
                <p class="font-medium text-gray-800 dark:text-gray-200 font-mono">{{ $style->sku }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Pricing + Availability --}}
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

        <div class="rounded-xl border border-gray-200 shadow-sm p-4 text-center {{ $available === 0 ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' }}">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Available</p>
            <p class="text-2xl font-bold {{ $available === 0 ? 'text-red-600' : 'text-green-700 dark:text-green-400' }}">{{ $available }}</p>
            <p class="text-xs text-gray-500 mt-0.5">of {{ $sample->quantity }} total</p>
        </div>
    </div>

    {{-- Location --}}
    @if ($sample->location)
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

    {{-- Checkout button --}}
    @can('manage sample checkouts')
    <div class="pt-2">
        @if ($available > 0 && in_array($sample->status, ['active', 'checked_out']))
            <a href="{{ route('mobile.samples.checkout', $sample->sample_id) }}"
               class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                </svg>
                Check Out Sample
            </a>
        @else
            <div class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-gray-400 bg-gray-100 dark:bg-gray-700 dark:text-gray-500 rounded-xl cursor-not-allowed">
                {{ $available === 0 ? 'No copies available' : 'Sample not available' }}
            </div>
        @endif
    </div>
    @endcan

    {{-- View in system (for admin) --}}
    @can('edit samples')
    <a href="{{ route('pages.samples.show', $sample) }}"
       class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
        </svg>
        Open in System
    </a>
    @endcan

</x-mobile-layout>
