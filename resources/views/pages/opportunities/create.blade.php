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

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" form="opportunity-form"
                                    class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" {{ $status === 'New' ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person 1</label>
                            <input type="text" name="sales_person_1" form="opportunity-form"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div class="md:col-span-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sales Person 2</label>
                            <input type="text" name="sales_person_2" form="opportunity-form"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
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
                                <h2 class="text-lg font-semibold mb-3">Parent Customer</h2>

                                <select id="parent_customer_select" name="parent_customer_id" form="opportunity-form"
                                        class="w-full bg-white border border-gray-300 rounded-lg p-2.5 text-sm">
                                    <option value="">— Select Parent Customer —</option>
                                    @foreach ($parentCustomers as $c)
                                        <option value="{{ $c->id }}">
                                            {{ $c->company_name ?: $c->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="lg:col-span-6">
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5">
                                <div class="flex justify-between items-center mb-3">
                                    <h2 class="text-lg font-semibold">Job Site</h2>

                                    <button id="open-job-site-modal"
                                            type="button"
                                            data-modal-target="create-job-site-modal"
                                            data-modal-toggle="create-job-site-modal"
                                            class="px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg">
                                        + Create Job Site
                                    </button>
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
                     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">

                    <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl">
                        <form method="POST" action="{{ route('admin.customers.store') }}">
                            @csrf

                            <input type="hidden" name="redirect_to" value="{{ route('pages.opportunities.create') }}">
                            <input type="hidden" name="parent_id" id="job_site_parent_id">

                            <div class="p-6 space-y-4">
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
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1">Phone</label>
                                        <input type="text" name="phone"
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
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
                                               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
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
document.addEventListener('DOMContentLoaded', () => {
    const parentSelect =
        document.getElementById('parent_customer_select') ||
        document.querySelector('select[name="parent_customer_id"]');

    const jobSiteSelect = document.getElementById('job_site_customer_id');
    const pmSelect = document.getElementById('project_manager_id');

    if (!parentSelect) return;

    // ----------------------------
    // Job Site filtering (existing)
    // ----------------------------
    const allJobSiteOptions = jobSiteSelect
        ? Array.from(jobSiteSelect.querySelectorAll('option')).filter(o => o.value)
        : [];

    function filterJobSites() {
        if (!jobSiteSelect) return;

        const parentId = parentSelect.value;

        // Reset selection + hide all job site options first
        jobSiteSelect.value = "";
        allJobSiteOptions.forEach(o => (o.hidden = true));

        if (!parentId) {
            jobSiteSelect.disabled = true;
            return;
        }

        jobSiteSelect.disabled = false;

        // Unhide only options that match selected parent
        allJobSiteOptions.forEach(o => {
            o.hidden = (o.dataset.parentId !== parentId);
        });
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

            // Auto-select if only 1 PM
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
    });

    // Run once on load
    filterJobSites();
    loadProjectManagers();
});
</script>


</x-app-layout>