{{-- resources/views/pages/purchase-orders/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $purchaseOrder->po_number }}
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">

                        @php
                            $statusColors = [
                                'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                'ordered'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'received'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'delivered' => 'bg-teal-700 text-white dark:bg-teal-800',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            ];
                            $statusColor = $statusColors[$purchaseOrder->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp

                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                            {{ $purchaseOrder->status_label }}
                        </span>

                        @if($purchaseOrder->sale)
                        <span class="text-gray-400">•</span>
                        <span>Sale:
                            <a href="{{ route('pages.sales.show', $purchaseOrder->sale) }}"
                               class="font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                {{ $purchaseOrder->sale->sale_number }}
                            </a>
                        </span>

                        @if($purchaseOrder->sale->customer_name)
                            <span class="text-gray-400">•</span>
                            <span>{{ $purchaseOrder->sale->customer_name }}</span>
                        @endif
                        @else
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-500 dark:text-gray-400">Stock PO</span>
                        @endif

                        @if($purchaseOrder->sent_at)
                            <span class="text-gray-400">•</span>
                            <span class="text-green-600 dark:text-green-400">
                                Sent {{ $purchaseOrder->sent_at->format('M j, Y') }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($purchaseOrder->sale)
                    <a href="{{ route('pages.sales.show', $purchaseOrder->sale) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Back to Sale
                    </a>
                    @else
                    <a href="{{ route('pages.purchase-orders.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Back to POs
                    </a>
                    @endif

                    @can('edit purchase orders')
                    <a href="{{ route('pages.purchase-orders.edit', $purchaseOrder) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Edit
                    </a>
                    @endcan

                    @can('edit purchase orders')
                    @if ($purchaseOrder->status === 'ordered')
                    <a href="{{ route('pages.purchase-orders.receive.form', $purchaseOrder) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Receive items
                    </a>
                    @endif
                    @endcan

                    <a href="{{ route('pages.purchase-orders.pdf', $purchaseOrder) }}" target="_blank"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Print PDF
                    </a>

                    @can('edit purchase orders')
                    <button type="button"
                            onclick="document.getElementById('send-email-modal').classList.remove('hidden')"
                            class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Send to Vendor
                    </button>
                    @endcan

                    @can('delete purchase orders')
                    <form method="POST" action="{{ route('pages.purchase-orders.destroy', $purchaseOrder) }}"
                          onsubmit="return confirm('Delete this purchase order? It can be restored by an admin.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-200 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700 dark:focus:ring-red-900">
                            Delete
                        </button>
                    </form>
                    @endcan

                    @role('admin')
                    <form method="POST" action="{{ route('pages.purchase-orders.force-destroy', $purchaseOrder) }}"
                          onsubmit="return confirm('PERMANENTLY delete {{ $purchaseOrder->po_number }}? This cannot be undone.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center rounded-lg bg-red-700 px-4 py-2 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-800 dark:hover:bg-red-900 dark:focus:ring-red-900">
                            Permanently Delete
                        </button>
                    </form>
                    @endrole
                </div>
            </div>

            {{-- PO Navigation --}}
            <div class="mb-4 flex items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Prev --}}
                @if($prev)
                    <a href="{{ route('pages.purchase-orders.show', $prev) }}"
                       class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Prev
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed select-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Prev
                    </span>
                @endif

                {{-- Centre: position + jump --}}
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        @if($purchaseOrder->sale_id)
                            PO {{ $position }} of {{ $total }} &mdash; Sale #{{ $purchaseOrder->sale->sale_number }}
                        @else
                            Stock PO {{ $position }} of {{ $total }}
                        @endif
                    </span>
                    <form method="GET" action="{{ route('pages.purchase-orders.jump') }}" class="flex items-center gap-1.5">
                        <input type="text" name="q" placeholder="PO #" autocomplete="off"
                               class="w-20 rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs text-gray-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <button type="submit"
                                class="rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Go
                        </button>
                    </form>
                </div>

                {{-- Next --}}
                @if($next)
                    <a href="{{ route('pages.purchase-orders.show', $next) }}"
                       class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed select-none">
                        Next
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif

            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-6 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400" role="alert">
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 flex items-center rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400" role="alert">
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            {{-- Details Card --}}
            <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Purchase Order Details</h2>
                </div>
                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Vendor</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $purchaseOrder->vendor->company_name }}</p>
                        @if($purchaseOrder->vendor->email)
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $purchaseOrder->vendor->email }}</p>
                        @endif
                        @if($purchaseOrder->vendor->phone)
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $purchaseOrder->vendor->phone }}</p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">PO Number</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $purchaseOrder->po_number }}</p>
                        @if($purchaseOrder->vendor_order_number)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Vendor Order #: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $purchaseOrder->vendor_order_number }}</span></p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                        <p class="mt-1">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                {{ $purchaseOrder->status_label }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Expected Delivery</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $purchaseOrder->expected_delivery_date?->format('M j, Y') ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Fulfillment</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $purchaseOrder->fulfillment_label }}</p>
                        @if($purchaseOrder->delivery_address)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $purchaseOrder->delivery_address }}</p>
                        @endif
                        @if($purchaseOrder->pickup_at)
                            <p class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                                Pickup: <span class="font-medium">{{ $purchaseOrder->pickup_at->format('M j, Y g:i A') }}</span>
                                @if($purchaseOrder->calendar_event_id)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">Synced</span>
                                @endif
                            </p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Ordered By</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $purchaseOrder->orderedBy->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $purchaseOrder->created_at->format('M j, Y') }}</p>
                    </div>

                </div>
            </div>

            {{-- Items Table --}}
            <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-700 dark:bg-gray-700/40 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Item</th>
                                <th class="px-6 py-3 text-right">Qty</th>
                                <th class="px-6 py-3">Unit</th>
                                <th class="px-6 py-3 text-right">Unit Cost</th>
                                <th class="px-6 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($purchaseOrder->items as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $item->item_name }}</div>
                                        @if($item->po_notes)
                                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $item->po_notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">{{ $item->quantity }}</td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $item->unit }}</td>
                                    <td class="px-6 py-4 text-right">${{ number_format($item->cost_price, 2) }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">${{ number_format($item->cost_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/40">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">Grand Total</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($purchaseOrder->items->sum('cost_total'), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Special Instructions --}}
            @if($purchaseOrder->special_instructions)
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Special Instructions</h2>
                    </div>
                    <div class="p-6">
                        <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $purchaseOrder->special_instructions }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

    {{-- Send Email Modal --}}
    @can('edit purchase orders')
    <div id="send-email-modal"
         class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl dark:bg-gray-800"
             onclick="event.stopPropagation()">

            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Send PO to Vendor</h3>
                <button type="button"
                        onclick="document.getElementById('send-email-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('pages.purchase-orders.send-email', $purchaseOrder) }}">
                @csrf
                <div class="space-y-4 p-6">

                    <div>
                        <label for="email-to" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>
                        <input type="email" id="email-to" name="to" required
                               value="{{ $purchaseOrder->vendor->email }}"
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    {{-- CC Addresses --}}
                    <div x-data="{ ccEmails: [], ccInput: '' }">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">CC <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
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
                                   class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <button type="button"
                                    @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                    class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700">
                                Add
                            </button>
                        </div>
                    </div>

                    <div>
                        <label for="email-subject" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                        <input type="text" id="email-subject" name="subject" required
                               value="Purchase Order {{ $purchaseOrder->po_number }} — RM Flooring"
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    @php
                        $emailBody = 'Please find attached Purchase Order ' . $purchaseOrder->po_number . ' from RM Flooring.' . "\n\n";
                        if ($purchaseOrder->expected_delivery_date) {
                            $emailBody .= 'Expected Delivery: ' . $purchaseOrder->expected_delivery_date->format('F j, Y') . "\n";
                        }
                        if ($purchaseOrder->fulfillment_method !== 'pickup' && $purchaseOrder->delivery_address) {
                            $emailBody .= 'Delivery Address: ' . $purchaseOrder->delivery_address . "\n";
                        } elseif ($purchaseOrder->fulfillment_method === 'pickup') {
                            $emailBody .= 'Fulfillment: Pickup — we will pick up from your location.' . "\n";
                        }
                        if ($purchaseOrder->special_instructions) {
                            $emailBody .= "\nSpecial Instructions: " . $purchaseOrder->special_instructions . "\n";
                        }
                        $emailBody .= "\nPlease confirm receipt of this order.\n\nThank you,\nRM Flooring";
                    @endphp
                    <div>
                        <label for="email-body" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                        <textarea id="email-body" name="body" rows="6" required
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ $emailBody }}</textarea>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        The PO PDF will be attached automatically.
                        @if($purchaseOrder->sent_at)
                            <span class="text-amber-600 dark:text-amber-400">Previously sent {{ $purchaseOrder->sent_at->format('M j, Y') }}.</span>
                        @endif
                    </p>

                </div>

                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button"
                            onclick="document.getElementById('send-email-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

</x-app-layout>
