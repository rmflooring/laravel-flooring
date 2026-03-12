<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Request for Measure</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Job #{{ $opportunity->job_no ?? '—' }} &mdash;
                        {{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.opportunities.rfms.edit', [$opportunity->id, $rfm->id]) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Edit
                    </a>
                    <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Back to Opportunity
                    </a>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between dark:bg-green-900/30 dark:text-green-200 dark:border-green-700">
                    <div>{{ session('success') }}</div>
                    <button type="button" onclick="this.closest('div').remove()"
                            class="text-green-900 hover:text-green-700 text-sm font-medium dark:text-green-200">✕</button>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">

                {{-- Status --}}
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Status</h2>
                    @php
                        $statusColors = [
                            'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                            'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                        ];
                        $statusColor = $statusColors[$rfm->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColor }}">
                        {{ ucfirst($rfm->status) }}
                    </span>
                </div>

                {{-- Job Info --}}
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-400">Job Info</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        {{-- Parent Customer + PM --}}
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Parent Customer</p>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}
                                </p>
                            </div>
                            @if($opportunity->projectManager)
                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm dark:bg-gray-700/50 dark:border-gray-600">
                                    <p class="font-medium text-gray-700 dark:text-gray-200 mb-1">PM: {{ $opportunity->projectManager->name }}</p>
                                    @if($opportunity->projectManager->phone)
                                        <p class="text-gray-500 dark:text-gray-400">{{ $opportunity->projectManager->phone }}</p>
                                    @endif
                                    @if($opportunity->projectManager->email)
                                        <p class="text-gray-500 dark:text-gray-400">{{ $opportunity->projectManager->email }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Job Site + Address --}}
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Job Site</p>
                                <p class="text-sm text-gray-900 dark:text-white">
                                    {{ $opportunity->jobSiteCustomer?->company_name ?: $opportunity->jobSiteCustomer?->name ?? '—' }}
                                </p>
                            </div>
                            @php
                                $addressParts = array_filter([$rfm->site_address, $rfm->site_city, $rfm->site_postal_code]);
                            @endphp
                            @if($addressParts)
                                <div class="text-sm text-gray-900 dark:text-white space-y-0.5">
                                    @if($rfm->site_address)
                                        <p>{{ $rfm->site_address }}</p>
                                    @endif
                                    @if($rfm->site_city || $rfm->site_postal_code)
                                        <p>{{ implode(', ', array_filter([$rfm->site_city, $rfm->site_postal_code])) }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

                {{-- Measure Details --}}
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4 dark:text-gray-400">Measure Details</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Estimator</p>
                            <p class="text-sm text-gray-900 dark:text-white">
                                {{ $rfm->estimator ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name) : '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Flooring Type</p>
                            <div class="flex flex-wrap gap-1.5 mt-1">
                                @foreach((array) $rfm->flooring_type as $type)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                        {{ $type }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Scheduled Date &amp; Time</p>
                            <p class="text-sm text-gray-900 dark:text-white">
                                {{ $rfm->scheduled_at->format('M j, Y \a\t g:i A') }}
                            </p>
                        </div>


                    </div>
                </div>

                {{-- Special Instructions --}}
                @if($rfm->special_instructions)
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 dark:text-gray-400">Special Instructions</h2>
                    <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $rfm->special_instructions }}</p>
                </div>
                @endif

                {{-- Calendar Event --}}
                @if($rfm->calendarEvent)
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Calendar</h2>
                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Synced to MS365 calendar</span>
                    </div>
                </div>
                @endif

                {{-- Meta --}}
                <div class="p-6 text-xs text-gray-400 dark:text-gray-500 flex flex-wrap gap-x-6 gap-y-1">
                    <span>Created {{ $rfm->created_at->format('M j, Y g:i A') }}</span>
                    <span>Last updated {{ $rfm->updated_at->format('M j, Y g:i A') }}</span>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
