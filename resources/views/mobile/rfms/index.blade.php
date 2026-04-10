{{-- resources/views/mobile/rfms/index.blade.php --}}
<x-mobile-layout title="Measures (RFMs)">

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.rfms.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search customer or job #…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex gap-2">
            @foreach (['active' => 'Active', 'all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="flex-1 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-blue-600 border-blue-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    {{-- Results --}}
    @if ($rfms->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5"/>
            </svg>
            <p class="text-sm">No measures found</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($rfms as $rfm)
                @php
                    $statusColors = [
                        'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                    ];
                    $statusColor = $statusColors[$rfm->status] ?? 'bg-gray-100 text-gray-700';
                    $customerName = $rfm->jobSiteCustomer?->company_name
                        ?: ($rfm->jobSiteCustomer?->name
                        ?: ($rfm->parentCustomer?->company_name
                        ?: ($rfm->parentCustomer?->name
                        ?: 'Unknown Customer')));
                @endphp
                <a href="{{ route('mobile.rfms.show', $rfm) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50 dark:active:bg-gray-750">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $customerName }}</span>
                            <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColor }}">
                                {{ ucfirst($rfm->status) }}
                            </span>
                        </div>
                        @if ($rfm->opportunity?->job_no)
                            <p class="text-xs text-gray-500 dark:text-gray-400">Job #{{ $rfm->opportunity->job_no }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1">
                            @if ($rfm->scheduled_at)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $rfm->scheduled_at->format('M j, Y g:i A') }}
                                </span>
                            @endif
                            @if ($rfm->estimator)
                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $rfm->estimator->name }}</span>
                            @endif
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if ($rfms->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($rfms->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $rfms->previousPageUrl() }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $rfms->currentPage() }} / {{ $rfms->lastPage() }}</span>
                @if ($rfms->hasMorePages())
                    <a href="{{ $rfms->nextPageUrl() }}" class="text-sm text-blue-600 dark:text-blue-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
