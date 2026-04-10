{{-- resources/views/mobile/samples/index.blade.php --}}
<x-mobile-layout title="Samples">

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.samples.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search sample ID, style, or location…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500">
        </div>
        <div class="flex gap-2 flex-wrap">
            @foreach (['active' => 'Active', 'all' => 'All', 'checked_out' => 'Out', 'discontinued' => 'Discontinued'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-amber-600 border-amber-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    {{-- Results --}}
    @if ($samples->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
            </svg>
            <p class="text-sm">No samples found</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($samples as $sample)
                @php
                    $statusBadge = [
                        'active'       => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'checked_out'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                        'discontinued' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        'retired'      => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                        'lost'         => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                    ];
                    $badgeClass = $statusBadge[$sample->status] ?? 'bg-gray-100 text-gray-700';
                    $statusLabel = \App\Models\Sample::STATUSES[$sample->status] ?? ucfirst($sample->status);
                    $styleName = $sample->productStyle?->name ?? '—';
                    $lineName  = $sample->productStyle?->productLine?->name ?? null;
                @endphp
                <a href="{{ route('mobile.samples.show', $sample->sample_id) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50 dark:active:bg-gray-750">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ $sample->sample_id }}</span>
                            <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ $styleName }}</p>
                        <div class="flex items-center gap-3 mt-0.5">
                            @if ($lineName)
                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $lineName }}</span>
                            @endif
                            @if ($sample->location)
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ $sample->location }}</span>
                            @endif
                            @if ($sample->status === 'active')
                                <span class="text-xs text-gray-400 dark:text-gray-500">Qty: {{ $sample->available_qty }}</span>
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
        @if ($samples->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($samples->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $samples->previousPageUrl() }}" class="text-sm text-amber-600 dark:text-amber-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $samples->currentPage() }} / {{ $samples->lastPage() }}</span>
                @if ($samples->hasMorePages())
                    <a href="{{ $samples->nextPageUrl() }}" class="text-sm text-amber-600 dark:text-amber-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
