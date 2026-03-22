{{-- resources/views/mobile/purchase-orders/receive.blade.php --}}
<x-mobile-layout :title="'Receive ' . $purchaseOrder->po_number">

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
            <ul class="space-y-1 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- PO Identity --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-4 py-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Receiving Inventory</p>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $purchaseOrder->po_number }}</h1>
        <p class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $purchaseOrder->vendor->company_name }}</p>
        @if ($purchaseOrder->vendor_order_number)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Vendor order #{{ $purchaseOrder->vendor_order_number }}</p>
        @endif
        @if ($purchaseOrder->sale)
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Sale #{{ $purchaseOrder->sale->sale_number }}</p>
        @endif
    </div>

    <form method="POST" action="{{ route('pages.purchase-orders.receive', $purchaseOrder) }}"
          x-data="mobileReceiveForm()">
        @csrf

        {{-- Date received --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-4 py-4">
            <label for="received_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                Date received
            </label>
            <input type="date"
                   id="received_date"
                   name="received_date"
                   value="{{ old('received_date', now()->toDateString()) }}"
                   max="{{ now()->toDateString() }}"
                   required
                   class="block w-full rounded-xl border-gray-300 text-base shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white py-3 px-4">
        </div>

        {{-- Item cards --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Items ({{ $purchaseOrder->items->count() }})
                </p>
                <button type="button" @click="resetAll()"
                        class="text-xs font-medium text-blue-600 dark:text-blue-400 underline underline-offset-2">
                    Reset all to ordered qty
                </button>
            </div>

            @foreach ($purchaseOrder->items as $item)
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 px-4 py-4">

                    {{-- Item name + notes --}}
                    <p class="text-base font-bold text-gray-900 dark:text-white leading-tight">{{ $item->item_name }}</p>
                    @if ($item->po_notes)
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $item->po_notes }}</p>
                    @endif

                    {{-- Ordered qty badge --}}
                    <div class="mt-3 flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                            Ordered: {{ $item->quantity }} {{ $item->unit }}
                        </span>
                    </div>

                    {{-- Large qty input --}}
                    <div class="mt-4">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">
                            Qty received
                        </label>
                        <input type="number"
                               name="quantities[{{ $item->id }}]"
                               value="{{ old('quantities.' . $item->id, $item->quantity) }}"
                               min="0"
                               step="0.01"
                               data-ordered="{{ $item->quantity }}"
                               class="qty-input block w-full rounded-xl border-2 border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-3xl font-bold text-gray-900 text-center py-4 focus:border-green-500 focus:ring-green-500"
                               inputmode="decimal"
                               required>
                        @if ($item->unit)
                            <p class="mt-1 text-center text-xs text-gray-400 dark:text-gray-500">{{ $item->unit }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Submit --}}
        <div class="space-y-3 pt-2 pb-8">
            <button type="submit"
                    class="flex w-full items-center justify-center gap-3 rounded-xl bg-green-600 py-5 text-lg font-bold text-white shadow-lg active:scale-95 transition-transform hover:bg-green-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Confirm Receipt
            </button>

            <a href="{{ route('mobile.purchase-orders.show', $purchaseOrder) }}"
               class="flex w-full items-center justify-center rounded-xl border-2 border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800 py-4 text-base font-semibold text-gray-700 dark:text-gray-200 active:scale-95 transition-transform">
                Cancel
            </a>
        </div>

    </form>

    <script>
        function mobileReceiveForm() {
            return {
                resetAll() {
                    document.querySelectorAll('.qty-input').forEach(input => {
                        input.value = input.dataset.ordered;
                    });
                },
            };
        }
    </script>

</x-mobile-layout>
