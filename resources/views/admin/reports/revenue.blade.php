<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1200px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Reports</a>
                            <h1 class="text-3xl font-bold">Revenue Summary — {{ $year }}</h1>
                        </div>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                           class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Export CSV
                        </a>
                    </div>

                    {{-- Year Selector --}}
                    <form method="GET" action="{{ route('admin.reports.revenue') }}" class="mb-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="flex items-end gap-4">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Year</label>
                                    <select name="year" onchange="this.form.submit()"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5">
                                        @foreach($availableYears as $yr)
                                            <option value="{{ $yr }}" @selected($yr == $year)>{{ $yr }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Year Totals --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Invoices</p>
                            <p class="text-2xl font-bold text-gray-800">{{ number_format($yearTotals['invoice_count']) }}</p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-xs text-blue-600 uppercase font-semibold mb-1">Total Invoiced</p>
                            <p class="text-2xl font-bold text-blue-800">${{ number_format($yearTotals['total_invoiced'], 2) }}</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-xs text-green-600 uppercase font-semibold mb-1">Total Paid</p>
                            <p class="text-2xl font-bold text-green-800">${{ number_format($yearTotals['total_paid'], 2) }}</p>
                        </div>
                        <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                            <p class="text-xs text-orange-600 uppercase font-semibold mb-1">Outstanding</p>
                            <p class="text-2xl font-bold text-orange-800">${{ number_format($yearTotals['outstanding'], 2) }}</p>
                        </div>
                    </div>

                    {{-- Monthly Table --}}
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Month</th>
                                    <th class="px-4 py-3 text-center"># Invoices</th>
                                    <th class="px-4 py-3 text-right">Total Invoiced</th>
                                    <th class="px-4 py-3 text-right">Total Paid</th>
                                    <th class="px-4 py-3 text-right">Outstanding</th>
                                    <th class="px-4 py-3">Collection Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthly as $row)
                                    @php
                                        $rate = $row->total_invoiced > 0 ? ($row->total_paid / $row->total_invoiced) * 100 : 0;
                                        $barColor = $rate >= 90 ? '#16a34a' : ($rate >= 60 ? '#d97706' : '#dc2626');
                                    @endphp
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $row->month_label }}</td>
                                        <td class="px-4 py-3 text-center">{{ number_format($row->invoice_count) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($row->total_invoiced, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-green-700">${{ number_format($row->total_paid, 2) }}</td>
                                        <td class="px-4 py-3 text-right {{ $row->outstanding > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                                            ${{ number_format($row->outstanding, 2) }}
                                        </td>
                                        <td class="px-4 py-3 min-w-[140px]">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                                    <div class="h-2 rounded-full" style="width: {{ min(100, $rate) }}%; background-color: {{ $barColor }};"></div>
                                                </div>
                                                <span class="text-xs font-medium" style="color: {{ $barColor }};">{{ number_format($rate, 0) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">No invoice data for {{ $year }}.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($monthly->isNotEmpty())
                                <tfoot class="text-xs text-gray-700 uppercase bg-gray-50 font-bold border-t-2 border-gray-300">
                                    <tr>
                                        <td class="px-4 py-3">Year Total</td>
                                        <td class="px-4 py-3 text-center">{{ number_format($yearTotals['invoice_count']) }}</td>
                                        <td class="px-4 py-3 text-right">${{ number_format($yearTotals['total_invoiced'], 2) }}</td>
                                        <td class="px-4 py-3 text-right text-green-700">${{ number_format($yearTotals['total_paid'], 2) }}</td>
                                        <td class="px-4 py-3 text-right text-orange-600">${{ number_format($yearTotals['outstanding'], 2) }}</td>
                                        <td class="px-4 py-3">
                                            @php
                                                $totalRate = $yearTotals['total_invoiced'] > 0 ? ($yearTotals['total_paid'] / $yearTotals['total_invoiced']) * 100 : 0;
                                            @endphp
                                            {{ number_format($totalRate, 0) }}%
                                        </td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
