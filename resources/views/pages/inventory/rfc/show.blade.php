{{-- resources/views/pages/inventory/rfc/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.rfc.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">RFC</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $rfc->rfc_number }}</span>
            </nav>

            {{-- Flash --}}
            @if (session('success'))
                <div class="flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $badgeClass = match($rfc->status) {
                    'draft'     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                    'received'  => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    default     => 'bg-gray-100 text-gray-700',
                };
            @endphp

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Header card --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ $rfc->rfc_number }}</h1>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ $rfc->status_label }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($rfc->isDraft())
                                    @can('edit rfcs')
                                        <a href="{{ route('pages.inventory.rfc.edit', $rfc) }}"
                                           class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Edit
                                        </a>
                                    @endcan

                                    {{-- Mark Received modal trigger --}}
                                    @can('create rfcs')
                                        <button type="button"
                                                onclick="document.getElementById('receive-modal').classList.remove('hidden')"
                                                class="inline-flex items-center gap-1.5 rounded-md bg-teal-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Mark Received
                                        </button>

                                        <form method="POST" action="{{ route('pages.inventory.rfc.destroy', $rfc) }}"
                                              onsubmit="return confirm('Delete this RFC? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </div>

                        {{-- Items table --}}
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/40">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Condition</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty returned</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Inventory record</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($rfc->items as $item)
                                    <tr>
                                        <td class="px-6 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $item->item_name }}</div>
                                            @if ($item->notes)
                                                <div class="text-xs text-gray-400 mt-0.5">{{ $item->notes }}</div>
                                            @endif
                                            @if ($item->saleItem?->room)
                                                <div class="text-xs text-gray-400">{{ $item->saleItem->room->room_name }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300 capitalize">
                                            {{ $item->condition ?: '—' }}
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity_returned, 2), '0'), '.') }}
                                            <span class="text-xs text-gray-400 font-normal">{{ $item->unit }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm">
                                            @if ($item->inventoryReceipt)
                                                <a href="{{ route('pages.inventory.show', $item->inventoryReceipt) }}"
                                                   class="text-teal-600 hover:underline dark:text-teal-400 font-medium">
                                                    Record #{{ $item->inventoryReceipt->id }}
                                                </a>
                                            @else
                                                <span class="text-gray-400 text-xs">Pending receipt</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-400">No items.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">

                    {{-- Details --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Details</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">RFC #</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $rfc->rfc_number }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Status</dt>
                                <dd><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $rfc->status_label }}</span></dd>
                            </div>
                            @if ($rfc->pickTicket)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Pick Ticket</dt>
                                    <dd>
                                        <a href="{{ route('pages.warehouse.pick-tickets.show', $rfc->pickTicket) }}"
                                           class="text-blue-600 hover:underline dark:text-blue-400">
                                            PT #{{ $rfc->pickTicket->pt_number }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            @if ($rfc->sale)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Sale</dt>
                                    <dd>
                                        <a href="{{ route('pages.sales.show', $rfc->sale) }}"
                                           class="text-indigo-600 hover:underline dark:text-indigo-400">
                                            Sale #{{ $rfc->sale->sale_number }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            @if ($rfc->received_date)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Received on</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $rfc->received_date->format('M j, Y') }}</dd>
                                </div>
                            @endif
                            @if ($rfc->received_by)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Received by</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $rfc->received_by }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    {{-- Reason / Notes --}}
                    @if ($rfc->reason || $rfc->notes)
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            @if ($rfc->reason)
                                <h3 class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">Reason</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">{{ $rfc->reason }}</p>
                            @endif
                            @if ($rfc->notes)
                                <h3 class="mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">Notes</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $rfc->notes }}</p>
                            @endif
                        </div>
                    @endif

                    {{-- Audit --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Audit</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Created by</dt>
                                <dd class="text-gray-900 dark:text-white text-right">{{ $rfc->creator?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Created at</dt>
                                <dd class="text-gray-500 text-xs text-right">{{ $rfc->created_at->format('M j, Y g:ia') }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Mark Received Modal --}}
    @if ($rfc->isDraft())
    <div id="receive-modal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Mark RFC as Received</h2>
                <button onclick="document.getElementById('receive-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('pages.inventory.rfc.receive', $rfc) }}">
                @csrf
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Marking as received will create new inventory records for each item, adding the returned quantities back into available stock.
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Received by</label>
                        <input type="text" name="received_by" value="{{ old('received_by') }}"
                               placeholder="Name of person who received the goods…"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date received</label>
                        <input type="date" name="received_date" value="{{ now()->toDateString() }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- Item summary --}}
                    <div class="bg-teal-50 dark:bg-teal-900/20 border border-teal-200 dark:border-teal-800 rounded-lg p-4">
                        <p class="text-xs font-semibold text-teal-800 dark:text-teal-300 mb-2 uppercase tracking-wide">Inventory records that will be created</p>
                        <ul class="space-y-1 text-sm text-teal-900 dark:text-teal-200">
                            @foreach ($rfc->items as $item)
                                <li class="flex justify-between gap-2">
                                    <span>{{ $item->item_name }}</span>
                                    <span class="font-medium whitespace-nowrap">
                                        +{{ rtrim(rtrim(number_format((float) $item->quantity_returned, 2), '0'), '.') }} {{ $item->unit }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 rounded-b-xl">
                    <button type="button" onclick="document.getElementById('receive-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-4 focus:ring-teal-300">
                        Confirm — Mark Received
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</x-app-layout>
