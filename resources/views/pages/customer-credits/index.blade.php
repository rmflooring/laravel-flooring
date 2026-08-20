<x-app-layout>
    <div class="py-8">
        <div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Customer Credits</h1>
                <p class="mt-1 text-sm text-gray-500">Store credit issued to customers — redeemable against a future sale or invoice</p>
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50">{{ session('error') }}</div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.customer-credits.index') }}"
                  class="bg-white border border-gray-200 rounded-lg p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status" class="rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">All</option>
                            <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="voided" {{ ($filters['status'] ?? '') === 'voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800">
                        Filter
                    </button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.customer-credits.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Credit #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Customer</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Remaining</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Notes</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($credits as $credit)
                            <tr class="{{ $credit->status === 'voided' ? 'opacity-60' : '' }}">
                                <td class="px-6 py-3 font-mono font-semibold text-gray-900">{{ $credit->credit_number }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <a href="{{ route('admin.customers.show', $credit->customer) }}" class="text-blue-600 hover:underline">
                                        {{ $credit->customer->company_name ?? $credit->customer->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700">${{ number_format($credit->amount, 2) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-bold {{ $credit->remaining_balance > 0 ? 'text-emerald-700' : 'text-gray-400' }}">
                                    ${{ number_format($credit->remaining_balance, 2) }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-{{ $credit->status_color }}-100 text-{{ $credit->status_color }}-700">
                                        {{ $credit->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $credit->notes ?: '—' }}</td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.customers.show', $credit->customer) }}" class="text-sm font-medium text-blue-600 hover:underline">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-400">
                                    No customer credits found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $credits->withQueryString()->links() }}

        </div>
    </div>
</x-app-layout>
