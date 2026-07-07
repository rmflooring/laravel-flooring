<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1800px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Reports</a>
                            <h1 class="text-3xl font-bold">Unconverted Estimates</h1>
                        </div>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                           class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Export CSV
                        </a>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.reports.unconvertedEstimates') }}" class="mb-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="Estimate #, job, customer..."
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Sent Status</label>
                                    <select name="sent" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All</option>
                                        <option value="sent"     @selected(request('sent') === 'sent')>Sent to Customer</option>
                                        <option value="not_sent" @selected(request('sent') === 'not_sent')>Not Yet Sent</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Project Manager</label>
                                    <select name="pm_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All PMs</option>
                                        @foreach($pmNames as $pm)
                                            <option value="{{ $pm }}" @selected(request('pm_name') === $pm)>{{ $pm }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Estimator</label>
                                    <select name="estimator_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All Estimators</option>
                                        @foreach($estimators as $user)
                                            <option value="{{ $user->id }}" @selected(request('estimator_id') == $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date From</label>
                                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date To</label>
                                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                            </div>
                            <div class="flex items-center gap-3 mt-4">
                                <button type="submit"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2">
                                    Apply Filters
                                </button>
                                <a href="{{ route('admin.reports.unconvertedEstimates') }}"
                                   class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2">
                                    Reset
                                </a>
                                <div class="ml-auto flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Per page:</label>
                                    <select name="perPage" onchange="this.form.submit()"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2">
                                        @foreach([25, 50, 100] as $n)
                                            <option value="{{ $n }}" @selected(request('perPage', 25) == $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-2 gap-4 mb-6" style="max-width: 500px;">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Estimates</p>
                            <p class="text-2xl font-bold text-gray-800">{{ number_format($totals->total_count) }}</p>
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                            <p class="text-xs text-teal-600 uppercase font-semibold mb-1">Total Value</p>
                            <p class="text-2xl font-bold text-teal-800">${{ number_format($totals->total_value ?? 0, 2) }}</p>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Estimate #</th>
                                    <th class="px-4 py-3">Job Name</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Homeowner</th>
                                    <th class="px-4 py-3">PM</th>
                                    <th class="px-4 py-3">Estimator</th>
                                    <th class="px-4 py-3 text-right">Grand Total</th>
                                    <th class="px-4 py-3">Sent</th>
                                    <th class="px-4 py-3">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estimates as $estimate)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                            <a href="{{ route('pages.estimates.show', $estimate) }}" class="text-blue-600 hover:underline">
                                                {{ $estimate->estimate_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-gray-900">{{ $estimate->job_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->customer_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->homeowner_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->pm_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->creator?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($estimate->grand_total, 2) }}</td>
                                        <td class="px-4 py-3">
                                            @if($estimate->first_sent_at)
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Sent {{ $estimate->first_sent_at->format('M j, Y') }}
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                    Not sent
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $estimate->created_at->format('M j, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-8 text-center text-gray-400">No unconverted estimates found matching your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $estimates->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
