<x-admin-layout>

<div class="py-6">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
		
		@if (session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-100 px-4 py-3 text-green-800">
        {{ session('success') }}
    </div>
@endif
		
		@php
    $saveUrl = $recordType === 'estimate'
        ? route('pages.estimates.profits.save-costs', $record->id)
        : route('pages.sales.profits.save-costs', $record->id);
@endphp

<form id="profit-costs-form" method="POST" action="{{ $saveUrl }}">
    @csrf

{{-- Page Header --}}
<div class="flex items-start justify-between mb-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">

            @if($recordType === 'estimate')
                Estimate Profit Analysis
            @else
                Sale Profit Analysis
            @endif

        </h1>

        <p class="text-sm text-gray-600 mt-1">
            @if($recordType === 'estimate')
                Estimate #: <span class="font-semibold">{{ $record->estimate_number ?? $record->id }}</span>
            @else
                Sale #: <span class="font-semibold">{{ $record->sale_number ?? $record->id }}</span>
            @endif
        </p>
    </div>

    <div class="flex items-center gap-2">

    @if($recordType === 'estimate')
        <a href="{{ route('pages.estimates.edit', $record->id) }}"
           class="inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
            Back to Estimate
        </a>
    @else
        <a href="{{ route('pages.sales.edit', $record->id) }}"
           class="inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">
            Back to Sale
        </a>
    @endif

    <button
        type="submit"
        id="save-profit-costs-btn"
        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
    >
        Save Costs
    </button>

</div>

</div>      
				{{-- Overall Profit Summary panel --}}
		@php
    $totalSell = $rooms->sum(fn($room) =>
        $room->items->sum(fn($item) => (float) ($item->line_total ?? 0))
    );

    $totalCost = $rooms->sum(fn($room) =>
        $room->items->sum(fn($item) => (float) ($item->cost_total ?? 0))
    );

    $totalProfit = $totalSell - $totalCost;

    $profitMargin = $totalSell > 0
        ? ($totalProfit / $totalSell) * 100
        : 0;
@endphp

<div class="bg-white shadow rounded-lg p-6 mb-6">

    <h2 class="text-lg font-semibold text-gray-900 mb-4">
        Profit Summary
    </h2>

    <div class="grid grid-cols-4 gap-6 text-sm">

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-gray-500">Total Sell</p>
            <p class="text-lg font-semibold" data-summary-sell>
                ${{ number_format($totalSell, 2) }}
            </p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-gray-500">Total Cost</p>
            <p class="text-lg font-semibold" data-summary-cost>
                ${{ number_format($totalCost, 2) }}
            </p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-gray-500">Total Profit</p>
            <p class="text-lg font-semibold text-green-600" data-summary-profit>
                ${{ number_format($totalProfit, 2) }}
            </p>
        </div>

        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <p class="text-gray-500">Profit Margin</p>
            <p class="text-lg font-semibold" data-summary-margin>
                {{ number_format($profitMargin, 2) }}%
            </p>
        </div>

    </div>

</div>
		
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Rooms</h2>

            @if(isset($rooms) && $rooms->count())
                <div class="space-y-3">
                    @foreach($rooms as $room)                        <div class="border rounded-lg p-4" data-room-card>
                            <h3 class="font-semibold text-gray-800">
                                {{ $room->room_name ?: 'Unnamed Room' }}
                            </h3>

                            <p class="text-sm text-gray-500 mb-3">
                                Room ID: {{ $room->id }} | Items in room: {{ $room->items->count() }}
                            </p>

                            @if($room->items->count())
                                <div class="overflow-x-auto">
                                    <table class="min-w-full text-sm border border-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left border-b">Type</th>
                                                <th class="px-3 py-2 text-left border-b">Description</th>
                                                <th class="px-3 py-2 text-right border-b">Qty</th>
                                                <th class="px-3 py-2 text-left border-b">Unit</th>
                                                <th class="px-3 py-2 text-right border-b">Sell</th>
												<th class="px-3 py-2 text-right border-b">Line Total</th>
												<th class="px-3 py-2 text-right border-b">Cost</th>
												<th class="px-3 py-2 text-right border-b">Cost Total</th>
												<th class="px-3 py-2 text-right border-b">Profit</th>
												<th class="px-3 py-2 text-right border-b">Margin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($room->items as $item)
                                                <tr
													class="odd:bg-white even:bg-gray-50"
													data-profit-row
													data-item-id="{{ $item->id }}"
												>
                                                    <td class="px-3 py-2 border-b">
                                                        {{ ucfirst($item->item_type ?? '') }}
                                                    </td>

                                                    <td class="px-3 py-2 border-b">
                                                        @if(($item->item_type ?? '') === 'material')
                                                            {{ $item->product_type }}{{ $item->style ? ' - '.$item->style : '' }}{{ $item->color_item_number ? ' - '.$item->color_item_number : '' }}
                                                        @elseif(($item->item_type ?? '') === 'labour')
                                                            {{ $item->labour_type }}{{ $item->description ? ' - '.$item->description : '' }}
                                                        @elseif(($item->item_type ?? '') === 'freight')
                                                            {{ $item->freight_description }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <td
														class="px-3 py-2 text-right border-b"
														data-qty="{{ (float)($item->quantity ?? 0) }}"
													>
														{{ rtrim(rtrim(number_format((float)($item->quantity ?? 0), 2), '0'), '.') }}
													</td>

                                                    <td class="px-3 py-2 border-b">
                                                        {{ $item->unit }}
                                                    </td>

                                                    <td class="px-3 py-2 text-right border-b">
                                                        ${{ number_format((float)($item->sell_price ?? 0), 2) }}
                                                    </td>

                                                    <td
														class="px-3 py-2 text-right border-b"
														data-line-total
														data-value="{{ (float)($item->line_total ?? 0) }}"
													>
														${{ number_format((float)($item->line_total ?? 0), 2) }}
													</td>

                                                    <td class="px-3 py-2 text-right border-b">
    <input
    type="number"
    name="items[{{ $item->id }}][cost_price]"
    value="{{ number_format((float)($item->cost_price ?? 0), 2, '.', '') }}"
    step="0.01"
    min="0"
    class="w-24 rounded-md border-gray-300 text-sm text-right profit-cost-input"
>
    <input
        type="hidden"
        name="items[{{ $item->id }}][id]"
        value="{{ $item->id }}"
    >
</td>

                                                    <td
														class="px-3 py-2 text-right border-b"
														data-cost-total
														data-value="{{ (float)($item->cost_total ?? 0) }}"
													>
														${{ number_format((float)($item->cost_total ?? 0), 2) }}
													</td>
													<td
														class="px-3 py-2 text-right border-b"
														data-profit
													>
														${{ number_format((float)($item->line_total ?? 0) - (float)($item->cost_total ?? 0), 2) }}
													</td>
													<td
														class="px-3 py-2 text-right border-b"
														data-margin
													>
														@php
															$lineTotal = (float) ($item->line_total ?? 0);
															$costTotal = (float) ($item->cost_total ?? 0);
															$lineProfit = $lineTotal - $costTotal;
															$lineMargin = $lineTotal > 0 ? ($lineProfit / $lineTotal) * 100 : 0;
														@endphp
														{{ number_format($lineMargin, 2) }}%
													</td>
                                                </tr>
                                            @endforeach                                        </tbody>
                                       <tfoot class="bg-gray-100 font-semibold">
    @php
        $roomSellTotal = $room->items->sum(fn($item) => (float) ($item->line_total ?? 0));
        $roomCostTotal = $room->items->sum(fn($item) => (float) ($item->cost_total ?? 0));
        $roomProfitTotal = $roomSellTotal - $roomCostTotal;
        $roomMargin = $roomSellTotal > 0 ? ($roomProfitTotal / $roomSellTotal) * 100 : 0;
    @endphp

    <tr>
        <td colspan="8" class="px-3 py-2 text-right border-t">Room Sell Total</td>
        <td
    class="px-3 py-2 text-right border-t"
    data-room-sell-total
    data-value="{{ $roomSellTotal }}"
>
    ${{ number_format($roomSellTotal, 2) }}
</td>
        <td class="px-3 py-2 border-t"></td>
    </tr>

    <tr>
        <td colspan="8" class="px-3 py-2 text-right border-t">Room Cost Total</td>
        <td
    class="px-3 py-2 text-right border-t"
    data-room-cost-total
    data-value="{{ $roomCostTotal }}"
>
    ${{ number_format($roomCostTotal, 2) }}
</td>
        <td class="px-3 py-2 border-t"></td>
    </tr>

    <tr>
        <td colspan="8" class="px-3 py-2 text-right border-t">Room Profit</td>
        <td
    class="px-3 py-2 text-right border-t"
    data-room-profit
    data-value="{{ $roomProfitTotal }}"
>
    ${{ number_format($roomProfitTotal, 2) }}
</td>
        <td class="px-3 py-2 border-t"></td>
    </tr>

    <tr>
        <td colspan="9" class="px-3 py-2 text-right border-t">Room Margin</td>
        <td
    class="px-3 py-2 text-right border-t"
    data-room-margin
    data-value="{{ $roomMargin }}"
>
    {{ number_format($roomMargin, 2) }}%
</td>
    </tr>
</tfoot>
                                    </table>
                                </div>
                            @else
                                <p class="text-sm text-gray-500">No items in this room.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500">No rooms found for this record.</p>
            @endif
        </div>

	</form>

    </div>

</div>

	<script>
document.addEventListener('DOMContentLoaded', function () {
    function formatMoney(value) {
        return '$' + (parseFloat(value || 0).toFixed(2));
    }

    function formatPercent(value) {
        return (parseFloat(value || 0).toFixed(2)) + '%';
    }

    function setMarginColor(cell, margin) {
    cell.style.color = '';

    if (margin < 20) {
        cell.style.color = '#dc2626';
    } else if (margin <= 38) {
        cell.style.color = '#d97706';
    } else {
        cell.style.color = '#16a34a';
    }
}

    function setProfitColor(cell, margin) {
    cell.style.color = '';

    if (margin < 20) {
        cell.style.color = '#dc2626';
    } else if (margin <= 38) {
        cell.style.color = '#d97706';
    } else {
        cell.style.color = '#16a34a';
    }
}

    function recalculateRow(row) {
        const qtyCell = row.querySelector('[data-qty]');
        const lineTotalCell = row.querySelector('[data-line-total]');
        const costInput = row.querySelector('.profit-cost-input');
        const costTotalCell = row.querySelector('[data-cost-total]');
        const profitCell = row.querySelector('[data-profit]');
        const marginCell = row.querySelector('[data-margin]');

        if (!qtyCell || !lineTotalCell || !costInput || !costTotalCell || !profitCell || !marginCell) {
            return;
        }
		
	function recalculateRoom(roomCard) {
    let sellTotal = 0;
    let costTotal = 0;

    const rows = roomCard.querySelectorAll('[data-profit-row]');

    rows.forEach(row => {
        const lineTotalCell = row.querySelector('[data-line-total]');
        const costTotalCell = row.querySelector('[data-cost-total]');

        if (!lineTotalCell || !costTotalCell) return;

        const lineTotal = parseFloat(lineTotalCell.dataset.value || 0);
        const costTotalValue = parseFloat(costTotalCell.dataset.value || 0);

        sellTotal += lineTotal;
        costTotal += costTotalValue;
    });

    const profit = sellTotal - costTotal;
    const margin = sellTotal > 0 ? (profit / sellTotal) * 100 : 0;

    const sellCell = roomCard.querySelector('[data-room-sell-total]');
    const costCell = roomCard.querySelector('[data-room-cost-total]');
    const profitCell = roomCard.querySelector('[data-room-profit]');
    const marginCell = roomCard.querySelector('[data-room-margin]');

    if (sellCell) {
    sellCell.textContent = formatMoney(sellTotal);
    sellCell.dataset.value = sellTotal;
}

if (costCell) {
    costCell.textContent = formatMoney(costTotal);
    costCell.dataset.value = costTotal;
}

if (profitCell) {
    profitCell.textContent = formatMoney(profit);
    profitCell.dataset.value = profit;
}

if (marginCell) {
    marginCell.textContent = formatPercent(margin);
    marginCell.dataset.value = margin;
}
}

        const qty = parseFloat(qtyCell.dataset.qty || 0);
        const lineTotal = parseFloat(lineTotalCell.dataset.value || 0);
        const costPrice = parseFloat(costInput.value || 0);

        const costTotal = qty * costPrice;
        const profit = lineTotal - costTotal;
        const margin = lineTotal > 0 ? (profit / lineTotal) * 100 : 0;

        costTotalCell.dataset.value = costTotal;
        costTotalCell.textContent = formatMoney(costTotal);

        profitCell.textContent = formatMoney(profit);
        marginCell.textContent = formatPercent(margin);

        setProfitColor(profitCell, margin);
        setMarginColor(marginCell, margin);
		
		const roomCard = row.closest('[data-room-card]');
if (roomCard) {
    recalculateRoom(roomCard);
    updateProfitSummaryHeader();
}
    }

    document.querySelectorAll('[data-profit-row]').forEach(function (row) {
        const input = row.querySelector('.profit-cost-input');

        if (input) {
            input.addEventListener('input', function () {
                recalculateRow(row);
            });

            recalculateRow(row);
        }
    });
});
		

function updateProfitSummaryHeader() {

    let totalSell = 0;
    let totalCost = 0;

    document.querySelectorAll('[data-room-card]').forEach(function(room){

        const sell = parseFloat(room.querySelector('[data-room-sell-total]')?.dataset.value || 0);
const cost = parseFloat(room.querySelector('[data-room-cost-total]')?.dataset.value || 0);

        totalSell += sell;
        totalCost += cost;

    });

    const totalProfit = totalSell - totalCost;

    const margin = totalSell > 0
        ? (totalProfit / totalSell) * 100
        : 0;

    document.querySelector('[data-summary-sell]').textContent =
        '$' + totalSell.toFixed(2);

    document.querySelector('[data-summary-cost]').textContent =
        '$' + totalCost.toFixed(2);

    const profitCell = document.querySelector('[data-summary-profit]');
const marginCell = document.querySelector('[data-summary-margin]');

profitCell.textContent = '$' + totalProfit.toFixed(2);
marginCell.textContent = margin.toFixed(2) + '%';

profitCell.style.color = '';
marginCell.style.color = '';

if (margin < 20) {
    profitCell.style.color = '#dc2626';
    marginCell.style.color = '#dc2626';
} else if (margin <= 38) {
    profitCell.style.color = '#d97706';
    marginCell.style.color = '#d97706';
} else {
    profitCell.style.color = '#16a34a';
    marginCell.style.color = '#16a34a';
}
}


// run once when page loads
document.addEventListener('DOMContentLoaded', function () {
    updateProfitSummaryHeader();
});
		
</script>
	
</x-admin-layout>