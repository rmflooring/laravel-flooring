<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Flash Banners --}}
            @if (session('success'))
                <div class="mb-4 p-4 text-sm text-green-800 bg-green-100 border border-green-300 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 text-sm text-red-800 bg-red-100 border border-red-300 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

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
        @php $s = request('sort', 'updated_desc'); @endphp
        <option value="updated_desc"   {{ $s === 'updated_desc'   ? 'selected' : '' }}>Updated (newest)</option>
        <option value="updated_asc"    {{ $s === 'updated_asc'    ? 'selected' : '' }}>Updated (oldest)</option>
        <option value="job_no_asc"     {{ $s === 'job_no_asc'     ? 'selected' : '' }}>Job # (A → Z)</option>
        <option value="job_no_desc"    {{ $s === 'job_no_desc'    ? 'selected' : '' }}>Job # (Z → A)</option>
        <option value="parent_asc"     {{ $s === 'parent_asc'     ? 'selected' : '' }}>Parent (A → Z)</option>
        <option value="parent_desc"    {{ $s === 'parent_desc'    ? 'selected' : '' }}>Parent (Z → A)</option>
        <option value="job_site_asc"   {{ $s === 'job_site_asc'   ? 'selected' : '' }}>Job Site (A → Z)</option>
        <option value="job_site_desc"  {{ $s === 'job_site_desc'  ? 'selected' : '' }}>Job Site (Z → A)</option>
        <option value="pm_asc"         {{ $s === 'pm_asc'         ? 'selected' : '' }}>PM (A → Z)</option>
        <option value="pm_desc"        {{ $s === 'pm_desc'        ? 'selected' : '' }}>PM (Z → A)</option>
        <option value="status_asc"     {{ $s === 'status_asc'     ? 'selected' : '' }}>Status (A → Z)</option>
        <option value="status_desc"    {{ $s === 'status_desc'    ? 'selected' : '' }}>Status (Z → A)</option>
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

                @php
                    $qs = http_build_query(request()->only(['q', 'status', 'parent_customer_id', 'project_manager_id', 'sort']));

                    $currentSort   = request('sort', 'updated_desc');
                    $filterParams  = request()->only(['q', 'status', 'parent_customer_id', 'project_manager_id']);

                    $colSorts = [
                        'job_no'   => ['asc' => 'job_no_asc',    'desc' => 'job_no_desc'],
                        'parent'   => ['asc' => 'parent_asc',    'desc' => 'parent_desc'],
                        'job_site' => ['asc' => 'job_site_asc',  'desc' => 'job_site_desc'],
                        'pm'       => ['asc' => 'pm_asc',         'desc' => 'pm_desc'],
                        'status'   => ['asc' => 'status_asc',     'desc' => 'status_desc'],
                        'updated'  => ['asc' => 'updated_asc',    'desc' => 'updated_desc'],
                    ];

                    $sortLink = function($col) use ($currentSort, $filterParams, $colSorts) {
                        $sorts = $colSorts[$col];
                        $next  = ($currentSort === $sorts['asc']) ? $sorts['desc'] : $sorts['asc'];
                        return route('pages.opportunities.index', array_merge($filterParams, ['sort' => $next]));
                    };

                    $sortArrow = function($col) use ($currentSort, $colSorts) {
                        if ($currentSort === $colSorts[$col]['asc'])  return ' ↑';
                        if ($currentSort === $colSorts[$col]['desc']) return ' ↓';
                        return '';
                    };
                @endphp

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">
                                    <a href="{{ $sortLink('job_no') }}" class="hover:text-blue-600 whitespace-nowrap">Job #{{ $sortArrow('job_no') }}</a>
                                </th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">
                                    <a href="{{ $sortLink('parent') }}" class="hover:text-blue-600 whitespace-nowrap">Parent Customer{{ $sortArrow('parent') }}</a>
                                </th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">
                                    <a href="{{ $sortLink('job_site') }}" class="hover:text-blue-600 whitespace-nowrap">Job Site{{ $sortArrow('job_site') }}</a>
                                </th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">
                                    <a href="{{ $sortLink('pm') }}" class="hover:text-blue-600 whitespace-nowrap">PM{{ $sortArrow('pm') }}</a>
                                </th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">
                                    <a href="{{ $sortLink('status') }}" class="hover:text-blue-600 whitespace-nowrap">Status{{ $sortArrow('status') }}</a>
                                </th>
                                <th class="text-left font-semibold px-4 md:px-6 py-3">
                                    <a href="{{ $sortLink('updated') }}" class="hover:text-blue-600 whitespace-nowrap">Updated{{ $sortArrow('updated') }}</a>
                                </th>
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
                                        @php
                                            $hasActivity = $opp->rfms_count > 0
                                                || $opp->estimates_count > 0
                                                || $opp->sales_count > 0
                                                || $opp->purchase_orders_count > 0;
                                        @endphp
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('pages.opportunities.show', $opp->id) }}{{ $qs ? '?' . $qs : '' }}"
                                               class="inline-flex items-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                                View
                                            </a>
                                            <a href="{{ route('pages.opportunities.edit', $opp->id) }}"
                                               class="inline-flex items-center px-3 py-2 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                                Edit
                                            </a>
                                            @if ($hasActivity)
                                                <form method="POST" action="{{ route('pages.opportunities.deactivate', $opp->id) }}"
                                                      onsubmit="return confirm('Deactivate this opportunity?')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-yellow-800 bg-yellow-100 border border-yellow-300 rounded-lg hover:bg-yellow-200">
                                                        Deactivate
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('pages.opportunities.destroy', $opp->id) }}"
                                                      onsubmit="return confirm('Delete this opportunity? This cannot be undone.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center px-3 py-2 text-xs font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
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
