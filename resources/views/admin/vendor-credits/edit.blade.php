<x-app-layout>
    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('admin.vendor-credits.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Vendor Credits</a>
                <span>/</span>
                <a href="{{ route('admin.vendor-credits.show', $vendorCredit) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $vendorCredit->credit_memo_number }}</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">Edit</span>
            </nav>

            <form method="POST" action="{{ route('admin.vendor-credits.update', $vendorCredit) }}">
                @csrf @method('PUT')

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm divide-y divide-gray-100 dark:divide-gray-700">

                    {{-- Header --}}
                    <div class="px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Edit {{ $vendorCredit->credit_memo_number }}</h2>
                    </div>

                    {{-- Fields --}}
                    <div class="px-6 py-5 space-y-5">

                        {{-- Vendor --}}
                        <div>
                            <label for="vendor_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Vendor <span class="text-red-500">*</span>
                            </label>
                            <select name="vendor_id" id="vendor_id" required
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @foreach ($vendors as $v)
                                    <option value="{{ $v->id }}" {{ old('vendor_id', $vendorCredit->vendor_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vendor_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            {{-- Date --}}
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Credit Memo Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="date" id="date" required
                                       value="{{ old('date', $vendorCredit->date->toDateString()) }}"
                                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Vendor reference --}}
                            <div>
                                <label for="reference_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Vendor Credit Memo # <span class="text-gray-400 font-normal">(optional)</span>
                                </label>
                                <input type="text" name="reference_number" id="reference_number"
                                       value="{{ old('reference_number', $vendorCredit->reference_number) }}"
                                       placeholder="e.g. CM-12345"
                                       class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        {{-- Subtotal --}}
                        <div>
                            <label for="subtotal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Subtotal (pre-tax credit amount) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                <input type="number" name="subtotal" id="subtotal" required
                                       min="0" step="0.01"
                                       value="{{ old('subtotal', number_format((float)$vendorCredit->subtotal, 2, '.', '')) }}"
                                       class="w-full pl-7 rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                       oninput="recalcTax()">
                            </div>
                        </div>

                        {{-- Tax section --}}
                        <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tax</h3>
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                                    <input type="checkbox" name="tax_manual" id="tax_manual" value="1"
                                           {{ old('tax_manual', $vendorCredit->tax_manual) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                           onchange="toggleTaxManual()">
                                    Override amounts manually
                                </label>
                            </div>

                            <div id="tax-group-row" class="{{ old('tax_manual', $vendorCredit->tax_manual) ? 'hidden' : '' }}">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Quick-fill from tax group</label>
                                <select id="tax_group_picker"
                                        class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        onchange="fillTaxGroup(this)">
                                    <option value="">— Select tax group —</option>
                                    @foreach ($taxGroups as $tg)
                                        <option value="{{ $tg->id }}"
                                                data-gst="{{ $tg->gst_rate }}"
                                                data-pst="{{ $tg->pst_rate }}">
                                            {{ $tg->name }}
                                            (GST {{ number_format($tg->gst_rate, 2) }}%
                                            / PST {{ number_format($tg->pst_rate, 2) }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">GST Rate %</label>
                                    <input type="number" name="gst_rate" id="gst_rate"
                                           min="0" max="100" step="0.001"
                                           value="{{ old('gst_rate', number_format((float)$vendorCredit->gst_rate * 100, 3, '.', '')) }}"
                                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           oninput="recalcTax()">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">PST Rate %</label>
                                    <input type="number" name="pst_rate" id="pst_rate"
                                           min="0" max="100" step="0.001"
                                           value="{{ old('pst_rate', number_format((float)$vendorCredit->pst_rate * 100, 3, '.', '')) }}"
                                           class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                           oninput="recalcTax()">
                                </div>
                            </div>

                            <div id="manual-amounts-row" class="{{ old('tax_manual', $vendorCredit->tax_manual) ? '' : 'hidden' }}">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">GST Amount $</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                            <input type="number" name="gst_amount_override" id="gst_amount_override"
                                                   min="0" step="0.01"
                                                   value="{{ old('gst_amount_override', number_format((float)$vendorCredit->gst_amount, 2, '.', '')) }}"
                                                   class="w-full pl-7 rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                   oninput="recalcTax()">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 mb-1">PST Amount $</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                            <input type="number" name="pst_amount_override" id="pst_amount_override"
                                                   min="0" step="0.01"
                                                   value="{{ old('pst_amount_override', number_format((float)$vendorCredit->pst_amount, 2, '.', '')) }}"
                                                   class="w-full pl-7 rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                   oninput="recalcTax()">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-md bg-gray-50 dark:bg-gray-700/30 p-3 text-sm space-y-1">
                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>Subtotal</span><span id="display-subtotal">$0.00</span>
                                </div>
                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>GST</span><span id="display-gst">$0.00</span>
                                </div>
                                <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>PST</span><span id="display-pst">$0.00</span>
                                </div>
                                <div class="flex justify-between font-semibold text-green-700 dark:text-green-400 border-t border-gray-200 dark:border-gray-600 pt-1 mt-1">
                                    <span>Total Credit</span><span id="display-total">$0.00</span>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes', $vendorCredit->notes) }}</textarea>
                        </div>

                        {{-- RTV link (read-only) --}}
                        @if ($vendorCredit->inventoryReturn)
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Linked to RTV
                                <a href="{{ route('pages.inventory.rtv.show', $vendorCredit->inventoryReturn) }}"
                                   class="text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $vendorCredit->inventoryReturn->return_number }}
                                </a>
                                — this link cannot be changed.
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-between gap-3 px-6 py-4 bg-gray-50 dark:bg-gray-700/30 rounded-b-lg">
                        <a href="{{ route('admin.vendor-credits.show', $vendorCredit) }}"
                           class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:ring-green-300">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function fmt(n) { return '$' + parseFloat(n || 0).toFixed(2); }

    function recalcTax() {
        const subtotal = parseFloat(document.getElementById('subtotal').value) || 0;
        const manual   = document.getElementById('tax_manual').checked;
        let gst, pst;

        if (manual) {
            gst = parseFloat(document.getElementById('gst_amount_override').value) || 0;
            pst = parseFloat(document.getElementById('pst_amount_override').value) || 0;
        } else {
            const gstRate = (parseFloat(document.getElementById('gst_rate').value) || 0) / 100;
            const pstRate = (parseFloat(document.getElementById('pst_rate').value) || 0) / 100;
            gst = Math.round(subtotal * gstRate * 100) / 100;
            pst = Math.round(subtotal * pstRate * 100) / 100;
        }

        document.getElementById('display-subtotal').textContent = fmt(subtotal);
        document.getElementById('display-gst').textContent      = fmt(gst);
        document.getElementById('display-pst').textContent      = fmt(pst);
        document.getElementById('display-total').textContent    = fmt(subtotal + gst + pst);
    }

    function toggleTaxManual() {
        const manual = document.getElementById('tax_manual').checked;
        document.getElementById('tax-group-row').classList.toggle('hidden', manual);
        document.getElementById('manual-amounts-row').classList.toggle('hidden', !manual);
        recalcTax();
    }

    function fillTaxGroup(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt.value) return;
        document.getElementById('gst_rate').value = opt.dataset.gst;
        document.getElementById('pst_rate').value = opt.dataset.pst;
        recalcTax();
    }

    recalcTax();
    </script>
</x-app-layout>
