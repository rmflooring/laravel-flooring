{{-- resources/views/mobile/warehouse/rfc/index.blade.php --}}
<x-mobile-layout title="Customer Returns (RFC)">

    <a href="{{ route('mobile.warehouse.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Warehouse
    </a>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.warehouse.rfc.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search RFC number…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex gap-1.5 flex-wrap">
            @foreach(['all' => 'All', 'draft' => 'Draft', 'received' => 'Received', 'cancelled' => 'Cancelled'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-blue-600 border-blue-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @if ($rfcs->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
            </svg>
            <p class="text-sm">No customer returns found</p>
        </div>
    @else
        @php
            $statusColors = [
                'draft'     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                'received'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            ];
        @endphp
        <div class="space-y-2">
            @foreach ($rfcs as $rfc)
                <a href="{{ route('mobile.warehouse.rfc.show', $rfc) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $rfc->rfc_number }}</span>
                            <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$rfc->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $rfc->status_label }}
                            </span>
                        </div>
                        @if ($rfc->sale)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sale #{{ $rfc->sale->sale_number }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $rfc->items_count ?? 0 }} item{{ ($rfc->items_count ?? 0) !== 1 ? 's' : '' }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $rfc->created_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        @if ($rfcs->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($rfcs->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $rfcs->previousPageUrl() }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $rfcs->currentPage() }} / {{ $rfcs->lastPage() }}</span>
                @if ($rfcs->hasMorePages())
                    <a href="{{ $rfcs->nextPageUrl() }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
