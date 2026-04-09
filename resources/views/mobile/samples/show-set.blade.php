<x-mobile-layout :title="$sampleSet->set_id . ' – ' . ($sampleSet->name ?? $sampleSet->productLine->name)">

    @php
        $statusColors = \App\Models\SampleSet::STATUS_COLORS;
        $isAvailable  = $sampleSet->status === 'active';
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

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 mb-0.5">Sample Set</p>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sampleSet->set_id }}</h1>
                @if ($sampleSet->name)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-0.5">{{ $sampleSet->name }}</p>
                @endif
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sampleSet->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $sampleSet->status_label }}
            </span>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700 pt-3 flex gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400">Manufacturer</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $sampleSet->productLine->manufacturer }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400">Product Line</p>
                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $sampleSet->productLine->name }}</p>
            </div>
        </div>
    </div>

    {{-- Location + style count --}}
    <div class="grid grid-cols-2 gap-3">
        @if ($sampleSet->location)
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-4">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Location</p>
            <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $sampleSet->location }}</p>
        </div>
        @endif
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-4 text-center {{ $sampleSet->location ? '' : 'col-span-2' }}">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Styles</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sampleSet->items->count() }}</p>
        </div>
    </div>

    {{-- Styles list --}}
    @if ($sampleSet->items->count() > 0)
    <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Styles Included</p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach ($sampleSet->items as $item)
                <div class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->productStyle->name }}</p>
                    <p class="text-xs text-gray-500">
                        @if ($item->productStyle->sku){{ $item->productStyle->sku }}@endif
                        @if ($item->productStyle->color) · {{ $item->productStyle->color }}@endif
                    </p>
                    @if ($item->display_price)
                        <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5">${{ number_format($item->display_price, 2) }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Checkout button (desktop form inline for mobile) --}}
    @can('manage sample checkouts')
    <div class="pt-2">
        @if ($isAvailable)
            <a href="{{ route('mobile.sample-sets.checkout', $sampleSet->set_id) }}"
               class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                </svg>
                Check Out This Set
            </a>
        @elseif ($sampleSet->status === 'checked_out')
            <div class="flex flex-col items-center w-full gap-2 px-6 py-3.5 text-sm font-medium text-blue-700 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl">
                <span>Currently checked out</span>
                @if ($sampleSet->activeCheckout)
                    <span class="text-xs text-blue-600">to {{ $sampleSet->activeCheckout->borrower_name }}</span>
                @endif
            </div>
        @else
            <div class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-gray-400 bg-gray-100 dark:bg-gray-700 dark:text-gray-500 rounded-xl cursor-not-allowed">
                Set not available for checkout
            </div>
        @endif
    </div>
    @endcan

    {{-- View in system --}}
    @can('edit samples')
    <a href="{{ route('pages.sample-sets.show', $sampleSet) }}"
       class="flex items-center justify-center gap-2 w-full px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
        </svg>
        Open in System
    </a>
    @endcan

</x-mobile-layout>
