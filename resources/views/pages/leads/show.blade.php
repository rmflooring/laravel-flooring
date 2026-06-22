<x-app-layout>
    <div class="py-6" x-data="{ showApproveModal: false, showDenyModal: false, activeTab: 'email' }">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('pages.leads.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">&larr; Back to Leads</a>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Lead: {{ $lead->name }}</h1>
                </div>
                <div>
                    @if ($lead->status === 'pending')
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Pending Review</span>
                    @elseif ($lead->status === 'approved')
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Approved</span>
                    @else
                        <span class="px-3 py-1 text-sm font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Denied</span>
                    @endif
                </div>
            </div>

            {{-- Flash --}}
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{ session('error') }}</div>
            @endif

            {{-- Lead details card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Lead Details</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Received {{ $lead->created_at->format('M j, Y g:i A') }} &middot; Source: {{ $lead->source }}</p>
                </div>
                <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Phone</p>
                        <p class="mt-0.5 text-sm">
                            <a href="tel:{{ $lead->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $lead->phone }}</a>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Email</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">
                            @if ($lead->email)
                                <a href="mailto:{{ $lead->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $lead->email }}</a>
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">SMS Consent</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->sms_consent ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Service Type</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->service_type ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project Type</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->project_type ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Area</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->area ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">City</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->city ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Timeline</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->timeline ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Referral Source</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $lead->referral_source ?? '—' }}</p>
                    </div>
                </div>
                @if ($lead->message)
                    <div class="px-6 pb-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Message</p>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ $lead->message }}</p>
                    </div>
                @endif
            </div>

            {{-- Reviewed info (non-pending) --}}
            @if (! $lead->isPending())
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Review</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reviewed By</p>
                            <p class="mt-0.5 text-gray-900 dark:text-white">{{ $lead->reviewer?->name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reviewed At</p>
                            <p class="mt-0.5 text-gray-900 dark:text-white">{{ $lead->reviewed_at?->format('M j, Y g:i A') ?? '—' }}</p>
                        </div>
                        @if ($lead->status === 'approved' && $lead->opportunity)
                            <div class="sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Linked Opportunity</p>
                                <p class="mt-0.5">
                                    <a href="{{ route('pages.opportunities.show', $lead->opportunity) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        Opportunity #{{ $lead->opportunity->id }}
                                    </a>
                                </p>
                            </div>
                        @endif
                        @if ($lead->status === 'denied' && $lead->denial_reason)
                            <div class="sm:col-span-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Denial Reason</p>
                                <p class="mt-0.5 text-gray-900 dark:text-white whitespace-pre-wrap">{{ $lead->denial_reason }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Action buttons --}}
            @if ($lead->isPending())
                @can('manage leads')
                    <div class="flex items-center gap-3">
                        <button @click="showApproveModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300">
                            Approve &amp; Create Opportunity
                        </button>
                        <button @click="showDenyModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300">
                            Deny Lead
                        </button>
                    </div>
                @endcan
            @endif

            {{-- Reply panel --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Reply to Lead</h2>
                </div>

                {{-- Tabs --}}
                <div class="flex border-b border-gray-200 dark:border-gray-700 px-6 pt-3 gap-4">
                    <button @click="activeTab = 'email'"
                            :class="activeTab === 'email' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors">
                        Email
                        @if (! $lead->email)
                            <span class="ml-1 text-xs text-gray-400">(no address)</span>
                        @endif
                    </button>
                    <button @click="activeTab = 'sms'"
                            :class="activeTab === 'sms' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                            class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors">
                        SMS
                        @if (! $lead->sms_consent)
                            <span class="ml-1 text-xs text-gray-400">(no consent)</span>
                        @endif
                    </button>
                </div>

                <div class="px-6 py-4">
                    {{-- Email tab --}}
                    <div x-show="activeTab === 'email'">
                        @if ($lead->email)
                            <form method="POST" action="{{ route('pages.leads.reply-email', $lead) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">To</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $lead->email }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Subject</label>
                                    <input type="text" name="subject" required maxlength="255"
                                           value="Re: Your flooring inquiry"
                                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Message</label>
                                    <textarea name="body" required rows="5" maxlength="10000"
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                              placeholder="Hi {{ $lead->name }}, thank you for reaching out…"></textarea>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                                        </svg>
                                        Send Email
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No email address on this lead.</p>
                        @endif
                    </div>

                    {{-- SMS tab --}}
                    <div x-show="activeTab === 'sms'">
                        @if ($lead->sms_consent && $lead->phone)
                            <form method="POST" action="{{ route('pages.leads.reply-sms', $lead) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">To</label>
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $lead->phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1">Message</label>
                                    <textarea name="body" required rows="4" maxlength="1600"
                                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                              placeholder="Hi {{ $lead->name }}, thanks for your inquiry…"></textarea>
                                    <p class="mt-1 text-xs text-gray-400">Max 1600 characters</p>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit"
                                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                                        </svg>
                                        Send SMS
                                    </button>
                                </div>
                            </form>
                        @elseif (! $lead->sms_consent)
                            <p class="text-sm text-gray-500 dark:text-gray-400">This lead has not consented to SMS messages.</p>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No phone number on this lead.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Reply history --}}
            @if ($emailReplies->isNotEmpty() || $smsConversation?->messages->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Reply History</h2>
                    @if ($smsConversation)
                        <a href="{{ route('pages.sms.show', $smsConversation) }}"
                           class="text-xs text-blue-600 hover:underline dark:text-blue-400">View SMS thread →</a>
                    @endif
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    {{-- Email replies --}}
                    @foreach ($emailReplies as $log)
                        <li class="flex items-start gap-3 px-6 py-3">
                            <span class="mt-0.5 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/30">
                                <svg class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $log->subject }}</p>
                                    <span class="flex-shrink-0 text-xs {{ $log->status === 'sent' ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                                        {{ $log->status }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Email · {{ $log->created_at->format('M j, Y g:i A') }}</p>
                            </div>
                        </li>
                    @endforeach

                    {{-- SMS messages --}}
                    @if ($smsConversation)
                        @foreach ($smsConversation->messages as $msg)
                            <li class="flex items-start gap-3 px-6 py-3">
                                <span class="mt-0.5 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full {{ $msg->isOutbound() ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                                    <svg class="h-3.5 w-3.5 {{ $msg->isOutbound() ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                                    </svg>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm text-gray-900 dark:text-white">{{ Str::limit($msg->body, 100) }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        SMS · {{ $msg->isOutbound() ? ($msg->sentBy?->name ?? 'Staff') . ' → lead' : 'Lead → us' }}
                                        · {{ $msg->created_at->format('M j, Y g:i A') }}
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>
            @endif

        </div>

    {{-- Approve Modal --}}
    <div x-show="showApproveModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="showApproveModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Approve Lead &amp; Create Opportunity</h3>
            </div>
            <form method="POST" action="{{ route('pages.leads.approve', $lead) }}">
                @csrf
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Opportunity Name</label>
                        <input type="text" name="opportunity_name"
                               value="{{ $lead->name }}{{ $lead->service_type ? ' — ' . $lead->service_type : '' }}"
                               required
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes (pre-filled with message)</label>
                        <textarea name="notes" rows="4"
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500">{{ $lead->message }}</textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button type="button" @click="showApproveModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300">
                        Approve &amp; Create
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Deny Modal --}}
    <div x-show="showDenyModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="showDenyModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Deny Lead</h3>
            </div>
            <form method="POST" action="{{ route('pages.leads.deny', $lead) }}">
                @csrf
                <div class="px-6 py-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason (optional)</label>
                        <textarea name="denial_reason" rows="3" placeholder="e.g. Outside service area, duplicate inquiry..."
                                  class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button type="button" @click="showDenyModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300">
                        Deny Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>
</x-app-layout>

