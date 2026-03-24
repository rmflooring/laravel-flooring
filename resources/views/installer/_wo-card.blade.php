@php
    $statusColor = $statusColors[$wo->status] ?? 'bg-gray-100 text-gray-800';
    $customerName = $wo->sale?->homeowner_name ?? $wo->sale?->customer_name ?? $wo->sale?->job_name ?? 'Customer';
    $jobAddress   = $wo->sale?->job_address ?? '';
    $dateLabel    = $wo->scheduled_date?->format('D, M j') ?? '—';
    $timeLabel    = $wo->scheduled_time ? \Carbon\Carbon::createFromFormat('H:i', $wo->scheduled_time)->format('g:i A') : null;
@endphp

<a href="{{ route('mobile.work-orders.show', $wo) }}"
   class="flex items-center gap-4 rounded-xl border {{ $highlight ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} px-4 py-3.5 shadow-sm active:scale-95 transition-transform">

    {{-- Date block --}}
    <div class="flex flex-col items-center justify-center w-12 shrink-0 {{ $highlight ? 'text-blue-700 dark:text-blue-300' : 'text-gray-500 dark:text-gray-400' }}">
        <span class="text-[10px] font-bold uppercase tracking-wide leading-none">
            {{ $wo->scheduled_date?->format('M') ?? '—' }}
        </span>
        <span class="text-2xl font-black leading-tight">
            {{ $wo->scheduled_date?->format('j') ?? '—' }}
        </span>
    </div>

    {{-- Details --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $customerName }}</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $statusColor }}">
                {{ \App\Models\WorkOrder::STATUS_LABELS[$wo->status] ?? $wo->status }}
            </span>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            WO {{ $wo->wo_number }}
            @if($timeLabel) &middot; {{ $timeLabel }} @endif
        </p>
        @if($jobAddress)
            <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{ $jobAddress }}</p>
        @endif
        @if($wo->installer_notes)
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 truncate italic">{{ $wo->installer_notes }}</p>
        @endif
    </div>

    <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
    </svg>
</a>
