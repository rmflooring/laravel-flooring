{{-- resources/views/pages/warehouse/pickups/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $isPickup   = $purchaseOrder->fulfillment_method === 'pickup';
                $isDelivery = $purchaseOrder->fulfillment_method === 'delivery_warehouse';

                $scheduledDate = $isPickup
                    ? $purchaseOrder->pickup_at
                    : ($purchaseOrder->expected_delivery_date ? \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date) : null);

                $isOverdue = $scheduledDate
                    && $scheduledDate->isPast()
                    && ! in_array($purchaseOrder->status, ['received', 'delivered', 'cancelled']);

                $statusColors = [
                    'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'ordered'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'received'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'delivered' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                ];
                $statusColor = $statusColors[$purchaseOrder->status] ?? 'bg-gray-100 text-gray-800';
            @endphp

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            PO #{{ $purchaseOrder->po_number }}
                        </h1>
                        {{-- Type badge --}}
                        @if ($isPickup)
                            <span class="inline-flex items-center gap-1 rounded-full px-3 py-0.5 text-sm font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                Pickup
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full px-3 py-0.5 text-sm font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                Warehouse Delivery
                            </span>
                        @endif
                        {{-- Status --}}
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                            {{ $purchaseOrder->status_label }}
                        </span>
                        @if ($isOverdue)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-red-600 text-white">
                                Overdue
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $purchaseOrder->vendor->company_name ?? '—' }}
                        @if ($purchaseOrder->vendor_order_number)
                            &middot; Vendor Order: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $purchaseOrder->vendor_order_number }}</span>
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('pages.warehouse.pickups.index') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Back to List
                    </a>
                    @can('view purchase orders')
                    <a href="{{ route('pages.purchase-orders.show', $purchaseOrder) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Full PO Details
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    @endcan
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 dark:bg-green-900 dark:border-green-700 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Main two-column layout --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Left column (2/3) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Fulfillment Card --}}
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ $isPickup ? 'Pickup Details' : 'Delivery Details' }}
                            </h2>
                        </div>
                        <div class="p-5 space-y-3">
                            @if ($scheduledDate)
                                <div class="flex items-start gap-3">
                                    <span class="w-32 text-sm text-gray-500 dark:text-gray-400 shrink-0">
                                        {{ $isPickup ? 'Pickup at' : 'Expected on' }}
                                    </span>
                                    <span class="text-sm font-semibold {{ $isOverdue ? 'text-red-700 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                                        {{ $scheduledDate->format('l, F j, Y') }}
                                        @if ($isPickup && $purchaseOrder->pickup_at)
                                            at {{ $purchaseOrder->pickup_at->format('g:i A') }}
                                        @endif
                                        @if ($isOverdue)
                                            <span class="ml-2 text-xs font-semibold text-red-600 dark:text-red-400">— Overdue</span>
                                        @endif
                                    </span>
                                </div>
                            @else
                                <div class="flex items-start gap-3">
                                    <span class="w-32 text-sm text-gray-500 dark:text-gray-400 shrink-0">
                                        {{ $isPickup ? 'Pickup at' : 'Expected on' }}
                                    </span>
                                    <span class="text-sm text-gray-400 dark:text-gray-500 italic">Not scheduled</span>
                                </div>
                            @endif

                            @if ($purchaseOrder->delivery_address)
                                <div class="flex items-start gap-3">
                                    <span class="w-32 text-sm text-gray-500 dark:text-gray-400 shrink-0">Address</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $purchaseOrder->delivery_address }}</span>
                                </div>
                            @endif

                            @if ($purchaseOrder->expected_delivery_date && ! $isPickup)
                                <div class="flex items-start gap-3">
                                    <span class="w-32 text-sm text-gray-500 dark:text-gray-400 shrink-0">Expected</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('M j, Y') }}</span>
                                </div>
                            @endif

                            @if ($purchaseOrder->special_instructions)
                                <div class="flex items-start gap-3">
                                    <span class="w-32 text-sm text-gray-500 dark:text-gray-400 shrink-0">Instructions</span>
                                    <span class="text-sm text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded px-2 py-1 whitespace-pre-line">{{ $purchaseOrder->special_instructions }}</span>
                                </div>
                            @endif

                            @if ($purchaseOrder->calendarEvent)
                                <div class="flex items-start gap-3">
                                    <span class="w-32 text-sm text-gray-500 dark:text-gray-400 shrink-0">Calendar</span>
                                    <span class="inline-flex items-center gap-1 text-xs text-green-700 dark:text-green-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Synced to RM Warehouse calendar
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Items Card --}}
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                Items
                                <span class="ml-1 text-xs font-normal text-gray-400">({{ $purchaseOrder->items->count() }})</span>
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-750">
                                        <th class="px-5 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 hidden sm:table-cell">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                    @forelse ($purchaseOrder->items as $item)
                                        <tr>
                                            <td class="px-5 py-3 text-sm text-gray-900 dark:text-gray-100">
                                                {{ $item->item_name }}
                                                @if ($item->po_notes)
                                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 sm:hidden">{{ $item->po_notes }}</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {{ number_format($item->quantity, 2) }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                                {{ $item->unit ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 hidden sm:table-cell">
                                                {{ $item->po_notes ?? '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-5 py-6 text-center text-sm text-gray-400 dark:text-gray-500">
                                                No items on this PO.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Order Confirmations --}}
                    @include('pages.purchase-orders._documents', ['purchaseOrder' => $purchaseOrder])

                </div>

                {{-- Right column (1/3) --}}
                <div class="space-y-6">

                    {{-- Vendor Card --}}
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Vendor</h2>
                        </div>
                        <div class="p-5 space-y-2">
                            @if ($purchaseOrder->vendor)
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $purchaseOrder->vendor->company_name }}</p>
                                @if ($purchaseOrder->vendor->phone)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <a href="tel:{{ $purchaseOrder->vendor->phone }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $purchaseOrder->vendor->phone }}
                                        </a>
                                    </p>
                                @endif
                                @if ($purchaseOrder->vendor->email)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <a href="mailto:{{ $purchaseOrder->vendor->email }}" class="hover:text-blue-600 dark:hover:text-blue-400 truncate">
                                            {{ $purchaseOrder->vendor->email }}
                                        </a>
                                    </p>
                                @endif
                                @if ($purchaseOrder->vendor->address)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-start gap-2">
                                        <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span class="whitespace-pre-line">{{ $purchaseOrder->vendor->address }}</span>
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No vendor linked.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Job Info Card --}}
                    @if ($purchaseOrder->sale)
                        @php $sale = $purchaseOrder->sale; @endphp
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Job Details</h2>
                            </div>
                            <div class="p-5 space-y-3">
                                {{-- Sale # --}}
                                <div class="flex items-start gap-2">
                                    <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">Sale #</span>
                                    <a href="{{ route('pages.sales.show', $sale) }}"
                                       class="text-sm font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $sale->sale_number }}
                                    </a>
                                </div>

                                {{-- Customer --}}
                                @if ($sale->customer_name)
                                    <div class="flex items-start gap-2">
                                        <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">Customer</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $sale->customer_name }}</span>
                                    </div>
                                @endif

                                {{-- Job Name --}}
                                @if ($sale->job_name)
                                    <div class="flex items-start gap-2">
                                        <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">Job Name</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $sale->job_name }}</span>
                                    </div>
                                @endif

                                {{-- Homeowner / Job Site --}}
                                @if ($sale->homeowner_name)
                                    <div class="flex items-start gap-2">
                                        <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">Site Contact</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $sale->homeowner_name }}</span>
                                    </div>
                                @endif

                                {{-- Job Address --}}
                                @if ($sale->job_address)
                                    <div class="flex items-start gap-2">
                                        <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">Job Site</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $sale->job_address }}</span>
                                    </div>
                                @endif

                                {{-- PM --}}
                                @if ($sale->opportunity?->projectManager)
                                    <div class="flex items-start gap-2">
                                        <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">PM</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $sale->opportunity->projectManager->name }}</span>
                                    </div>
                                @endif

                                {{-- Sale Status --}}
                                <div class="flex items-start gap-2">
                                    <span class="w-24 text-xs text-gray-500 dark:text-gray-400 shrink-0 pt-0.5">Sale Status</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ ucfirst(str_replace('_', ' ', $sale->status)) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Install Date Card --}}
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700 {{ $nextInstall ? 'border-l-4 border-l-orange-400 dark:border-l-orange-500' : '' }}">
                            <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                                </svg>
                                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Install Date</h2>
                            </div>
                            <div class="p-5">
                                @if ($nextInstall && $nextInstall->scheduled_date)
                                    @php
                                        $installDate = \Carbon\Carbon::parse($nextInstall->scheduled_date);
                                    @endphp
                                    <p class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                        {{ $installDate->format('l, M j, Y') }}
                                    </p>
                                    <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <p>WO <span class="font-medium text-gray-800 dark:text-gray-200">{{ $nextInstall->wo_number }}</span></p>
                                        @if ($nextInstall->installer)
                                            <p>Installer: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $nextInstall->installer->name }}</span></p>
                                        @endif
                                        <p class="capitalize">Status: {{ str_replace('_', ' ', $nextInstall->status) }}</p>
                                    </div>
                                    @if ($scheduledDate)
                                        @php $daysUntilInstall = \Carbon\Carbon::today()->diffInDays($installDate, false); @endphp
                                        @if ($daysUntilInstall >= 0 && $daysUntilInstall <= 7)
                                            <div class="mt-3 flex items-center gap-1.5 rounded-md bg-orange-50 border border-orange-200 dark:bg-orange-900/30 dark:border-orange-700 px-3 py-2 text-xs font-medium text-orange-700 dark:text-orange-300">
                                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                </svg>
                                                Install in {{ $daysUntilInstall }} day{{ $daysUntilInstall !== 1 ? 's' : '' }} — materials needed soon
                                            </div>
                                        @endif
                                    @endif
                                @else
                                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">No work order with a scheduled install date.</p>
                                    @if ($sale->workOrders->where('status', '<>', 'cancelled')->count() > 0)
                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                            {{ $sale->workOrders->where('status', '<>', 'cancelled')->count() }} work order(s) exist but none are scheduled yet.
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>

                    @else
                        {{-- Stock PO --}}
                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Job Details</h2>
                            </div>
                            <div class="p-5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                    </svg>
                                    Stock Purchase Order
                                </span>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">This PO is not linked to a specific job.</p>
                            </div>
                        </div>
                    @endif

                    {{-- Order Info Card --}}
                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 rounded-t-lg">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Order Info</h2>
                        </div>
                        <div class="p-5 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">PO #</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $purchaseOrder->po_number }}</span>
                            </div>
                            @if ($purchaseOrder->vendor_order_number)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Vendor Order #</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $purchaseOrder->vendor_order_number }}</span>
                                </div>
                            @endif
                            @if ($purchaseOrder->orderedBy)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Ordered By</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $purchaseOrder->orderedBy->name }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Created</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $purchaseOrder->created_at->format('M j, Y') }}</span>
                            </div>
                            @if ($purchaseOrder->sent_at)
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Sent to Vendor</span>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $purchaseOrder->sent_at->format('M j, Y') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
