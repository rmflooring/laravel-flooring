<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Sale {{ $sale->sale_number ?? ('#' . $sale->id) }}
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Read-only view of the sale record.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.sales.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        Back
                    </a>

                    <a href="{{ route('pages.sales.edit', $sale) }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
                        Edit
                    </a>
                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-send-email-modal'))"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-purple-700 rounded-lg hover:bg-purple-800 focus:outline-none focus:ring-4 focus:ring-purple-300">
                        Send Email
                    </button>
                </div>
            </div>

            {{-- Summary cards --}}
            @php
                $revisedContract = (float) ($sale->revised_contract_total ?? 0);
                $lockedGrand     = (float) ($sale->locked_grand_total ?? 0);
                $grandTotal      = (float) ($sale->grand_total ?? 0);

                // Treat 0.00 as "not set" (common when column has a default), so we can fall back.
                $revisedContractDisplay = $revisedContract != 0.0
                    ? $revisedContract
                    : ($lockedGrand != 0.0 ? $lockedGrand : $grandTotal);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Status</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $sale->status ?? '—' }}</div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Locked</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ $sale->locked_at ? 'Yes' : 'No' }}
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Revised Contract Total</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ number_format($revisedContractDisplay, 2) }}
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Invoiced Total</div>
                    <div class="mt-1 font-semibold text-gray-900">
                        {{ number_format((float) ($sale->invoiced_total ?? 0), 2) }}
                    </div>
                </div>
            </div>

            {{-- Details --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div class="font-medium text-gray-900">{{ $sale->customer_name ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">PM</div>
                        <div class="font-medium text-gray-900">{{ $sale->pm_name ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Job</div>
                        <div class="font-medium text-gray-900">{{ $sale->job_name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $sale->job_no ?? '' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Job Address</div>
                        <div class="font-medium text-gray-900">{{ $sale->job_address ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Source Estimate #</div>
                        <div class="font-medium text-gray-900">{{ $sale->source_estimate_number ?? '—' }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Notes</div>
                        <div class="font-medium text-gray-900 whitespace-pre-line">{{ $sale->notes ?? '—' }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

{{-- Send Email Modal --}}
@php $homeownerEmail = $sale->sourceEstimate?->homeowner_email ?? ''; @endphp
<div x-data="{ open: false }"
     @open-send-email-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.5)">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" @click.outside="open = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h5 class="text-base font-semibold text-gray-800">Send Sale Email</h5>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('pages.sales.send-email', $sale) }}">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                @if (! $homeownerEmail)
                    <div class="p-3 text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
                        No homeowner email found on the source estimate. Enter a recipient below.
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="email" name="to" value="{{ $homeownerEmail }}"
                           placeholder="customer@example.com"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
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
                <p class="text-xs text-gray-400">
                    @if (auth()->user()->microsoftAccount?->mail_connected)
                        Sending from <strong>{{ auth()->user()->microsoftAccount->email }}</strong> via your personal MS365 account (Track 2).
                    @else
                        Sending from the shared mailbox via Track 1.
                    @endif
                </p>
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-purple-700 rounded-lg hover:bg-purple-800">
                    Send Sale Email
                </button>
            </div>
        </form>
    </div>
</div>

</x-admin-layout>
