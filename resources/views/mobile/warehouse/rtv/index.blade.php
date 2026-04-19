{{-- resources/views/mobile/warehouse/rtv/index.blade.php --}}
<x-mobile-layout title="Returns to Vendor (RTV)">

    <a href="{{ route('mobile.warehouse.index') }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Warehouse
    </a>

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.warehouse.rtv.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search RTV number…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>
        <div class="flex gap-1.5 flex-wrap">
            @foreach(['all' => 'All', 'draft' => 'Draft', 'shipped' => 'Shipped', 'resolved' => 'Resolved'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-purple-600 border-purple-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    @if ($rtvs->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
            </svg>
            <p class="text-sm">No vendor returns found</p>
        </div>
    @else
        @php
            $statusColors = [
                'draft'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                'shipped'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                'resolved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            ];
        @endphp
        <div class="space-y-2">
            @foreach ($rtvs as $rtv)
                <a href="{{ route('mobile.warehouse.rtv.show', $rtv) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $rtv->return_number }}</span>
                            <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColors[$rtv->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $rtv->status_label }}
                            </span>
                        </div>
                        @if ($rtv->vendor)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rtv->vendor->company_name }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $rtv->items_count ?? 0 }} item{{ ($rtv->items_count ?? 0) !== 1 ? 's' : '' }}</span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $rtv->created_at->format('M j, Y') }}</span>
                            @if ($rtv->reason)
                                <span class="text-xs text-gray-400 dark:text-gray-500 capitalize">{{ $rtv->reason_label }}</span>
                            @endif
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        @if ($rtvs->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($rtvs->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $rtvs->previousPageUrl() }}" class="text-sm text-purple-600 dark:text-purple-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $rtvs->currentPage() }} / {{ $rtvs->lastPage() }}</span>
                @if ($rtvs->hasMorePages())
                    <a href="{{ $rtvs->nextPageUrl() }}" class="text-sm text-purple-600 dark:text-purple-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
