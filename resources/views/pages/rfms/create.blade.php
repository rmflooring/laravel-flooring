<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Request for Measure</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Job #{{ $opportunity->job_no ?? '—' }} &mdash;
                        {{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}
                    </p>
                </div>
                <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
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

            <form method="POST" action="{{ route('pages.opportunities.rfms.store', $opportunity->id) }}">
                @csrf

                <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                    {{-- Job Info (read-only) --}}
                    <div class="p-6">
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

                                {{-- Site Address fields --}}
                                <div>
                                    <label for="site_address" class="block text-sm font-medium text-gray-700 mb-1">
                                        Street Address
                                    </label>
                                    <input type="text" id="site_address" name="site_address"
                                           value="{{ old('site_address', $opportunity->jobSiteCustomer?->address) }}"
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
                                           value="{{ old('site_address2', $opportunity->jobSiteCustomer?->address2) }}"
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
                                               value="{{ old('site_city', $opportunity->jobSiteCustomer?->city) }}"
                                               placeholder="City"
                                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_city') border-red-500 @enderror">
                                        @error('site_city')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="site_province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                                        <input type="text" id="site_province" name="site_province"
                                               value="{{ old('site_province', $opportunity->jobSiteCustomer?->province) }}"
                                               placeholder="BC"
                                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_province') border-red-500 @enderror">
                                        @error('site_province')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="site_postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                                        <input type="text" id="site_postal_code" name="site_postal_code"
                                               value="{{ old('site_postal_code', $opportunity->jobSiteCustomer?->postal_code) }}"
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
                    <div class="p-6">
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
                                        <option value="{{ $e->id }}" {{ old('estimator_id') == $e->id ? 'selected' : '' }}>
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
                                                   {{ in_array($type, old('flooring_type', [])) ? 'checked' : '' }}
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
                                       value="{{ old('scheduled_at') }}"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('scheduled_at') border-red-500 @enderror">
                                @error('scheduled_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>


                        </div>
                    </div>

                    {{-- Special Instructions --}}
                    <div class="p-6">
                        <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-1">
                            Special Instructions
                        </label>
                        <textarea id="special_instructions" name="special_instructions" rows="4"
                                  placeholder="Parking instructions, access codes, contact on site, etc."
                                  class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('special_instructions') border-red-500 @enderror">{{ old('special_instructions') }}</textarea>
                        @error('special_instructions')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notifications --}}
                    <div class="p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Notifications</h2>
                        <p class="text-xs text-gray-400 mb-4">Choose who to notify when this RFM is created.</p>

                        @if($emailNotificationsEnabled)
                        <div class="space-y-3">

                            {{-- Notify Estimator --}}
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="notify_estimator" value="1" checked
                                       class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Notify estimator</span>
                                    <p class="text-xs text-gray-400 mt-0.5" id="estimator-email-hint">
                                        Select an estimator above to see their email.
                                    </p>
                                </div>
                            </label>

                            {{-- Notify Project Manager --}}
                            @if($opportunity->projectManager && $opportunity->projectManager->email)
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" name="notify_pm" value="1"
                                           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Notify Project Manager</span>
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
                                        <span class="text-sm font-medium text-gray-500">Notify Project Manager</span>
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
                                    <span class="text-sm text-gray-500">Notify estimator</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" disabled class="w-4 h-4 border-gray-300 rounded">
                                    <span class="text-sm text-gray-500">Notify Project Manager</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-amber-600">
                            Email notifications are currently disabled. Contact your admin to enable them.
                        </p>
                        @endif
                    </div>

                    {{-- SMS Notifications --}}
                    <div class="p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">SMS Notifications</h2>
                        <p class="text-xs text-gray-400 mb-4">Choose who to notify via SMS when this RFM is created.</p>

                        @if($smsRfmBookedEnabled)
                        <div class="space-y-3">

                            {{-- SMS Notify Estimator --}}
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="sms_notify_estimator" value="1" checked
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
                                    <input type="checkbox" name="sms_notify_pm" value="1" checked
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
                            Create RFM
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>

    @php
        $estimatorEmails = $estimators->mapWithKeys(fn($e) => [$e->id => $e->email])->toArray();
        $estimatorPhones = $estimators->mapWithKeys(fn($e) => [$e->id => $e->phone])->toArray();
    @endphp

    <script>
        const estimatorEmails = @json($estimatorEmails);
        const estimatorPhones = @json($estimatorPhones);
        const estimatorSelect = document.getElementById('estimator_id');
        const estimatorHint   = document.getElementById('estimator-email-hint');
        const estimatorPhoneHint = document.getElementById('estimator-phone-hint');

        function updateEstimatorHint() {
            const id    = estimatorSelect.value;
            const email = estimatorEmails[id] || null;
            if (email) {
                estimatorHint.textContent = 'Will be sent to: ' + email;
            } else if (id) {
                estimatorHint.textContent = 'This estimator has no email address on record.';
            } else {
                estimatorHint.textContent = 'Select an estimator above to see their email.';
            }

            if (estimatorPhoneHint) {
                const phone = estimatorPhones[id] || null;
                if (phone) {
                    estimatorPhoneHint.textContent = 'Will be sent to: ' + phone;
                } else if (id) {
                    estimatorPhoneHint.textContent = 'This estimator has no phone number on record.';
                } else {
                    estimatorPhoneHint.textContent = 'Select an estimator above to see their phone number.';
                }
            }
        }

        estimatorSelect.addEventListener('change', updateEstimatorHint);
        updateEstimatorHint();
    </script>
</x-app-layout>
