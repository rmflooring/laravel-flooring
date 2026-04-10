{{-- resources/views/mobile/opportunities/index.blade.php --}}
<x-mobile-layout title="Opportunities">

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.opportunities.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search job #, customer, or site…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-sky-500">
        </div>
        <div class="flex gap-2">
            @foreach (['active' => 'Active', 'all' => 'All'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="flex-1 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-sky-600 border-sky-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    {{-- Results --}}
    @if ($opportunities->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            <p class="text-sm">No opportunities found</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($opportunities as $opp)
                @php
                    $customerName = $opp->parentCustomer?->company_name
                        ?? $opp->parentCustomer?->name
                        ?? '—';
                    $siteName = $opp->jobSiteCustomer?->company_name
                        ?? $opp->jobSiteCustomer?->name
                        ?? null;
                @endphp
                <a href="{{ route('mobile.opportunity.show', $opp) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50 dark:active:bg-gray-750">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ $customerName }}</span>
                            @if ($opp->job_no)
                                <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                                    {{ $opp->job_no }}
                                </span>
                            @endif
                        </div>
                        @if ($siteName && $siteName !== $customerName)
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $siteName }}</p>
                        @endif
                        <div class="flex items-center gap-3 mt-1">
                            @if ($opp->projectManager)
                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate">PM: {{ $opp->projectManager->name }}</span>
                            @endif
                            @if ($opp->status)
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ ucfirst($opp->status) }}</span>
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
        @if ($opportunities->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($opportunities->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $opportunities->previousPageUrl() }}" class="text-sm text-sky-600 dark:text-sky-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $opportunities->currentPage() }} / {{ $opportunities->lastPage() }}</span>
                @if ($opportunities->hasMorePages())
                    <a href="{{ $opportunities->nextPageUrl() }}" class="text-sm text-sky-600 dark:text-sky-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
