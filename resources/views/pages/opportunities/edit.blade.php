<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Opportunity</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Update this opportunity and its linked customer and job site.
                    </p>
                </div>

                <a href="{{ url()->previous() }}"
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

            {{-- Flash Errors --}}
            @if (session('error'))
                <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Flash Success --}}
            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between">
                    <div>{{ session('success') }}</div>

                    <button type="button"
                            onclick="this.closest('div').remove()"
                            class="text-green-900 hover:text-green-700 text-sm font-medium">
                        ✕
                    </button>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm">

                {{-- Top Row --}}
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Job No</label>
                            <input type="text" name="job_no" form="opportunity-form"
                                   value="{{ old('job_no', $opportunity->job_no) }}"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div class="md:col-span-3" x-data="{ status: '{{ old('status', $opportunity->status) }}', activeSales: {{ $activeSaleCount }} }">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" form="opportunity-form" x-model="status"
                                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', $opportunity->status) === $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>

                            <div x-show="status === 'Lost' && activeSales > 0" x-cloak class="mt-2 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-2.5 text-xs text-amber-800">
                                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                                <span>This opportunity has <strong x-text="activeSales"></strong> active <span x-text="activeSales === 1 ? 'job' : 'jobs'"></span>. Cancel all active jobs before marking as Lost.</span>
                            </div>

                            <div x-show="status === 'Lost' || status === 'Closed'" x-cloak class="mt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <textarea name="status_reason" form="opportunity-form" rows="3"
                                          placeholder="Enter reason for this status…"
                                          class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm resize-none">{{ old('status_reason', $opportunity->status_reason) }}</textarea>
                            </div>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person 1</label>
                           <select name="sales_person_1" form="opportunity-form"
								class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
							<option value="">-- Select --</option>
							@foreach ($employees as $e)
								<option value="{{ $e->id }}" @selected((string) $opportunity->sales_person_1 === (string) $e->id)>
									{{ $e->first_name }}
								</option>
							@endforeach
						</select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person 2</label>
                            <select name="sales_person_2" form="opportunity-form"
								class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
							<option value="">-- Select --</option>
							@foreach ($employees as $e)
								<option value="{{ $e->id }}" @selected((string) $opportunity->sales_person_2 === (string) $e->id)>
									{{ $e->first_name }}
								</option>
							@endforeach
						</select>
                        </div>

                        <div class="md:col-span-3 flex items-center gap-3 pt-6">
                            <input type="checkbox" name="requires_rfm" id="requires_rfm" form="opportunity-form" value="1"
                                   {{ old('requires_rfm', $opportunity->requires_rfm) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 bg-gray-50 border-gray-300 rounded">
                            <label for="requires_rfm" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Requires RFM
                                <span class="block text-xs font-normal text-gray-500">Site visit needed before estimate</span>
                            </label>
                        </div>

                        <div class="md:col-span-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Project Manager</label>

                            <select id="project_manager_id" name="project_manager_id" form="opportunity-form" disabled
                                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                                <option value="">— Select PM —</option>
                            </select>

                            <p class="mt-1 text-xs text-gray-500" id="pm-help">
                                Select a Parent Customer first to see available PMs.
                            </p>
                        </div>

                    </div>
                </div>

                {{-- Customers --}}
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                        <div class="lg:col-span-6">
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                                <h2 class="text-lg font-semibold mb-3">Parent Customer</h2>

                                <select id="parent_customer_select" name="parent_customer_id" form="opportunity-form"
                                        class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm">
                                    <option value="">— Select Parent Customer —</option>
                                    @foreach ($parentCustomers as $c)
                                        <option value="{{ $c->id }}"
                                            {{ (string)old('parent_customer_id', $opportunity->parent_customer_id) === (string)$c->id ? 'selected' : '' }}>
                                            {{ $c->company_name ?: $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="lg:col-span-6">
                            <div x-data="jobSiteEdit()" class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                                <div class="flex justify-between items-center mb-3">
                                    <h2 class="text-lg font-semibold">Job Site</h2>

                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                x-show="selectedId"
                                                x-cloak
                                                @click="openEdit()"
                                                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                            Edit Job Site
                                        </button>
                                        <button id="same-as-parent-btn"
                                                type="button"
                                                style="display:none"
                                                class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                            Same as Parent
                                        </button>
                                        <button id="open-job-site-modal"
                                                type="button"
                                                data-modal-target="create-job-site-modal"
                                                data-modal-toggle="create-job-site-modal"
                                                class="px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg">
                                            + Create Job Site
                                        </button>
                                    </div>
                                </div>

                                <select id="job_site_customer_id"
                                        name="job_site_customer_id"
                                        form="opportunity-form"
                                        class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm"
                                        @change="selectedId = $event.target.value"
                                        disabled>
                                    <option value="">— Select Job Site —</option>

                                    @foreach ($jobSiteCustomers as $js)
                                        <option value="{{ $js->id }}"
                                                data-parent-id="{{ $js->parent_id }}"
                                                {{ (string)old('job_site_customer_id', $opportunity->job_site_customer_id) === (string)$js->id ? 'selected' : '' }}>
                                            {{ $js->company_name ?: $js->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs text-gray-500">
                                    Select a Parent Customer first to see available job sites.
                                </p>

                                {{-- Edit Job Site Modal --}}
                                <div x-show="editOpen"
                                     x-cloak
                                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                     style="background: rgba(0,0,0,0.5)">
                                    <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto"
                                         @click.outside="editOpen = false">

                                        <form method="POST" :action="editAction">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="redirect_to" value="{{ request()->url() }}">

                                            <div class="flex items-center justify-between px-6 py-4 border-b">
                                                <h3 class="text-lg font-semibold">Edit Job Site</h3>
                                                <button type="button" @click="editOpen = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                                            </div>

                                            <div class="p-6 space-y-4">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Name</label>
                                                        <input type="text" name="name" x-model="form.name"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Company Name</label>
                                                        <input type="text" name="company_name" x-model="form.company_name"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Email</label>
                                                        <input type="email" name="email" x-model="form.email"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm email-input">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Phone</label>
                                                        <input type="text" name="phone" x-model="form.phone"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm phone-input">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Mobile</label>
                                                        <input type="text" name="mobile" x-model="form.mobile"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Address</label>
                                                        <input type="text" name="address" x-model="form.address"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Address 2</label>
                                                        <input type="text" name="address2" x-model="form.address2"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">City</label>
                                                        <input type="text" name="city" x-model="form.city"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Province</label>
                                                        <select name="province" x-model="form.province"
                                                                class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                            <option value="">Select Province</option>
                                                            <option value="AB">Alberta</option>
                                                            <option value="BC">British Columbia</option>
                                                            <option value="MB">Manitoba</option>
                                                            <option value="NB">New Brunswick</option>
                                                            <option value="NL">Newfoundland and Labrador</option>
                                                            <option value="NS">Nova Scotia</option>
                                                            <option value="NT">Northwest Territories</option>
                                                            <option value="NU">Nunavut</option>
                                                            <option value="ON">Ontario</option>
                                                            <option value="PE">Prince Edward Island</option>
                                                            <option value="QC">Quebec</option>
                                                            <option value="SK">Saskatchewan</option>
                                                            <option value="YT">Yukon</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium mb-1">Postal Code</label>
                                                        <input type="text" name="postal_code" x-model="form.postal_code"
                                                               @blur="(function(){ var c = form.postal_code.replace(/\s/g,'').toUpperCase(); if(/^[A-Z]\d[A-Z]\d[A-Z]\d$/.test(c)){ form.postal_code = c.slice(0,3)+' '+c.slice(3); } })()"
                                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                    </div>

                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium mb-1">Notes</label>
                                                    <textarea name="notes" rows="3" x-model="form.notes"
                                                              class="w-full border border-gray-300 rounded-lg p-2 text-sm"></textarea>
                                                </div>

                                                {{-- Insurance Details --}}
                                                <div class="col-span-1 md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Insurance Details</p>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                                        <div>
                                                            <label class="block text-sm font-medium mb-1">Insurance Co.</label>
                                                            <input type="text" name="insurance_company" x-model="form.insurance_company"
                                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium mb-1">Adjuster</label>
                                                            <input type="text" name="adjuster" x-model="form.adjuster"
                                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium mb-1">Policy #</label>
                                                            <input type="text" name="policy_number" x-model="form.policy_number"
                                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium mb-1">Claim #</label>
                                                            <input type="text" name="claim_number" x-model="form.claim_number"
                                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium mb-1">DOL (Date of Loss)</label>
                                                            <input type="date" name="dol" x-model="form.dol"
                                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                                <button type="button" @click="editOpen = false"
                                                        class="px-4 py-2 text-sm border rounded-lg">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                        class="px-4 py-2 text-sm text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>

                {{-- Create Job Site Modal --}}
                <div id="create-job-site-modal" tabindex="-1" aria-hidden="true"
                     class="hidden fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">

                    <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl my-auto flex flex-col max-h-[90vh]">
                        <form method="POST" action="{{ route('pages.job-sites.store') }}" class="flex flex-col min-h-0 flex-1">
                            @csrf

                            <input type="hidden" name="redirect_to" value="{{ route('pages.opportunities.edit', $opportunity->id) }}">
                            <input type="hidden" name="parent_id" id="job_site_parent_id">

                            <div class="p-6 space-y-4 overflow-y-auto flex-1">
                                <h3 class="text-lg font-semibold">Create Job Site (Customer)</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Name *</label>
                                        <input type="text" name="name"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Company Name</label>
                                        <input type="text" name="company_name"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Email</label>
                                        <input type="email" name="email"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm email-input">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Phone</label>
                                        <input type="text" name="phone"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm phone-input">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Mobile</label>
                                        <input type="text" name="mobile"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Parent Customer</label>
                                        <input type="text" id="job_site_parent_display" disabled
                                               class="w-full bg-gray-100 border border-gray-300 rounded-lg p-2 text-sm"
                                               value="(Selected parent will apply)">
                                        <p class="mt-1 text-xs text-gray-500">
                                            This job site will be saved under the selected Parent Customer.
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Address</label>
                                        <input type="text" name="address"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Address 2</label>
                                        <input type="text" name="address2"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">City</label>
                                        <input type="text" name="city"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Province</label>
                                        <select name="province"
                                                class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                            <option value="">Select Province</option>
                                            <option value="AB">Alberta</option>
                                            <option value="BC">British Columbia</option>
                                            <option value="MB">Manitoba</option>
                                            <option value="NB">New Brunswick</option>
                                            <option value="NL">Newfoundland and Labrador</option>
                                            <option value="NS">Nova Scotia</option>
                                            <option value="NT">Northwest Territories</option>
                                            <option value="NU">Nunavut</option>
                                            <option value="ON">Ontario</option>
                                            <option value="PE">Prince Edward Island</option>
                                            <option value="QC">Quebec</option>
                                            <option value="SK">Saskatchewan</option>
                                            <option value="YT">Yukon</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Postal Code</label>
                                        <input type="text" name="postal_code"
                                               class="postal-input w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Customer Type</label>
                                        <select name="customer_type"
                                                class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                            <option value="individual" selected>Individual</option>
                                            <option value="company">Company</option>
                                            <option value="restoration">Restoration</option>
                                            <option value="builder">Builder</option>
                                            <option value="property_manager">Property Manager</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Customer Status</label>
                                        <select name="customer_status"
                                                class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                            <option value="Active" selected>Active</option>
                                            <option value="Inactive">Inactive</option>
                                            <option value="Archived">Archived</option>
                                        </select>
                                    </div>

                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1">Notes</label>
                                    <textarea name="notes" rows="3"
                                              class="w-full border border-gray-300 rounded-lg p-2 text-sm"></textarea>
                                </div>

                                {{-- Insurance Details --}}
                                <div class="col-span-1 md:col-span-2 border-t border-gray-200 pt-4 mt-2">
                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Insurance Details</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                        <div>
                                            <label class="block text-sm font-medium mb-1">Insurance Co.</label>
                                            <input type="text" name="insurance_company"
                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium mb-1">Adjuster</label>
                                            <input type="text" name="adjuster"
                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium mb-1">Policy #</label>
                                            <input type="text" name="policy_number"
                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium mb-1">Claim #</label>
                                            <input type="text" name="claim_number"
                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium mb-1">DOL (Date of Loss)</label>
                                            <input type="date" name="dol"
                                                   class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                <button type="button"
                                        data-modal-hide="create-job-site-modal"
                                        class="px-4 py-2 text-sm border rounded-lg">
                                    Cancel
                                </button>

                                <button type="submit"
                                        class="px-4 py-2 text-sm text-white bg-blue-700 rounded-lg">
                                    Create Job Site
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="p-6 border-t border-gray-200 flex justify-end">
                    <form id="opportunity-form" method="POST" action="{{ route('pages.opportunities.update', $opportunity->id) }}">
                        @csrf
                        @method('PUT')

                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg">
                            Update Opportunity
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        const parentCustomerData = @json($parentCustomers->keyBy('id'));

        document.addEventListener('DOMContentLoaded', () => {
            const parentSelect =
                document.getElementById('parent_customer_select') ||
                document.querySelector('select[name="parent_customer_id"]');

            const jobSiteSelect = document.getElementById('job_site_customer_id');
            const pmSelect = document.getElementById('project_manager_id');
            const sameAsParentBtn = document.getElementById('same-as-parent-btn');

            // Desired values for edit (also respect validation failures)
            const desiredParentId = @json(old('parent_customer_id', $opportunity->parent_customer_id));
            const desiredJobSiteId = @json(old('job_site_customer_id', $opportunity->job_site_customer_id));
            const desiredPmId = @json(old('project_manager_id', $opportunity->project_manager_id));

            if (!parentSelect) return;

            // Ensure parent select is set (usually already via selected attr)
            if (desiredParentId && !parentSelect.value) {
                parentSelect.value = String(desiredParentId);
            }

            // ----------------------------
            // Job Site filtering
            // ----------------------------
            const allJobSiteOptions = jobSiteSelect
                ? Array.from(jobSiteSelect.querySelectorAll('option')).filter(o => o.value)
                : [];

            function filterJobSites() {
                if (!jobSiteSelect) return;

                const parentId = parentSelect.value;

                // Hide all job site options first
                allJobSiteOptions.forEach(o => (o.hidden = true));

                if (!parentId) {
                    jobSiteSelect.value = "";
                    jobSiteSelect.disabled = true;
                    if (sameAsParentBtn) sameAsParentBtn.style.display = 'none';
                    return;
                }

                jobSiteSelect.disabled = false;
                if (sameAsParentBtn) sameAsParentBtn.style.display = '';

                // Unhide only options that match selected parent
                allJobSiteOptions.forEach(o => {
                    o.hidden = (o.dataset.parentId !== parentId);
                });

                // Re-select saved job site if it belongs to this parent
                if (desiredJobSiteId) {
                    const opt = jobSiteSelect.querySelector(`option[value="${desiredJobSiteId}"]`);
                    if (opt && opt.dataset.parentId === parentId) {
                        jobSiteSelect.value = String(desiredJobSiteId);
                        return;
                    }
                }

                // Otherwise clear selection if current selection doesn't match visible set
                jobSiteSelect.value = "";
            }

            // ----------------------------
            // Same as Parent — pre-fill Create Job Site modal
            // ----------------------------
            function fillModalFromParent() {
                const parentId = parentSelect.value;
                if (!parentId || !parentCustomerData[parentId]) return;

                const p = parentCustomerData[parentId];
                const modal = document.getElementById('create-job-site-modal');
                if (!modal) return;

                const set = (name, val) => {
                    const el = modal.querySelector(`[name="${name}"]`);
                    if (el) el.value = val || '';
                };

                set('name',          p.name);
                set('company_name',  p.company_name);
                set('email',         p.email);
                set('phone',         p.phone);
                set('mobile',        p.mobile);
                set('address',       p.address);
                set('address2',      p.address2);
                set('city',          p.city);
                set('postal_code',   p.postal_code);
                set('customer_type', p.customer_type);

                const provinceEl = modal.querySelector('[name="province"]');
                if (provinceEl) provinceEl.value = p.province || '';

                // Open the modal (Flowbite or fallback)
                if (window.FlowbiteInstances) {
                    const instance = window.FlowbiteInstances.getInstance('Modal', 'create-job-site-modal');
                    if (instance) { instance.show(); return; }
                }
                modal.classList.remove('hidden');
                modal.removeAttribute('aria-hidden');
            }

            if (sameAsParentBtn) {
                sameAsParentBtn.addEventListener('click', fillModalFromParent);
            }

            // ----------------------------
            // PM filtering
            // ----------------------------
            function resetPmSelect(message = '— Select PM —', disabled = true) {
                if (!pmSelect) return;

                pmSelect.innerHTML = '';
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = message;
                pmSelect.appendChild(opt);

                pmSelect.value = '';
                pmSelect.disabled = disabled;
            }

            async function loadProjectManagers() {
                if (!pmSelect) return;

                const parentId = parentSelect.value;

                // Reset immediately when parent changes
                resetPmSelect(parentId ? 'Loading PMs…' : '— Select Parent Customer first —', true);

                if (!parentId) return;

                try {
                    const url = `{{ url('/pages/customers') }}/${encodeURIComponent(parentId)}/project-managers`;
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

                    if (!res.ok) throw new Error('Failed to load PMs');

                    const pms = await res.json();

                    // 0 PMs: friendly message + keep disabled
                    if (!Array.isArray(pms) || pms.length === 0) {
                        resetPmSelect('No project managers found for this customer', true);
                        return;
                    }

                    // Rebuild options
                    pmSelect.innerHTML = '';

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = '— Select PM —';
                    pmSelect.appendChild(placeholder);

                    pms.forEach(pm => {
                        const opt = document.createElement('option');
                        opt.value = pm.id;
                        opt.textContent = pm.name ?? '(Unnamed PM)';
                        pmSelect.appendChild(opt);
                    });

                    pmSelect.disabled = false;

                    // Prefer re-selecting saved PM if present and valid
                    if (desiredPmId) {
                        const exists = Array.from(pmSelect.options).some(o => String(o.value) === String(desiredPmId));
                        if (exists) {
                            pmSelect.value = String(desiredPmId);
                            return;
                        }
                    }

                    // Otherwise auto-select if only 1 PM
                    if (pms.length === 1) {
                        pmSelect.value = String(pms[0].id);
                    } else {
                        pmSelect.value = '';
                    }
                } catch (e) {
                    resetPmSelect('Unable to load PMs', true);
                    console.error(e);
                }
            }

            // When parent changes: update both job sites + PMs
            parentSelect.addEventListener('change', () => {
                filterJobSites();
                loadProjectManagers();

                // Also update the Job Site modal hidden parent + display
                const hiddenParent = document.getElementById('job_site_parent_id');
                const parentDisplay = document.getElementById('job_site_parent_display');
                if (hiddenParent) hiddenParent.value = parentSelect.value || '';
                if (parentDisplay) {
                    const txt = parentSelect.options[parentSelect.selectedIndex]?.textContent?.trim() || '(Selected parent will apply)';
                    parentDisplay.value = txt;
                }
            });

            // Initialize modal parent fields immediately
            const hiddenParent = document.getElementById('job_site_parent_id');
            const parentDisplay = document.getElementById('job_site_parent_display');
            if (hiddenParent) hiddenParent.value = parentSelect.value || '';
            if (parentDisplay) {
                const txt = parentSelect.options[parentSelect.selectedIndex]?.textContent?.trim() || '(Selected parent will apply)';
                parentDisplay.value = txt;
            }

            // Run once on load
            filterJobSites();
            loadProjectManagers();
        });
    </script>

    <script>
        const jobSiteData = @json($jobSiteCustomers->keyBy('id'));

        function jobSiteEdit() {
            return {
                editOpen: false,
                selectedId: '{{ old('job_site_customer_id', $opportunity->job_site_customer_id) }}',
                editAction: '',
                form: {
                    name: '', company_name: '', email: '', phone: '', mobile: '',
                    address: '', address2: '', city: '', province: '', postal_code: '', notes: '',
                    insurance_company: '', adjuster: '', policy_number: '', claim_number: '', dol: ''
                },
                openEdit() {
                    const site = jobSiteData[this.selectedId];
                    if (!site) return;
                    this.editAction = '/pages/job-sites/' + site.id;
                    this.form.name              = site.name || '';
                    this.form.company_name      = site.company_name || '';
                    this.form.email             = site.email || '';
                    this.form.phone             = site.phone || '';
                    this.form.mobile            = site.mobile || '';
                    this.form.address           = site.address || '';
                    this.form.address2          = site.address2 || '';
                    this.form.city              = site.city || '';
                    this.form.province          = site.province || '';
                    this.form.postal_code       = site.postal_code || '';
                    this.form.notes             = site.notes || '';
                    this.form.insurance_company = site.insurance_company || '';
                    this.form.adjuster          = site.adjuster || '';
                    this.form.policy_number     = site.policy_number || '';
                    this.form.claim_number      = site.claim_number || '';
                    this.form.dol               = site.dol || '';
                    this.editOpen = true;
                }
            };
        }
    </script>
</x-app-layout>
