<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Create Opportunity</h1>
                    <p class="text-sm text-gray-600 mt-1">
                        Create a new opportunity and link it to a customer and job site.
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
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div class="md:col-span-3" x-data="{ status: '{{ old('status', 'New') }}' }">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" form="opportunity-form" x-model="status"
                                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ old('status', 'New') === $status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>

                            <div x-show="status === 'Lost' || status === 'Closed'" x-cloak class="mt-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <textarea name="status_reason" form="opportunity-form" rows="3"
                                          placeholder="Enter reason for this status…"
                                          class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm resize-none">{{ old('status_reason') }}</textarea>
                            </div>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person 1</label>
                            <select name="sales_person_1" form="opportunity-form"
									class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
								<option value="">-- Select --</option>
								@foreach ($employees as $e)
									<option value="{{ $e->id }}">{{ $e->first_name }}</option>
								@endforeach
							</select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person 2</label>
                            <select name="sales_person_2" form="opportunity-form"
									class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
								<option value="">-- Select --</option>
								@foreach ($employees as $e)
									<option value="{{ $e->id }}">{{ $e->first_name }}</option>
								@endforeach
							</select>
                        </div>

                        <div class="md:col-span-3 flex items-center gap-3 pt-6">
                            <input type="checkbox" name="requires_rfm" id="requires_rfm" form="opportunity-form" value="1"
                                   {{ old('requires_rfm', true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 bg-gray-50 border-gray-300 rounded">
                            <label for="requires_rfm" class="text-sm font-medium text-gray-700 cursor-pointer">
                                Requires RFM
                                <span class="block text-xs font-normal text-gray-500">Site visit needed before estimate</span>
                            </label>
                        </div>

                       <div class="md:col-span-6">
    <label class="block text-sm font-medium text-gray-700 mb-1">Project Manager</label>

    <select id="project_manager_id" name="project_manager_id" form="opportunity-form" disabled>
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
                                <div class="flex items-center justify-between mb-3">
                                    <h2 class="text-lg font-semibold">Parent Customer</h2>
                                    <button type="button"
                                            onclick="document.getElementById('create-parent-customer-modal').classList.remove('hidden')"
                                            class="px-3 py-1.5 text-xs font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                        + New Customer
                                    </button>
                                </div>

                                <div x-data="parentCustomerTypeahead()" @click.outside="isOpen = false">

                                    {{-- Hidden input submitted with the form --}}
                                    <input type="hidden" id="parent_customer_id_input"
                                           name="parent_customer_id" form="opportunity-form"
                                           :value="selectedId">

                                    <div class="relative">
                                        <input type="text"
                                               x-ref="searchInput"
                                               x-model="query"
                                               @focus="handleFocus()"
                                               @input.debounce.300ms="search()"
                                               placeholder="Search by name or company…"
                                               autocomplete="off"
                                               class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-blue-300 focus:border-blue-400">

                                        {{-- Dropdown --}}
                                        <div x-show="isOpen" x-cloak
                                             class="absolute z-50 w-full bg-white border border-gray-200 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto">

                                            <template x-if="loading">
                                                <div class="px-3 py-2 text-sm text-gray-400">Searching…</div>
                                            </template>

                                            <template x-if="!loading && results.length === 0 && query.length > 0">
                                                <div class="px-3 py-2 text-sm text-gray-400">No customers found.</div>
                                            </template>

                                            <template x-for="item in results" :key="item.id">
                                                <button type="button"
                                                        @click="selectCustomer(item)"
                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 hover:text-blue-700 border-b border-gray-50 last:border-0"
                                                        x-text="item.label">
                                                </button>
                                            </template>

                                            <button type="button"
                                                    @click="isOpen = false; document.getElementById('create-parent-customer-modal').classList.remove('hidden')"
                                                    class="w-full text-left px-3 py-2 text-sm text-blue-700 font-medium border-t border-gray-100 hover:bg-blue-50">
                                                + Create New Parent Customer
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Selected customer chip --}}
                                    <div x-show="selectedId" x-cloak
                                         class="mt-2 flex items-center gap-2 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                                        <svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 8v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                        <span x-text="selectedLabel" class="text-sm font-medium text-blue-800 flex-1 truncate"></span>
                                        <button type="button" @click="clear()"
                                                class="text-blue-400 hover:text-red-500 text-xs font-medium flex-shrink-0">
                                            ✕ Change
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-6">
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                                <div class="flex justify-between items-center mb-3">
                                    <h2 class="text-lg font-semibold">Job Site</h2>

                                    <div class="flex items-center gap-2">
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
                                        disabled>
                                    <option value="">— Select Job Site —</option>

                                    @foreach ($jobSiteCustomers as $js)
                                        <option value="{{ $js->id }}" data-parent-id="{{ $js->parent_id }}">
                                            {{ $js->company_name ?: $js->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <p class="mt-2 text-xs text-gray-500">
                                    Select a Parent Customer first to see available job sites.
                                </p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Create Job Site Modal --}}
                <div id="create-job-site-modal" tabindex="-1" aria-hidden="true"
                     class="hidden fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">

                    <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl my-auto flex flex-col max-h-[90vh]">
                        <form method="POST" action="{{ route('admin.customers.store') }}" class="flex flex-col min-h-0 flex-1">
                            @csrf

                            <input type="hidden" name="redirect_to" id="job-site-redirect-to" value="{{ route('pages.opportunities.create') }}">
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

                {{-- Create Parent Customer Modal --}}
                <div id="create-parent-customer-modal" tabindex="-1" aria-hidden="true"
                     class="hidden fixed inset-0 z-50 flex items-start justify-center bg-black/50 overflow-y-auto p-4">

                    <div class="bg-white rounded-xl shadow-lg w-full max-w-lg my-auto">
                        <div class="flex items-center justify-between px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold">Create Parent Customer</h3>
                            <button type="button"
                                    onclick="document.getElementById('create-parent-customer-modal').classList.add('hidden')"
                                    class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                        </div>

                        <div class="p-6 space-y-4">
                            <div id="create-parent-customer-error"
                                 class="hidden p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded-lg"></div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Name</label>
                                    <input type="text" id="pc_name"
                                           class="w-full border border-gray-300 rounded-lg p-2 text-sm"
                                           placeholder="Individual name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Company Name</label>
                                    <input type="text" id="pc_company_name"
                                           class="w-full border border-gray-300 rounded-lg p-2 text-sm"
                                           placeholder="Business / company name">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Email</label>
                                    <input type="email" id="pc_email"
                                           class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Phone</label>
                                    <input type="text" id="pc_phone"
                                           class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Mobile</label>
                                    <input type="text" id="pc_mobile"
                                           class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Customer Type</label>
                                    <select id="pc_customer_type"
                                            class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                        <option value="restoration">Restoration</option>
                                        <option value="builder">Builder</option>
                                        <option value="property_manager">Property Manager</option>
                                    </select>
                                </div>
                            </div>

                            <p class="text-xs text-gray-400">At least one of Name or Company Name is required. You can add address and other details from the customer profile later.</p>
                        </div>

                        <div class="flex justify-end gap-3 px-6 py-4 border-t">
                            <button type="button"
                                    onclick="document.getElementById('create-parent-customer-modal').classList.add('hidden')"
                                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="button"
                                    id="create-parent-customer-btn"
                                    onclick="submitCreateParentCustomer()"
                                    class="px-4 py-2 text-sm text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                                Create Customer
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="p-6 border-t border-gray-200 flex justify-end">
                    <form id="opportunity-form" method="POST" action="{{ route('pages.opportunities.store') }}">
                        @csrf
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg">
                            Save Opportunity
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
// ============================================================
// Alpine.js component — parent customer typeahead
// ============================================================
function parentCustomerTypeahead() {
    return {
        query: '',
        results: [],
        selectedId: '',
        selectedLabel: '',
        isOpen: false,
        loading: false,

        init() {
            // Expose this instance globally so vanilla JS can call selectNew()
            window._parentCustomerTypeahead = this;

            // Restore state after job site modal redirect
            const params = new URLSearchParams(window.location.search);
            const savedId    = params.get('_parent_id');
            const savedLabel = params.get('_parent_label');
            if (savedId && savedLabel) {
                this.selectedId    = savedId;
                this.selectedLabel = decodeURIComponent(savedLabel);
                this.query         = this.selectedLabel;
                this.$nextTick(() => window.onParentCustomerSelected(savedId, this.selectedLabel));
            }
        },

        handleFocus() {
            this.isOpen = true;
            if (this.results.length === 0) this.search();
        },

        async search() {
            this.loading = true;
            this.isOpen  = true;
            try {
                const res = await fetch(
                    `{{ route('pages.opportunities.api.parent-customers.search') }}?q=${encodeURIComponent(this.query)}`,
                    { headers: { Accept: 'application/json' } }
                );
                this.results = await res.json();
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        selectCustomer(item) {
            this.selectedId    = item.id;
            this.selectedLabel = item.label;
            this.query         = item.label;
            this.isOpen        = false;
            window.onParentCustomerSelected(item.id, item.label);
        },

        clear() {
            this.selectedId    = '';
            this.selectedLabel = '';
            this.query         = '';
            this.results       = [];
            this.isOpen        = false;
            window.onParentCustomerSelected('', '');
        },

        // Called from the AJAX create modal after a new customer is saved
        selectNew(id, label) {
            this.selectCustomer({ id, label });
        },
    };
}

// ============================================================
// AJAX — Create Parent Customer modal submit
// ============================================================
async function submitCreateParentCustomer() {
    const btn = document.getElementById('create-parent-customer-btn');
    const errBox = document.getElementById('create-parent-customer-error');
    errBox.classList.add('hidden');
    errBox.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Saving…';

    const payload = {
        name:            document.getElementById('pc_name').value.trim(),
        company_name:    document.getElementById('pc_company_name').value.trim(),
        email:           document.getElementById('pc_email').value.trim(),
        phone:           document.getElementById('pc_phone').value.trim(),
        mobile:          document.getElementById('pc_mobile').value.trim(),
        customer_type:   document.getElementById('pc_customer_type').value,
        customer_status: 'Active',
        _token:          document.querySelector('meta[name="csrf-token"]')?.content
                         || '{{ csrf_token() }}',
    };

    try {
        const res = await fetch('{{ route('pages.opportunities.api.parent-customers.store') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (!res.ok) {
            errBox.textContent = data.error || 'An error occurred. Please try again.';
            errBox.classList.remove('hidden');
            return;
        }

        // Close modal and clear fields
        document.getElementById('create-parent-customer-modal').classList.add('hidden');
        ['pc_name','pc_company_name','pc_email','pc_phone','pc_mobile'].forEach(id => {
            document.getElementById(id).value = '';
        });

        // Select the newly created customer in the typeahead via the global Alpine reference
        if (window._parentCustomerTypeahead) {
            window._parentCustomerTypeahead.selectNew(data.id, data.label);
        }

    } catch (e) {
        errBox.textContent = 'Network error. Please try again.';
        errBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Create Customer';
    }
}

// ============================================================
// Vanilla JS — job sites, PMs, job site modal sync
// (reads parent ID from the hidden input set by Alpine)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {

    const getParentId = () => document.getElementById('parent_customer_id_input')?.value || '';

    const jobSiteSelect   = document.getElementById('job_site_customer_id');
    const pmSelect        = document.getElementById('project_manager_id');
    const sameAsParentBtn = document.getElementById('same-as-parent-btn');

    const urlParams = new URLSearchParams(window.location.search);

    // ----- Job Site filtering -----
    const allJobSiteOptions = jobSiteSelect
        ? Array.from(jobSiteSelect.querySelectorAll('option')).filter(o => o.value)
        : [];

    function filterJobSites(preselectJobSiteId = null) {
        if (!jobSiteSelect) return;

        const parentId = getParentId();
        jobSiteSelect.value = '';
        allJobSiteOptions.forEach(o => (o.hidden = true));

        if (!parentId) {
            jobSiteSelect.disabled = true;
            if (sameAsParentBtn) sameAsParentBtn.style.display = 'none';
            return;
        }

        jobSiteSelect.disabled = false;
        if (sameAsParentBtn) sameAsParentBtn.style.display = '';

        allJobSiteOptions.forEach(o => {
            o.hidden = (o.dataset.parentId !== parentId);
        });

        if (preselectJobSiteId) {
            jobSiteSelect.value = String(preselectJobSiteId);
        }
    }

    // ----- PM loading -----
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

    async function loadProjectManagers(preselectId = null) {
        if (!pmSelect) return;

        const parentId = getParentId();
        resetPmSelect(parentId ? 'Loading PMs…' : '— Select Parent Customer first —', true);

        if (!parentId) return;

        try {
            const url = `{{ url('/pages/customers') }}/${encodeURIComponent(parentId)}/project-managers`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error();
            const pms = await res.json();

            if (!Array.isArray(pms) || pms.length === 0) {
                resetPmSelect('No project managers found for this customer', true);
                return;
            }

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
            if (preselectId) {
                pmSelect.value = String(preselectId);
            } else if (pms.length === 1) {
                pmSelect.value = String(pms[0].id);
            }
        } catch (e) {
            resetPmSelect('Unable to load PMs', true);
        }
    }

    // ----- Job Site modal — keep parent_id hidden input in sync -----
    const jobSiteParentIdInput = document.getElementById('job_site_parent_id');
    const jobSiteParentDisplay = document.getElementById('job_site_parent_display');

    function syncModalParent(parentId, parentLabel) {
        if (jobSiteParentIdInput) jobSiteParentIdInput.value = parentId || '';
        if (jobSiteParentDisplay) {
            jobSiteParentDisplay.value = parentLabel || '(Selected parent will apply)';
        }
    }

    // ----- Same as Parent button — pre-fill job site modal -----
    if (sameAsParentBtn) {
        sameAsParentBtn.addEventListener('click', () => {
            // Fetch the selected parent's data to pre-fill the job site modal
            const parentId = getParentId();
            if (!parentId) return;

            fetch(`{{ route('pages.opportunities.api.parent-customers.search') }}?q=`, {
                headers: { Accept: 'application/json' }
            }).then(r => r.json()).then(list => {
                const found = list.find(c => String(c.id) === String(parentId));
                // We only have id+label from the search endpoint, so just open the modal
                const modal = document.getElementById('create-job-site-modal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.removeAttribute('aria-hidden');
                }
            });
        });
    }

    // ----- Callback invoked by Alpine when parent changes -----
    window.onParentCustomerSelected = function(parentId, parentLabel, preselectJobSiteId = null, preselectPmId = null) {
        filterJobSites(preselectJobSiteId);
        loadProjectManagers(preselectPmId);
        syncModalParent(parentId, parentLabel);
    };

    // ----- Save form state into redirect_to URL before job site modal submits -----
    const modalForm = document.querySelector('#create-job-site-modal form');
    if (modalForm) {
        modalForm.addEventListener('submit', () => {
            const redirectInput = document.getElementById('job-site-redirect-to');
            if (!redirectInput) return;

            const params = new URLSearchParams();
            const jobNoInput   = document.querySelector('input[name="job_no"]');
            const statusSelect = document.querySelector('select[name="status"]');
            const sp1          = document.querySelector('select[name="sales_person_1"]');
            const sp2          = document.querySelector('select[name="sales_person_2"]');

            if (jobNoInput?.value)   params.set('_job_no',       jobNoInput.value);
            if (statusSelect?.value) params.set('_status',       statusSelect.value);
            if (sp1?.value)          params.set('_sp1',          sp1.value);
            if (sp2?.value)          params.set('_sp2',          sp2.value);

            const parentId    = getParentId();
            const parentLabel = window._parentCustomerTypeahead?.selectedLabel || '';
            if (parentId) {
                params.set('_parent_id',    parentId);
                params.set('_parent_label', encodeURIComponent(parentLabel));
            }
            if (pmSelect?.value) params.set('_pm_id', pmSelect.value);

            const qs = params.toString();
            if (qs) {
                redirectInput.value = redirectInput.value.split('?')[0] + '?' + qs;
            }
        });
    }

    // ----- Restore simple fields from URL params (after job site create redirect) -----
    const jobNoInput   = document.querySelector('input[name="job_no"]');
    const statusSelect = document.querySelector('select[name="status"]');
    const sp1          = document.querySelector('select[name="sales_person_1"]');
    const sp2          = document.querySelector('select[name="sales_person_2"]');

    if (urlParams.get('_job_no') && jobNoInput)   jobNoInput.value   = urlParams.get('_job_no');
    if (urlParams.get('_status') && statusSelect)  statusSelect.value = urlParams.get('_status');
    if (urlParams.get('_sp1')    && sp1)           sp1.value          = urlParams.get('_sp1');
    if (urlParams.get('_sp2')    && sp2)           sp2.value          = urlParams.get('_sp2');

    // Parent customer + job site + PM are restored by Alpine's init() via URL params,
    // which then calls window.onParentCustomerSelected → filterJobSites / loadProjectManagers.
    // If there is no saved parent but there IS a new_js_id, still try to filter job sites.
    const newJobSiteId = urlParams.get('new_js_id') || null;
    if (!urlParams.get('_parent_id') && newJobSiteId) {
        filterJobSites(newJobSiteId);
    }
});
</script>


</x-app-layout>