{{-- resources/views/pages/inventory/rfc/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Returns From Customer (RFC)</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Customer returns received back into warehouse inventory.</p>
                </div>
                @can('create rfcs')
                <a href="{{ route('pages.inventory.rfc.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300 dark:bg-teal-700 dark:hover:bg-teal-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New RFC
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
                <form method="GET" action="{{ route('pages.inventory.rfc.index') }}" class="flex flex-wrap items-end gap-3">
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search RFC #</label>
                        <input type="text" name="q" value="{{ $q }}"
                               placeholder="RFC-2026-0001…"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">
                        Filter
                    </button>
                    @if ($q || $status)
                        <a href="{{ route('pages.inventory.rfc.index') }}"
                           class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            Clear
                        </a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                @if ($rfcs->isEmpty())
                    <div class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                        No RFCs found.
                        @can('create rfcs')
                            <a href="{{ route('pages.inventory.rfc.create') }}" class="text-teal-600 hover:underline">Create one</a>.
                        @endcan
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 text-xs text-gray-500 dark:text-gray-400 uppercase">
                                <tr>
                                    <th class="px-5 py-3 font-medium">RFC #</th>
                                    <th class="px-5 py-3 font-medium">Pick Ticket</th>
                                    <th class="px-5 py-3 font-medium">Sale</th>
                                    <th class="px-5 py-3 font-medium text-center">Items</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="px-5 py-3 font-medium">Created</th>
                                    <th class="px-5 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($rfcs as $rfc)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-5 py-3 font-mono font-medium text-gray-900 dark:text-white">
                                            {{ $rfc->rfc_number }}
                                        </td>
                                        <td class="px-5 py-3">
                                            @if ($rfc->pickTicket)
                                                <a href="{{ route('pages.warehouse.pick-tickets.show', $rfc->pickTicket) }}"
                                                   class="text-blue-600 hover:underline dark:text-blue-400">
                                                    PT #{{ $rfc->pickTicket->pt_number }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            @if ($rfc->sale)
                                                <a href="{{ route('pages.sales.show', $rfc->sale) }}"
                                                   class="text-indigo-600 hover:underline dark:text-indigo-400">
                                                    Sale #{{ $rfc->sale->sale_number }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-center text-gray-500">{{ $rfc->items_count }}</td>
                                        <td class="px-5 py-3">
                                            @php
                                                $badgeClass = match($rfc->status) {
                                                    'draft'     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    'received'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                    'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                                    default     => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                                {{ $rfc->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 text-xs whitespace-nowrap">
                                            {{ $rfc->created_at->format('M j, Y') }}
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('pages.inventory.rfc.show', $rfc) }}"
                                               class="text-sm font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($rfcs->hasPages())
                        <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-4">
                            {{ $rfcs->links() }}
                        </div>
                    @endif
                @endif
            </div>

        {{-- Archived RFCs (admin only) --}}
        @role('admin')
        @if ($trashedRfcs->isNotEmpty())
        <div class="bg-white border border-orange-200 rounded-xl shadow-sm">
            <div class="px-6 py-4 border-b border-orange-100 flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <h2 class="text-sm font-semibold text-orange-700 uppercase tracking-wide">Archived RFCs</h2>
                <span class="text-xs text-orange-500 ml-1">Soft-deleted RFCs. Restore to reactivate or permanently delete if no longer needed.</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700">
                    <thead class="border-b border-orange-100 bg-orange-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-5 py-3 font-medium">RFC #</th>
                            <th class="px-5 py-3 font-medium">Sale</th>
                            <th class="px-5 py-3 font-medium text-center">Items</th>
                            <th class="px-5 py-3 font-medium">Deleted</th>
                            <th class="px-5 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-orange-50">
                        @foreach ($trashedRfcs as $trfc)
                            @php
                                $rfcBlocked = $trfc->items()->whereHas('inventoryReceipt')->exists();
                            @endphp
                            <tr class="hover:bg-orange-50/50">
                                <td class="px-5 py-3 font-mono font-medium text-gray-900">{{ $trfc->rfc_number }}</td>
                                <td class="px-5 py-3">
                                    @if ($trfc->sale)
                                        <span class="text-gray-600">Sale #{{ $trfc->sale->sale_number }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-center text-gray-500">{{ $trfc->items_count }}</td>
                                <td class="px-5 py-3 text-gray-500 text-xs">{{ $trfc->deleted_at->format('M j, Y') }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <form method="POST" action="{{ route('pages.inventory.rfc.restore', $trfc) }}"
                                              onsubmit="return confirm('Restore RFC {{ $trfc->rfc_number }}?')">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-green-600 hover:underline">Restore</button>
                                        </form>
                                        @if ($rfcBlocked)
                                            <span class="text-xs text-gray-400 italic" title="Has inventory receipts linked to items">Cannot delete</span>
                                        @else
                                            <form method="POST" action="{{ route('pages.inventory.rfc.force-destroy', $trfc) }}"
                                                  onsubmit="return confirm('Permanently delete RFC {{ $trfc->rfc_number }}? This cannot be undone.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-red-600 hover:underline">Permanently Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @endrole

        </div>
    </div>
</x-app-layout>
