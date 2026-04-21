<x-app-layout>
    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Invoice #{{ $bill->reference_number }}
                        </h1>
                        @php
                            $colorMap = [
                                'draft'    => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                'pending'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'approved' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'overdue'  => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                'voided'   => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorMap[$bill->status] ?? '' }}">
                            {{ $bill->status_label }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $bill->bill_type === 'vendor' ? 'Vendor' : 'Installer' }} bill ·
                        {{ $bill->bill_date->format('M j, Y') }}
                        @if ($bill->due_date)
                            · Due {{ $bill->due_date->format('M j, Y') }}
                            @if ($bill->days_overdue > 0)
                                <span class="text-red-600 font-medium">({{ $bill->days_overdue }} days overdue)</span>
                            @endif
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    @if ($bill->status !== 'voided')
                        @can('edit bills')
                        <a href="{{ route('admin.bills.edit', $bill) }}"
                           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                            Edit
                        </a>
                        @endcan
                    @endif
                    {{-- Push to QBO --}}
                    @if ($bill->bill_type === 'vendor' && app(\App\Services\QuickBooksService::class)->isConnected())
                        <form method="POST" action="{{ route('admin.bills.push-to-qbo', $bill) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium rounded-lg border transition
                                        {{ $bill->qbo_id
                                            ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                {{ $bill->qbo_id ? 'Re-sync to QBO' : 'Push to QBO' }}
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('admin.bills.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        ← Back
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
            @endif

            @if ($bill->status === 'voided')
                <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Voided on {{ $bill->voided_at->format('M j, Y g:i A') }}
                        @if ($bill->void_reason)
                            — <span class="text-gray-500">{{ $bill->void_reason }}</span>
                        @endif
                    </p>
                </div>
            @endif

            {{-- Details card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Payee</p>
                            <p class="font-semibold text-gray-900 dark:text-white mt-0.5">{{ $bill->payee_name }}</p>
                        </div>
                        @if ($bill->purchaseOrder)
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Linked PO</p>
                            <a href="{{ route('pages.purchase-orders.show', $bill->purchaseOrder) }}" class="text-blue-600 hover:underline dark:text-blue-400 mt-0.5 block">
                                PO #{{ $bill->purchaseOrder->po_number }}
                            </a>
                        </div>
                        @elseif ($bill->workOrder)
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Linked WO</p>
                            <a href="{{ route('pages.sales.work-orders.show', [$bill->workOrder->sale_id, $bill->workOrder]) }}" class="text-blue-600 hover:underline dark:text-blue-400 mt-0.5 block">
                                WO #{{ $bill->workOrder->wo_number }}
                            </a>
                        </div>
                        @endif
                        @if ($bill->paymentTerm)
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Payment Term</p>
                            <p class="text-gray-900 dark:text-white mt-0.5">{{ $bill->paymentTerm->name }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Bill Date</p>
                            <p class="text-gray-900 dark:text-white mt-0.5">{{ $bill->bill_date->format('M j, Y') }}</p>
                        </div>
                        @if ($bill->due_date)
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Due Date</p>
                            <p class="mt-0.5 {{ $bill->days_overdue > 0 ? 'text-red-600 font-semibold dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $bill->due_date->format('M j, Y') }}
                            </p>
                        </div>
                        @endif
                        @if ($bill->notes)
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-xs uppercase font-semibold tracking-wider">Notes</p>
                            <p class="text-gray-700 dark:text-gray-300 mt-0.5">{{ $bill->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Line Items</h2>
                </div>
                <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3 text-right">Unit Cost</th>
                            <th class="px-4 py-3 text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($bill->items as $item)
                        <tr>
                            <td class="px-4 py-3">{{ $item->item_name }}</td>
                            <td class="px-4 py-3 text-right">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $item->unit ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">${{ number_format($item->line_total, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Totals --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col items-end gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                        <div class="flex justify-between w-56">
                            <span>Subtotal</span>
                            <span class="font-medium">${{ number_format($bill->subtotal, 2) }}</span>
                        </div>
                        @if ($bill->gst_amount > 0)
                        <div class="flex justify-between w-56">
                            <span>GST ({{ number_format($bill->gst_rate * 100, 3) }}%)</span>
                            <span class="font-medium">${{ number_format($bill->gst_amount, 2) }}</span>
                        </div>
                        @endif
                        @if ($bill->pst_amount > 0)
                        <div class="flex justify-between w-56">
                            <span>PST ({{ number_format($bill->pst_rate * 100, 3) }}%)</span>
                            <span class="font-medium">${{ number_format($bill->pst_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between w-56 font-bold text-base text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-600 pt-2 mt-1">
                            <span>Total</span>
                            <span>${{ number_format($bill->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions: Approve / Void --}}
            @if ($bill->status !== 'voided')
            <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex gap-3">
                    @if (in_array($bill->status, ['pending', 'overdue']))
                    @can('edit bills')
                    <form method="POST" action="{{ route('admin.bills.update', $bill) }}">
                        @csrf @method('PUT')
                        {{-- Send all required fields to only update status --}}
                        <input type="hidden" name="vendor_id" value="{{ $bill->vendor_id }}">
                        <input type="hidden" name="installer_id" value="{{ $bill->installer_id }}">
                        <input type="hidden" name="payment_term_id" value="{{ $bill->payment_term_id }}">
                        <input type="hidden" name="reference_number" value="{{ $bill->reference_number }}">
                        <input type="hidden" name="bill_date" value="{{ $bill->bill_date->toDateString() }}">
                        <input type="hidden" name="due_date" value="{{ $bill->due_date?->toDateString() }}">
                        <input type="hidden" name="status" value="approved">
                        <input type="hidden" name="gst_rate" value="{{ $bill->gst_rate * 100 }}">
                        <input type="hidden" name="pst_rate" value="{{ $bill->pst_rate * 100 }}">
                        <input type="hidden" name="notes" value="{{ $bill->notes }}">
                        @foreach ($bill->items as $i => $item)
                            <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                            <input type="hidden" name="items[{{ $i }}][purchase_order_item_id]" value="{{ $item->purchase_order_item_id }}">
                            <input type="hidden" name="items[{{ $i }}][work_order_item_id]" value="{{ $item->work_order_item_id }}">
                            <input type="hidden" name="items[{{ $i }}][item_name]" value="{{ $item->item_name }}">
                            <input type="hidden" name="items[{{ $i }}][quantity]" value="{{ $item->quantity }}">
                            <input type="hidden" name="items[{{ $i }}][unit]" value="{{ $item->unit }}">
                            <input type="hidden" name="items[{{ $i }}][unit_cost]" value="{{ $item->unit_cost }}">
                        @endforeach
                        <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700">
                            Mark as Approved
                        </button>
                    </form>
                    @endcan
                    @endif
                </div>

                @can('edit bills')
                <div x-data="{ showVoid: false }">
                    <button @click="showVoid = !showVoid"
                        class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-700">
                        Void Bill
                    </button>
                    <div x-show="showVoid" x-cloak
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 dark:bg-black/70">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Void this bill?</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Invoice #{{ $bill->reference_number }} · ${{ number_format($bill->grand_total, 2) }}. This cannot be undone.</p>
                            <form method="POST" action="{{ route('admin.bills.void', $bill) }}">
                                @csrf
                                <div class="mb-4">
                                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Reason (optional)</label>
                                    <input type="text" name="void_reason" placeholder="e.g. duplicate, credit issued..."
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div class="flex gap-3 justify-end">
                                    <button type="button" @click="showVoid = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 text-sm font-medium text-white bg-red-700 rounded-lg hover:bg-red-800">
                                        Void Bill
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endcan
            </div>
            @endif

            {{-- QuickBooks status --}}
            @if ($bill->bill_type === 'vendor' && app(\App\Services\QuickBooksService::class)->isConnected())
            <div class="bg-white border border-gray-200 rounded-lg p-4 dark:bg-gray-800 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">QuickBooks Online</h3>
                @if ($bill->qbo_id)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Synced</span>
                        <span class="text-gray-500">QBO ID: <span class="font-mono">{{ $bill->qbo_id }}</span></span>
                        <span class="text-gray-400">· {{ $bill->qbo_synced_at?->format('M j, Y g:i a') }}</span>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Not yet synced to QuickBooks. Use the "Push to QBO" button above.</p>
                @endif
            </div>
            @endif

            {{-- Delete (admin) --}}
            @can('delete bills')
            <div class="flex justify-end">
                <form method="POST" action="{{ route('admin.bills.destroy', $bill) }}"
                    onsubmit="return confirm('Permanently delete this bill record?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                        Delete record
                    </button>
                </form>
            </div>
            @endcan

        </div>
    </div>
</x-app-layout>
