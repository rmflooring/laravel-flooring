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
                    <a href="{{ route('mobile.rfms.show', $rfm->id) }}"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3h3m-3 3h3"/>
                        </svg>
                        Mobile View
                    </a>
                    <a href="{{ route('pages.opportunities.rfms.pdf', [$opportunity->id, $rfm->id]) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                        </svg>
                        Print PDF
                    </a>
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
            @if (session('warning'))
                <div class="mb-4 p-4 text-yellow-800 bg-yellow-100 border border-yellow-300 rounded-lg flex items-center justify-between dark:bg-yellow-900/30 dark:text-yellow-200 dark:border-yellow-700">
                    <div>{{ session('warning') }}</div>
                    <button type="button" onclick="this.closest('div').remove()"
                            class="text-yellow-900 hover:text-yellow-700 text-sm font-medium dark:text-yellow-200">✕</button>
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
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 dark:text-gray-400">Job Info</h2>
                    @if($opportunity->job_no)
                        <p class="text-sm font-bold text-gray-900 dark:text-white mb-4">Job #{{ $opportunity->job_no }}</p>
                    @else
                        <div class="mb-4"></div>
                    @endif
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

                        {{-- Job Site + Address + Contact --}}
                        <div>
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:bg-gray-700/50 dark:border-gray-600 space-y-1">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Job Site</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $opportunity->jobSiteCustomer?->name ?: ($opportunity->jobSiteCustomer?->company_name ?? '—') }}
                                </p>
                                @if($opportunity->jobSiteCustomer?->phone)
                                    <div><span class="text-gray-500 dark:text-gray-400">Phone:</span> {{ $opportunity->jobSiteCustomer->phone }}</div>
                                @endif
                                @if($opportunity->jobSiteCustomer?->mobile)
                                    <div><span class="text-gray-500 dark:text-gray-400">Mobile:</span> {{ $opportunity->jobSiteCustomer->mobile }}</div>
                                @endif
                                @if($opportunity->jobSiteCustomer?->email)
                                    <div><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $opportunity->jobSiteCustomer->email }}</div>
                                @endif
                                @php
                                    $hasAddr = $rfm->site_address || $rfm->site_city || $rfm->site_postal_code;
                                @endphp
                                @if($hasAddr)
                                    <div class="pt-1 text-gray-700 dark:text-gray-200">
                                        @if($rfm->site_address)
                                            <div>{{ $rfm->site_address }}</div>
                                        @endif
                                        @if($rfm->site_city || $rfm->site_postal_code)
                                            <div>{{ implode(', ', array_filter([$rfm->site_city, $rfm->site_postal_code])) }}</div>
                                        @endif
                                    </div>
                                @endif
                            </div>
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
                @php $ce = $rfm->calendarEvent; @endphp
                @php $ceStarts = $ce->starts_at?->format('M j, Y \a\t g:i A'); @endphp
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3 dark:text-gray-400">Calendar</h2>
                    <button type="button"
                            onclick="openRfmCalendarModal()"
                            class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ $rfm->estimator ? ($rfm->estimator->first_name . ' ' . strtoupper(substr($rfm->estimator->last_name, 0, 1)) . '.') : 'Estimator' }} scheduled in Calendar for {{ $ceStarts }}
                    </button>
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

    {{-- Calendar event details modal --}}
    @if($rfm->calendarEvent)
    @include('components.calendar.event-details-modal')

    @php
        $ce = $rfm->calendarEvent;
        $ceData = [
            'title'       => $ce->title,
            'start'       => $ce->starts_at?->format('M j, Y \a\t g:i A'),
            'end'         => $ce->ends_at?->format('M j, Y \a\t g:i A'),
            'location'    => $ce->location,
            'description' => $ce->description,
            'provider'    => 'Microsoft 365',
        ];
    @endphp
    <script>
        function openRfmCalendarModal() {
            const event = @json($ceData);

            document.getElementById('event-modal-title').textContent       = event.title       ?? '';
            document.getElementById('event-modal-start').textContent       = event.start       ?? '';
            document.getElementById('event-modal-end').textContent         = event.end         ?? '';
            document.getElementById('event-modal-location').textContent    = event.location    ?? '';
            document.getElementById('event-modal-description').textContent = event.description ?? '';
            document.getElementById('event-modal-provider').textContent    = event.provider    ?? '';

            const modalEl = document.getElementById('event-details-modal');
            const modal   = new Modal(modalEl);
            modal.show();
        }
    </script>
    @endif

</x-app-layout>
