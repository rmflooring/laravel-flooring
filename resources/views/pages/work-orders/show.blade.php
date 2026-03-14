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

            {{-- Items Table --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Labour Items</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Item</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Unit Cost</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @foreach ($workOrder->items as $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                        {{ $item->item_name }}
                                        @if($item->unit) <span class="text-xs text-gray-400 ml-1">{{ $item->unit }}</span> @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">{{ number_format($item->quantity, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">${{ number_format($item->cost_price, 2) }}</td>
                                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->cost_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <td colspan="3" class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">Grand Total</td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-gray-900 dark:text-white">${{ number_format($workOrder->grand_total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
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
