{{-- resources/views/pages/inventory/rtv/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Returns to Vendor (RTV)</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Vendor returns — goods shipped back, awaiting resolution.</p>
                </div>
                @can('create rtvs')
                <a href="{{ route('pages.inventory.rtv.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New RTV
                </a>
                @endcan
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-4">
                <form method="GET" action="{{ route('pages.inventory.rtv.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search RTV #</label>
                        <input type="text" name="q" value="{{ $q }}"
                               placeholder="RTV-2026-0001…"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">
                        Filter
                    </button>
                    @if ($q || $status)
                        <a href="{{ route('pages.inventory.rtv.index') }}"
                           class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                @if ($rtvs->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                        No RTVs found.
                        @can('create rtvs')
                            <a href="{{ route('pages.inventory.rtv.create') }}" class="text-orange-600 hover:underline">Create one</a>.
                        @endcan
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <tr>
                                    <th class="px-5 py-3 font-medium">RTV #</th>
                                    <th class="px-5 py-3 font-medium">Vendor</th>
                                    <th class="px-5 py-3 font-medium">PO #</th>
                                    <th class="px-5 py-3 font-medium">Reason</th>
                                    <th class="px-5 py-3 font-medium text-center">Items</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium">Created</th>
                                    <th class="px-5 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($rtvs as $rtv)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-5 py-3 font-mono font-medium text-gray-900 dark:text-white">
                                            {{ $rtv->return_number }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                            {{ $rtv->vendor?->name ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            @if ($rtv->purchaseOrder)
                                                <a href="{{ route('pages.purchase-orders.show', $rtv->purchaseOrder) }}"
                                                   class="text-blue-600 hover:underline dark:text-blue-400">
                                                    PO #{{ $rtv->purchaseOrder->po_number }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-gray-600 dark:text-gray-400">
                                            {{ $rtv->reason_label }}
                                        </td>
                                        <td class="px-5 py-3 text-center text-gray-500">{{ $rtv->items_count }}</td>
                                        <td class="px-5 py-3">
                                            @php
                                                $badgeClass = match($rtv->status) {
                                                    'draft'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    'shipped'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                    'resolved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    default    => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                                {{ $rtv->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">
                                            {{ $rtv->created_at->format('M j, Y') }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('pages.inventory.rtv.show', $rtv) }}"
                                               class="text-sm font-medium text-orange-600 hover:text-orange-800 dark:text-orange-400">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($rtvs->hasPages())
                        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-4">
                            {{ $rtvs->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
