{{-- resources/views/pages/work-orders/edit.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Work Order</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $workOrder->wo_number }}</span>
                        <span class="text-gray-400">•</span>
                        <span>Sale: {{ $sale->sale_number }}</span>
                        @if($sale->customer_name)
                            <span class="text-gray-400">•</span>
                            <span>{{ $sale->customer_name }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.sales.work-orders.show', [$sale, $workOrder]) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                    <a href="{{ route('pages.sales.work-orders.pdf', [$sale, $workOrder]) }}" target="_blank"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Print PDF
                    </a>
                </div>
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

            <form method="POST" action="{{ route('pages.sales.work-orders.update', [$sale, $workOrder]) }}"
                  x-data="woEdit()">
                @csrf
                @method('PUT')

                {{-- Installer --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Installer</h2>
                    </div>
                    <div class="p-6">
                        <label for="installer_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Installer <span class="text-red-500">*</span>
                        </label>
                        <select id="installer_id" name="installer_id" required
                                x-model="installerId"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            <option value="">— Select an installer —</option>
                            @foreach($installers as $installer)
                                <option value="{{ $installer->id }}"
                                        {{ old('installer_id', $workOrder->installer_id) == $installer->id ? 'selected' : '' }}>
                                    {{ $installer->company_name }}
                                    @if($installer->email) ({{ $installer->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Status --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Status</h2>
                    </div>
                    <div class="p-6">
                        <select id="status" name="status" required
                                class="block w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            @foreach (\App\Models\WorkOrder::STATUS_LABELS as $val => $label)
                                <option value="{{ $val }}" {{ old('status', $workOrder->status) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Labour Items --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Labour Items</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Adjust quantities, costs, and notes. Items can be removed — removed items free up their qty for other work orders.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Unit Cost</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @php $grandTotal = 0; @endphp
                                @foreach ($workOrder->items as $item)
                                    @php
                                        $max = $maxQtys[$item->id] ?? 9999;
                                        $grandTotal += (float) $item->cost_total;
                                    @endphp
                                    <tr x-data="woRow({{ $item->id }}, {{ (float)$item->quantity }}, {{ (float)$item->cost_price }})"
                                        :class="pendingDelete ? 'opacity-40' : ''">
                                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                            <div class="font-medium" :class="pendingDelete ? 'line-through text-gray-400' : ''">{{ $item->item_name }}</div>
                                            <div class="text-xs text-gray-400">{{ $item->unit }}</div>
                                            <template x-if="!pendingDelete">
                                                <div>
                                                    <textarea name="wo_items[{{ $item->id }}][wo_notes]"
                                                              rows="2"
                                                              placeholder="WO notes..."
                                                              class="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ old('wo_items.' . $item->id . '.wo_notes', $item->wo_notes) }}</textarea>
                                                    {{-- Related Materials --}}
                                                    @php
                                                        $roomMats = $item->saleItem?->room?->items ?? collect();
                                                        $linkedIds = $item->relatedMaterials->pluck('sale_item_id')->toArray();
                                                    @endphp
                                                    @if($roomMats->isNotEmpty())
                                                    <div class="mt-2">
                                                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Related Materials</p>
                                                        <div class="space-y-1">
                                                            @foreach($roomMats as $mat)
                                                                @php
                                                                    $matName = implode(' — ', array_filter([$mat->product_type, $mat->manufacturer, $mat->style, $mat->color_item_number])) ?: 'Material';
                                                                @endphp
                                                                <label class="flex items-center gap-2 rounded border border-gray-200 bg-gray-50 px-2 py-1.5 cursor-pointer hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700">
                                                                    <input type="checkbox"
                                                                           name="wo_materials[{{ $item->id }}][]"
                                                                           value="{{ $mat->id }}"
                                                                           {{ in_array($mat->id, old('wo_materials.' . $item->id, $linkedIds)) ? 'checked' : '' }}
                                                                           class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                    <span class="text-xs text-gray-700 dark:text-gray-300">
                                                                        {{ $matName }}
                                                                        <span class="text-gray-400 ml-1">{{ number_format((float)$mat->quantity, 2) }} {{ $mat->unit }}</span>
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </template>
                                            <template x-if="pendingDelete">
                                                <input type="hidden" name="wo_items[{{ $item->id }}][delete]" value="1">
                                            </template>
                                        </td>
                                        <td class="px-6 py-4">
                                            <template x-if="!pendingDelete">
                                                <div>
                                                    <input type="number" name="wo_items[{{ $item->id }}][quantity]"
                                                           x-model="qty"
                                                           @input="validateQty({{ $max }})"
                                                           step="0.01" min="0.01"
                                                           class="w-28 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                           :class="qtyError ? 'border-red-400' : ''">
                                                    <div class="mt-1 text-xs text-gray-400">max {{ number_format($max, 2) }}</div>
                                                    <p x-show="qtyError" x-text="qtyError" x-cloak class="mt-1 text-xs text-red-500"></p>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="px-6 py-4">
                                            <template x-if="!pendingDelete">
                                                <input type="number" name="wo_items[{{ $item->id }}][cost_price]"
                                                       x-model="cost"
                                                       step="0.01" min="0"
                                                       class="w-28 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            </template>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white"
                                            x-show="!pendingDelete"
                                            x-text="'$' + (parseFloat(qty||0) * parseFloat(cost||0)).toFixed(2)">
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button type="button"
                                                    x-show="!pendingDelete"
                                                    @click="pendingDelete = true"
                                                    title="Remove this item"
                                                    class="inline-flex items-center rounded-lg border border-red-300 bg-white px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700">
                                                Remove
                                            </button>
                                            <button type="button"
                                                    x-show="pendingDelete"
                                                    x-cloak
                                                    @click="pendingDelete = false"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                                Undo
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <td colspan="4" class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">Grand Total</td>
                                    <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">
                                        ${{ number_format($grandTotal, 2) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                                   value="{{ old('scheduled_date', $workOrder->scheduled_date?->format('Y-m-d')) }}"
                                   x-model="scheduledDate"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                        <div>
                            <label for="scheduled_time" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled Time</label>
                            <input type="time" id="scheduled_time" name="scheduled_time"
                                   value="{{ old('scheduled_time', $workOrder->scheduled_time) }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2 flex items-start gap-3">
                            <input type="checkbox" id="sync_calendar" name="sync_calendar" value="1"
                                   x-model="syncCalendar"
                                   {{ old('sync_calendar', '1') !== '0' ? 'checked' : '' }}
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="sync_calendar" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                @if($workOrder->calendar_synced)
                                    Sync to RM – Installations calendar
                                    <p x-show="syncCalendar" x-cloak class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">
                                        The calendar event will be updated on save.
                                    </p>
                                    <p x-show="!syncCalendar" x-cloak class="mt-0.5 text-xs text-amber-600 dark:text-amber-400">
                                        The existing calendar event will be removed on save.
                                    </p>
                                @else
                                    Add to RM – Installations calendar
                                    <p x-show="syncCalendar && installerId && scheduledDate" x-cloak class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">
                                        A calendar event will be created when saved.
                                    </p>
                                @endif
                            </label>
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
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">{{ old('notes', $workOrder->notes) }}</textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="submit"
                            class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Save Changes
                    </button>
                    <a href="{{ route('pages.sales.work-orders.show', [$sale, $workOrder]) }}"
                       class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
    function woEdit() {
        return {
            installerId:   '{{ old('installer_id', $workOrder->installer_id ?? '') }}',
            scheduledDate: '{{ old('scheduled_date', $workOrder->scheduled_date?->format('Y-m-d') ?? '') }}',
            syncCalendar:  {{ old('sync_calendar', '1') }} !== '0',
        };
    }

    function woRow(id, qty, cost) {
        return {
            qty:           qty,
            cost:          cost,
            qtyError:      '',
            pendingDelete: false,
            validateQty(max) {
                const v = parseFloat(this.qty);
                if (isNaN(v) || v <= 0) this.qtyError = 'Must be > 0';
                else if (v > max)        this.qtyError = `Max is ${max}`;
                else                     this.qtyError = '';
            },
        };
    }
    </script>
</x-app-layout>
