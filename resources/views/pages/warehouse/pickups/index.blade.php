{{-- resources/views/pages/warehouse/pickups/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pickups &amp; Deliveries</h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Vendor pickups and warehouse deliveries scheduled for the warehouse.
                </p>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 dark:bg-green-900 dark:border-green-700 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('pages.warehouse.pickups.index') }}"
                  class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">

                    {{-- Search --}}
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" value="{{ $q }}"
                                   placeholder="PO #, vendor, sale #..."
                                   class="block w-full pl-10 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
                        </div>
                    </div>

                    {{-- Type --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select name="type"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Types</option>
                            <option value="pickup" @selected($type === 'pickup')>Pickup</option>
                            <option value="delivery_warehouse" @selected($type === 'delivery_warehouse')>Warehouse Delivery</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All Statuses</option>
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt }}" @selected($status === $opt)>{{ ucfirst($opt) }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Date To --}}
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Buttons --}}
                    <div class="lg:col-span-12 flex flex-wrap items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Apply Filters
                        </button>
                        <a href="{{ route('pages.warehouse.pickups.index') }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                            Reset
                        </a>
                        <span class="ml-auto text-sm text-gray-500 dark:text-gray-400">
                            Showing <strong>{{ $purchaseOrders->firstItem() ?? 0 }}</strong>–<strong>{{ $purchaseOrders->lastItem() ?? 0 }}</strong>
                            of <strong>{{ $purchaseOrders->total() }}</strong>
                        </span>
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PO #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Vendor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scheduled Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Job / Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Install Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($purchaseOrders as $po)
                                @php
                                    // Scheduled date: pickup_at for pickups, expected_delivery_date for deliveries
                                    $scheduledDate = $po->fulfillment_method === 'pickup'
                                        ? $po->pickup_at
                                        : ($po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date) : null);

                                    $isOverdue = $scheduledDate
                                        && $scheduledDate->isPast()
                                        && ! in_array($po->status, ['received', 'delivered', 'cancelled']);

                                    // Next install date from work orders
                                    $nextInstall = null;
                                    if ($po->sale) {
                                        $nextInstall = $po->sale->workOrders
                                            ->whereNotIn('status', ['cancelled', 'completed'])
                                            ->whereNotNull('scheduled_date')
                                            ->sortBy('scheduled_date')
                                            ->first();
                                    }

                                    $statusColors = [
                                        'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'ordered'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'received'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'delivered' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    ];
                                    $statusColor = $statusColors[$po->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 {{ $isOverdue ? 'bg-red-50 dark:bg-red-950' : '' }}">

                                    {{-- PO # --}}
                                    <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                        <a href="{{ route('pages.warehouse.pickups.show', $po) }}" class="hover:underline">
                                            {{ $po->po_number }}
                                        </a>
                                    </td>

                                    {{-- Type badge --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($po->fulfillment_method === 'pickup')
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                Pickup
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                                Delivery
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Vendor --}}
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $po->vendor->company_name ?? '—' }}
                                    </td>

                                    {{-- Scheduled date --}}
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        @if ($scheduledDate)
                                            <span class="{{ $isOverdue ? 'font-semibold text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $scheduledDate->format('M j, Y') }}
                                                @if ($po->fulfillment_method === 'pickup' && $po->pickup_at)
                                                    <span class="text-gray-400 text-xs">{{ $po->pickup_at->format('g:i A') }}</span>
                                                @endif
                                            </span>
                                            @if ($isOverdue)
                                                <span class="ml-1 text-xs font-semibold text-red-600 dark:text-red-400">Overdue</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Job / Type --}}
                                    <td class="px-4 py-3 text-sm">
                                        @if ($po->sale)
                                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                                <a href="{{ route('pages.sales.show', $po->sale) }}" class="hover:underline text-blue-600 dark:text-blue-400">
                                                    Sale #{{ $po->sale->sale_number }}
                                                </a>
                                            </div>
                                            @if ($po->sale->customer_name)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $po->sale->customer_name }}</div>
                                            @endif
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                Stock PO
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Install date --}}
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        @if ($nextInstall && $nextInstall->scheduled_date)
                                            @php
                                                $installDate = \Carbon\Carbon::parse($nextInstall->scheduled_date);
                                                $installSoon = $scheduledDate && $installDate->diffInDays($scheduledDate, false) <= 3 && $installDate->isFuture();
                                            @endphp
                                            <span class="{{ $installSoon ? 'font-semibold text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $installDate->format('M j, Y') }}
                                            </span>
                                            <div class="text-xs text-gray-400 dark:text-gray-500">WO {{ $nextInstall->wo_number }}</div>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                            {{ $po->status_label }}
                                        </span>
                                    </td>

                                    {{-- Action --}}
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('pages.warehouse.pickups.show', $po) }}"
                                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No pickups or deliveries found matching your filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if ($purchaseOrders->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                        {{ $purchaseOrders->links() }}
                    </div>
                @endif
            </div>

            {{-- Sale Pickups & Deliveries (direct-staged pick tickets) --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sale Pickups &amp; Deliveries</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Material orders staged directly from a sale (no work order).</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">PT #</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Sale / Customer</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Scheduled Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($pickTickets as $pt)
                                @php
                                    $ptStatusColors = [
                                        'staged'              => 'bg-orange-100 text-orange-800',
                                        'pending'             => 'bg-gray-100 text-gray-700',
                                        'ready'               => 'bg-blue-100 text-blue-800',
                                        'picked'              => 'bg-purple-100 text-purple-800',
                                        'partially_delivered' => 'bg-yellow-100 text-yellow-800',
                                        'delivered'           => 'bg-green-100 text-green-800',
                                    ];
                                    $ptStatusColor = $ptStatusColors[$pt->status] ?? 'bg-gray-100 text-gray-700';

                                    $isOverdue = $pt->delivery_date
                                        && $pt->delivery_date->isPast()
                                        && ! in_array($pt->status, ['delivered', 'cancelled', 'returned']);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 {{ $isOverdue ? 'bg-red-50 dark:bg-red-950' : '' }}">

                                    {{-- PT # --}}
                                    <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400 whitespace-nowrap">
                                        <a href="{{ route('pages.warehouse.pick-tickets.show', $pt) }}" class="hover:underline">
                                            {{ $pt->pt_number }}
                                        </a>
                                    </td>

                                    {{-- Type badge --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($pt->fulfillment_type === 'pickup')
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                </svg>
                                                Pickup
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                </svg>
                                                Delivery
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Sale / Customer --}}
                                    <td class="px-4 py-3 text-sm">
                                        @if ($pt->sale)
                                            <a href="{{ route('pages.sales.show', $pt->sale) }}"
                                               class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                Sale #{{ $pt->sale->sale_number }}
                                            </a>
                                            @if ($pt->sale->customer_name)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $pt->sale->customer_name }}</div>
                                            @endif
                                            @if ($pt->sale->homeowner_name)
                                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $pt->sale->homeowner_name }}</div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Scheduled date --}}
                                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                                        @if ($pt->delivery_date)
                                            <span class="{{ $isOverdue ? 'font-semibold text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                {{ $pt->delivery_date->format('M j, Y') }}
                                                @if ($pt->delivery_time)
                                                    <span class="text-gray-400 text-xs">{{ \Carbon\Carbon::createFromFormat('H:i', $pt->delivery_time)->format('g:i A') }}</span>
                                                @endif
                                            </span>
                                            @if ($isOverdue)
                                                <span class="ml-1 text-xs font-semibold text-red-600 dark:text-red-400">Overdue</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                        @php
                                            $ptNextInstall = null;
                                            if ($pt->sale) {
                                                $ptNextInstall = $pt->sale->workOrders
                                                    ->whereNotIn('status', ['cancelled', 'completed'])
                                                    ->whereNotNull('scheduled_date')
                                                    ->sortBy('scheduled_date')
                                                    ->first();
                                            }
                                        @endphp
                                        @if ($ptNextInstall)
                                            <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                                install {{ \Carbon\Carbon::parse($ptNextInstall->scheduled_date)->format('M j, Y') }}
                                                @if ($ptNextInstall->scheduled_time)
                                                    {{ \Carbon\Carbon::createFromFormat('H:i', $ptNextInstall->scheduled_time)->format('g:i A') }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ptStatusColor }}">
                                            {{ $pt->status_label }}
                                        </span>
                                    </td>

                                    {{-- Notes --}}
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                        {{ $pt->staging_notes ?? '—' }}
                                    </td>

                                    {{-- Action --}}
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('pages.warehouse.pick-tickets.show', $pt) }}"
                                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No sale pickups or deliveries found matching your filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
