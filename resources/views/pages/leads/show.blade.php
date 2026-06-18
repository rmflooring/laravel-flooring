<x-app-layout>
    <div class="py-6" x-data="{ showApproveModal: false, showDenyModal: false }">
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

        </div>
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
</x-app-layout>
