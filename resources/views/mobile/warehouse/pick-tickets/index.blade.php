{{-- resources/views/mobile/warehouse/pick-tickets/index.blade.php --}}
<x-mobile-layout title="Pick Tickets">

    <a href="{{ route('mobile.warehouse.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Warehouse
    </a>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.warehouse.pick-tickets.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search PT number…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div class="flex gap-1.5 flex-wrap">
            @foreach(['active' => 'Active', 'all' => 'All', 'pending' => 'Pending', 'ready' => 'Ready', 'picked' => 'Picked', 'staged' => 'Staged', 'delivered' => 'Delivered'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-orange-600 border-orange-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @if ($pickTickets->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm">No pick tickets found</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($pickTickets as $pt)
                @php
                    $statusColors = [
                        'pending'             => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        'ready'               => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                        'picked'              => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                        'staged'              => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
                        'partially_delivered' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                        'delivered'           => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'returned'            => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                        'cancelled'           => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                    ];
                    $badgeClass = $statusColors[$pt->status] ?? 'bg-gray-100 text-gray-700';
                @endphp
                <a href="{{ route('mobile.warehouse.pick-tickets.show', $pt) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">PT #{{ $pt->pt_number }}</span>
                            <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                {{ $pt->status_label }}
                            </span>
                        </div>
                        @if ($pt->sale)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Sale #{{ $pt->sale->sale_number }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1">
                            @if ($pt->workOrder)
                                <span class="text-xs text-gray-400 dark:text-gray-500">WO #{{ $pt->workOrder->wo_number }}</span>
                            @endif
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $pt->items_count ?? $pt->items->count() }} item{{ ($pt->items_count ?? $pt->items->count()) !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        @if ($pickTickets->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($pickTickets->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $pickTickets->previousPageUrl() }}" class="text-sm text-orange-600 dark:text-orange-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $pickTickets->currentPage() }} / {{ $pickTickets->lastPage() }}</span>
                @if ($pickTickets->hasMorePages())
                    <a href="{{ $pickTickets->nextPageUrl() }}" class="text-sm text-orange-600 dark:text-orange-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
