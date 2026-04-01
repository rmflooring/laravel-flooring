<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between" x-data="{ showDelete: false }">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit RFM</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Job #{{ $opportunity->job_no ?? '—' }} &mdash;
                        {{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @can('delete rfms')
                    {{-- Trash toggle --}}
                    <button type="button" @click="showDelete = !showDelete"
                            :title="showDelete ? 'Cancel delete' : 'Delete this RFM'"
                            :class="showDelete ? 'text-red-600 bg-red-50 border-red-300' : 'text-gray-400 bg-white border-gray-200 hover:border-red-300 hover:text-red-500'"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-lg border transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>

                    {{-- Inline delete confirmation strip --}}
                    <div x-show="showDelete" x-cloak
                         class="flex items-center gap-2 px-3 py-1.5 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        <span class="font-medium">Delete this RFM?</span>
                        <form method="POST" action="{{ route('pages.opportunities.rfms.destroy', [$opportunity->id, $rfm->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold underline hover:text-red-900">Yes</button>
                        </form>
                        <button type="button" @click="showDelete = false" class="font-semibold underline hover:text-red-900">No</button>
                        @role('admin')
                        <span class="text-red-300 mx-1">|</span>
                        <form method="POST" action="{{ route('pages.opportunities.rfms.force-destroy', [$opportunity->id, $rfm->id]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-semibold underline hover:text-red-900" title="Permanently delete — cannot be undone">Permanent</button>
                        </form>
                        @endrole
                    </div>
                    @endcan

                    <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between">
                    <div>{{ session('success') }}</div>
                    <button type="button" onclick="this.closest('div').remove()"
                            class="text-green-900 hover:text-green-700 text-sm font-medium">✕</button>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                {{-- Status (standalone mini-forms, outside main edit form) --}}
                <div class="p-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Status</h2>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $statusColors = [
                                'pending'   => ['active' => 'bg-yellow-100 text-yellow-800 ring-yellow-400', 'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                                'confirmed' => ['active' => 'bg-blue-100 text-blue-800 ring-blue-400',   'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                                'completed' => ['active' => 'bg-green-100 text-green-800 ring-green-400', 'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                                'cancelled' => ['active' => 'bg-red-100 text-red-800 ring-red-400',     'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                            ];
                        @endphp
                        @foreach(\App\Models\Rfm::STATUSES as $s)
                            <form method="POST"
                                  action="{{ route('pages.opportunities.rfms.updateStatus', [$opportunity->id, $rfm->id]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $s }}">
                                <button type="submit"
                                        class="px-4 py-1.5 rounded-full text-sm font-medium
                                            {{ $rfm->status === $s
                                                ? $statusColors[$s]['active'] . ' ring-2'
                                                : $statusColors[$s]['inactive'] }}">
                                    {{ ucfirst($s) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>

                {{-- Main Edit Form — wraps Job Info through Notifications --}}
                <form method="POST"
                      action="{{ route('pages.opportunities.rfms.update', [$opportunity->id, $rfm->id]) }}"
                      id="rfm-edit-form">
                    @csrf
                    @method('PATCH')

                    {{-- Job Info --}}
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Job Info</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            {{-- Parent Customer + PM --}}
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent Customer</label>
                                    <input type="text" disabled
                                           value="{{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}"
                                           class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-600">
                                </div>
                                @if($opportunity->projectManager)
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                                        <p class="font-medium text-gray-700 mb-1">PM: {{ $opportunity->projectManager->name }}</p>
                                        @if($opportunity->projectManager->phone)
                                            <p class="text-gray-500">{{ $opportunity->projectManager->phone }}</p>
                                        @endif
                                        @if($opportunity->projectManager->email)
                                            <p class="text-gray-500">{{ $opportunity->projectManager->email }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Job Site + Address --}}
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Site</label>
                                    <input type="text" disabled
                                           value="{{ $opportunity->jobSiteCustomer?->company_name ?: $opportunity->jobSiteCustomer?->name ?? '—' }}"
                                           class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-600">
                                </div>

                                <div>
                                    <label for="site_address" class="block text-sm font-medium text-gray-700 mb-1">
                                        Street Address
                                    </label>
                                    <input type="text" id="site_address" name="site_address"
                                           value="{{ old('site_address', $rfm->site_address) }}"
                                           placeholder="123 Main St"
                                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_address') border-red-500 @enderror">
                                    @error('site_address')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="site_address2" class="block text-sm font-medium text-gray-700 mb-1">
                                        Address 2 <span class="text-gray-400 font-normal">(Suite, Unit, etc.)</span>
                                    </label>
                                    <input type="text" id="site_address2" name="site_address2"
                                           value="{{ old('site_address2', $rfm->site_address2) }}"
                                           placeholder="Suite 100"
                                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_address2') border-red-500 @enderror">
                                    @error('site_address2')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label for="site_city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input type="text" id="site_city" name="site_city"
                                               value="{{ old('site_city', $rfm->site_city) }}"
                                               placeholder="City"
                                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_city') border-red-500 @enderror">
                                        @error('site_city')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="site_province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                                        <input type="text" id="site_province" name="site_province"
                                               value="{{ old('site_province', $rfm->site_province) }}"
                                               placeholder="BC"
                                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_province') border-red-500 @enderror">
                                        @error('site_province')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="site_postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                        <input type="text" id="site_postal_code" name="site_postal_code"
                                               value="{{ old('site_postal_code', $rfm->site_postal_code) }}"
                                               placeholder="A1A 1A1"
                                               class="postal-input w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_postal_code') border-red-500 @enderror">
                                        @error('site_postal_code')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Measure Details --}}
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Measure Details</h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            {{-- Estimator --}}
                            <div>
                                <label for="estimator_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Estimator <span class="text-red-500">*</span>
                                </label>
                                <select id="estimator_id" name="estimator_id"
                                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('estimator_id') border-red-500 @enderror">
                                    <option value="">— Select Estimator —</option>
                                    @foreach ($estimators as $e)
                                        <option value="{{ $e->id }}"
                                            {{ old('estimator_id', $rfm->estimator_id) == $e->id ? 'selected' : '' }}>
                                            {{ $e->first_name }} {{ $e->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('estimator_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Flooring Type --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Flooring Type <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    @foreach ($flooringTypes as $type)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="flooring_type[]" value="{{ $type }}"
                                                   {{ in_array($type, old('flooring_type', $rfm->flooring_type ?? [])) ? 'checked' : '' }}
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">{{ $type }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('flooring_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Scheduled Date/Time --}}
                            <div>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">
                                    Scheduled Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                                       value="{{ old('scheduled_at', $rfm->scheduled_at->format('Y-m-d\TH:i')) }}"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('scheduled_at') border-red-500 @enderror">
                                @error('scheduled_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                        {{-- Special Instructions --}}
                        <div class="mt-4">
                            <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-1">
                                Special Instructions
                            </label>
                            <textarea id="special_instructions" name="special_instructions" rows="4"
                                      class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('special_instructions') border-red-500 @enderror">{{ old('special_instructions', $rfm->special_instructions) }}</textarea>
                            @error('special_instructions')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Notifications --}}
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Notifications</h2>
                        <p class="text-xs text-gray-400 mb-4">Choose who to notify about this update. The estimator box is auto-checked when key fields change.</p>

                        @if($emailNotificationsEnabled)
                        <div class="space-y-3">

                            {{-- Notify Estimator --}}
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" id="notify_estimator" name="notify_estimator" value="1"
                                       class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Notify estimator about this change</span>
                                    <p class="text-xs text-gray-400 mt-0.5" id="estimator-notify-hint">
                                        They will receive an email showing what changed.
                                    </p>
                                </div>
                            </label>

                            {{-- Notify Project Manager --}}
                            @if($opportunity->projectManager && $opportunity->projectManager->email)
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="notify_pm" value="1"
                                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Notify Project Manager about this change</span>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $opportunity->projectManager->name }} &mdash; {{ $opportunity->projectManager->email }}
                                        </p>
                                    </div>
                                </label>
                            @else
                                <div class="flex items-start gap-3 opacity-50 cursor-not-allowed">
                                    <input type="checkbox" disabled
                                           class="mt-0.5 w-4 h-4 border-gray-300 rounded">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Notify Project Manager about this change</span>
                                        <p class="text-xs text-gray-400 mt-0.5">No PM with an email address is assigned to this opportunity.</p>
                                    </div>
                                </div>
                            @endif

                        </div>
                        @else
                        <div class="flex items-start gap-3 opacity-50 cursor-not-allowed select-none">
                            <div class="mt-0.5 flex flex-col gap-2">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" disabled class="w-4 h-4 border-gray-300 rounded">
                                    <span class="text-sm text-gray-500">Notify estimator about this change</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" disabled class="w-4 h-4 border-gray-300 rounded">
                                    <span class="text-sm text-gray-500">Notify Project Manager about this change</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-amber-600">
                            Email notifications are currently disabled. Contact your admin to enable them.
                        </p>
                        @endif
                    </div>

                    {{-- SMS Notifications --}}
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">SMS Notifications</h2>
                        <p class="text-xs text-gray-400 mb-4">Choose who to notify via SMS about this update.</p>

                        @if($smsRfmUpdatedEnabled)
                        <div class="space-y-3">

                            {{-- SMS Notify Estimator --}}
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="sms_notify_estimator" value="1"
                                       class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">SMS estimator</span>
                                    <p class="text-xs text-gray-400 mt-0.5" id="estimator-phone-hint">
                                        Select an estimator above to see their phone number.
                                    </p>
                                </div>
                            </label>

                            {{-- SMS Notify PM --}}
                            @if($opportunity->projectManager && $opportunity->projectManager->mobile)
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="sms_notify_pm" value="1"
                                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">SMS Project Manager</span>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $opportunity->projectManager->name }} &mdash; {{ $opportunity->projectManager->mobile }}
                                        </p>
                                    </div>
                                </label>
                            @else
                                <div class="flex items-start gap-3 opacity-50 cursor-not-allowed">
                                    <input type="checkbox" disabled
                                           class="mt-0.5 w-4 h-4 border-gray-300 rounded">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">SMS Project Manager</span>
                                        <p class="text-xs text-gray-400 mt-0.5">No PM with a mobile number is assigned to this opportunity.</p>
                                    </div>
                                </div>
                            @endif

                            {{-- SMS Notify Customer --}}
                            @if($smsRfmUpdatedToCustomer)
                                @php
                                    $customerPhone    = $opportunity->jobSiteCustomer?->mobile ?? $opportunity->jobSiteCustomer?->phone ?? null;
                                    $customerLabel    = $opportunity->jobSiteCustomer?->company_name ?: $opportunity->jobSiteCustomer?->name ?: 'Customer';
                                    $customerOptedOut = (bool) ($opportunity->jobSiteCustomer?->sms_opted_out);
                                @endphp
                                @if($customerOptedOut)
                                    <div class="flex items-start gap-3 opacity-50 cursor-not-allowed">
                                        <input type="checkbox" disabled class="mt-0.5 w-4 h-4 border-gray-300 rounded">
                                        <div>
                                            <span class="text-sm font-medium text-gray-500">SMS Customer</span>
                                            <p class="text-xs text-red-500 mt-0.5">{{ $customerLabel }} has opted out of SMS notifications.</p>
                                        </div>
                                    </div>
                                @elseif($customerPhone)
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input type="checkbox" name="sms_notify_customer" value="1"
                                               class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <div>
                                            <span class="text-sm font-medium text-gray-700">SMS Customer</span>
                                            <p class="text-xs text-gray-400 mt-0.5">{{ $customerLabel }} &mdash; {{ $customerPhone }}</p>
                                        </div>
                                    </label>
                                @else
                                    <div class="flex items-start gap-3 opacity-50 cursor-not-allowed">
                                        <input type="checkbox" disabled class="mt-0.5 w-4 h-4 border-gray-300 rounded">
                                        <div>
                                            <span class="text-sm font-medium text-gray-500">SMS Customer</span>
                                            <p class="text-xs text-gray-400 mt-0.5">No phone number on file for this customer.</p>
                                        </div>
                                    </div>
                                @endif
                            @endif

                        </div>
                        @else
                        <div class="flex items-start gap-3 opacity-50 cursor-not-allowed select-none">
                            <div class="mt-0.5 flex flex-col gap-2">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" disabled class="w-4 h-4 border-gray-300 rounded">
                                    <span class="text-sm text-gray-500">SMS estimator</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" disabled class="w-4 h-4 border-gray-300 rounded">
                                    <span class="text-sm text-gray-500">SMS Project Manager</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" disabled class="w-4 h-4 border-gray-300 rounded">
                                    <span class="text-sm text-gray-500">SMS Customer</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-amber-600">
                            SMS notifications are currently disabled. Contact your admin to enable them.
                        </p>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="p-6 flex justify-end gap-3">
                        <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    @php
        $estimatorEmails = $estimators->mapWithKeys(fn($e) => [$e->id => $e->email])->toArray();
        $estimatorPhones = $estimators->mapWithKeys(fn($e) => [$e->id => $e->phone])->toArray();
    @endphp

    <script>
        // Original values for change detection
        const origValues = {
            estimator_id:    '{{ $rfm->estimator_id }}',
            scheduled_at:    '{{ $rfm->scheduled_at->format('Y-m-d\TH:i') }}',
            site_address:    @json($rfm->site_address ?? ''),
            site_address2:   @json($rfm->site_address2 ?? ''),
            site_city:       @json($rfm->site_city ?? ''),
            site_province:   @json($rfm->site_province ?? ''),
            site_postal_code:@json($rfm->site_postal_code ?? ''),
        };

        const estimatorEmails    = @json($estimatorEmails);
        const estimatorPhones    = @json($estimatorPhones);
        const watchedFields      = ['estimator_id', 'scheduled_at', 'site_address', 'site_address2', 'site_city', 'site_province', 'site_postal_code'];
        const notifyEstimatorBox = document.getElementById('notify_estimator');
        const estimatorHint      = document.getElementById('estimator-notify-hint');
        const estimatorPhoneHint = document.getElementById('estimator-phone-hint');

        function hasKeyFieldChanged() {
            return watchedFields.some(id => {
                const el = document.getElementById(id);
                return el && el.value !== origValues[id];
            });
        }

        function updateEstimatorHints() {
            const estimatorId = document.getElementById('estimator_id').value;

            if (estimatorHint) {
                const email = estimatorEmails[estimatorId] || null;
                if (email) {
                    estimatorHint.textContent = 'Will be sent to: ' + email + '. They will receive an email showing what changed.';
                } else {
                    estimatorHint.textContent = 'They will receive an email showing what changed.';
                }
            }

            if (estimatorPhoneHint) {
                const phone = estimatorPhones[estimatorId] || null;
                if (phone) {
                    estimatorPhoneHint.textContent = 'Will be sent to: ' + phone;
                } else if (estimatorId) {
                    estimatorPhoneHint.textContent = 'This estimator has no phone number on record.';
                } else {
                    estimatorPhoneHint.textContent = 'Select an estimator above to see their phone number.';
                }
            }
        }

        function onFieldChange() {
            if (notifyEstimatorBox && hasKeyFieldChanged()) {
                notifyEstimatorBox.checked = true;
            }
            updateEstimatorHints();
        }

        watchedFields.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', onFieldChange);
            if (el && (el.tagName === 'INPUT' && el.type !== 'checkbox')) {
                el.addEventListener('input', onFieldChange);
            }
        });

        updateEstimatorHints();
    </script>
</x-app-layout>
