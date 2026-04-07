<x-app-layout>
    <div class="py-8">
        <div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AP Aging Report</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Outstanding payables by age — as of {{ now()->format('M j, Y') }}</p>
                </div>
                <a href="{{ route('admin.bills.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    ← Payables
                </a>
            </div>

            {{-- Filter --}}
            <form method="GET" action="{{ route('admin.bills.aging') }}" class="flex items-center gap-3">
                <select name="bill_type"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all" @selected($billType === 'all')>All Types</option>
                    <option value="vendor" @selected($billType === 'vendor')>Vendors Only</option>
                    <option value="installer" @selected($billType === 'installer')>Installers Only</option>
                </select>
                <button type="submit"
                    class="px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Apply
                </button>
            </form>

            @if (empty($aging))
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-12 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No outstanding payables found.</p>
                </div>
            @else

            {{-- Aging Table --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Payee</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3 text-right">Current</th>
                                <th class="px-4 py-3 text-right">1–30 days</th>
                                <th class="px-4 py-3 text-right">31–60 days</th>
                                <th class="px-4 py-3 text-right">61–90 days</th>
                                <th class="px-4 py-3 text-right">90+ days</th>
                                <th class="px-4 py-3 text-right font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($aging as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                <td class="px-4 py-3">
                                    @if ($row['type'] === 'vendor')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">Vendor</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">Installer</span>
                                    @endif
                                </td>
                                @foreach ($buckets as $bucket)
                                <td class="px-4 py-3 text-right @if($row[$bucket] > 0 && $bucket !== 'current') text-red-600 dark:text-red-400 @endif">
                                    {{ $row[$bucket] > 0 ? '$'.number_format($row[$bucket], 2) : '—' }}
                                </td>
                                @endforeach
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">
                                    ${{ number_format($row['total'], 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-700 border-t-2 border-gray-200 dark:border-gray-600">
                            <tr class="font-bold text-gray-900 dark:text-white">
                                <td class="px-4 py-3" colspan="2">Total</td>
                                @foreach ($buckets as $bucket)
                                <td class="px-4 py-3 text-right @if($totals[$bucket] > 0 && $bucket !== 'current') text-red-600 dark:text-red-400 @endif">
                                    {{ $totals[$bucket] > 0 ? '$'.number_format($totals[$bucket], 2) : '—' }}
                                </td>
                                @endforeach
                                <td class="px-4 py-3 text-right">${{ number_format($totals['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Summary chips --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                @php
                    $chipColors = [
                        'current' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300',
                        '1_30'    => 'bg-amber-50 border-amber-200 text-amber-800 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-300',
                        '31_60'   => 'bg-orange-50 border-orange-200 text-orange-800 dark:bg-orange-900/20 dark:border-orange-800 dark:text-orange-300',
                        '61_90'   => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300',
                        '90_plus' => 'bg-red-100 border-red-300 text-red-900 dark:bg-red-900/30 dark:border-red-700 dark:text-red-200',
                    ];
                @endphp
                @foreach ($buckets as $bucket)
                <div class="border rounded-lg p-4 {{ $chipColors[$bucket] }}">
                    <p class="text-xs font-semibold uppercase tracking-wider">{{ $bucketLabels[$bucket] }}</p>
                    <p class="text-xl font-bold mt-1">${{ number_format($totals[$bucket], 2) }}</p>
                </div>
                @endforeach
            </div>

            @endif

        </div>
    </div>
</x-app-layout>
