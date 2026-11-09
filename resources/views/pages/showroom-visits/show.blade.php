{{-- resources/views/pages/showroom-visits/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6" x-data="{ showCancel: false, copied: false }">

            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Showroom Visit</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $visit->contact_name }}
                        @if ($visit->opportunity)
                            &mdash; <a href="{{ route('pages.opportunities.show', $visit->opportunity_id) }}" class="text-blue-600 hover:underline">Job #{{ $visit->opportunity->job_no ?: $visit->opportunity_id }}</a>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @can('edit showroom visits')
                        <a href="{{ route('pages.showroom-visits.edit', $visit) }}"
                           class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            {{ $visit->status === 'change_requested' ? 'Reschedule' : 'Edit / Reschedule' }}
                        </a>
                    @endcan
                    <a href="{{ route('pages.showroom-visits.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">All Visits</a>
                </div>
            </div>

            {{-- Change request banner --}}
            @if ($visit->status === 'change_requested')
                <div class="p-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900">
                    <p class="font-semibold">The customer asked to change this visit
                        <span class="font-normal text-sm">({{ $visit->change_requested_at?->format('M j, Y g:i A') }})</span></p>
                    @if ($visit->change_request_preferred)
                        <p class="mt-2 text-sm"><strong>Preferred times:</strong><br>{!! nl2br(e($visit->change_request_preferred)) !!}</p>
                    @endif
                    @if ($visit->change_request_note)
                        <p class="mt-2 text-sm"><strong>Note:</strong><br>{!! nl2br(e($visit->change_request_note)) !!}</p>
                    @endif
                    <p class="mt-3 text-sm">Click <strong>Reschedule</strong> to pick the new time — the customer will get an updated confirmation.</p>
                </div>
            @endif

            @if ($visit->status === 'cancelled')
                <div class="p-4 rounded-lg border border-red-200 bg-red-50 text-red-900 text-sm">
                    Cancelled by <strong>{{ $visit->cancelled_by === 'customer' ? 'the customer' : 'staff' }}</strong>
                    on {{ $visit->cancelled_at?->format('M j, Y g:i A') }}.
                    @if ($visit->cancellation_reason)
                        <br><strong>Reason:</strong> {!! nl2br(e($visit->cancellation_reason)) !!}
                    @endif
                </div>
            @endif

            {{-- Details --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <div class="text-xs uppercase text-gray-500 mb-1">When</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">{{ $visit->scheduled_at->format('l, F j, Y') }}</div>
                        <div class="text-gray-700 dark:text-gray-300">{{ $visit->scheduled_at->format('g:i A') }} – {{ $visit->endsAt()->format('g:i A') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500 mb-1">Status</div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $visit->statusColor() }}">{{ $visit->statusLabel() }}</span>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500 mb-1">Customer</div>
                        <div class="text-gray-900 dark:text-white font-medium">{{ $visit->contact_name }}</div>
                        @if ($visit->customer && ($visit->customer->company_name || $visit->customer->name) !== $visit->contact_name)
                            <div class="text-sm text-gray-500">{{ $visit->customer->company_name ?: $visit->customer->name }}</div>
                        @endif
                        <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            @if ($visit->contact_phone) {{ $visit->contact_phone }}<br>@endif
                            @if ($visit->contact_email) <a href="mailto:{{ $visit->contact_email }}" class="text-blue-600 hover:underline">{{ $visit->contact_email }}</a>@endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500 mb-1">Host</div>
                        <div class="text-gray-900 dark:text-white">{{ $visit->hostName() ?: 'Unassigned' }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500 mb-1">Opportunity</div>
                        @if ($visit->opportunity)
                            <a href="{{ route('pages.opportunities.show', $visit->opportunity_id) }}" class="text-blue-600 hover:underline">
                                {{ $visit->opportunity->job_no ?: 'Opportunity #' . $visit->opportunity_id }}
                            </a>
                            <div class="text-sm text-gray-500">{{ $visit->opportunity->jobSiteCustomer?->name }} · {{ $visit->opportunity->status }}</div>
                        @else
                            —
                        @endif
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500 mb-1">Showroom calendar</div>
                        @if ($visit->calendar_event_id)
                            <span class="text-green-700 text-sm">● On the Showroom calendar</span>
                        @elseif ($visit->status === 'cancelled')
                            <span class="text-gray-500 text-sm">Removed (cancelled)</span>
                        @else
                            <span class="text-amber-700 text-sm">Not on the calendar — saving the visit again will retry.</span>
                        @endif
                    </div>
                    @if ($visit->notes)
                        <div class="sm:col-span-2">
                            <div class="text-xs uppercase text-gray-500 mb-1">Internal notes</div>
                            <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ $visit->notes }}</div>
                        </div>
                    @endif
                </div>

                {{-- Customer link + notifications --}}
                <div class="p-6 space-y-3">
                    <div class="text-xs uppercase text-gray-500">Customer notifications</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Email: <strong>{{ $visit->notify_email && $visit->contact_email ? 'On' : 'Off' }}</strong> ·
                        Text: <strong>{{ $visit->notify_sms && $visit->contact_phone ? 'On' : 'Off' }}</strong>
                        @if ($visit->customer?->sms_opted_out)<span class="text-red-600">(customer has opted out of texts)</span>@endif
                    </div>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li>Booked {{ $visit->created_at->format('M j, Y g:i A') }}{{ $visit->creator ? ' by ' . $visit->creator->name : '' }}</li>
                        <li>Confirmation sent: {{ $visit->confirmation_sent_at?->format('M j, Y g:i A') ?? '—' }}</li>
                        <li>Day-before reminder: {{ $visit->reminder_day_before_sent_at?->format('M j, Y g:i A') ?? 'not yet' }}</li>
                        <li>Morning-of reminder: {{ $visit->reminder_morning_sent_at?->format('M j, Y g:i A') ?? 'not yet' }}</li>
                    </ul>

                    @if ($visit->isActive())
                        <div class="flex flex-col sm:flex-row gap-2 sm:items-center pt-2">
                            <input type="text" readonly value="{{ $visit->publicUrl() }}" x-ref="link"
                                   class="flex-1 p-2 text-xs text-gray-700 bg-gray-50 border border-gray-300 rounded-lg">
                            <button type="button"
                                    @click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                <span x-text="copied ? 'Copied!' : 'Copy customer link'"></span>
                            </button>
                            @can('edit showroom visits')
                                <form method="POST" action="{{ route('pages.showroom-visits.resend', $visit) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Resend confirmation
                                    </button>
                                </form>
                            @endcan
                        </div>
                        <p class="text-xs text-gray-500">This is the customer's "manage my appointment" page, where they can cancel or ask for a different time.</p>
                    @endif
                </div>

                {{-- Status actions --}}
                @can('edit showroom visits')
                    <div class="p-6 flex flex-wrap gap-2">
                        @if ($visit->isActive())
                            <form method="POST" action="{{ route('pages.showroom-visits.update-status', $visit) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="completed">
                                <button class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Mark Completed</button>
                            </form>
                            <form method="POST" action="{{ route('pages.showroom-visits.update-status', $visit) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="no_show">
                                <button class="px-4 py-2 text-sm font-medium text-gray-800 bg-gray-200 rounded-lg hover:bg-gray-300">No Show</button>
                            </form>
                            <button type="button" @click="showCancel = !showCancel"
                                    class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50">Cancel Visit</button>
                        @else
                            <form method="POST" action="{{ route('pages.showroom-visits.update-status', $visit) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="scheduled">
                                <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Re-open as Scheduled</button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('pages.showroom-visits.destroy', $visit) }}" class="ml-auto"
                              onsubmit="return confirm('Delete this showroom visit? This also removes it from the calendar. The customer is NOT notified.')">
                            @csrf @method('DELETE')
                            <button class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-red-600">Delete</button>
                        </form>
                    </div>

                    <div x-show="showCancel" x-cloak class="p-6 bg-red-50/50">
                        <form method="POST" action="{{ route('pages.showroom-visits.update-status', $visit) }}" class="space-y-3">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <label class="block text-sm font-medium text-gray-700">Reason (internal)</label>
                            <textarea name="cancellation_reason" rows="2" class="block w-full p-2.5 text-sm bg-white border border-gray-300 rounded-lg"></textarea>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="notify_customer" value="1" checked class="rounded border-gray-300 text-blue-600">
                                Let the customer know it's cancelled (email / text)
                            </label>
                            <button class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Confirm Cancellation</button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
