{{-- resources/views/pages/work-orders/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $workOrder->wo_number }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">

                        @php
                            $statusColors = [
                                'created'     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                'scheduled'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                'in_progress' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300',
                                'completed'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                'cancelled'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            ];
                        @endphp

                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$workOrder->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $workOrder->status_label }}
                        </span>

                        <span class="text-gray-400">•</span>
                        <span>Sale:
                            <a href="{{ route('pages.sales.show', $sale) }}"
                               class="font-semibold text-blue-600 hover:underline dark:text-blue-400">
                                {{ $sale->sale_number }}
                            </a>
                        </span>

                        @if($sale->customer_name)
                            <span class="text-gray-400">•</span>
                            <span>{{ $sale->customer_name }}</span>
                        @endif

                        @if($workOrder->calendar_synced)
                            <span class="text-gray-400">•</span>
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500 inline-block"></span>
                                On RM – Installations calendar
                            </span>
                        @endif

                        @if($workOrder->sent_at)
                            <span class="text-gray-400">•</span>
                            <span class="text-green-600 dark:text-green-400">Sent {{ $workOrder->sent_at->format('M j, Y') }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Back to Sale
                    </a>

                    @can('edit work orders')
                    <a href="{{ route('pages.sales.work-orders.edit', [$sale, $workOrder]) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Edit
                    </a>
                    @endcan

                    <a href="{{ route('pages.sales.work-orders.pdf', [$sale, $workOrder]) }}" target="_blank"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Print PDF
                    </a>

                    @can('edit work orders')
                    <button type="button"
                            onclick="document.getElementById('send-email-modal').classList.remove('hidden')"
                            class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Send to Installer
                    </button>
                    @endcan

                    @can('delete work orders')
                    <form method="POST" action="{{ route('pages.sales.work-orders.destroy', [$sale, $workOrder]) }}"
                          onsubmit="return confirm('Delete this work order?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-gray-700">
                            Delete
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- Installer Details --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Installer</h2>
                    </div>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $workOrder->installer?->company_name ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $workOrder->installer?->contact_name ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $workOrder->installer?->phone ?? $workOrder->installer?->mobile ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">
                                @if($workOrder->installer?->email)
                                    <a href="mailto:{{ $workOrder->installer->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $workOrder->installer->email }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- WO Details --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Work Order Details</h2>
                    </div>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">WO Number</dt>
                            <dd class="col-span-2 text-sm font-medium text-gray-900 dark:text-white">{{ $workOrder->wo_number }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">
                                @if($workOrder->scheduled_date)
                                    {{ $workOrder->scheduled_date->format('M j, Y') }}
                                    @if($workOrder->scheduled_time)
                                        at {{ \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') }}
                                    @endif
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Address</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $sale->job_address ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Calendar</dt>
                            <dd class="col-span-2 text-sm">
                                @if($workOrder->calendar_synced)
                                    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500 inline-block"></span>
                                        On RM – Installations
                                    </span>
                                @else
                                    <span class="text-gray-400">Not synced</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $workOrder->creator?->name ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Labour Items grouped by Room --}}
            @php
                $itemsByRoom = $workOrder->items->groupBy(fn($item) => $item->saleItem?->sale_room_id ?? 0);
            @endphp

            <div class="mt-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Labour Items</h2>

                @foreach ($itemsByRoom as $roomId => $roomItems)
                    @php
                        $roomName = $roomItems->first()->saleItem?->room?->room_name ?? 'Uncategorized';
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        {{-- Room header --}}
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-700/40">
                            <svg class="h-4 w-4 flex-shrink-0 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $roomName }}</h3>
                        </div>
                        {{-- Items table --}}
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/30">
                                    <tr>
                                        <th class="px-6 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Item</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Qty</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Unit Cost</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                    @foreach ($roomItems as $item)
                                        <tr>
                                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                                {{-- Linked materials --}}
                                                @if($item->relatedMaterials->isNotEmpty())
                                                    <div class="mb-2 space-y-0.5">
                                                        @foreach($item->relatedMaterials as $mat)
                                                            @php
                                                                $si = $mat->saleItem;
                                                                $matName = $si ? implode(' — ', array_filter([$si->product_type, $si->manufacturer, $si->style, $si->color_item_number])) : 'Material';
                                                            @endphp
                                                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                                <svg class="h-3 w-3 flex-shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                                <span>{{ $matName }}</span>
                                                                @if($si)
                                                                    <span class="text-gray-400">— {{ number_format((float)$si->quantity, 2) }} {{ $si->unit }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                <div class="font-medium">{{ $item->item_name }}</div>
                                                @if($item->unit) <div class="text-xs text-gray-400">{{ $item->unit }}</div> @endif
                                                @if($item->wo_notes)
                                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 whitespace-pre-line">{{ $item->wo_notes }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($item->quantity, 2) }}</td>
                                            <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">${{ number_format($item->cost_price, 2) }}</td>
                                            <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->cost_total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach

                {{-- Grand Total --}}
                <div class="flex justify-end rounded-lg border border-gray-200 bg-white px-6 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Grand Total:&nbsp;</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">${{ number_format($workOrder->grand_total, 2) }}</span>
                </div>
            </div>

            {{-- Notes --}}
            @if($workOrder->notes)
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                </div>
                <div class="px-6 py-4">
                    <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $workOrder->notes }}</p>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Send Email Modal --}}
    <div id="send-email-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Send Work Order to Installer</h3>
                <button type="button" onclick="document.getElementById('send-email-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('pages.sales.work-orders.send-email', [$sale, $workOrder]) }}">
                @csrf
                <div class="space-y-4 px-6 py-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">To</label>
                        <input type="email" name="to"
                               value="{{ old('to', $workOrder->installer?->email) }}"
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                        <input type="text" name="subject"
                               value="{{ old('subject', 'Work Order ' . $workOrder->wo_number . ' — ' . ($sale->customer_name ?? $sale->sale_number)) }}"
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                        <textarea name="body" rows="5"
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  required>Please find the attached work order {{ $workOrder->wo_number }}.

Scheduled: {{ $workOrder->scheduled_date?->format('M j, Y') ?? 'TBD' }}{{ $workOrder->scheduled_time ? ' at ' . \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') : '' }}
Address: {{ $sale->job_address ?? 'TBD' }}

Please review and confirm.

Thank you.</textarea>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">A PDF copy of {{ $workOrder->wo_number }} will be attached automatically.</p>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button"
                            onclick="document.getElementById('send-email-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
