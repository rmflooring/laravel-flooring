{{-- resources/views/pages/work-orders/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Work Order</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Sale: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $sale->sale_number }}</span>
                        @if($sale->customer_name) &mdash; {{ $sale->customer_name }} @endif
                    </p>
                </div>
                <a href="{{ route('pages.sales.show', $sale) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Cancel
                </a>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-gray-800">
                    <p class="mb-2 text-sm font-semibold text-red-800 dark:text-red-400">Please fix the following errors:</p>
                    <ul class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.sales.work-orders.store', $sale) }}"
                  x-data="woCreate()" @submit.prevent="submitForm">
                @csrf

                {{-- Installer --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Installer</h2>
                    </div>
                    <div class="p-6">
                        <label for="installer_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assign Installer <span class="text-red-500">*</span>
                        </label>
                        <select id="installer_id" name="installer_id" required
                                x-model="installerId"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            <option value="">— Select an installer —</option>
                            @foreach($installers as $installer)
                                <option value="{{ $installer->id }}"
                                        data-email="{{ $installer->email }}"
                                        {{ old('installer_id') == $installer->id ? 'selected' : '' }}>
                                    {{ $installer->company_name }}
                                    @if($installer->email) ({{ $installer->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Labour Items --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Labour Items</h2>
                            <button type="button" @click="toggleAll"
                                    class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                                Toggle All
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        @php $hasLabourItems = false; @endphp
                        @foreach ($rooms as $room)
                            @php $labourItems = $room->items->where('item_type', 'labour'); @endphp
                            @if ($labourItems->isEmpty()) @continue @endif
                            @php $hasLabourItems = true; @endphp

                            <div class="mb-5">
                                <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                    {{ $room->name }}
                                </h3>
                                <div class="space-y-3">
                                    @foreach ($labourItems as $item)
                                        @php
                                            $scheduled  = (float) ($scheduledQtys[$item->id] ?? 0);
                                            $remaining  = max(0, (float) $item->quantity - $scheduled);
                                            $fullyDone  = $remaining <= 0;
                                            $itemName   = implode(' — ', array_filter([$item->labour_type, $item->description])) ?: 'Labour Item';
                                        @endphp
                                        <div class="rounded-lg border {{ $fullyDone ? 'border-gray-200 bg-gray-50 opacity-60 dark:border-gray-700 dark:bg-gray-900' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-4"
                                             x-data="woItem({{ $item->id }}, {{ $remaining }}, {{ (float) $item->cost_price }})">

                                            <div class="flex items-start gap-3">
                                                <input type="checkbox" name="items[{{ $item->id }}]" value="1"
                                                       id="item_{{ $item->id }}"
                                                       {{ $fullyDone ? 'disabled' : '' }}
                                                       x-model="checked"
                                                       @change="onCheck"
                                                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">

                                                <div class="flex-1">
                                                    <label for="item_{{ $item->id }}"
                                                           class="block text-sm font-medium {{ $fullyDone ? 'text-gray-400' : 'text-gray-900 dark:text-white' }} {{ $fullyDone ? '' : 'cursor-pointer' }}">
                                                        {{ $itemName }}
                                                    </label>
                                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                        <span>{{ number_format((float)$item->quantity, 2) }} {{ $item->unit }}</span>
                                                        @if ($fullyDone)
                                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                                Fully scheduled
                                                            </span>
                                                        @elseif ($scheduled > 0)
                                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                                                {{ number_format($remaining, 2) }} {{ $item->unit }} remaining
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- Qty + Cost override (shown when checked) --}}
                                                    <div x-show="checked" x-cloak class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                        <div class="sm:col-span-2">
                                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                                                Qty <span class="text-gray-400">(max {{ number_format($remaining, 2) }} {{ $item->unit }})</span>
                                                            </label>
                                                            <input type="number" name="qty[{{ $item->id }}]"
                                                                   x-model="qty"
                                                                   @input="validateQty"
                                                                   step="0.01" min="0.01" max="{{ $remaining }}"
                                                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                   :class="qtyError ? 'border-red-400' : ''">
                                                            <p x-show="qtyError" x-text="qtyError" x-cloak class="mt-1 text-xs text-red-500"></p>
                                                        </div>
                                                        <div class="sm:col-span-2">
                                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unit Cost</label>
                                                            <input type="number" name="cost[{{ $item->id }}]"
                                                                   x-model="cost"
                                                                   step="0.01" min="0"
                                                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if (!$hasLabourItems)
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                No labour items found on this sale. Add labour items to the sale before creating a work order.
                            </p>
                        @endif

                        <p x-show="itemError" x-text="itemError" x-cloak class="mt-2 text-sm text-red-500"></p>
                    </div>
                </div>

                {{-- Scheduling --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Scheduling</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                        <div>
                            <label for="scheduled_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled Date</label>
                            <input type="date" id="scheduled_date" name="scheduled_date"
                                   value="{{ old('scheduled_date') }}"
                                   x-model="scheduledDate"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                        <div>
                            <label for="scheduled_time" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled Time</label>
                            <input type="time" id="scheduled_time" name="scheduled_time"
                                   value="{{ old('scheduled_time') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2">
                            <p x-show="installerId && scheduledDate" x-cloak class="text-xs text-blue-600 dark:text-blue-400">
                                A calendar event will be created on the RM – Installations calendar when this work order is saved.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                    </div>
                    <div class="p-6">
                        <textarea id="notes" name="notes" rows="3"
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500"
                                  placeholder="Special instructions or context...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="submit"
                            class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Create Work Order
                    </button>
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
    function woCreate() {
        return {
            installerId:   '{{ old('installer_id', '') }}',
            scheduledDate: '{{ old('scheduled_date', '') }}',
            itemError:     '',
            hasQtyErrors:  false,

            toggleAll() {
                const boxes = document.querySelectorAll('input[name^="items["]:not(:disabled)');
                const anyUnchecked = [...boxes].some(b => !b.checked);
                boxes.forEach(b => {
                    b.checked = anyUnchecked;
                    b.dispatchEvent(new Event('change'));
                });
            },

            submitForm() {
                const checked = document.querySelectorAll('input[name^="items["]:checked');
                if (!checked.length) {
                    this.itemError = 'Please select at least one labour item.';
                    return;
                }
                this.itemError = '';
                this.$el.submit();
            },
        };
    }

    function woItem(itemId, maxQty, defaultCost) {
        return {
            checked:  {{ old('items.' . '0', 'false') }},
            qty:      maxQty,
            cost:     defaultCost,
            qtyError: '',

            onCheck() {
                if (this.checked) this.qty = maxQty;
            },

            validateQty() {
                const v = parseFloat(this.qty);
                if (isNaN(v) || v <= 0) {
                    this.qtyError = 'Qty must be greater than 0.';
                } else if (v > maxQty) {
                    this.qtyError = `Max allowed is ${maxQty}.`;
                } else {
                    this.qtyError = '';
                }
            },
        };
    }
    </script>
</x-app-layout>
