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

                    {{-- AP: Record / View Bill --}}
                    @can('view bills')
                    @if ($linkedBill)
                        <a href="{{ route('admin.bills.show', $linkedBill) }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-green-300 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75"/>
                            </svg>
                            Bill #{{ $linkedBill->reference_number }}
                        </a>
                    @elseif (auth()->user()->can('create bills'))
                        <a href="{{ route('admin.bills.create', ['work_order' => $workOrder->id]) }}"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Record Bill
                        </a>
                    @endif
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
            @if (session('warning'))
                <div class="mb-4 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-200">
                    {{ session('warning') }}
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

            {{-- ── Material Staging Pick Ticket ──────────────────────────── --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Material Staging</h2>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Stage materials for warehouse pick-up or delivery to site.</p>
                    </div>
                    @if (!$stagingPickTicket)
                        @can('edit work orders')
                        <button type="button"
                                onclick="document.getElementById('stage-modal').classList.remove('hidden')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-300 dark:bg-orange-500 dark:hover:bg-orange-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Stage Work Order
                        </button>
                        @endcan
                    @endif
                </div>

                @if ($stagingPickTicket)
                    @php
                        $ptStatusColors = [
                            'staged'    => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                            'delivered' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        ];
                        $ptStatusColor = $ptStatusColors[$stagingPickTicket->status] ?? 'bg-gray-100 text-gray-700';
                        $ptLabel = \App\Models\PickTicket::STATUS_LABELS[$stagingPickTicket->status] ?? ucfirst($stagingPickTicket->status);
                    @endphp

                    {{-- PT header row --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">PT #{{ $stagingPickTicket->pt_number }}</span>
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ptStatusColor }}">
                                {{ $ptLabel }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">Created {{ $stagingPickTicket->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if (Route::has('pages.warehouse.pick-tickets.show'))
                                <a href="{{ route('pages.warehouse.pick-tickets.show', $stagingPickTicket) }}"
                                   class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                    View full ticket →
                                </a>
                            @endif
                            @if (in_array($stagingPickTicket->status, ['staged', 'partially_delivered']))
                                @can('edit work orders')
                                <a href="{{ route('pages.warehouse.pick-tickets.show', $stagingPickTicket) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Record Delivery
                                </a>
                                @if ($stagingPickTicket->status === 'staged')
                                <button type="button"
                                        onclick="document.getElementById('wo-unstage-modal').classList.remove('hidden')"
                                        class="inline-flex items-center gap-1 rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                                    Unstage
                                </button>
                                @endif
                                @endcan
                            @endif
                        </div>
                    </div>

                    {{-- Job info grid --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-0 divide-x divide-y divide-gray-100 dark:divide-gray-700 border-b border-gray-100 dark:border-gray-700">
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Sale #</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $sale->sale_number }}</p>
                        </div>
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Job</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate" title="{{ $sale->job_name ?? $sale->customer_name }}">
                                {{ $sale->job_name ?? $sale->customer_name ?? '—' }}
                            </p>
                        </div>
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Installer</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $workOrder->installer?->company_name ?? '—' }}</p>
                        </div>
                        <div class="px-5 py-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Install date</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                @if ($workOrder->scheduled_date)
                                    {{ $workOrder->scheduled_date->format('M j, Y') }}
                                    @if ($workOrder->scheduled_time)
                                        · {{ \Carbon\Carbon::createFromFormat('H:i', $workOrder->scheduled_time)->format('g:i A') }}
                                    @endif
                                @else
                                    <span class="text-gray-400">Not scheduled</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Staging meta bar --}}
                    <div class="flex flex-wrap items-start gap-x-6 gap-y-1 px-6 py-2.5 bg-orange-50 dark:bg-orange-900/10 border-b border-orange-100 dark:border-orange-900/30 text-xs text-gray-600 dark:text-gray-400">
                        <span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Staged by:</span>
                            {{ $stagingPickTicket->creator?->name ?? '—' }}
                            · {{ $stagingPickTicket->created_at->format('M j, Y g:i a') }}
                        </span>
                        @if ($stagingPickTicket->staging_notes)
                            <span>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Notes:</span>
                                {{ $stagingPickTicket->staging_notes }}
                            </span>
                        @endif
                    </div>

                    {{-- Materials table --}}
                    @if ($stagingPickTicket->items->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700/30">
                                    <tr>
                                        <th class="px-6 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Material</th>
                                        <th class="px-6 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Qty</th>
                                        <th class="px-6 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Unit</th>
                                        <th class="px-6 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Room</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-800">
                                    @foreach ($stagingPickTicket->items as $ptItem)
                                        <tr>
                                            <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $ptItem->item_name }}</td>
                                            <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">
                                                {{ rtrim(rtrim(number_format((float)$ptItem->quantity, 2), '0'), '.') }}
                                            </td>
                                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $ptItem->unit ?: '—' }}</td>
                                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $ptItem->saleItem?->room?->room_name ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="px-6 py-5 text-sm text-gray-400 dark:text-gray-500 text-center">
                            No materials linked to this work order.
                        </div>
                    @endif

                @else
                    <div class="px-6 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        No staging pick ticket yet.
                        @can('edit work orders')
                            Click <strong>Stage Work Order</strong> above to create one.
                        @endcan
                    </div>
                @endif

            </div>

        </div>
    </div>

    {{-- Stage Work Order Modal --}}
    <div id="stage-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Stage Work Order</h3>
                <button type="button" onclick="document.getElementById('stage-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('pages.sales.work-orders.stage-pick-ticket', [$sale, $workOrder]) }}">
                @csrf
                <div class="space-y-4 px-6 py-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        A staging pick ticket will be created for the materials linked to this work order.
                        Optionally add notes for the warehouse team.
                    </p>

                    @if ($materialWarnings->isNotEmpty())
                        <div class="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 dark:border-yellow-700 dark:bg-yellow-900/20">
                            <div class="flex items-start gap-2">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                <div>
                                    <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-300">Stock not fully allocated for {{ $materialWarnings->count() }} item(s)</p>
                                    <ul class="mt-1.5 space-y-0.5">
                                        @foreach ($materialWarnings as $w)
                                            <li class="text-xs text-yellow-700 dark:text-yellow-400">
                                                <span class="font-medium">{{ $w['name'] }}</span>
                                                — need {{ rtrim(rtrim(number_format($w['needed'], 2), '0'), '.') }} {{ $w['unit'] }},
                                                allocated {{ rtrim(rtrim(number_format($w['allocated'], 2), '0'), '.') }}
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-1.5 text-xs text-yellow-600 dark:text-yellow-500">You can still stage, but make sure these materials are physically in the warehouse.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Warehouse Notes <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea name="staging_notes" rows="3"
                                  placeholder="e.g. Deliver to the loading bay. Handle with care."
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-orange-500 focus:ring-orange-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">{{ old('staging_notes') }}</textarea>
                    </div>
                    <div class="rounded-md bg-orange-50 px-3 py-2 text-xs text-orange-700 dark:bg-orange-900/20 dark:text-orange-300">
                        Staged by: <strong>{{ auth()->user()->name }}</strong> · {{ now()->format('M j, Y') }}
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button"
                            onclick="document.getElementById('stage-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white hover:bg-orange-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Stage Work Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Unstage Pick Ticket Modal (from WO page) --}}
    @if ($stagingPickTicket && $stagingPickTicket->status === 'staged')
    <div id="wo-unstage-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="w-full max-w-md rounded-lg bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Unstage Pick Ticket</h3>
                <button type="button" onclick="document.getElementById('wo-unstage-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('pages.warehouse.pick-tickets.unstage', $stagingPickTicket) }}">
                @csrf
                <div class="space-y-4 px-6 py-4">
                    <div class="rounded-md bg-gray-50 px-3 py-2.5 text-xs text-gray-600 dark:bg-gray-700/50 dark:text-gray-400 space-y-1">
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">PT #:</span>
                            {{ $stagingPickTicket->pt_number }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700 dark:text-gray-300">Staged by:</span>
                            {{ $stagingPickTicket->creator?->name ?? '—' }}
                            · {{ $stagingPickTicket->created_at->format('M j, Y g:i a') }}
                        </div>
                        @if ($stagingPickTicket->staging_notes)
                            <div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">Staging notes:</span>
                                {{ $stagingPickTicket->staging_notes }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Reason for unstaging <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <textarea name="unstage_reason" rows="3"
                                  placeholder="e.g. Materials not yet available. Rescheduling install date."
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"></textarea>
                    </div>
                    <div class="rounded-md bg-red-50 px-3 py-2 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-400">
                        Unstaged by: <strong>{{ auth()->user()->name }}</strong> · {{ now()->format('M j, Y') }}
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button"
                            onclick="document.getElementById('wo-unstage-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Unstage
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

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
                    {{-- CC Addresses --}}
                    <div x-data="{ ccEmails: [], ccInput: '' }">
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">CC <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                        @if($customerContacts->isNotEmpty())
                        <div class="mb-2">
                            <p class="text-xs text-gray-500 mb-1.5">Quick-add from contacts:</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($customerContacts->filter(fn($c) => $c->email) as $contact)
                                <button type="button"
                                        @click="if(!ccEmails.includes('{{ $contact->email }}')) { ccEmails.push('{{ $contact->email }}') }"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full border border-gray-300 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors">
                                    {{ $contact->name }}@if($contact->title) <span class="text-gray-400">&middot; {{ $contact->title }}</span>@endif
                                </button>
                                @endforeach
                            </div>
                        </div>
                        @endif
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
                                   class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <button type="button"
                                    @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                    class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-700">
                                Add
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                        <input type="text" name="subject"
                               value="{{ old('subject', $emailSubject) }}"
                               class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                               required>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                        <textarea name="body" rows="7"
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                  required>{{ old('body', $emailBody) }}</textarea>
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
