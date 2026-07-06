<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1800px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Reports</a>
                            <h1 class="text-3xl font-bold">Aging Estimates</h1>
                            <p class="text-gray-500 text-sm mt-1">Sent estimates that haven't been converted to a sale. Follow up before they go cold.</p>
                        </div>
                    </div>

                    {{-- Action Required --}}
                    @if($actionRequired->isNotEmpty())
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-red-700 mb-3 flex items-center gap-2">
                                <span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                Follow-up Required ({{ $actionRequired->count() }})
                            </h2>
                            <div class="space-y-3">
                                @foreach($actionRequired as $est)
                                    @php
                                        $daysSent  = (int) $est->first_sent_at->diffInDays(now());
                                        $stage     = $est->follow_up_stage;
                                        $dueStage  = $stage + 1;
                                        $lastFu    = $est->followUps->first();
                                        $customer  = $est->homeowner_name ?: $est->customer_name ?: '—';
                                    @endphp
                                    <div class="border border-red-200 bg-red-50 rounded-lg p-4 flex flex-wrap items-center gap-4">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-3 flex-wrap">
                                                <span class="font-semibold text-gray-900">
                                                    <a href="{{ route('pages.estimates.show', $est) }}" class="hover:underline text-blue-700">
                                                        #{{ $est->estimate_number }}
                                                    </a>
                                                </span>
                                                <span class="text-gray-700">{{ $customer }}</span>
                                                @if($est->job_name)
                                                    <span class="text-gray-500 text-sm">{{ $est->job_name }}</span>
                                                @endif
                                                <span class="text-sm font-medium text-gray-800">${{ number_format($est->grand_total, 2) }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      style="background-color: {{ $daysSent > 30 ? '#fee2e2' : ($daysSent > 14 ? '#ffedd5' : '#fef9c3') }}; color: {{ $daysSent > 30 ? '#991b1b' : ($daysSent > 14 ? '#9a3412' : '#713f12') }};">
                                                    {{ $daysSent }}d since sent
                                                </span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    Follow-up {{ $dueStage }} due
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Sent {{ $est->first_sent_at->format('M j, Y') }}
                                                @if($est->creator) · Created by {{ $est->creator->name }} @endif
                                                @if($lastFu) · Last follow-up: {{ $lastFu->created_at->format('M j, Y') }} ({{ ucfirst($lastFu->channel) }}) @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 flex-shrink-0">
                                            <button type="button"
                                                    onclick="openFollowUpModal({{ $est->id }}, {{ $dueStage }}, '{{ addslashes($customer) }}', '{{ addslashes($est->estimate_number) }}')"
                                                    class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-3 py-1.5">
                                                Follow Up
                                            </button>
                                            <form method="POST" action="{{ route('admin.estimates.follow-up.close', $est) }}" class="inline">
                                                @csrf
                                                <button type="submit" onclick="return confirm('Remove this estimate from the follow-up queue?')"
                                                        class="text-gray-600 hover:text-gray-900 bg-white border border-gray-300 hover:bg-gray-50 font-medium rounded-lg text-sm px-3 py-1.5">
                                                    Dismiss
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800 text-sm font-medium">
                            No estimates are currently overdue for follow-up.
                        </div>
                    @endif

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.reports.agingEstimates') }}" class="mb-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="Est #, customer, job..."
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Follow-up Stage</label>
                                    <select name="stage" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All Stages</option>
                                        <option value="0" @selected(request('stage') === '0')>Stage 0 — Not Yet Followed Up</option>
                                        <option value="1" @selected(request('stage') === '1')>Stage 1 — 7-day sent</option>
                                        <option value="2" @selected(request('stage') === '2')>Stage 2 — 14-day sent</option>
                                        <option value="3" @selected(request('stage') === '3')>Stage 3 — 30-day sent</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Estimator</label>
                                    <select name="estimator_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All</option>
                                        @foreach($estimators as $emp)
                                            <option value="{{ $emp->id }}" @selected(request('estimator_id') == $emp->id)>
                                                {{ $emp->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Show Dismissed</label>
                                    <div class="flex items-center h-10">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="show_closed" value="1" @checked(request('show_closed'))
                                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Include dismissed estimates</span>
                                        </label>
                                    </div>
                                </div>

                            </div>
                            <div class="flex items-center gap-3 mt-4">
                                <button type="submit"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2">
                                    Filter
                                </button>
                                <a href="{{ route('admin.reports.agingEstimates') }}"
                                   class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2">
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- Table --}}
                    @if($estimates->isEmpty())
                        <p class="text-gray-500 text-sm py-6 text-center">No estimates found matching your filters.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3">Estimate #</th>
                                        <th class="px-4 py-3">Customer</th>
                                        <th class="px-4 py-3">Job</th>
                                        <th class="px-4 py-3">Amount</th>
                                        <th class="px-4 py-3">Sent</th>
                                        <th class="px-4 py-3">Age</th>
                                        <th class="px-4 py-3">Stage</th>
                                        <th class="px-4 py-3">Last Activity</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($estimates as $est)
                                        @php
                                            $daysSent     = (int) $est->first_sent_at->diffInDays(now());
                                            $stage        = $est->follow_up_stage;
                                            $dueStage     = $stage + 1;
                                            $lastFu       = $est->followUps->first();
                                            $customer     = $est->homeowner_name ?: $est->customer_name ?: '—';
                                            $isClosed     = $est->follow_up_closed;

                                            if ($daysSent > 30) {
                                                $ageBg    = '#fee2e2'; $ageFg = '#991b1b';
                                            } elseif ($daysSent > 14) {
                                                $ageBg    = '#ffedd5'; $ageFg = '#9a3412';
                                            } elseif ($daysSent > 7) {
                                                $ageBg    = '#fef9c3'; $ageFg = '#713f12';
                                            } else {
                                                $ageBg    = '#dcfce7'; $ageFg = '#166534';
                                            }
                                        @endphp
                                        <tr class="{{ $isClosed ? 'opacity-50' : '' }} hover:bg-gray-50">
                                            <td class="px-4 py-3 font-medium">
                                                <a href="{{ route('pages.estimates.show', $est) }}" class="text-blue-700 hover:underline">
                                                    #{{ $est->estimate_number }}
                                                </a>
                                                @if($isClosed)
                                                    <span class="ml-1 text-xs text-gray-400">(dismissed)</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">{{ $customer }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $est->job_name ?: '—' }}</td>
                                            <td class="px-4 py-3 font-medium">${{ number_format($est->grand_total, 2) }}</td>
                                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $est->first_sent_at->format('M j, Y') }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                      style="background-color: {{ $ageBg }}; color: {{ $ageFg }};">
                                                    {{ $daysSent }}d
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($isClosed)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">Dismissed</span>
                                                @elseif($stage === 3)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700">Stage 3 done</span>
                                                @elseif($stage > 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Stage {{ $stage }} done</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Not contacted</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 text-xs">
                                                @if($lastFu)
                                                    {{ $lastFu->created_at->format('M j') }} · {{ ucfirst($lastFu->channel) }}
                                                    @if($lastFu->user) · {{ $lastFu->user->name }} @endif
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    @if(! $isClosed)
                                                        <button type="button"
                                                                onclick="openFollowUpModal({{ $est->id }}, {{ min($dueStage, 3) }}, '{{ addslashes($customer) }}', '{{ addslashes($est->estimate_number) }}')"
                                                                class="text-blue-700 hover:text-blue-900 text-xs font-medium">
                                                            Follow Up
                                                        </button>
                                                        <span class="text-gray-300">|</span>
                                                        <form method="POST" action="{{ route('admin.estimates.follow-up.close', $est) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" onclick="return confirm('Dismiss this estimate from the follow-up queue?')"
                                                                    class="text-gray-500 hover:text-gray-700 text-xs font-medium">
                                                                Dismiss
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.estimates.follow-up.reopen', $est) }}" class="inline">
                                                            @csrf
                                                            <button type="submit" class="text-green-700 hover:text-green-900 text-xs font-medium">
                                                                Re-open
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $estimates->withQueryString()->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Follow-up Modal --}}
    <div id="followUpModal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-50" onclick="closeFollowUpModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl pointer-events-auto" style="max-height: 90vh; overflow-y: auto;">
                <div class="p-6">

                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900" id="modalTitle">Send Follow-up</h3>
                            <p class="text-sm text-gray-500 mt-0.5" id="modalSubtitle"></p>
                        </div>
                        <button type="button" onclick="closeFollowUpModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Tabs --}}
                    <div class="border-b border-gray-200 mb-4">
                        <nav class="flex gap-6" aria-label="Tabs">
                            <button type="button" onclick="switchTab('email')" id="tab-email"
                                    class="tab-btn border-b-2 border-blue-600 text-blue-600 pb-2 text-sm font-medium">
                                Email
                            </button>
                            <button type="button" onclick="switchTab('sms')" id="tab-sms"
                                    class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 pb-2 text-sm font-medium">
                                SMS
                            </button>
                            <button type="button" onclick="switchTab('note')" id="tab-note"
                                    class="tab-btn border-b-2 border-transparent text-gray-500 hover:text-gray-700 pb-2 text-sm font-medium">
                                Log Note
                            </button>
                        </nav>
                    </div>

                    {{-- Loading state --}}
                    <div id="modalLoading" class="py-8 text-center text-gray-500 text-sm">Loading draft...</div>

                    {{-- Email Tab --}}
                    <div id="panel-email" class="hidden">
                        <form id="emailForm" method="POST">
                            @csrf
                            <input type="hidden" name="stage" id="emailStage">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                                    <input type="email" name="to" id="emailTo" required
                                           class="w-full border border-gray-300 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                    <input type="text" name="subject" id="emailSubject" required
                                           class="w-full border border-gray-300 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                    <textarea name="body" id="emailBody" rows="10" required
                                              class="w-full border border-gray-300 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500 font-mono"></textarea>
                                </div>
                                <div class="flex justify-end gap-3 pt-2">
                                    <button type="button" onclick="closeFollowUpModal()"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                        Send Email
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- SMS Tab --}}
                    <div id="panel-sms" class="hidden">
                        <form id="smsForm" method="POST">
                            @csrf
                            <input type="hidden" name="stage" id="smsStage">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="phone" id="smsPhone" required
                                           class="w-full border border-gray-300 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                    <textarea name="body" id="smsBody" rows="5" required maxlength="320"
                                              class="w-full border border-gray-300 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500"
                                              oninput="updateSmsCount()"></textarea>
                                    <p class="text-xs text-gray-400 mt-1 text-right"><span id="smsCount">0</span>/320</p>
                                </div>
                                <div class="flex justify-end gap-3 pt-2">
                                    <button type="button" onclick="closeFollowUpModal()"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                        Send SMS
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Note Tab --}}
                    <div id="panel-note" class="hidden">
                        <form id="noteForm" method="POST">
                            @csrf
                            <input type="hidden" name="stage" id="noteStage">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                                    <textarea name="notes" id="noteBody" rows="5" required maxlength="2000"
                                              placeholder="e.g. Called and left voicemail. Will try again next week."
                                              class="w-full border border-gray-300 rounded-lg text-sm p-2.5 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                                <div class="flex justify-end gap-3 pt-2">
                                    <button type="button" onclick="closeFollowUpModal()"
                                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                            class="px-4 py-2 text-sm font-medium text-white bg-gray-700 rounded-lg hover:bg-gray-800">
                                        Save Note
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        let currentEstimateId = null;
        let currentStage      = null;
        let activeTab         = 'email';

        function openFollowUpModal(estimateId, stage, customer, estimateNum) {
            currentEstimateId = estimateId;
            currentStage      = stage;

            document.getElementById('modalTitle').textContent   = 'Follow-up — Estimate #' + estimateNum;
            document.getElementById('modalSubtitle').textContent = customer + ' · Stage ' + stage;
            document.getElementById('followUpModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // Set action URLs
            document.getElementById('emailForm').action = '/admin/estimates/' + estimateId + '/follow-up/email';
            document.getElementById('smsForm').action   = '/admin/estimates/' + estimateId + '/follow-up/sms';
            document.getElementById('noteForm').action  = '/admin/estimates/' + estimateId + '/follow-up/note';

            // Set stage hidden fields
            document.getElementById('emailStage').value = stage;
            document.getElementById('smsStage').value   = stage;
            document.getElementById('noteStage').value  = stage;

            switchTab('email');
            loadDraft(estimateId, stage);
        }

        function closeFollowUpModal() {
            document.getElementById('followUpModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function switchTab(tab) {
            activeTab = tab;
            ['email', 'sms', 'note'].forEach(t => {
                document.getElementById('panel-' + t).classList.add('hidden');
                const btn = document.getElementById('tab-' + t);
                btn.classList.remove('border-blue-600', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            document.getElementById('panel-' + tab).classList.remove('hidden');
            const activeBtn = document.getElementById('tab-' + tab);
            activeBtn.classList.add('border-blue-600', 'text-blue-600');
            activeBtn.classList.remove('border-transparent', 'text-gray-500');

            if (tab !== 'note') {
                document.getElementById('modalLoading').classList.add('hidden');
            }
        }

        function loadDraft(estimateId, stage) {
            document.getElementById('modalLoading').classList.remove('hidden');
            ['email', 'sms', 'note'].forEach(t => document.getElementById('panel-' + t).classList.add('hidden'));

            fetch('/admin/estimates/' + estimateId + '/follow-up/draft?stage=' + stage, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('emailTo').value      = data.email.to || '';
                document.getElementById('emailSubject').value = data.email.subject || '';
                document.getElementById('emailBody').value    = data.email.body || '';
                document.getElementById('smsPhone').value     = data.sms.phone || '';
                document.getElementById('smsBody').value      = data.sms.body || '';
                updateSmsCount();
                document.getElementById('modalLoading').classList.add('hidden');
                switchTab(activeTab);
            })
            .catch(() => {
                document.getElementById('modalLoading').textContent = 'Failed to load draft.';
            });
        }

        function updateSmsCount() {
            const body = document.getElementById('smsBody');
            document.getElementById('smsCount').textContent = body ? body.value.length : 0;
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeFollowUpModal();
        });
    </script>
</x-app-layout>
