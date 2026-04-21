<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Record {{ $billType === 'vendor' ? 'Vendor' : 'Installer' }} Bill
                    </h1>
                    @if ($purchaseOrder)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Linked to PO #{{ $purchaseOrder->po_number }} · {{ $purchaseOrder->vendor?->company_name }}
                        </p>
                    @elseif ($workOrder)
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Linked to WO #{{ $workOrder->wo_number }} · {{ $workOrder->installer?->company_name }}
                        </p>
                    @endif
                </div>
                <a href="{{ route('admin.bills.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    Cancel
                </a>
            </div>

            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.bills.store') }}" x-data="billForm()">
                @csrf
                <input type="hidden" name="bill_type" value="{{ $billType }}">
                @if ($purchaseOrder)
                    <input type="hidden" name="purchase_order_id" value="{{ $purchaseOrder->id }}">
                @endif
                @if ($workOrder)
                    <input type="hidden" name="work_order_id" value="{{ $workOrder->id }}">
                @endif

                {{-- Bill Header --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Bill Details</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                        {{-- Payee --}}
                        @if ($billType === 'vendor')
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Vendor <span class="text-red-500">*</span></label>
                                <select name="vendor_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="">— Select vendor —</option>
                                    @foreach ($vendors as $vendor)
                                        <option value="{{ $vendor->id }}"
                                            @selected(old('vendor_id', $purchaseOrder?->vendor_id) == $vendor->id)>
                                            {{ $vendor->company_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vendor_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @else
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Installer <span class="text-red-500">*</span></label>
                                <select name="installer_id"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" required>
                                    <option value="">— Select installer —</option>
                                    @foreach ($installers as $installer)
                                        <option value="{{ $installer->id }}"
                                            @selected(old('installer_id', $workOrder?->installer_id) == $installer->id)>
                                            {{ $installer->company_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('installer_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endif

                        {{-- Invoice Number --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Invoice # (from vendor) <span class="text-red-500">*</span></label>
                            <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                                placeholder="e.g. INV-2026-00042"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                required>
                            @error('reference_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Bill Date --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Bill Date <span class="text-red-500">*</span></label>
                            <input type="date" name="bill_date" id="bill_date"
                                value="{{ old('bill_date', now()->toDateString()) }}"
                                x-model="billDate"
                                @change="updateDueDateFromTerm"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                required>
                            @error('bill_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Payment Term --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Payment Term</label>
                            <select name="payment_term_id" id="payment_term_id"
                                x-model="termId"
                                @change="updateDueDateFromTerm"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">— None —</option>
                                @foreach ($paymentTerms as $term)
                                    <option value="{{ $term->id }}" data-days="{{ $term->days }}"
                                        @selected(old('payment_term_id') == $term->id)>
                                        {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Due Date --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                            <input type="date" name="due_date" id="due_date"
                                value="{{ old('due_date') }}"
                                x-model="dueDate"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('due_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tax Group --}}
                        <div class="sm:col-span-2">
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Tax Group</label>
                            <select id="tax_group_select" x-model="selectedTaxGroup" @change="applyTaxGroup"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">— Select tax group —</option>
                                @foreach ($taxGroups as $tg)
                                    <option value="{{ $tg->id }}"
                                        data-gst="{{ $tg->gst_rate }}"
                                        data-pst="{{ $tg->pst_rate }}">
                                        {{ $tg->name }}{{ $tg->description ? ' — '.$tg->description : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- GST Rate --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">GST Rate (%)</label>
                            <input type="number" name="gst_rate" step="0.001" min="0" max="100"
                                value="{{ old('gst_rate', '5') }}"
                                x-model.number="gstRate"
                                @input="selectedTaxGroup = ''; recalculate()"
                                placeholder="5"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        {{-- PST Rate --}}
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">PST Rate (%)</label>
                            <input type="number" name="pst_rate" step="0.001" min="0" max="100"
                                value="{{ old('pst_rate', '0') }}"
                                x-model.number="pstRate"
                                @input="selectedTaxGroup = ''; recalculate()"
                                placeholder="0"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                        <textarea name="notes" rows="2"
                            placeholder="Internal notes..."
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm mt-4">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Line Items</h2>
                        <button type="button" @click="addRow"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-700 hover:text-blue-800 dark:text-blue-400">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add Row
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3 w-24">Qty</th>
                                    <th class="px-4 py-3 w-20">Unit</th>
                                    <th class="px-4 py-3 w-32">Unit Cost</th>
                                    <th class="px-4 py-3 w-32 text-right">Line Total</th>
                                    <th class="px-4 py-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, index) in rows" :key="row.key">
                                    <tr class="border-t border-gray-100 dark:border-gray-700">
                                        <td class="px-4 py-2">
                                            <input type="hidden" :name="`items[${index}][purchase_order_item_id]`" :value="row.purchase_order_item_id">
                                            <input type="hidden" :name="`items[${index}][work_order_item_id]`" :value="row.work_order_item_id">
                                            <input type="text" :name="`items[${index}][item_name]`" x-model="row.item_name"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                placeholder="Item description" required>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" :name="`items[${index}][quantity]`" x-model.number="row.quantity"
                                                @input="recalcRow(row); recalculate()"
                                                step="0.01" min="0"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                required>
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="text" :name="`items[${index}][unit]`" x-model="row.unit"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                placeholder="SF, EA...">
                                        </td>
                                        <td class="px-4 py-2">
                                            <input type="number" :name="`items[${index}][unit_cost]`" x-model.number="row.unit_cost"
                                                @input="recalcRow(row); recalculate()"
                                                step="0.01" min="0"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                required>
                                        </td>
                                        <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-white">
                                            $<span x-text="row.line_total.toFixed(2)"></span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" @click="removeRow(index)"
                                                class="text-red-500 hover:text-red-700 dark:text-red-400"
                                                x-show="rows.length > 1">
                                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals --}}
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">
                        <input type="hidden" name="tax_manual" :value="taxManual ? '1' : '0'">
                        <input type="hidden" name="gst_amount_override" x-show="taxManual" :value="gstAmountOverride">
                        <input type="hidden" name="pst_amount_override" x-show="taxManual" :value="pstAmountOverride">

                        <div class="flex flex-col items-end gap-1 text-sm text-gray-700 dark:text-gray-300">
                            <div class="flex justify-between w-72">
                                <span>Subtotal</span>
                                <span class="font-medium">$<span x-text="subtotal.toFixed(2)">0.00</span></span>
                            </div>

                            {{-- GST row --}}
                            <div class="flex items-center justify-between w-72" x-show="gstRate > 0 || taxManual">
                                <span x-text="taxManual ? 'GST' : `GST (${gstRate}%)`">GST</span>
                                <div x-show="!taxManual" class="font-medium">$<span x-text="gstAmount.toFixed(2)">0.00</span></div>
                                <div x-show="taxManual" class="flex items-center gap-1">
                                    <span class="text-gray-500">$</span>
                                    <input type="number" step="0.01" min="0"
                                        x-model.number="gstAmountOverride"
                                        @input="recalculate()"
                                        class="w-24 bg-white border border-blue-300 text-gray-900 text-sm rounded p-1 text-right dark:bg-gray-700 dark:border-blue-500 dark:text-white">
                                </div>
                            </div>

                            {{-- PST row --}}
                            <div class="flex items-center justify-between w-72" x-show="pstRate > 0 || taxManual">
                                <span x-text="taxManual ? 'PST' : `PST (${pstRate}%)`">PST</span>
                                <div x-show="!taxManual" class="font-medium">$<span x-text="pstAmount.toFixed(2)">0.00</span></div>
                                <div x-show="taxManual" class="flex items-center gap-1">
                                    <span class="text-gray-500">$</span>
                                    <input type="number" step="0.01" min="0"
                                        x-model.number="pstAmountOverride"
                                        @input="recalculate()"
                                        class="w-24 bg-white border border-blue-300 text-gray-900 text-sm rounded p-1 text-right dark:bg-gray-700 dark:border-blue-500 dark:text-white">
                                </div>
                            </div>

                            <div class="flex justify-between w-72 font-bold text-base text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-600 pt-2 mt-1">
                                <span>Total</span>
                                <span>$<span x-text="grandTotal.toFixed(2)">0.00</span></span>
                            </div>

                            {{-- Override toggle --}}
                            <div class="flex items-center gap-2 mt-2 w-72 justify-end">
                                <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 cursor-pointer select-none">
                                    <input type="checkbox" x-model="taxManual" @change="onTaxManualToggle()"
                                        class="w-3.5 h-3.5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                    Override tax amounts manually
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3 mt-4">
                    <a href="{{ route('admin.bills.index') }}"
                       class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Save Bill
                    </button>
                </div>
            </form>

        </div>
    </div>

@php
    // Pre-fill rows from PO or WO items
    $initialRows = [];
    if ($purchaseOrder) {
        foreach ($purchaseOrder->items as $i => $item) {
            $initialRows[] = [
                'key' => $i,
                'purchase_order_item_id' => $item->id,
                'work_order_item_id' => null,
                'item_name' => $item->item_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit ?? '',
                'unit_cost' => (float) $item->cost_price,
                'line_total' => (float) $item->cost_total,
            ];
        }
    } elseif ($workOrder) {
        foreach ($workOrder->items as $i => $item) {
            $initialRows[] = [
                'key' => $i,
                'purchase_order_item_id' => null,
                'work_order_item_id' => $item->id,
                'item_name' => $item->item_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit ?? '',
                'unit_cost' => (float) $item->cost_price,
                'line_total' => (float) $item->cost_total,
            ];
        }
    }

    if (empty($initialRows)) {
        $initialRows = [[
            'key' => 0,
            'purchase_order_item_id' => null,
            'work_order_item_id' => null,
            'item_name' => '',
            'quantity' => 1,
            'unit' => '',
            'unit_cost' => 0,
            'line_total' => 0,
        ]];
    }
@endphp

<script>
function billForm() {
    const termDays = {};
    document.querySelectorAll('#payment_term_id option[data-days]').forEach(opt => {
        termDays[opt.value] = parseInt(opt.dataset.days) || null;
    });

    const initialRows = @json($initialRows);

    return {
        rows: initialRows.map(r => ({ ...r })),
        rowKey: initialRows.length,
        billDate: document.getElementById('bill_date').value,
        termId: '',
        dueDate: '',
        selectedTaxGroup: '',
        gstRate: 5,
        pstRate: 0,
        taxManual: false,
        gstAmountOverride: 0,
        pstAmountOverride: 0,
        subtotal: 0,
        gstAmount: 0,
        pstAmount: 0,
        grandTotal: 0,

        init() {
            this.recalculate();
        },

        applyTaxGroup() {
            const opt = document.querySelector(`#tax_group_select option[value="${this.selectedTaxGroup}"]`);
            if (opt && this.selectedTaxGroup) {
                this.gstRate = parseFloat(opt.dataset.gst) || 0;
                this.pstRate = parseFloat(opt.dataset.pst) || 0;
            }
            this.recalculate();
        },

        onTaxManualToggle() {
            if (this.taxManual) {
                // Pre-fill overrides with currently calculated amounts
                this.gstAmountOverride = this.gstAmount;
                this.pstAmountOverride = this.pstAmount;
            }
            this.recalculate();
        },

        addRow() {
            this.rows.push({
                key: this.rowKey++,
                purchase_order_item_id: null,
                work_order_item_id: null,
                item_name: '',
                quantity: 1,
                unit: '',
                unit_cost: 0,
                line_total: 0,
            });
        },

        removeRow(index) {
            if (this.rows.length > 1) {
                this.rows.splice(index, 1);
                this.recalculate();
            }
        },

        recalcRow(row) {
            row.line_total = Math.round(row.quantity * row.unit_cost * 100) / 100;
        },

        recalculate() {
            this.subtotal = this.rows.reduce((s, r) => s + (r.line_total || 0), 0);
            if (this.taxManual) {
                this.gstAmount = Math.round((parseFloat(this.gstAmountOverride) || 0) * 100) / 100;
                this.pstAmount = Math.round((parseFloat(this.pstAmountOverride) || 0) * 100) / 100;
            } else {
                this.gstAmount = Math.round(this.subtotal * (this.gstRate / 100) * 100) / 100;
                this.pstAmount = Math.round(this.subtotal * (this.pstRate / 100) * 100) / 100;
            }
            this.grandTotal = this.subtotal + this.gstAmount + this.pstAmount;
        },

        updateDueDateFromTerm() {
            const days = termDays[this.termId];
            if (days !== null && days !== undefined && this.billDate) {
                const d = new Date(this.billDate);
                d.setDate(d.getDate() + days);
                this.dueDate = d.toISOString().slice(0, 10);
            }
        },

    };
}
</script>
</x-app-layout>
