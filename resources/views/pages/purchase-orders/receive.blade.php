{{-- resources/views/pages/purchase-orders/receive.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6">
                <nav class="flex items-center gap-1.5 text-sm text-gray-500 mb-2">
                    <a href="{{ route('pages.purchase-orders.show', $purchaseOrder) }}"
                       class="inline-flex items-center gap-1 hover:text-gray-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        PO {{ $purchaseOrder->po_number }}
                    </a>
                    @if ($purchaseOrder->sale)
                        <span class="text-gray-300">·</span>
                        <a href="{{ route('pages.sales.show', $purchaseOrder->sale) }}"
                           class="hover:text-gray-700">
                            Sale {{ $purchaseOrder->sale->sale_number }}
                        </a>
                    @endif
                </nav>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Receive items</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Confirm quantities received. Each item will be added to inventory and the PO status will be set to
                    <span class="font-medium text-green-700 dark:text-green-400">Received</span>.
                </p>
            </div>

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-gray-800">
                    <ul class="space-y-1 text-sm text-red-700 dark:text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.purchase-orders.receive', $purchaseOrder) }}"
                  x-data="receiveForm()">
                @csrf

                {{-- Received date --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Receipt details</h2>
                    </div>
                    <div class="p-6">
                        <label for="received_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Date received <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               id="received_date"
                               name="received_date"
                               value="{{ old('received_date', now()->toDateString()) }}"
                               max="{{ now()->toDateString() }}"
                               required
                               class="w-48 rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>

                {{-- Items --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items ordered</h2>
                        <button type="button" @click="setAllToOrdered()"
                                class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            Reset all to ordered qty
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Item</th>
                                    <th class="px-6 py-3 text-right whitespace-nowrap">Ordered qty</th>
                                    <th class="px-6 py-3 text-right whitespace-nowrap">Qty received</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($purchaseOrder->items as $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</div>
                                            @if ($item->po_notes)
                                                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $item->po_notes }}</div>
                                            @endif
                                            <div class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $item->unit }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $item->quantity }} {{ $item->unit }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <input type="number"
                                                   name="quantities[{{ $item->id }}]"
                                                   value="{{ old('quantities.' . $item->id, $item->quantity) }}"
                                                   min="0"
                                                   step="0.01"
                                                   data-ordered="{{ $item->quantity }}"
                                                   class="qty-input w-28 rounded-lg border-gray-300 text-sm text-right shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                   required>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Vendor / PO info reminder --}}
                <div class="mb-6 rounded-lg border border-gray-100 bg-gray-50 px-5 py-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400">
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $purchaseOrder->vendor->company_name }}</span>
                    <span class="mx-2 text-gray-300">·</span>
                    PO {{ $purchaseOrder->po_number }}
                    @if ($purchaseOrder->vendor_order_number)
                        <span class="mx-2 text-gray-300">·</span>
                        Vendor order #{{ $purchaseOrder->vendor_order_number }}
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between">
                    <a href="{{ route('pages.purchase-orders.show', $purchaseOrder) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-green-700 px-5 py-2 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Confirm receipt &amp; mark received
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        function receiveForm() {
            return {
                setAllToOrdered() {
                    document.querySelectorAll('.qty-input').forEach(input => {
                        input.value = input.dataset.ordered;
                    });
                },
            };
        }
    </script>
</x-app-layout>
