{{-- resources/views/mobile/work-orders/index.blade.php --}}
<x-mobile-layout title="Work Orders">

    {{-- Search & Filter --}}
    <form method="GET" action="{{ route('mobile.work-orders.index') }}" class="space-y-2">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
            </svg>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search WO #, installer, or customer…"
                   class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex gap-2 flex-wrap">
            @foreach (['active' => 'Active', 'all' => 'All', 'scheduled' => 'Scheduled', 'in_progress' => 'In Progress', 'completed' => 'Completed'] as $val => $label)
                <button type="submit" name="status" value="{{ $val }}"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                               {{ $status === $val
                                   ? 'bg-indigo-600 border-indigo-600 text-white'
                                   : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </form>

    {{-- Results --}}
    @if ($workOrders->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
            </svg>
            <p class="text-sm">No work orders found</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($workOrders as $wo)
                @php
                    $statusColors = [
                        'created'         => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        'scheduled'       => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                        'in_progress'     => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
                        'partial'         => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                        'site_not_ready'  => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                        'needs_levelling' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                        'needs_attention' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                        'completed'       => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                        'cancelled'       => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                    ];
                    $statusColor = $statusColors[$wo->status] ?? 'bg-gray-100 text-gray-700';
                    $statusLabel = \App\Models\WorkOrder::STATUS_LABELS[$wo->status] ?? ucfirst($wo->status);
                    $customerName = $wo->sale?->opportunity?->parentCustomer?->company_name
                        ?? $wo->sale?->opportunity?->parentCustomer?->name
                        ?? '—';
                @endphp
                <a href="{{ route('mobile.work-orders.show', $wo) }}"
                   class="flex items-start gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 shadow-sm active:bg-gray-50 dark:active:bg-gray-750">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">WO #{{ $wo->wo_number }}</span>
                            <span class="shrink-0 text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColor }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $customerName }}</p>
                        <div class="flex items-center gap-3 mt-1">
                            @if ($wo->scheduled_date)
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $wo->scheduled_date->format('M j, Y') }}
                                </span>
                            @endif
                            @if ($wo->installer)
                                <span class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $wo->installer->name }}</span>
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
        @if ($workOrders->hasPages())
            <div class="flex items-center justify-between pt-1">
                @if ($workOrders->onFirstPage())
                    <span class="text-sm text-gray-300 dark:text-gray-600">Previous</span>
                @else
                    <a href="{{ $workOrders->previousPageUrl() }}" class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">Previous</a>
                @endif
                <span class="text-xs text-gray-400">{{ $workOrders->currentPage() }} / {{ $workOrders->lastPage() }}</span>
                @if ($workOrders->hasMorePages())
                    <a href="{{ $workOrders->nextPageUrl() }}" class="text-sm text-indigo-600 dark:text-indigo-400 font-medium">Next</a>
                @else
                    <span class="text-sm text-gray-300 dark:text-gray-600">Next</span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
