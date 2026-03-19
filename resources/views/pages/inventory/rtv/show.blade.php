{{-- resources/views/pages/inventory/rtv/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.rtv.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">RTV</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $rtv->return_number }}</span>
            </nav>

            {{-- Flash --}}
            @if (session('success'))
                <div class="flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $badgeClass = match($rtv->status) {
                    'draft'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                    'shipped'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'resolved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    default    => 'bg-gray-100 text-gray-700',
                };
            @endphp

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Header card --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <h1 class="text-lg font-bold text-gray-900 dark:text-white">{{ $rtv->return_number }}</h1>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                    {{ $rtv->status_label }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($rtv->isDraft())
                                    @can('edit rtvs')
                                        <a href="{{ route('pages.inventory.rtv.edit', $rtv) }}"
                                           class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Edit
                                        </a>
                                    @endcan

                                    @can('create rtvs')
                                        <button type="button"
                                                onclick="document.getElementById('ship-modal').classList.remove('hidden')"
                                                class="inline-flex items-center gap-1.5 rounded-md bg-orange-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-orange-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M12 5l7 7-7 7"/>
                                            </svg>
                                            Mark as Shipped
                                        </button>

                                        <form method="POST" action="{{ route('pages.inventory.rtv.destroy', $rtv) }}"
                                              onsubmit="return confirm('Delete this RTV? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400">
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                @endif

                                @if ($rtv->status === 'shipped')
                                    @can('create rtvs')
                                        <button type="button"
                                                onclick="document.getElementById('resolve-modal').classList.remove('hidden')"
                                                class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Resolve
                                        </button>
                                    @endcan
                                @endif
                            </div>
                        </div>

                        {{-- Items table --}}
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/40">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty returned</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit cost</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Line total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @php $grandTotal = 0; @endphp
                                @forelse ($rtv->items as $item)
                                    @php $grandTotal += (float) $item->line_total; @endphp
                                    <tr>
                                        <td class="px-6 py-3">
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">
                                                {{ $item->purchaseOrderItem?->item_name ?? '—' }}
                                            </div>
                                            @if ($item->inventoryReceipt)
                                                <div class="text-xs text-gray-400 mt-0.5">
                                                    <a href="{{ route('pages.inventory.show', $item->inventoryReceipt) }}"
                                                       class="text-teal-600 hover:underline dark:text-teal-400">
                                                        Record #{{ $item->inventoryReceipt->id }}
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity_returned, 2), '0'), '.') }}
                                            <span class="text-xs text-gray-400 font-normal">{{ $item->purchaseOrderItem?->unit }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                            ${{ number_format((float) $item->unit_cost, 2) }}
                                        </td>
                                        <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            ${{ number_format((float) $item->line_total, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-6 text-center text-sm text-gray-400">No items.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if ($rtv->items->isNotEmpty())
                                <tfoot>
                                    <tr class="bg-gray-50 dark:bg-gray-700/40 border-t border-gray-200 dark:border-gray-700">
                                        <td colspan="3" class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">Total</td>
                                        <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">${{ number_format($grandTotal, 2) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
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
                                <dt class="text-gray-500 shrink-0">RTV #</dt>
                                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $rtv->return_number }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Status</dt>
                                <dd><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">{{ $rtv->status_label }}</span></dd>
                            </div>
                            @if ($rtv->purchaseOrder)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">PO</dt>
                                    <dd>
                                        <a href="{{ route('pages.purchase-orders.show', $rtv->purchaseOrder) }}"
                                           class="text-blue-600 hover:underline dark:text-blue-400">
                                            PO #{{ $rtv->purchaseOrder->po_number }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            @if ($rtv->vendor)
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Vendor</dt>
                                    <dd class="text-gray-900 dark:text-white text-right">{{ $rtv->vendor->name }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Reason</dt>
                                <dd class="text-gray-900 dark:text-white text-right">{{ $rtv->reason_label }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Outcome (shown once shipped) --}}
                    @if ($rtv->status !== 'draft')
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Resolution</h3>
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500 shrink-0">Outcome</dt>
                                    <dd class="text-gray-900 dark:text-white">{{ $rtv->outcome_label }}</dd>
                                </div>
                                @if ($rtv->vendor_reference)
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-gray-500 shrink-0">Vendor ref</dt>
                                        <dd class="font-mono text-gray-900 dark:text-white">{{ $rtv->vendor_reference }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    @endif

                    {{-- Notes --}}
                    @if ($rtv->notes)
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Notes</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $rtv->notes }}</p>
                        </div>
                    @endif

                    {{-- Audit --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Audit</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Created by</dt>
                                <dd class="text-gray-900 dark:text-white text-right">{{ $rtv->returnedBy?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Created at</dt>
                                <dd class="text-gray-500 text-xs text-right">{{ $rtv->created_at->format('M j, Y g:ia') }}</dd>
                            </div>
                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Ship modal --}}
    @if ($rtv->isDraft())
    <div id="ship-modal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Confirm Shipment</h2>
                <button onclick="document.getElementById('ship-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 py-5 space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Marking as shipped will:
                </p>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside">
                    <li>Deduct the returned quantities from inventory</li>
                    <li>Reduce sale coverage allocations for affected items</li>
                    <li>Update the PO item returned quantities</li>
                </ul>
                <p class="text-sm font-medium text-orange-700 dark:text-orange-400">This action cannot be undone.</p>
            </div>

            <form method="POST" action="{{ route('pages.inventory.rtv.ship', $rtv) }}">
                @csrf
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 rounded-b-xl">
                    <button type="button" onclick="document.getElementById('ship-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-300">
                        Confirm — Mark as Shipped
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Resolve modal --}}
    @if ($rtv->status === 'shipped')
    <div id="resolve-modal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Resolve RTV</h2>
                <button onclick="document.getElementById('resolve-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('pages.inventory.rtv.resolve', $rtv) }}">
                @csrf
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Outcome <span class="text-red-500">*</span></label>
                        <select name="outcome" required
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            @foreach (\App\Models\InventoryReturn::OUTCOME_LABELS as $value => $label)
                                @if ($value !== 'pending')
                                    <option value="{{ $value }}" {{ old('outcome') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vendor reference #</label>
                        <input type="text" name="vendor_reference" value="{{ old('vendor_reference') }}"
                               placeholder="e.g. RMA-12345 or credit note number…"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  placeholder="Any resolution notes…"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 rounded-b-xl">
                    <button type="button" onclick="document.getElementById('resolve-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                        Save Resolution
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</x-app-layout>
