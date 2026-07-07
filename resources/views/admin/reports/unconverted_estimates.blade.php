<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1800px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">{{ session('error') }}</div>
                    @endif

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <a href="{{ route('admin.reports.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-1 inline-block">← Reports</a>
                            <h1 class="text-3xl font-bold">Unconverted Estimates</h1>
                        </div>
                        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                           class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 flex items-center gap-2">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Export CSV
                        </a>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" action="{{ route('admin.reports.unconvertedEstimates') }}" class="mb-6">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}"
                                           placeholder="Estimate #, job, customer..."
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Status</label>
                                    <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All Statuses</option>
                                        @foreach($statuses as $s)
                                            <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Project Manager</label>
                                    <select name="pm_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All PMs</option>
                                        @foreach($pmNames as $pm)
                                            <option value="{{ $pm }}" @selected(request('pm_name') === $pm)>{{ $pm }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Sent Status</label>
                                    <select name="sent" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All</option>
                                        <option value="sent"     @selected(request('sent') === 'sent')>Sent to Customer</option>
                                        <option value="not_sent" @selected(request('sent') === 'not_sent')>Not Yet Sent</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Estimator</label>
                                    <select name="estimator_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <option value="">All Estimators</option>
                                        @foreach($estimators as $user)
                                            <option value="{{ $user->id }}" @selected(request('estimator_id') == $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900">Date From / To</label>
                                    <div class="flex gap-2">
                                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                                    </div>
                                </div>

                            </div>
                            <div class="flex items-center gap-3 mt-4">
                                <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                                    <input type="checkbox" name="include_rejected" value="1"
                                           @checked(request()->boolean('include_rejected'))
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                    Include rejected
                                </label>
                                <button type="submit"
                                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2">
                                    Apply Filters
                                </button>
                                <a href="{{ route('admin.reports.unconvertedEstimates') }}"
                                   class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2">
                                    Reset
                                </a>
                                <div class="ml-auto flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Per page:</label>
                                    <select name="perPage" onchange="this.form.submit()"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2">
                                        @foreach([25, 50, 100] as $n)
                                            <option value="{{ $n }}" @selected(request('perPage', 25) == $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-2 gap-4 mb-6" style="max-width: 500px;">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase font-semibold mb-1">Total Estimates</p>
                            <p class="text-2xl font-bold text-gray-800">{{ number_format($totals->total_count) }}</p>
                        </div>
                        <div class="bg-teal-50 border border-teal-200 rounded-lg p-4">
                            <p class="text-xs text-teal-600 uppercase font-semibold mb-1">Total Value</p>
                            <p class="text-2xl font-bold text-teal-800">${{ number_format($totals->total_value ?? 0, 2) }}</p>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500" id="estimates-table">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" id="select-all"
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                    </th>
                                    <th class="px-4 py-3">Estimate #</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Job Name</th>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Homeowner</th>
                                    <th class="px-4 py-3">PM</th>
                                    <th class="px-4 py-3">Estimator</th>
                                    <th class="px-4 py-3 text-right">Grand Total</th>
                                    <th class="px-4 py-3">Sent</th>
                                    <th class="px-4 py-3">Aging</th>
                                    <th class="px-4 py-3">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($estimates as $estimate)
                                    <tr class="bg-white border-b hover:bg-gray-50"
                                        data-pm-email="{{ $estimate->opportunity?->projectManager?->email ?? '' }}">
                                        <td class="px-4 py-3">
                                            <input type="checkbox" value="{{ $estimate->id }}"
                                                   class="estimate-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 cursor-pointer">
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                            <a href="{{ route('pages.estimates.show', $estimate) }}" class="text-blue-600 hover:underline">
                                                {{ $estimate->estimate_number }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'draft'    => 'bg-gray-100 text-gray-600',
                                                    'sent'     => 'bg-blue-100 text-blue-800',
                                                    'revised'  => 'bg-indigo-100 text-indigo-800',
                                                    'approved' => 'bg-green-100 text-green-800',
                                                    'rejected' => 'bg-red-100 text-red-800',
                                                    'void'     => 'bg-gray-100 text-gray-500',
                                                ];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$estimate->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                {{ ucfirst($estimate->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-900">{{ $estimate->job_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->customer_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->homeowner_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->pm_name ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $estimate->creator?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900">${{ number_format($estimate->grand_total, 2) }}</td>
                                        <td class="px-4 py-3">
                                            @if($estimate->first_sent_at)
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Sent {{ $estimate->first_sent_at->format('M j, Y') }}
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                    Not sent
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if($estimate->first_sent_at)
                                                @php $days = (int) $estimate->first_sent_at->diffInDays(now()); @endphp
                                                @if($days >= 60)
                                                    <span class="px-2 py-0.5 rounded text-xs font-medium" style="background:#fee2e2;color:#991b1b;">{{ $days }}d ago</span>
                                                @elseif($days >= 30)
                                                    <span class="px-2 py-0.5 rounded text-xs font-medium" style="background:#ffedd5;color:#9a3412;">{{ $days }}d ago</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded text-xs font-medium" style="background:#f0fdf4;color:#166534;">{{ $days }}d ago</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $estimate->created_at->format('M j, Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="px-4 py-8 text-center text-gray-400">No unconverted estimates found matching your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 mb-40">
                        {{ $estimates->withQueryString()->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Floating Action Bar ──────────────────────────────────────────────── --}}
    <div id="action-bar"
         class="hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-lg px-6 py-3">
        <div class="flex items-center gap-4 max-w-screen-xl mx-auto">
            <span id="selected-count" class="text-sm font-medium text-gray-700"></span>
            <button onclick="clearSelection()"
                    class="text-xs text-gray-500 hover:text-gray-700 underline">
                Clear
            </button>
            <div class="flex items-center gap-3 ml-auto">
                <button onclick="downloadPdf()"
                        class="inline-flex items-center gap-2 text-white bg-teal-600 hover:bg-teal-700 font-medium rounded-lg text-sm px-4 py-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Download PDF
                </button>
                <button onclick="openEmailModal()"
                        class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-4 py-2"
                        data-bs-toggle="modal" data-bs-target="#email-modal">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
                    </svg>
                    Email Selected...
                </button>
            </div>
        </div>
    </div>

    {{-- Hidden PDF form --}}
    <form id="pdf-form" method="POST" action="{{ route('admin.reports.unconvertedEstimatesPdf') }}">
        @csrf
    </form>

    {{-- ── Email Modal ──────────────────────────────────────────────────────── --}}
    <div class="modal fade" id="email-modal" tabindex="-1" aria-labelledby="email-modal-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="email-form" method="POST" action="{{ route('admin.reports.unconvertedEstimatesEmail') }}">
                    @csrf
                    <div class="modal-header border-b border-gray-200 px-5 py-4">
                        <h5 class="text-lg font-semibold text-gray-900" id="email-modal-label">Email Estimate Status Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-5 py-4 space-y-4">

                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                            A PDF list of the selected estimates will be attached to this email.
                            <span id="modal-estimate-count" class="font-semibold"></span>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-sm font-medium text-gray-900">
                                To <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-400 font-normal ml-1">Separate multiple addresses with commas</span>
                            </label>
                            <input type="text" id="email-to" name="to"
                                   placeholder="pm@example.com, another@example.com"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-sm font-medium text-gray-900">
                                CC
                                <span class="text-xs text-gray-400 font-normal ml-1">Separate multiple addresses with commas</span>
                            </label>
                            <input type="text" id="email-cc" name="cc"
                                   placeholder="office@example.com"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        </div>

                        <div>
                            <label class="block mb-1.5 text-sm font-medium text-gray-900">Subject <span class="text-red-500">*</span></label>
                            <input type="text" id="email-subject" name="subject"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>

                        <div>
                            <label class="block mb-1.5 text-sm font-medium text-gray-900">Message <span class="text-red-500">*</span></label>
                            <textarea id="email-body" name="body" rows="8"
                                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                      required></textarea>
                        </div>

                    </div>
                    <div class="modal-footer border-t border-gray-200 px-5 py-4 flex justify-end gap-3">
                        <button type="button" data-bs-dismiss="modal"
                                class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit"
                                class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-5 py-2 inline-flex items-center gap-2">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                            </svg>
                            Send Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const checkboxes  = () => document.querySelectorAll('.estimate-checkbox');
        const checkedBoxes = () => document.querySelectorAll('.estimate-checkbox:checked');
        const selectAll   = document.getElementById('select-all');
        const actionBar   = document.getElementById('action-bar');
        const countLabel  = document.getElementById('selected-count');

        function updateBar() {
            const checked = checkedBoxes();
            const n       = checked.length;
            const total   = checkboxes().length;

            countLabel.textContent = n + ' estimate' + (n !== 1 ? 's' : '') + ' selected';
            actionBar.classList.toggle('hidden', n === 0);

            selectAll.checked       = n > 0 && n === total;
            selectAll.indeterminate = n > 0 && n < total;
        }

        selectAll.addEventListener('change', function () {
            checkboxes().forEach(cb => cb.checked = this.checked);
            updateBar();
        });

        document.getElementById('estimates-table').addEventListener('change', function (e) {
            if (e.target.classList.contains('estimate-checkbox')) updateBar();
        });

        window.clearSelection = function () {
            checkboxes().forEach(cb => cb.checked = false);
            selectAll.checked       = false;
            selectAll.indeterminate = false;
            updateBar();
        };

        window.downloadPdf = function () {
            const form = document.getElementById('pdf-form');
            form.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checkedBoxes().forEach(cb => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'ids[]';
                inp.value = cb.value;
                form.appendChild(inp);
            });
            form.submit();
        };

        window.openEmailModal = function () {
            const checked = checkedBoxes();
            const n       = checked.length;

            // Collect unique PM emails from row data attributes
            const pmEmails = new Set();
            checked.forEach(cb => {
                const email = cb.closest('tr').dataset.pmEmail;
                if (email) pmEmails.add(email);
            });

            document.getElementById('email-to').value = Array.from(pmEmails).join(', ');
            document.getElementById('modal-estimate-count').textContent =
                n + ' estimate' + (n !== 1 ? 's' : '') + ' selected.';

            // Default subject & body
            const today = new Date().toLocaleDateString('en-CA', { year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('email-subject').value =
                'Estimate Status Update Request — ' + today;
            document.getElementById('email-body').value =
                'Hi,\n\nPlease find attached a list of estimates that are currently open and awaiting your feedback.\n\nCould you please provide a status update on each job listed? Let us know if any of these are likely to proceed, have been declined, or require a revised estimate.\n\nThank you,\n{{ auth()->user()->name }}';

            // Populate hidden IDs in email form
            const emailForm = document.getElementById('email-form');
            emailForm.querySelectorAll('input[name="ids[]"]').forEach(el => el.remove());
            checked.forEach(cb => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = 'ids[]';
                inp.value = cb.value;
                emailForm.appendChild(inp);
            });

            new bootstrap.Modal(document.getElementById('email-modal')).show();
        };

        // Hide action bar while modal is open so it doesn't cover the modal footer
        const emailModalEl = document.getElementById('email-modal');
        emailModalEl.addEventListener('show.bs.modal', () => actionBar.classList.add('hidden'));
        emailModalEl.addEventListener('hidden.bs.modal', () => {
            if (checkedBoxes().length > 0) actionBar.classList.remove('hidden');
        });
    })();
    </script>

</x-app-layout>
