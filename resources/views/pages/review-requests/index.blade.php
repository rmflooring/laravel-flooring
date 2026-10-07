<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Review Requests</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Google review requests sent to customers — who was sent what, and which low ratings need a follow-up.</p>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{ session('error') }}</div>
            @endif

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="me-2">
                        <a href="{{ route('pages.review-requests.index', ['status' => 'needs_followup']) }}"
                           class="inline-flex items-center gap-2 justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'needs_followup' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Needs Follow-up
                            @if ($needsFollowupCount > 0)
                                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">{{ $needsFollowupCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="me-2">
                        <a href="{{ route('pages.review-requests.index', ['status' => 'positive']) }}"
                           class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'positive' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Positive (4–5★)
                        </a>
                    </li>
                    <li class="me-2">
                        <a href="{{ route('pages.review-requests.index', ['status' => 'pending']) }}"
                           class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'pending' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Pending (not yet rated)
                        </a>
                    </li>
                    <li class="me-2">
                        <a href="{{ route('pages.review-requests.index', ['status' => 'all']) }}"
                           class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'all' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            All
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                @if ($reviewRequests->isEmpty())
                    <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        @if ($status === 'needs_followup')
                            No low ratings waiting on a follow-up. 🎉
                        @else
                            No review requests found.
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Job</th>
                                    <th class="px-4 py-3">Sent Via</th>
                                    <th class="px-4 py-3">Sent By</th>
                                    <th class="px-4 py-3">Sent At</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Reason / Feedback</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($reviewRequests as $rr)
                                    @php
                                        $opp = $rr->opportunity;
                                        $jobLabel = $opp
                                            ? trim(($opp->jobSiteCustomer?->name ?: $opp->jobSiteCustomer?->company_name ?: '') . ($opp->job_no ? ' · ' . $opp->job_no : ''), ' ·')
                                            : null;
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 align-top">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                            {{ $rr->customer_name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($opp)
                                                <a href="{{ route('pages.opportunities.show', $opp->id) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                                    {{ $jobLabel ?: ('Opportunity #' . $opp->id) }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($rr->sent_via === 'sms')
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">SMS</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">Email</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ $rr->sentBy?->name ?? 'Staff' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ $rr->created_at->format('M j, Y g:i A') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if (! $rr->isSubmitted())
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Pending</span>
                                            @elseif ($rr->isPositive())
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                    {{ str_repeat('★', $rr->rating) }} {{ $rr->rating }}/5
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                    {{ str_repeat('★', $rr->rating) }} {{ $rr->rating }}/5
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 max-w-sm">
                                            @if ($rr->isSubmitted() && ! $rr->isPositive())
                                                @if ($rr->feedback)
                                                    <span class="italic text-gray-600 dark:text-gray-300">"{{ Str::limit($rr->feedback, 160) }}"</span>
                                                @else
                                                    <span class="text-gray-400 italic">No reason given</span>
                                                @endif
                                            @elseif (! $rr->isSubmitted())
                                                @if ($rr->reminder_sent_at)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">Reminder sent {{ $rr->reminder_sent_at->format('M j, Y') }}</span>
                                                @else
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">No reminder yet</span>
                                                @endif
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ $rr->publicUrl() }}" target="_blank"
                                               class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                View Link
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($reviewRequests->hasPages())
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                            {{ $reviewRequests->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
