{{-- resources/views/pages/work-orders/show.blade.php --}}
<x-admin-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            @php
                $statusColors = [
                    'created'     => 'bg-gray-100 text-gray-700',
                    'scheduled'   => 'bg-blue-100 text-blue-800',
                    'in_progress' => 'bg-amber-100 text-amber-800',
                    'completed'   => 'bg-green-100 text-green-800',
                    'cancelled'   => 'bg-red-100 text-red-800',
                ];
            @endphp

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <nav class="flex items-center gap-1.5 text-sm text-gray-500 mb-2">
                        <a href="{{ route('pages.sales.show', $sale) }}"
                           class="inline-flex items-center gap-1 hover:text-gray-700 font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Sale {{ $sale->sale_number ?? ('#' . $sale->id) }}
                        </a>
                        @if ($sale->customer_name)
                            <span class="text-gray-300">·</span>
                            <span>{{ $sale->customer_name }}</span>
                        @endif
                    </nav>

                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-900">{{ $workOrder->wo_number }}</h1>

                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$workOrder->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $workOrder->status_label }}
                        </span>

                        @if ($workOrder->calendar_synced)
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                On calendar
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                Not synced
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @can('edit work orders')
                    <a href="{{ route('pages.sales.work-orders.edit', [$sale, $workOrder]) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Edit
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Details card --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Work type</div>
                        <div class="font-medium text-gray-900">{{ $workOrder->work_type }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Assigned to</div>
                        <div class="font-medium text-gray-900">
                            {{ $workOrder->assignedTo?->name ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Scheduled date</div>
                        <div class="font-medium text-gray-900">
                            {{ $workOrder->scheduled_date?->format('M j, Y') ?? '—' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Scheduled time</div>
                        <div class="font-medium text-gray-900">
                            @if ($workOrder->scheduled_time)
                                {{ \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') }}
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Sale</div>
                        <div class="font-medium">
                            <a href="{{ route('pages.sales.show', $sale) }}"
                               class="text-blue-600 hover:underline">
                                {{ $sale->sale_number ?? ('#' . $sale->id) }}
                            </a>
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 mb-0.5">Calendar</div>
                        <div class="font-medium text-gray-900">
                            @if ($workOrder->calendar_synced)
                                <span style="color:#16a34a">On calendar</span>
                            @else
                                <span class="text-gray-400">Not synced</span>
                            @endif
                        </div>
                    </div>

                    @if ($workOrder->notes)
                        <div class="sm:col-span-2">
                            <div class="text-xs text-gray-500 mb-0.5">Notes</div>
                            <div class="text-gray-700 whitespace-pre-line">{{ $workOrder->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
