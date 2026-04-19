{{-- resources/views/mobile/warehouse/index.blade.php --}}
<x-mobile-layout title="Warehouse">

    {{-- Stats row --}}
    <div class="grid grid-cols-3 gap-2">
        <div class="rounded-xl border border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-900/20 px-3 py-3 text-center">
            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ $pendingPts }}</p>
            <p class="text-xs text-orange-600 dark:text-orange-400 mt-0.5 leading-tight">Active Pick Tickets</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20 px-3 py-3 text-center">
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $draftRfcs }}</p>
            <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5 leading-tight">Draft RFCs</p>
        </div>
        <div class="rounded-xl border border-purple-200 bg-purple-50 dark:border-purple-800 dark:bg-purple-900/20 px-3 py-3 text-center">
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $draftRtvs }}</p>
            <p class="text-xs text-purple-600 dark:text-purple-400 mt-0.5 leading-tight">Draft RTVs</p>
        </div>
    </div>

    {{-- Navigation cards --}}
    <div class="space-y-2">

        @can('view pick tickets')
        <a href="{{ route('mobile.warehouse.pick-tickets.index') }}"
           class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm active:bg-gray-50">
            <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white text-sm">Pick Tickets</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Pick, stage & deliver orders</p>
            </div>
            @if($pendingPts > 0)
                <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-orange-500 text-white text-xs font-bold">{{ $pendingPts }}</span>
            @endif
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endcan

        <a href="{{ route('mobile.inventory.index') }}"
           class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm active:bg-gray-50">
            <div class="w-10 h-10 rounded-xl bg-teal-100 dark:bg-teal-900/40 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white text-sm">Stock / Inventory</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Browse available stock</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="{{ route('mobile.warehouse.rfc.index') }}"
           class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm active:bg-gray-50">
            <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white text-sm">Customer Returns (RFC)</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Items returned from job sites</p>
            </div>
            @if($draftRfcs > 0)
                <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-xs font-bold">{{ $draftRfcs }}</span>
            @endif
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>

        <a href="{{ route('mobile.warehouse.rtv.index') }}"
           class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-5 py-4 shadow-sm active:bg-gray-50">
            <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 dark:text-white text-sm">Returns to Vendor (RTV)</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">Items being returned to suppliers</p>
            </div>
            @if($draftRtvs > 0)
                <span class="shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-purple-500 text-white text-xs font-bold">{{ $draftRtvs }}</span>
            @endif
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>

    </div>

</x-mobile-layout>
