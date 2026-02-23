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
                </div>
            </div>

            {{-- Summary cards --}}
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
                        {{ number_format((float) ($sale->revised_contract_total ?? $sale->locked_grand_total ?? $sale->grand_total ?? 0), 2) }}
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
</x-admin-layout>
