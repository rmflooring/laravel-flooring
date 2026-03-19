{{-- resources/views/pages/inventory/rtv/edit.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('pages.inventory.rtv.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">RTV</a>
                <span>/</span>
                <a href="{{ route('pages.inventory.rtv.show', $rtv) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $rtv->return_number }}</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">Edit</span>
            </nav>

            <form method="POST" action="{{ route('pages.inventory.rtv.update', $rtv) }}" id="rtv-edit-form">
                @csrf @method('PUT')

                {{-- Receipt info (read-only) --}}
                @php
                    $firstItem = $rtv->items->first();
                    $receipt   = $firstItem?->inventoryReceipt;
                    $vendor    = $receipt?->purchaseOrder?->vendor ?? $rtv->vendor;
                @endphp
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Source Inventory Record</h2>
                    <div class="bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600 rounded-lg px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        @if ($receipt)
                            <a href="{{ route('pages.inventory.show', $receipt) }}" class="text-teal-600 hover:underline dark:text-teal-400 font-medium">
                                Record #{{ $receipt->id }}
                            </a>
                            @if ($vendor)
                                — {{ $vendor->name }}
                            @endif
                            @if ($receipt->purchaseOrder)
                                (PO #{{ $receipt->purchaseOrder->po_number }})
                            @endif
                        @else
                            <span class="text-gray-400">No record linked</span>
                        @endif
                    </div>
                </div>

                {{-- Return details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mt-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Return Details</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason <span class="text-red-500">*</span></label>
                        <select name="reason"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— Select a reason —</option>
                            @foreach (\App\Models\InventoryReturn::REASON_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ (old('reason', $rtv->reason) === $value) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="2"
                                  placeholder="Any additional notes…"
                                  class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes', $rtv->notes) }}</textarea>
                    </div>
                </div>

                {{-- Items --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm mt-6">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items to Return</h2>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($rtv->items as $idx => $item)
                            <div class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $item->purchaseOrderItem?->item_name ?? '—' }}
                                        </p>
                                        <p class="text-xs text-gray-400">{{ $item->purchaseOrderItem?->unit }}</p>
                                    </div>
                                    <div class="w-36">
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Qty returning</label>
                                        <input type="number"
                                               name="items[{{ $idx }}][quantity_returned]"
                                               value="{{ old("items.{$idx}.quantity_returned", $item->quantity_returned) }}"
                                               required min="0.01" step="0.01"
                                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>
                                <input type="hidden" name="items[{{ $idx }}][purchase_order_item_id]" value="{{ $item->purchase_order_item_id }}">
                                <input type="hidden" name="items[{{ $idx }}][inventory_receipt_id]" value="{{ $item->inventory_receipt_id }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('pages.inventory.rtv.show', $rtv) }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-orange-600 px-5 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-300">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
