{{-- resources/views/pages/showroom-visits/index.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Showroom Visits</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Customer appointments at the showroom for flooring selections.</p>
                </div>
                @can('create showroom visits')
                    <a href="{{ route('pages.showroom-visits.create') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        + Book Showroom Visit
                    </a>
                @endcan
            </div>

            {{-- Change requests needing attention --}}
            @if ($changeRequestCount > 0)
                <a href="{{ route('pages.showroom-visits.index', ['scope' => 'all', 'status' => 'change_requested']) }}"
                   class="block p-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 hover:bg-amber-100">
                    <strong>{{ $changeRequestCount }} {{ Str::plural('customer', $changeRequestCount) }}</strong>
                    {{ $changeRequestCount === 1 ? 'has' : 'have' }} asked to change their showroom visit time — click to review and reschedule.
                </a>
            @endif

            {{-- Filters --}}
            <form method="GET" action="{{ route('pages.showroom-visits.index') }}"
                  class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Search</label>
                        <input type="text" name="q" value="{{ $q }}" placeholder="Customer, email, phone, job #..."
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Show</label>
                        <select name="scope" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            <option value="upcoming" @selected($scope === 'upcoming')>Upcoming</option>
                            <option value="past" @selected($scope === 'past')>Past</option>
                            <option value="all" @selected($scope === 'all')>All</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                        <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            <option value="">All Statuses</option>
                            @foreach (\App\Models\ShowroomVisit::STATUS_LABELS as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Host</label>
                        <select name="host_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            <option value="">Anyone</option>
                            @foreach ($hosts as $h)
                                <option value="{{ $h->id }}" @selected((string) $hostId === (string) $h->id)>{{ $h->first_name }} {{ $h->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2 flex gap-2">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">Filter</button>
                        <a href="{{ route('pages.showroom-visits.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Reset</a>
                    </div>
                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b dark:bg-gray-700 dark:text-gray-300">
                            <tr>
                                <th class="px-6 py-3">When</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Job</th>
                                <th class="px-6 py-3">Host</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-center">Calendar</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($visits as $visit)
                                <tr class="border-b last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-3 whitespace-nowrap">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $visit->scheduled_at->format('D, M j, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $visit->scheduled_at->format('g:i A') }} – {{ $visit->endsAt()->format('g:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $visit->contact_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $visit->contact_phone }}{{ $visit->contact_phone && $visit->contact_email ? ' · ' : '' }}{{ $visit->contact_email }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        @if ($visit->opportunity)
                                            <a href="{{ route('pages.opportunities.show', $visit->opportunity_id) }}" class="text-blue-600 hover:underline">
                                                {{ $visit->opportunity->job_no ?: 'Opportunity #' . $visit->opportunity_id }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">{{ $visit->hostName() ?: '—' }}</td>
                                    <td class="px-6 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $visit->statusColor() }}">
                                            {{ $visit->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($visit->calendar_event_id)
                                            <span title="On the Team RM calendar" class="text-green-600">●</span>
                                        @else
                                            <span title="Not on a calendar" class="text-gray-300">○</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('pages.showroom-visits.show', $visit) }}" class="font-medium text-blue-600 hover:underline">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center text-gray-500">No showroom visits found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($visits->hasPages())
                    <div class="p-4 border-t dark:border-gray-700">{{ $visits->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
