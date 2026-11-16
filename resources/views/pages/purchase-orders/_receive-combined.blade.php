{{--
    "Receive combined" shortcut — for lines that were combined into one vendor
    line (same material, several rooms). One shipment arrives for the total, so
    staff type that total once and it's split across the rooms' qty inputs
    (quantities[po_item_id]) in proportion to what each room ordered. The
    per-room inputs stay editable. Used by the desktop and mobile receive pages.
--}}
@php $combinedLines = $purchaseOrder->vendorLines()->where('is_combined', true); @endphp
@if ($combinedLines->isNotEmpty())
    <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20" data-receive-combined>
        <h2 class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Combined lines — receive the total</h2>
        <p class="mt-0.5 mb-3 text-xs text-indigo-800/80 dark:text-indigo-300">
            The vendor shipped these as one line. Enter the total received and it's split across the rooms below.
        </p>
        <div class="space-y-3">
            @foreach ($combinedLines as $line)
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-sm">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $line->item_name }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">
                            Ordered {{ $line->quantity }} {{ $line->unit }} ·
                            {{ $line->members->map(fn ($m) => ($m->saleItem?->room?->room_name ?: 'Unnamed Room') . ' ' . (float) $m->quantity)->implode(', ') }}
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        Total received
                        <input type="number" min="0" step="any" value="{{ $line->quantity }}"
                               data-combined-total
                               data-members='@json($line->members->map(fn ($m) => ['id' => $m->id, 'ordered' => (float) $m->quantity])->values())'
                               class="w-28 rounded-lg border-gray-300 text-right text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <span class="text-xs text-gray-500">{{ $line->unit }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <script>
    // Split a combined total across the rooms, in proportion to each room's ordered qty
    document.addEventListener('input', function (e) {
        if (!e.target.matches('[data-combined-total]')) return;
        const total   = parseFloat(e.target.value) || 0;
        const members = JSON.parse(e.target.dataset.members);
        const ordered = members.reduce((t, m) => t + m.ordered, 0);
        let assigned  = 0;
        members.forEach((m, i) => {
            const share = i === 0 ? 0 : Math.round((ordered > 0 ? total * m.ordered / ordered : total / members.length) * 100) / 100;
            m.qty = share;
            assigned += share;
        });
        members[0].qty = Math.round((total - assigned) * 100) / 100; // remainder on the first room
        members.forEach(m => {
            const input = document.querySelector('input[name="quantities[' + m.id + ']"]');
            if (input) { input.value = m.qty; input.dispatchEvent(new Event('input', { bubbles: true })); }
        });
    });
    </script>
@endif
