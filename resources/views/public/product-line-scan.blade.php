@php
    $unitLabel    = $productLine->unit?->label ?? 'unit';
    $price        = $productLine->default_sell_price;
    $manufacturer = $productLine->manufacturer;
    $typeName     = $productLine->productType?->name;
    $styles       = $productLine->productStyles;
@endphp
<x-public-scan-layout :title="$title">

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-3">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-0.5">Product Line</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $productLine->name }}</h1>
            @if ($manufacturer)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $manufacturer }}</p>
            @endif
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex flex-wrap gap-6 text-sm">
            @if ($typeName)
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Category</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $typeName }}</p>
            </div>
            @endif
            @if ($price)
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Starting From</p>
                <p class="font-bold text-blue-600 dark:text-blue-400 text-xl">
                    ${{ number_format($price, 2) }}<span class="text-xs font-normal text-gray-400"> / {{ $unitLabel }}</span>
                </p>
            </div>
            @endif
            @if ($styles->isNotEmpty())
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide">Colors Available</p>
                <p class="font-bold text-gray-900 dark:text-white text-xl">{{ $styles->count() }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Styles / colors list --}}
    @if ($styles->isNotEmpty())
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Available Colors</p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($styles as $style)
                @php
                    $photo      = $style->photos->firstWhere('is_primary', true) ?? $style->photos->first();
                    $stylePrice = $style->sell_price ?? $price;
                @endphp
                <div class="flex items-center gap-3 px-4 py-3">
                    {{-- Photo thumbnail --}}
                    @if ($photo)
                        <div class="w-14 h-14 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 flex-shrink-0 bg-gray-100 dark:bg-gray-800">
                            <img src="{{ $photo->url }}" alt="{{ $style->name }}" class="w-full h-full object-cover">
                        </div>
                    @else
                        <div class="w-14 h-14 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 flex-shrink-0 bg-gray-50 dark:bg-gray-800 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                            </svg>
                        </div>
                    @endif

                    {{-- Name + meta --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $style->name }}</p>
                        <div class="flex flex-wrap gap-x-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            @if ($style->color)<span>{{ $style->color }}</span>@endif
                            @if ($style->sku)<span class="font-mono">{{ $style->sku }}</span>@endif
                        </div>
                    </div>

                    {{-- Price --}}
                    @if ($stylePrice)
                        <div class="flex-shrink-0 text-right">
                            <p class="text-sm font-bold text-blue-600 dark:text-blue-400">${{ number_format($stylePrice, 2) }}</p>
                            <p class="text-xs text-gray-400">/ {{ $unitLabel }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 text-center text-sm text-gray-400">
        No active colors listed for this product line.
    </div>
    @endif

</x-public-scan-layout>
