<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Opportunities</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Search, filter, and manage opportunities.
                    </p>
                </div>

                <a href="{{ route('pages.opportunities.create') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                    + Create Opportunity
                </a>
            </div>

            {{-- Filters --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6">
                <form method="GET" action="{{ route('pages.opportunities.index') }}" class="p-4 md:p-6">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

                        {{-- Search --}}
                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input type="text"
                                   name="q"
                                   value="{{ request('q') }}"
                                   placeholder="Job #, parent, job site, PM, sales person…"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        {{-- Status --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Parent Customer --}}
                        <div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-1">Parent Customer</label>
                            <select name="parent_customer_id"
                                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                                <option value="">All parents</option>
                                @foreach ($parentCustomers as $c)
                                    <option value="{{ $c->id }}" {{ (string)request('parent_customer_id') === (string)$c->id ? 'selected' : '' }}>
                                        {{ $c->company_name ?: $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
						
						{{-- Sort --}}
<div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-1">Sort</label>
    <select name="sort"
            class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
        <option value="updated_desc" {{ request('sort', 'updated_desc') === 'updated_desc' ? 'selected' : '' }}>
            Updated (newest)
        </option>
        <option value="updated_asc" {{ request('sort', 'updated_desc') === 'updated_asc' ? 'selected' : '' }}>
            Updated (oldest)
        </option>
        <option value="job_no_asc" {{ request('sort', 'updated_desc') === 'job_no_asc' ? 'selected' : '' }}>
            Job # (A → Z)
        </option>
        <option value="job_no_desc" {{ request('sort', 'updated_desc') === 'job_no_desc' ? 'selected' : '' }}>
            Job # (Z → A)
        </option>
    </select>
</div>
						{{-- Project Manager --}}
<div class="md:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-1">Project Manager</label>
    <select name="project_manager_id"
        onchange="this.form.submit()"
        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
        <option value="">All PMs</option>
        @foreach ($projectManagers as $pm)
            <option value="{{ $pm->id }}" {{ (string)request('project_manager_id') === (string)$pm->id ? 'selected' : '' }}>
                {{ $pm->name }}
            </option>
        @endforeach
    </select>
</div>

                        {{-- Actions --}}
                        <div class="md:col-span-1 flex gap-2">
                            <button type="submit"
                                    class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                Apply
                            </button>
                        </div>

                        <div class="md:col-span-12">
                            <a href="{{ route('pages.opportunities.index') }}"
                               class="text-sm text-gray-600 hover:text-gray-900 underline">
                                Clear filters
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

                <div class="px-4 md:px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium text-gray-900">{{ $opportunities->count() }}</span>
                        of <span class="font-medium text-gray-900">{{ $opportunities->total() }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">Job #</th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">Parent Customer</th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">Job Site</th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">PM</th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">Status</th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">Updated</th>
                                <th class="text-right font-semibold px-4 md:px-6 py-3">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($opportunities as $opp)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 md:px-6 py-3 whitespace-nowrap">
                                        <span class="font-medium text-gray-900">
                                            {{ $opp->job_no ?: '—' }}
                                        </span>
                                    </td>

                                    <td class="px-4 md:px-6 py-3">
                                        {{ $opp->parentCustomer?->company_name ?: $opp->parentCustomer?->name ?: '—' }}
                                    </td>

                                    <td class="px-4 md:px-6 py-3">
                                        {{ $opp->jobSiteCustomer?->company_name ?: $opp->jobSiteCustomer?->name ?: '—' }}
                                    </td>

                                    <td class="px-4 md:px-6 py-3">
                                        {{ $opp->projectManager?->name ?: '—' }}
                                    </td>

                                    <td class="px-4 md:px-6 py-3 whitespace-nowrap">
                                        @php
    $statusClasses = match($opp->status) {
        'New' => 'bg-blue-100 text-blue-800',
        'In Progress' => 'bg-yellow-100 text-yellow-800',
        'Awaiting Site Measure' => 'bg-purple-100 text-purple-800',
        'Estimate Sent' => 'bg-indigo-100 text-indigo-800',
        'Approved' => 'bg-green-100 text-green-800',
        'Lost' => 'bg-red-100 text-red-800',
        'Closed' => 'bg-gray-200 text-gray-800',
        default => 'bg-gray-100 text-gray-800',
    };
@endphp

<a href="{{ route('pages.opportunities.index', array_merge(request()->query(), ['status' => $opp->status])) }}"
   class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClasses }} hover:underline">
    {{ $opp->status ?: '—' }}
</a>
                                    </td>

                                    <td class="px-4 md:px-6 py-3 whitespace-nowrap text-gray-600">
                                        {{ optional($opp->updated_at)->format('Y-m-d') ?: '—' }}
                                    </td>

                                    <td class="px-4 md:px-6 py-3 text-right whitespace-nowrap">
    <div class="inline-flex items-center gap-2">
        <a href="{{ route('pages.opportunities.show', $opp->id) }}"
           class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            View
        </a>

        <a href="{{ route('pages.opportunities.edit', $opp->id) }}"
           class="inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
            Edit
        </a>
    </div>
</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 md:px-6 py-10 text-center text-gray-600">
                                        No opportunities found. Try adjusting your search or filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-4 md:px-6 py-4 border-t border-gray-200">
                    {{ $opportunities->links() }}
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
