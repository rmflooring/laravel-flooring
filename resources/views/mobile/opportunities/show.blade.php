{{-- resources/views/mobile/opportunities/show.blade.php --}}
@php
    $customerName = $opportunity->parentCustomer?->company_name
        ?: $opportunity->parentCustomer?->name
        ?: 'Opportunity';

    $jobSiteName = $opportunity->jobSiteCustomer?->company_name
        ?: $opportunity->jobSiteCustomer?->name
        ?: null;

    $rfmStatusColors = [
        'pending'   => 'bg-amber-100 text-amber-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
    $poStatusColors = [
        'pending'   => 'bg-amber-100 text-amber-800',
        'ordered'   => 'bg-blue-100 text-blue-800',
        'received'  => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
@endphp

<x-mobile-layout :title="($opportunity->job_no ? '#'.$opportunity->job_no.' – ' : '') . $customerName">

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
            <button type="button" onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">
                    Opportunity{{ $opportunity->job_no ? ' #'.$opportunity->job_no : '' }}
                </p>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $customerName }}</h1>
                @if($jobSiteName && $jobSiteName !== $customerName)
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $jobSiteName }}</p>
                @endif
                @if($opportunity->job_name)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $opportunity->job_name }}</p>
                @endif
            </div>
            @if($opportunity->status)
                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-200">
                    {{ $opportunity->status }}
                </span>
            @endif
        </div>

        @if($opportunity->projectManager)
            <div class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/>
                </svg>
                <span>PM: <strong>{{ $opportunity->projectManager->name }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Job Site contact --}}
    @if($opportunity->jobSiteCustomer)
    @php $js = $opportunity->jobSiteCustomer; @endphp
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4 space-y-3">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Job Site</p>

        <p class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ $js->company_name ?: $js->name }}
        </p>

        @php
            $jsAddr = collect([$js->address, $js->address2, $js->city, $js->postal_code])->filter()->implode(', ');
        @endphp
        @if($jsAddr)
            <a href="https://maps.google.com/?q={{ urlencode($jsAddr) }}" target="_blank"
               class="flex items-start gap-2 text-sm text-blue-600 dark:text-blue-400 underline">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                <span>{{ $jsAddr }}</span>
            </a>
        @endif

        @if($js->phone)
            <a href="tel:{{ $js->phone }}" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                </svg>
                {{ $js->phone }}
            </a>
        @endif
        @if($js->mobile)
            <a href="tel:{{ $js->mobile }}" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3"/>
                </svg>
                {{ $js->mobile }}
            </a>
        @endif
        @if($js->email)
            <a href="mailto:{{ $js->email }}" class="flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                {{ $js->email }}
            </a>
        @endif
    </div>
    @endif

    {{-- RFMs --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Requests for Measure</p>
            @can('create rfms')
            <a href="{{ route('pages.opportunities.rfms.create', $opportunity->id) }}"
               class="text-xs font-semibold text-blue-600 dark:text-blue-400">+ Add</a>
            @endcan
        </div>

        @if($opportunity->rfms->isEmpty())
            <p class="px-4 py-3 text-sm text-gray-400 dark:text-gray-500">No RFMs yet.</p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($opportunity->rfms as $rfm)
                    <li>
                        <a href="{{ route('mobile.rfms.show', $rfm->id) }}"
                           class="flex items-center justify-between px-4 py-3 active:bg-gray-50 dark:active:bg-gray-700/50">
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $rfm->scheduled_at->format('M j, Y') }}
                                    <span class="font-normal text-gray-500 dark:text-gray-400">at {{ $rfm->scheduled_at->format('g:i A') }}</span>
                                </p>
                                @if($rfm->estimator)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $rfm->estimator->first_name }} {{ $rfm->estimator->last_name }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rfmStatusColors[$rfm->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($rfm->status) }}
                                </span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Sales --}}
    @if($opportunity->sales->isNotEmpty())
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Sales</p>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($opportunity->sales as $sale)
                @php
                    $displayTotal = ($sale->revised_contract_total > 0)
                        ? $sale->revised_contract_total
                        : (($sale->locked_grand_total > 0) ? $sale->locked_grand_total : ($sale->grand_total ?? 0));
                @endphp
                <li>
                    <a href="{{ route('pages.sales.edit', $sale->id) }}"
                       class="flex items-center justify-between px-4 py-3 active:bg-gray-50 dark:active:bg-gray-700/50">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                Sale #{{ $sale->sale_number }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                ${{ number_format((float) $displayTotal, 2) }}
                                @if($sale->status) · {{ $sale->status }} @endif
                            </p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Purchase Orders --}}
    @can('view purchase orders')
    @if($opportunity->purchaseOrders->isNotEmpty())
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Purchase Orders</p>
        </div>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($opportunity->purchaseOrders as $po)
                <li>
                    <a href="{{ route('mobile.po.show', $po->id) }}"
                       class="flex items-center justify-between px-4 py-3 active:bg-gray-50 dark:active:bg-gray-700/50">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $po->po_number }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $po->vendor?->company_name }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $poStatusColors[$po->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $po->status_label }}
                            </span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif
    @endcan

    {{-- Photos --}}
    <a href="{{ route('mobile.opportunity.photos', $opportunity->id) }}"
       class="flex items-center gap-4 rounded-xl border border-indigo-200 bg-white dark:border-indigo-800 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40">
            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900 dark:text-white">Job Photos</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">View all photos for this job</p>
        </div>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</x-mobile-layout>
