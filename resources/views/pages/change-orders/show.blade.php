{{-- resources/views/pages/change-orders/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            @php
                $statusColors = [
                    'draft'     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                    'sent'      => 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-300',
                    'approved'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'rejected'  => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    'cancelled' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                ];
                $statusLabels = [
                    'draft'     => 'Draft',
                    'sent'      => 'Sent',
                    'approved'  => 'Approved',
                    'rejected'  => 'Rejected',
                    'cancelled' => 'Cancelled',
                ];
                $grandDelta    = $delta['grand_delta'];
                $isDraft       = in_array($changeOrder->status, ['draft', 'sent']);
            @endphp

            {{-- Flash --}}
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <nav class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <a href="{{ route('pages.sales.show', $sale) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                            Sale #{{ $sale->sale_number }}
                        </a>
                        <span>/</span>
                        <span class="text-gray-700 dark:text-gray-200">{{ $changeOrder->co_number }}</span>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $changeOrder->co_number }}
                        @if($changeOrder->title)
                            <span class="ml-2 text-lg font-normal text-gray-500 dark:text-gray-400">— {{ $changeOrder->title }}</span>
                        @endif
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$changeOrder->status] ?? '' }}">
                            {{ $statusLabels[$changeOrder->status] ?? $changeOrder->status }}
                        </span>
                        <span class="text-gray-400">•</span>
                        <span>{{ $sale->customer_name }}</span>
                        @if($sale->job_name)
                            <span class="text-gray-400">•</span>
                            <span>{{ $sale->job_name }}</span>
                        @endif
                        <span class="text-gray-400">•</span>
                        <span>Created {{ $changeOrder->created_at->format('M j, Y') }}</span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('pages.sales.change-orders.pdf', [$sale, $changeOrder]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z" />
                        </svg>
                        Print / PDF
                    </a>

                    @if(in_array($changeOrder->status, ['draft', 'sent']))
                        <button type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-co-email-modal'))"
                                class="inline-flex items-center gap-1.5 rounded-md bg-purple-700 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                            Send Email
                        </button>
                    @endif

                    @if($isDraft)
                        <a href="{{ route('pages.sales.edit', $sale) }}"
                           class="inline-flex items-center gap-1.5 rounded-md border border-blue-300 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 shadow-sm hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                            </svg>
                            Edit Sale Items
                        </a>

                        <form method="POST" action="{{ route('pages.sales.change-orders.approve', [$sale, $changeOrder]) }}"
                              onsubmit="return confirm('Approve this Change Order? The sale will be re-locked at the revised total.')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                </svg>
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('pages.sales.change-orders.cancel', [$sale, $changeOrder]) }}"
                              onsubmit="return confirm('Cancel this Change Order? Sale items will be reverted to the original snapshot.')">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 dark:border-red-700 dark:bg-gray-800 dark:text-red-400">
                                Revert & Cancel
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Summary Card --}}
            <div class="mb-6 grid grid-cols-3 gap-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Original Contract</p>
                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">${{ number_format($delta['orig_grand_total'], 2) }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Change Order</p>
                    <p class="mt-1 text-xl font-bold {{ $grandDelta >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                        {{ $grandDelta >= 0 ? '+' : '' }}${{ number_format($grandDelta, 2) }}
                    </p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Revised Total</p>
                    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">${{ number_format($delta['new_grand_total'], 2) }}</p>
                </div>
            </div>

            {{-- Notice if draft --}}
            @if($isDraft)
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <p class="text-sm text-amber-800 dark:text-amber-200">
                            This Change Order is <strong>in progress</strong>. The delta below updates live as you edit the sale items.
                            Purchase Orders and Work Orders are blocked until this CO is approved or cancelled.
                        </p>
                    </div>
                </div>
            @endif

            {{-- Delta Table --}}
            <div class="space-y-4">

                @foreach($delta['rooms'] as $room)
                    @php
                        $roomStatusColor = match($room['status']) {
                            'added'   => 'border-green-400 dark:border-green-600',
                            'removed' => 'border-red-400 dark:border-red-600',
                            'changed' => 'border-amber-400 dark:border-amber-600',
                            default   => 'border-gray-200 dark:border-gray-700',
                        };
                        $roomBadge = match($room['status']) {
                            'added'   => '<span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">New Room</span>',
                            'removed' => '<span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Removed</span>',
                            'changed' => '<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-300">Modified</span>',
                            default   => '',
                        };
                        $hasChanges = $room['status'] !== 'unchanged';
                    @endphp

                    <div class="overflow-hidden rounded-lg border-l-4 {{ $roomStatusColor }} border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        {{-- Room Header --}}
                        <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-center gap-2">
                                <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                </svg>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $room['room_name'] ?: 'Unnamed Room' }}</span>
                                {!! $roomBadge !!}
                            </div>
                            <div class="text-sm font-medium">
                                @if($room['status'] === 'removed')
                                    <span class="text-red-600 dark:text-red-400">−${{ number_format(abs($room['orig_total']), 2) }}</span>
                                @elseif($room['status'] === 'added')
                                    <span class="text-green-700 dark:text-green-400">+${{ number_format($room['new_total'], 2) }}</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">${{ number_format($room['orig_total'], 2) }}</span>
                                    @if(abs($room['delta']) >= 0.01)
                                        <span class="ml-2 {{ $room['delta'] >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $room['delta'] >= 0 ? '+' : '' }}${{ number_format($room['delta'], 2) }}
                                        </span>
                                        <span class="ml-1 text-gray-400">= ${{ number_format($room['new_total'], 2) }}</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Room Items --}}
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-2 text-left w-1/2">Item</th>
                                    <th class="px-3 py-2 text-right">Orig Qty</th>
                                    <th class="px-3 py-2 text-right">Orig Price</th>
                                    <th class="px-3 py-2 text-right">Orig Total</th>
                                    <th class="px-3 py-2 text-right">New Qty</th>
                                    <th class="px-3 py-2 text-right">New Price</th>
                                    <th class="px-3 py-2 text-right">New Total</th>
                                    <th class="px-3 py-2 text-right">Delta</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @foreach($room['rows'] as $row)
                                    @php
                                        $rowBg = match($row['status']) {
                                            'added'   => 'bg-green-50 dark:bg-green-950/30',
                                            'removed' => 'bg-red-50 dark:bg-red-950/30',
                                            'changed' => 'bg-amber-50 dark:bg-amber-950/30',
                                            default   => '',
                                        };
                                        $rowTag = match($row['status']) {
                                            'added'   => '<span class="rounded px-1.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">+Added</span>',
                                            'removed' => '<span class="rounded px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">−Removed</span>',
                                            'changed' => '<span class="rounded px-1.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">Changed</span>',
                                            default   => '',
                                        };
                                    @endphp
                                    <tr class="{{ $rowBg }}">
                                        <td class="px-4 py-2.5 text-gray-900 dark:text-gray-100">
                                            <div class="flex items-start gap-2">
                                                {!! $rowTag !!}
                                                <span class="{{ $row['status'] === 'removed' ? 'line-through text-gray-400' : '' }}">
                                                    {{ $row['label'] ?: ucfirst($row['item_type']) }}
                                                    @if($row['status'] === 'changed' && !empty($row['orig_label']) && $row['orig_label'] !== $row['label'])
                                                        <span class="block text-xs text-gray-400 line-through">was: {{ $row['orig_label'] }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-500 dark:text-gray-400">
                                            {{ $row['orig_qty'] !== null ? number_format($row['orig_qty'], 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-500 dark:text-gray-400">
                                            {{ $row['orig_price'] !== null ? '$'.number_format($row['orig_price'], 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-500 dark:text-gray-400">
                                            {{ $row['orig_total'] !== null ? '$'.number_format($row['orig_total'], 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-700 dark:text-gray-200">
                                            {{ $row['new_qty'] !== null ? number_format($row['new_qty'], 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-700 dark:text-gray-200">
                                            {{ $row['new_price'] !== null ? '$'.number_format($row['new_price'], 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-gray-700 dark:text-gray-200">
                                            {{ $row['new_total'] !== null ? '$'.number_format($row['new_total'], 2) : '—' }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right font-semibold">
                                            @if(abs($row['delta']) < 0.01)
                                                <span class="text-gray-400">—</span>
                                            @else
                                                <span class="{{ $row['delta'] >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $row['delta'] >= 0 ? '+' : '' }}${{ number_format($row['delta'], 2) }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                {{-- Grand Total Footer --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-700 dark:text-gray-200">Original Contract Total</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">${{ number_format($delta['orig_grand_total'], 2) }}</span>
                    </div>
                    <div class="mt-1 flex items-center justify-between">
                        <span class="font-semibold {{ $grandDelta >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            Change Order {{ $grandDelta >= 0 ? 'Addition' : 'Credit' }}
                        </span>
                        <span class="font-semibold {{ $grandDelta >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $grandDelta >= 0 ? '+' : '' }}${{ number_format($grandDelta, 2) }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-gray-300 pt-2 dark:border-gray-600">
                        <span class="text-lg font-bold text-gray-900 dark:text-white">Revised Contract Total</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">${{ number_format($delta['new_grand_total'], 2) }}</span>
                    </div>
                </div>

            </div>

            {{-- Notes / Reason --}}
            @if($changeOrder->reason || $changeOrder->notes)
                <div class="mt-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    @if($changeOrder->reason)
                        <div class="{{ $changeOrder->notes ? 'mb-3' : '' }}">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Reason for Change</p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $changeOrder->reason }}</p>
                        </div>
                    @endif
                    @if($changeOrder->notes)
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Internal Notes</p>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $changeOrder->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

{{-- Send Email Modal --}}
<div x-data="{
        open: false,
        toEmail: '{{ $homeownerEmail }}',
        customTo: '',
        selected: '{{ $homeownerEmail ? 'jobsite' : 'custom' }}',
        get finalTo() { return this.selected === 'custom' ? this.customTo : this.toEmail; },
        select(val, email) { this.selected = val; this.toEmail = email; }
     }"
     @open-co-email-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.5)">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" @click.outside="open = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h5 class="text-base font-semibold text-gray-800">Send Change Order Email</h5>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('pages.sales.change-orders.send-email', [$sale, $changeOrder]) }}">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>

                    {{-- Recipient quick-select buttons --}}
                    <div class="flex flex-wrap gap-2 mb-2">
                        @if($homeownerEmail)
                            <button type="button"
                                    @click="select('jobsite', '{{ $homeownerEmail }}')"
                                    :class="selected === 'jobsite' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Job Site — {{ $homeownerEmail }}
                            </button>
                        @endif

                        @if(!empty($pmEmail))
                            <button type="button"
                                    @click="select('pm', '{{ $pmEmail }}')"
                                    :class="selected === 'pm' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                PM — {{ $pmEmail }}
                            </button>
                        @endif

                        <button type="button"
                                @click="select('custom', ''); $nextTick(() => $refs.customToInput.focus())"
                                :class="selected === 'custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            Custom
                        </button>
                    </div>

                    <template x-if="selected !== 'custom'">
                        <div class="w-full bg-gray-100 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-700" x-text="toEmail"></div>
                    </template>
                    <template x-if="selected === 'custom'">
                        <input type="email" x-ref="customToInput" x-model="customTo"
                               placeholder="Enter email address"
                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                    </template>

                    <input type="hidden" name="to" :value="finalTo">

                    @if(!$homeownerEmail && empty($pmEmail))
                        <p class="mt-1.5 text-xs text-yellow-700">No job site or PM email on this sale. Use Custom to enter a recipient.</p>
                    @endif
                </div>

                {{-- CC Addresses --}}
                <div x-data="{ ccEmails: [], ccInput: '' }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CC <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="ccEmails.length > 0">
                        <template x-for="(email, i) in ccEmails" :key="i">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">
                                <span x-text="email"></span>
                                <input type="hidden" name="cc[]" :value="email">
                                <button type="button" @click="ccEmails.splice(i, 1)" class="text-blue-400 hover:text-blue-600 leading-none ml-1">&times;</button>
                            </span>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="email" x-model="ccInput"
                               @keydown.enter.prevent="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                               placeholder="cc@example.com"
                               class="flex-1 bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <button type="button"
                                @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                            Add
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" value="{{ $emailSubject }}"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="body" rows="10"
                              class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-mono">{{ $emailBody }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <a href="{{ route('pages.sales.change-orders.pdf', [$sale, $changeOrder]) }}" target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-colors">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $changeOrder->co_number }}.pdf</span>
                        <span class="text-xs text-gray-400 ml-1">— click to preview</span>
                    </a>
                </div>

                <p class="text-xs text-gray-400">
                    @if(auth()->user()->microsoftAccount?->mail_connected)
                        Sending from <strong>{{ auth()->user()->microsoftAccount->email }}</strong> via your personal MS365 account (Track 2).
                    @else
                        Sending via shared mailbox (Track 1). Connect your MS365 account in Settings for personal sending.
                    @endif
                </p>

            </div>
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-purple-700 rounded-lg hover:bg-purple-800">
                    Send Email
                </button>
            </div>
        </form>
    </div>
</div>
