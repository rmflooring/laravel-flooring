<x-app-layout>
    <div class="py-8">
        <div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('admin.vendors.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Vendors</a>
                <span>/</span>
                <a href="{{ route('admin.vendors.show', $vendor) }}" class="hover:text-gray-700 dark:hover:text-gray-200">{{ $vendor->company_name }}</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">Transactions</span>
            </nav>

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $vendor->company_name }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All financial transactions — bills, credits, and net balance</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('create bills')
                    <a href="{{ route('admin.bills.create') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        + New Bill
                    </a>
                    @endcan
                    @can('create vendor credits')
                    <a href="{{ route('admin.vendor-credits.create', ['vendor_id' => $vendor->id]) }}"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-sm font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                        + Credit Memo
                    </a>
                    @endcan
                    <a href="{{ route('admin.vendors.show', $vendor) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        ← Vendor
                    </a>
                </div>
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Billed</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalBills, 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">All non-voided bills</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wide">Outstanding</p>
                    <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-400">${{ number_format($outstandingBills, 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Pending &amp; overdue bills</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-green-200 dark:border-green-800 rounded-lg p-5">
                    <p class="text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wide">Open Credits</p>
                    <p class="mt-1 text-2xl font-bold text-green-700 dark:text-green-400">−${{ number_format($totalCredits, 2) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Credit memos reducing balance</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border {{ $netBalance > 0 ? 'border-amber-200 dark:border-amber-800' : 'border-green-200 dark:border-green-800' }} rounded-lg p-5">
                    <p class="text-xs font-medium {{ $netBalance > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-green-700 dark:text-green-400' }} uppercase tracking-wide">Net Owing</p>
                    <p class="mt-1 text-2xl font-bold {{ $netBalance > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-green-700 dark:text-green-400' }}">
                        ${{ number_format(max(0, $netBalance), 2) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Outstanding minus open credits</p>
                </div>
            </div>

            {{-- Filter block --}}
            <form method="GET" action="{{ route('admin.vendors.transactions', $vendor) }}"
                  class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex flex-wrap gap-3 items-end">

                    {{-- Search --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}"
                               placeholder="Ref #, credit memo #…"
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-48">
                    </div>

                    {{-- Type --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Type</label>
                        <select name="type"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                onchange="this.form.submit()">
                            <option value="all"    {{ $filters['type'] === 'all'     ? 'selected' : '' }}>All types</option>
                            <option value="bills"  {{ $filters['type'] === 'bills'   ? 'selected' : '' }}>Bills only</option>
                            <option value="credits"{{ $filters['type'] === 'credits' ? 'selected' : '' }}>Credit memos only</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status"
                                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">All statuses</option>
                            @if ($filters['type'] !== 'credits')
                                <optgroup label="Bill statuses">
                                    @foreach (\App\Models\Bill::STATUSES as $val => $label)
                                        <option value="{{ $val }}" {{ $filters['status'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @if ($filters['type'] !== 'bills')
                                <optgroup label="Credit statuses">
                                    <option value="open"   {{ $filters['status'] === 'open'   ? 'selected' : '' }}>Open</option>
                                    <option value="voided" {{ $filters['status'] === 'voided' ? 'selected' : '' }}>Voided</option>
                                </optgroup>
                            @endif
                        </select>
                    </div>

                    {{-- Date range --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">From</label>
                        <input type="date" name="date_from" value="{{ $filters['dateFrom'] }}"
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">To</label>
                        <input type="date" name="date_to" value="{{ $filters['dateTo'] }}"
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>

                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800 dark:bg-gray-600 dark:hover:bg-gray-500">
                        Filter
                    </button>

                    @if (array_filter([$filters['search'], $filters['status'], $filters['dateFrom'], $filters['dateTo']]) || $filters['type'] !== 'all')
                        <a href="{{ route('admin.vendors.transactions', $vendor) }}"
                           class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Transactions table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                @if ($transactions->isEmpty())
                    <div class="py-16 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M3 12h18M3 18h18"/>
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No transactions found.</p>
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Number</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subtotal</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tax</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @php $filteredTotal = 0; @endphp
                            @foreach ($transactions as $txn)
                                @php
                                    $isBill   = $txn['type'] === 'bill';
                                    $isVoided = $txn['status'] === 'voided';
                                    $isOverdue = $isBill && $txn['status'] === 'overdue';

                                    if (!$isVoided) {
                                        $filteredTotal += $isBill ? $txn['amount'] : -$txn['amount'];
                                    }

                                    $statusBadge = match(true) {
                                        $txn['status'] === 'voided'   => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                        $txn['status'] === 'overdue'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                        $txn['status'] === 'pending'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        $txn['status'] === 'approved' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        $txn['status'] === 'open'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                        $txn['status'] === 'draft'    => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $isVoided ? 'opacity-50' : '' }} {{ $isOverdue ? 'bg-red-50 dark:bg-red-900/10' : '' }}">

                                    {{-- Date --}}
                                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                        {{ $txn['date']->format('M j, Y') }}
                                    </td>

                                    {{-- Type badge --}}
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        @if ($isBill)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                Bill
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                Credit
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Number (linked) --}}
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <a href="{{ $txn['link'] }}"
                                           class="font-mono text-sm font-semibold {{ $isBill ? 'text-blue-600 hover:underline dark:text-blue-400' : 'text-green-700 hover:underline dark:text-green-400' }}">
                                            {{ $txn['number'] }}
                                        </a>
                                    </td>

                                    {{-- Description --}}
                                    <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $txn['description'] }}
                                    </td>

                                    {{-- Subtotal --}}
                                    <td class="px-5 py-3 text-right text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ $isBill ? '' : '−' }}${{ number_format($txn['subtotal'], 2) }}
                                    </td>

                                    {{-- Tax --}}
                                    <td class="px-5 py-3 text-right text-sm text-gray-500 dark:text-gray-500 whitespace-nowrap">
                                        ${{ number_format($txn['tax_amount'], 2) }}
                                    </td>

                                    {{-- Total --}}
                                    <td class="px-5 py-3 text-right whitespace-nowrap">
                                        @if ($isBill)
                                            <span class="text-sm font-semibold {{ $isOverdue ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                                ${{ number_format($txn['amount'], 2) }}
                                            </span>
                                        @else
                                            <span class="text-sm font-semibold text-green-700 dark:text-green-400">
                                                −${{ number_format($txn['amount'], 2) }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-5 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                            {{ $txn['status_label'] }}
                                        </span>
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40">
                            <tr>
                                <td colspan="6" class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    Net (filtered, excl. voided)
                                </td>
                                <td class="px-5 py-3 text-right text-sm font-bold whitespace-nowrap {{ $filteredTotal >= 0 ? 'text-gray-900 dark:text-white' : 'text-green-700 dark:text-green-400' }}">
                                    {{ $filteredTotal < 0 ? '−' : '' }}${{ number_format(abs($filteredTotal), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
