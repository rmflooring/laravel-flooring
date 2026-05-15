<x-app-layout>
    <div class="py-8">
        <div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Vendor Credit Memos</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Credits received from vendors — reduces AP balance</p>
                </div>
                @can('create vendor credits')
                <a href="{{ route('admin.vendor-credits.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    New Credit Memo
                </a>
                @endcan
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
            @endif

            {{-- Stat card --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-green-200 dark:border-green-800 rounded-lg p-5">
                    <p class="text-sm text-green-700 dark:text-green-400">Total Open Credits</p>
                    <p class="mt-1 text-2xl font-bold text-green-800 dark:text-green-300">${{ number_format($totalOpen, 2) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Reduces AP payables balance</p>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.vendor-credits.index') }}"
                  class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                               placeholder="VCM #, ref #, vendor…"
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Vendor</label>
                        <select name="vendor_id"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">All vendors</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}" {{ ($filters['vendor_id'] ?? '') == $v->id ? 'selected' : '' }}>
                                    {{ $v->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select name="status"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">Open only</option>
                            <option value="voided" {{ ($filters['status'] ?? '') === 'voided' ? 'selected' : '' }}>Voided</option>
                        </select>
                    </div>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500">
                        Filter
                    </button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.vendor-credits.index') }}"
                           class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Credit Memo #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Ref #</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subtotal</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tax</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Credit</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($credits as $credit)
                            @php
                                $statusBadge = match($credit->status) {
                                    'open'   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'voided' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    default  => 'bg-gray-100 text-gray-600',
                                };
                            @endphp
                            <tr class="{{ $credit->status === 'voided' ? 'opacity-60' : '' }}">
                                <td class="px-6 py-3">
                                    <a href="{{ route('admin.vendor-credits.show', $credit) }}"
                                       class="font-mono font-semibold text-green-700 hover:underline dark:text-green-400">
                                        {{ $credit->credit_memo_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-900 dark:text-white">
                                    {{ $credit->vendor->company_name ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $credit->reference_number ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ $credit->date->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    ${{ number_format($credit->subtotal, 2) }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    ${{ number_format($credit->tax_amount, 2) }}
                                </td>
                                <td class="px-6 py-3 text-right text-sm font-bold text-green-700 dark:text-green-400 whitespace-nowrap">
                                    −${{ number_format($credit->grand_total, 2) }}
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                        {{ $credit->status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('admin.vendor-credits.show', $credit) }}"
                                       class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                        View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                    No credit memos found.
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
