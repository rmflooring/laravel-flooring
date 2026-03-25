<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Installer</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create a new installer profile. Optionally link to an existing subcontractor vendor to pre-fill details.</p>
                </div>
                <a href="{{ route('admin.installers.index') }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Cancel
                </a>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-gray-800">
                    <p class="mb-2 text-sm font-semibold text-red-800 dark:text-red-400">Please fix the following errors:</p>
                    <ul class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.installers.store') }}"
                  x-data="installerForm({{ $subcontractors->toJson() }})">
                @csrf

                {{-- Link to subcontractor vendor --}}
                <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 shadow-sm dark:border-blue-800 dark:bg-gray-800">
                    <div class="border-b border-blue-200 px-6 py-4 dark:border-blue-800">
                        <h2 class="text-base font-semibold text-blue-900 dark:text-blue-300">Link Subcontractor Vendor (optional)</h2>
                        <p class="mt-0.5 text-xs text-blue-700 dark:text-blue-400">Select an existing subcontractor vendor to auto-fill the fields below. You can edit after.</p>
                    </div>
                    <div class="p-6">
                        <label for="vendor_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Subcontractor Vendor</label>
                        <select id="vendor_id" name="vendor_id"
                                x-model="selectedVendorId"
                                @change="prefill"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            <option value="">— No vendor link —</option>
                            @foreach ($subcontractors as $v)
                                <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>
                                    {{ $v->company_name }}
                                    @if ($v->city) ({{ $v->city }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Company & Contact --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Company & Contact</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                        <div>
                            <label for="company_name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Company Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="company_name" name="company_name"
                                   x-model="form.company_name"
                                   value="{{ old('company_name') }}" required
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            @error('company_name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact_name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Contact Name</label>
                            <input type="text" id="contact_name" name="contact_name"
                                   x-model="form.contact_name"
                                   value="{{ old('contact_name') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label for="phone" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                            <input type="text" id="phone" name="phone"
                                   x-model="form.phone"
                                   value="{{ old('phone') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500 phone-input">
                        </div>

                        <div>
                            <label for="mobile" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Mobile</label>
                            <input type="text" id="mobile" name="mobile"
                                   x-model="form.mobile"
                                   value="{{ old('mobile') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500 phone-input">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" id="email" name="email"
                                   x-model="form.email"
                                   value="{{ old('email') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500 email-input">
                        </div>
                    </div>
                </div>

                {{-- Address --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Address</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="address" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Street Address</label>
                            <input type="text" id="address" name="address"
                                   x-model="form.address"
                                   value="{{ old('address') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="address2" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Address 2</label>
                            <input type="text" id="address2" name="address2"
                                   x-model="form.address2"
                                   value="{{ old('address2') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label for="city" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
                            <input type="text" id="city" name="city"
                                   x-model="form.city"
                                   value="{{ old('city') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label for="province" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Province</label>
                            <select id="province" name="province"
                                    x-model="form.province"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                                @foreach ($provinces as $code => $name)
                                    <option value="{{ $code }}" {{ old('province') == $code ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="postal_code" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code"
                                   x-model="form.postal_code"
                                   value="{{ old('postal_code') }}"
                                   class="postal-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                    </div>
                </div>

                {{-- Account & Financial --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Account & Financial</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                        <div>
                            <label for="account_number" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Account Number</label>
                            <input type="text" id="account_number" name="account_number"
                                   x-model="form.account_number"
                                   value="{{ old('account_number') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label for="gst_number" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">GST #</label>
                            <input type="text" id="gst_number" name="gst_number"
                                   value="{{ old('gst_number') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label for="terms" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Terms</label>
                            <input type="text" id="terms" name="terms"
                                   x-model="form.terms"
                                   value="{{ old('terms') }}" placeholder="e.g. Net 30, COD"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>

                        <div>
                            <label for="status" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select id="status" name="status"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                                <option value="active"   {{ old('status', 'active') === 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label for="gl_cost_account_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Default GL Cost Account</label>
                            <select id="gl_cost_account_id" name="gl_cost_account_id"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                                <option value="">— None —</option>
                                @foreach ($glAccounts as $gl)
                                    <option value="{{ $gl->id }}" {{ old('gl_cost_account_id') == $gl->id ? 'selected' : '' }}>
                                        {{ $gl->account_number }} — {{ $gl->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="gl_sale_account_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Default GL Sale Account</label>
                            <select id="gl_sale_account_id" name="gl_sale_account_id"
                                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                                <option value="">— None —</option>
                                @foreach ($glAccounts as $gl)
                                    <option value="{{ $gl->id }}" {{ old('gl_sale_account_id') == $gl->id ? 'selected' : '' }}>
                                        {{ $gl->account_number }} — {{ $gl->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                    </div>
                    <div class="p-6">
                        <textarea id="notes" name="notes" rows="4"
                                  class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500"
                                  placeholder="Internal notes about this installer...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="submit"
                            class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Create Installer
                    </button>
                    <a href="{{ route('admin.installers.index') }}"
                       class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
    function installerForm(subcontractors) {
        return {
            selectedVendorId: '{{ old('vendor_id', '') }}',
            form: {
                company_name:   '{{ old('company_name', '') }}',
                contact_name:   '{{ old('contact_name', '') }}',
                phone:          '{{ old('phone', '') }}',
                mobile:         '{{ old('mobile', '') }}',
                email:          '{{ old('email', '') }}',
                address:        '{{ old('address', '') }}',
                address2:       '{{ old('address2', '') }}',
                city:           '{{ old('city', '') }}',
                province:       '{{ old('province', '') }}',
                postal_code:    '{{ old('postal_code', '') }}',
                account_number: '{{ old('account_number', '') }}',
                terms:          '{{ old('terms', '') }}',
            },

            prefill() {
                if (!this.selectedVendorId) return;
                const vendor = subcontractors.find(v => v.id == this.selectedVendorId);
                if (!vendor) return;
                this.form.company_name   = vendor.company_name   ?? '';
                this.form.contact_name   = vendor.contact_name   ?? '';
                this.form.phone          = vendor.phone          ?? '';
                this.form.mobile         = vendor.mobile         ?? '';
                this.form.email          = vendor.email          ?? '';
                this.form.address        = vendor.address        ?? '';
                this.form.address2       = vendor.address2       ?? '';
                this.form.city           = vendor.city           ?? '';
                this.form.province       = vendor.province       ?? '';
                this.form.postal_code    = vendor.postal_code    ?? '';
                this.form.account_number = vendor.account_number ?? '';
                this.form.terms          = vendor.terms          ?? '';
            },
        };
    }
    </script>
</x-app-layout>
