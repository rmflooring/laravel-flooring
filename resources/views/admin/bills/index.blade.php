<x-app-layout>
    <div class="py-8">
        <div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Accounts Payable</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vendor and installer bills outstanding</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.bills.aging') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                        </svg>
                        AP Aging
                    </a>
                    @can('create bills')
                    <a href="{{ route('admin.bills.create') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        New Bill
                    </a>
                    @endcan
                </div>
            </div>

            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
            @endif

            {{-- Stat Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Outstanding</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalOutstanding, 2) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Active bills (excl. voided &amp; approved)</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 rounded-lg p-5">
                    <p class="text-sm text-red-600 dark:text-red-400">Overdue</p>
                    <p class="mt-1 text-2xl font-bold text-red-700 dark:text-red-400">${{ number_format($totalOverdue, 2) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Past due date</p>
                </div>
                <div class="bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-800 rounded-lg p-5">
                    <p class="text-sm text-amber-600 dark:text-amber-400">Due This Week</p>
                    <p class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-400">${{ number_format($dueThisWeek, 2) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Next 7 days</p>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.bills.index') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400">Search</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Invoice #, vendor, PO#..."
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-56 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400">Status</label>
                        <select name="status"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $val => $label)
                                <option value="{{ $val }}" @selected(($filters['status'] ?? '') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400">Type</label>
                        <select name="bill_type"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">All types</option>
                            <option value="vendor" @selected(($filters['bill_type'] ?? '') === 'vendor')>Vendor</option>
                            <option value="installer" @selected(($filters['bill_type'] ?? '') === 'installer')>Installer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400">Due From</label>
                        <input type="date" name="due_from" value="{{ $filters['due_from'] ?? '' }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-1 text-xs font-medium text-gray-600 dark:text-gray-400">Due To</label>
                        <input type="date" name="due_to" value="{{ $filters['due_to'] ?? '' }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <button type="submit"
                        class="px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Filter
                    </button>
                    @if (array_filter($filters))
                        <a href="{{ route('admin.bills.index') }}"
                           class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            {{-- Bills Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                @if ($bills->isEmpty())
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                        </svg>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No bills found.</p>
                        @can('create bills')
                        <a href="{{ route('admin.bills.create') }}"
                           class="mt-4 inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            Record a Bill
                        </a>
                        @endcan
                    </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Invoice #</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Payee</th>
                                <th class="px-4 py-3">Linked To</th>
                                <th class="px-4 py-3">Bill Date</th>
                                <th class="px-4 py-3">Due Date</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($bills as $bill)
                            @php
                                $isOverdue = $bill->due_date && $bill->due_date->isPast() && ! in_array($bill->status, ['approved', 'voided']);
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $isOverdue ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">
                                    {{ $bill->reference_number }}
                                </td>
                                <td class="px-4 py-3">
                                    @if ($bill->bill_type === 'vendor')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">Vendor</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Installer</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $bill->payee_name }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                    @if ($bill->purchaseOrder)
                                        <a href="{{ route('pages.purchase-orders.show', $bill->purchaseOrder) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                            PO #{{ $bill->purchaseOrder->po_number }}
                                        </a>
                                    @elseif ($bill->workOrder)
                                        <a href="{{ route('pages.sales.work-orders.show', [$bill->workOrder->sale_id, $bill->workOrder]) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                            WO #{{ $bill->workOrder->wo_number }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $bill->bill_date->format('M j, Y') }}</td>
                                <td class="px-4 py-3">
                                    @if ($bill->due_date)
                                        <span class="{{ $isOverdue ? 'text-red-600 font-semibold dark:text-red-400' : '' }}">
                                            {{ $bill->due_date->format('M j, Y') }}
                                        </span>
                                        @if ($isOverdue)
                                            <span class="ml-1 text-xs text-red-500">({{ $bill->days_overdue }}d)</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                    ${{ number_format($bill->grand_total, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $colorMap = [
                                            'draft'    => 'gray',
                                            'pending'  => 'blue',
                                            'approved' => 'green',
                                            'overdue'  => 'red',
                                            'voided'   => 'gray',
                                        ];
                                        $c = $colorMap[$bill->status] ?? 'gray';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($c === 'gray') bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                        @elseif($c === 'blue') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                        @elseif($c === 'green') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                        @elseif($c === 'red') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300
                                        @endif">
                                        {{ $bill->status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.bills.show', $bill) }}"
                                       class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($bills->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
                        {{ $bills->links() }}
                    </div>
                @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
