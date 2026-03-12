{{-- resources/views/components/modals/profits-modal.blade.php --}}
@php
    use App\Models\Sale;
    use App\Models\Estimate;

    $record = null;

    if ($recordId) {
        if ($context === 'sale') {
            $record = Sale::with(['rooms.items'])->find($recordId);
        } else {
            $record = Estimate::with(['rooms.items'])->find($recordId);
        }
    }

    // Flatten to a single list of items, but keep room metadata with each row
$rows = collect();

if ($record) {
    $rows = $record->rooms->flatMap(function ($room) {
        return $room->items->map(function ($item) use ($room) {
            return [
                'item' => $item,
                'room_id' => $room->id,
                'room_name' => $room->room_name ?: ('Room ' . $room->id),
            ];
        });
    })->values();
}
@endphp
@props([
    // required: 'sale' or 'estimate'
    'context' => 'sale',

    // required: id of the parent record (sale_id or estimate_id)
    'recordId' => null,

    // optional: allow passing arrays if you already have items loaded server-side later
    'items' => [],
])

@php
    $modalId = "profits-modal-{$context}-{$recordId}";
@endphp

<div id="{{ $modalId }}" tabindex="-1" aria-hidden="true"
     class="hidden fixed inset-0 z-50 w-full overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
    <div class="relative w-full max-w-6xl">
        <div class="relative rounded-lg bg-white shadow dark:bg-gray-800">
            {{-- Header --}}
            <div class="flex items-start justify-between rounded-t border-b border-gray-200 p-4 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                      Quick Profit Editor
                  </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Enter costs per line item. Totals update live.
                    </p>
                </div>

                <button type="button"
                        class="inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white"
                        data-modal-hide="{{ $modalId }}">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path fill-rule="evenodd"
                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                              clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200 px-4 pt-4 dark:border-gray-700">
                <ul class="-mb-px flex flex-wrap text-sm font-medium" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button class="inline-block rounded-t-lg border-b-2 border-blue-600 px-4 py-2 text-blue-600 dark:text-blue-500"
                                type="button"
                                role="tab"
                                data-tabs-target="#{{ $modalId }}-tab-projected"
                                aria-controls="{{ $modalId }}-tab-projected"
                                aria-selected="true">
                            Projected (Live)
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button class="inline-block rounded-t-lg border-b-2 border-transparent px-4 py-2 text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200"
                                type="button"
                                role="tab"
                                data-tabs-target="#{{ $modalId }}-tab-locked"
                                aria-controls="{{ $modalId }}-tab-locked"
                                aria-selected="false">
                            Locked (Verified)
                        </button>
                    </li>
                </ul>
            </div>

            {{-- Body --}}
            <div class="p-4">
                {{-- Projected --}}
                <div id="{{ $modalId }}-tab-projected" role="tabpanel" class="block">
                    {{-- Line items --}}
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/30">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Type</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Description</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Qty</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 dark:text-gray-200">Unit</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Sell $</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Sell Total</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Cost $</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Cost Total</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Profit</th>
                                <th class="px-3 py-2 text-right font-semibold text-gray-700 dark:text-gray-200">Margin</th>
                            </tr>
                            </thead>

                            <tbody id="{{ $modalId }}-items" class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
						  @forelse($rows as $row)
								@php
									$item = $row['item'];
									$roomId = $row['room_id'];
									$roomName = $row['room_name'];
								@endphp
							@php
							  $qty      = (float)($item->quantity ?? 0);
							  $sell     = (float)($item->sell_price ?? $item->unit_price ?? 0);
							  $sellTot  = (float)($item->line_total ?? ($qty * $sell));

							  $cost     = (float)($item->cost_price ?? 0);
							  $costTot  = $qty * $cost;

							  $profit   = $sellTot - $costTot;
							  $margin   = $sellTot > 0 ? ($profit / $sellTot) * 100 : 0;

							  $type     = $item->item_type ?? $item->product_type ?? '—';

							  // best-effort description for each item type
							  $desc = $item->item_description
								  ?? $item->freight_description
								  ?? $item->description
								  ?? $item->product_style
								  ?? $item->color_item_number
								  ?? '—';

							  $unit = $item->unit ?? '—';
							@endphp

							<tr
  data-profit-row
  data-item-type="{{ strtolower($type) }}"
  data-room-id="{{ $roomId }}"
  data-room-name="{{ $roomName }}"
>
							  <td class="px-3 py-2">{{ ucfirst($type) }}</td>
							  <td class="px-3 py-2">{{ $desc }}</td>
							  <td class="px-3 py-2 text-right">{{ number_format($qty, 2) }}</td>
							  <td class="px-3 py-2">{{ $unit }}</td>
							  <td class="px-3 py-2 text-right">${{ number_format($sell, 2) }}</td>

							  <td class="px-3 py-2 text-right" data-sell-total>${{ number_format($sellTot, 2) }}</td>

							  <td class="px-3 py-2 text-right">
								<input
								  type="number" step="0.01"
								  class="w-24 rounded-lg border border-gray-300 bg-gray-50 p-1.5 text-right"
								  value="{{ number_format($cost, 2, '.', '') }}"
								  data-cost-input
								  data-item-id="{{ $item->id }}"
								/>
							  </td>

							  <td class="px-3 py-2 text-right" data-cost-total>${{ number_format($costTot, 2) }}</td>
							  <td class="px-3 py-2 text-right" data-profit>${{ number_format($profit, 2) }}</td>
							  <td class="px-3 py-2 text-right" data-margin>{{ number_format($margin, 1) }}%</td>
							</tr>
						  @empty
							<tr>
							  <td colspan="10" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
								No items found for this {{ $context }}.
							  </td>
							</tr>
						  @endforelse
						</tbody>
                        </table>
                    </div>					
					
                    {{-- Group summaries --}}
                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Grouped Total Profits</h4>

                            <div class="space-y-2 text-sm">

    <div class="flex items-center justify-between">
        <span class="text-gray-600 dark:text-gray-300">Materials</span>
        <div class="flex items-center gap-6">
            <span class="font-medium text-gray-900 dark:text-white" id="{{ $modalId }}-sum-materials">$0.00</span>
            <span class="text-gray-500 dark:text-gray-300" id="{{ $modalId }}-margin-materials">0.0%</span>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <span class="text-gray-600 dark:text-gray-300">Labour</span>
        <div class="flex items-center gap-6">
            <span class="font-medium text-gray-900 dark:text-white" id="{{ $modalId }}-sum-labour">$0.00</span>
            <span class="text-gray-500 dark:text-gray-300" id="{{ $modalId }}-margin-labour">0.0%</span>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <span class="text-gray-600 dark:text-gray-300">Freight</span>
        <div class="flex items-center gap-6">
            <span class="font-medium text-gray-900 dark:text-white" id="{{ $modalId }}-sum-freight">$0.00</span>
            <span class="text-gray-500 dark:text-gray-300" id="{{ $modalId }}-margin-freight">0.0%</span>
        </div>
    </div>

    <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>

    <div class="flex items-center justify-between">
        <span class="font-semibold text-gray-900 dark:text-white">Grand Total</span>
        <div class="flex items-center gap-6">
            <span class="font-semibold text-gray-900 dark:text-white" id="{{ $modalId }}-sum-grand">$0.00</span>
            <span class="font-semibold text-gray-900 dark:text-white" id="{{ $modalId }}-margin-grand">0.0%</span>
        </div>
    </div>

</div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Lock Status</h4>
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Status:</span>
                                    <span id="{{ $modalId }}-lock-status">Not locked</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Locked at:</span>
                                    <span id="{{ $modalId }}-locked-at">—</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">Locked by:</span>
                                    <span id="{{ $modalId }}-locked-by">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				
                {{-- Locked --}}
                <div id="{{ $modalId }}-tab-locked" role="tabpanel" class="hidden">
                    <div class="rounded-lg border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        Locked snapshot will display here (read-only).
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex flex-col gap-2 rounded-b border-t border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Context: <span class="font-medium">{{ $context }}</span> • ID: <span class="font-medium">{{ $recordId }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"
                            data-modal-hide="{{ $modalId }}">
                        Close
                    </button>

                    <button type="button"
                            id="{{ $modalId }}-save"
                            class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Save Costs
                    </button>

                    <button type="button"
                            id="{{ $modalId }}-lock"
                            class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-black dark:bg-gray-600 dark:hover:bg-gray-500">
                        Lock Profits
                    </button>
                </div>
            </div>
  </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalId = @json($modalId);
    const saveUrl = @json(
    $context === 'sale'
        ? route('pages.sales.profits.save-costs', $recordId)
        : route('pages.estimates.profits.save-costs', $recordId)
);

    function parseMoney(text) {
        return parseFloat(String(text).replace(/[^0-9.-]/g, '')) || 0;
    }

    function formatMoney(value) {
        return '$' + value.toFixed(2);
    }

    function calculateGroupedTotals() {
    let materialsTotal = 0;
    let labourTotal = 0;
    let freightTotal = 0;

    let materialsSellTotal = 0;
    let labourSellTotal = 0;
    let freightSellTotal = 0;

    const rows = document.querySelectorAll(`#${modalId} [data-profit-row]`);

    rows.forEach(row => {
        const type = (row.dataset.itemType || '').toLowerCase();

        const profitCell = row.querySelector('[data-profit]');
        const sellTotalCell = row.querySelector('[data-sell-total]');

        const profit = parseMoney(profitCell ? profitCell.textContent : '0');
        const sellTotal = parseMoney(sellTotalCell ? sellTotalCell.textContent : '0');

        if (type === 'material' || type === 'materials' || type === 'product') {
            materialsTotal += profit;
            materialsSellTotal += sellTotal;
        } else if (type === 'labour' || type === 'labor') {
            labourTotal += profit;
            labourSellTotal += sellTotal;
        } else if (type === 'freight') {
            freightTotal += profit;
            freightSellTotal += sellTotal;
        }
    });

    const grandTotal = materialsTotal + labourTotal + freightTotal;
    const grandSellTotal = materialsSellTotal + labourSellTotal + freightSellTotal;

    const materialsMargin = materialsSellTotal > 0 ? (materialsTotal / materialsSellTotal) * 100 : 0;
    const labourMargin = labourSellTotal > 0 ? (labourTotal / labourSellTotal) * 100 : 0;
    const freightMargin = freightSellTotal > 0 ? (freightTotal / freightSellTotal) * 100 : 0;
    const grandMargin = grandSellTotal > 0 ? (grandTotal / grandSellTotal) * 100 : 0;

    const materialsEl = document.getElementById(`${modalId}-sum-materials`);
    const labourEl = document.getElementById(`${modalId}-sum-labour`);
    const freightEl = document.getElementById(`${modalId}-sum-freight`);
    const grandEl = document.getElementById(`${modalId}-sum-grand`);

    const materialsMarginEl = document.getElementById(`${modalId}-margin-materials`);
    const labourMarginEl = document.getElementById(`${modalId}-margin-labour`);
    const freightMarginEl = document.getElementById(`${modalId}-margin-freight`);
    const grandMarginEl = document.getElementById(`${modalId}-margin-grand`);

    if (materialsEl) materialsEl.textContent = formatMoney(materialsTotal);
    if (labourEl) labourEl.textContent = formatMoney(labourTotal);
    if (freightEl) freightEl.textContent = formatMoney(freightTotal);
    if (grandEl) grandEl.textContent = formatMoney(grandTotal);

    if (materialsMarginEl) materialsMarginEl.textContent = materialsMargin.toFixed(1) + '%';
    if (labourMarginEl) labourMarginEl.textContent = labourMargin.toFixed(1) + '%';
    if (freightMarginEl) freightMarginEl.textContent = freightMargin.toFixed(1) + '%';
    if (grandMarginEl) grandMarginEl.textContent = grandMargin.toFixed(1) + '%';
}

    calculateGroupedTotals();
	

    document.querySelectorAll(`#${modalId} [data-cost-input]`).forEach(input => {
        input.addEventListener('input', function () {
            const row = this.closest('[data-profit-row]');
            if (!row) return;

            const sellTotalCell = row.querySelector('[data-sell-total]');
            const costTotalCell = row.querySelector('[data-cost-total]');
            const profitCell = row.querySelector('[data-profit]');
            const marginCell = row.querySelector('[data-margin]');

            const qty = parseFloat(row.children[2].textContent) || 0;
            const sellTotal = parseMoney(sellTotalCell.textContent);
            const costPrice = parseFloat(this.value) || 0;

            const costTotal = qty * costPrice;
            const profit = sellTotal - costTotal;
            const margin = sellTotal > 0 ? (profit / sellTotal) * 100 : 0;

            costTotalCell.textContent = formatMoney(costTotal);
            profitCell.textContent = formatMoney(profit);

            if (marginCell) {
                marginCell.textContent = margin.toFixed(1) + '%';
            }

            calculateGroupedTotals();
			
        });
    });

    const saveBtn = document.getElementById(`${modalId}-save`);

    if (saveBtn) {
        saveBtn.addEventListener('click', async function () {
            const rows = document.querySelectorAll(`#${modalId} [data-profit-row]`);
            const items = [];

            rows.forEach(row => {
                const input = row.querySelector('[data-cost-input]');
                if (!input) return;

                const itemId = input.dataset.itemId;
                const costPrice = parseFloat(input.value) || 0;

                items.push({
                    id: itemId,
                    cost_price: costPrice
                });
            });

            try {
                const response = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ items })
                });

                const data = await response.json();

                if (data.success) {
    rows.forEach(row => {
        const input = row.querySelector('[data-cost-input]');
        if (!input) return;

        const itemId = String(input.dataset.itemId || '');
        const costPrice = (parseFloat(input.value) || 0).toFixed(2);

        const idInput = document.querySelector(`input[name$="[id]"][value="${itemId}"]`);
        if (!idInput) return;

        const rowPrefix = idInput.name.replace(/\[id\]$/, '');

        const costPriceInput = document.querySelector(`input[name="${rowPrefix}[cost_price]"]`);
        const costTotalInput = document.querySelector(`input[name="${rowPrefix}[cost_total]"]`);

        let qty = 0;
        const qtyInput = document.querySelector(`input[name="${rowPrefix}[quantity]"]`);
        if (qtyInput) {
            qty = parseFloat(qtyInput.value) || 0;
        }

        if (costPriceInput) {
            costPriceInput.value = costPrice;
        }

        if (costTotalInput) {
            costTotalInput.value = (qty * parseFloat(costPrice)).toFixed(2);
        }
    });

    alert('Costs saved successfully.');
} else {
    alert('Error saving costs.');
}

            } catch (error) {
                console.error(error);
                alert('Unexpected error saving costs.');
            }
        });
    }
	
	
	
});
	
	
</script>