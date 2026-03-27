{{-- resources/views/pages/rfms/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Requests for Measure</h1>
                    <p class="text-sm text-gray-600 mt-1">Search and filter all RFMs across all opportunities.</p>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('pages.rfms.index') }}"
                  class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                    {{-- Search --}}
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                            </div>
                            <input type="text" name="q" value="{{ $q }}"
                                   placeholder="Customer, estimator, job #, address..."
                                   class="block w-full pl-10 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Statuses</option>
                            @foreach ($statusOptions as $opt)
                                <option value="{{ $opt }}" @selected($status === $opt)>
                                    {{ ucfirst($opt) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Estimator --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estimator</label>
                        <select name="estimator_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Estimators</option>
                            @foreach ($estimators as $emp)
                                <option value="{{ $emp->id }}" @selected($estimatorId == $emp->id)>
                                    {{ $emp->first_name }} {{ $emp->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Flooring Type --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Flooring Type</label>
                        <select name="flooring_type"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Types</option>
                            @foreach ($flooringTypes as $type)
                                <option value="{{ $type }}" @selected($flooringType === $type)>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled From</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    {{-- Date To --}}
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Scheduled To</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    {{-- Buttons + count --}}
                    <div class="md:col-span-6 flex flex-wrap items-end gap-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                            Apply Filters
                        </button>

                        <a href="{{ route('pages.rfms.index') }}"
                           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            Reset
                        </a>

                        <div class="ml-auto text-sm text-gray-600">
                            Showing <span class="font-semibold">{{ $rfms->firstItem() ?? 0 }}</span>
                            to <span class="font-semibold">{{ $rfms->lastItem() ?? 0 }}</span>
                            of <span class="font-semibold">{{ $rfms->total() }}</span>
                        </div>
                    </div>

                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Job</th>
                                <th class="px-6 py-3">Site Address</th>
                                <th class="px-6 py-3">Flooring</th>
                                <th class="px-6 py-3">Estimator</th>
                                <th class="px-6 py-3">Scheduled</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-center">Calendar</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rfms as $rfm)
                                @php
                                    $statusBadge = match ($rfm->status) {
                                        'confirmed'  => 'bg-blue-100 text-blue-800',
                                        'completed'  => 'bg-green-100 text-green-800',
                                        'cancelled'  => 'bg-red-100 text-red-800',
                                        default      => 'bg-gray-100 text-gray-700', // pending
                                    };

                                    $customerName = $rfm->parentCustomer?->company_name
                                        ?: $rfm->parentCustomer?->name
                                        ?: '—';

                                    $fullAddress = implode(', ', array_filter([
                                        $rfm->site_address,
                                        $rfm->site_city,
                                        $rfm->site_postal_code,
                                    ]));
                                @endphp

                                <tr class="bg-white border-b hover:bg-gray-50">

                                    {{-- Customer --}}
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $customerName }}
                                        @if ($rfm->opportunity?->projectManager)
                                            <div class="text-xs text-gray-500 font-normal">
                                                PM: {{ $rfm->opportunity->projectManager->name }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Job --}}
                                    <td class="px-6 py-4">
                                        @if ($rfm->opportunity)
                                            <a href="{{ route('pages.opportunities.show', $rfm->opportunity) }}"
                                               class="text-blue-700 hover:underline font-medium">
                                                {{ $rfm->opportunity->job_no ?: 'Opp #'.$rfm->opportunity->id }}
                                            </a>
                                            @if ($rfm->opportunity->job_name)
                                                <div class="text-xs text-gray-500">{{ $rfm->opportunity->job_name }}</div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Site Address --}}
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $fullAddress ?: '—' }}
                                    </td>

                                    {{-- Flooring --}}
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ((array) $rfm->flooring_type as $type)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    {{ $type }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    {{-- Estimator --}}
                                    <td class="px-6 py-4 text-gray-600">
                                        @if ($rfm->estimator)
                                            {{ $rfm->estimator->first_name }} {{ $rfm->estimator->last_name }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>

                                    {{-- Scheduled --}}
                                    <td class="px-6 py-4 text-gray-600 whitespace-nowrap">
                                        {{ $rfm->scheduled_at->format('M j, Y') }}
                                        <div class="text-xs text-gray-500">{{ $rfm->scheduled_at->format('g:i A') }}</div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusBadge }}">
                                            {{ ucfirst($rfm->status) }}
                                        </span>
                                    </td>

                                    {{-- Calendar --}}
                                    <td class="px-6 py-4 text-center">
                                        @if ($rfm->calendar_event_id)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-fuchsia-100 text-fuchsia-800">
                                                Synced
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>

                                    {{-- Action --}}
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        @if ($rfm->opportunity)
                                            <a href="{{ route('pages.opportunities.rfms.show', [$rfm->opportunity, $rfm]) }}"
                                               class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                                                View
                                            </a>
                                        @endif
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                        No RFMs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t bg-white">
                    {{ $rfms->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
