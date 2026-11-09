{{-- resources/views/pages/showroom-visits/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8"
             x-data="showroomVisitForm(@js($preset), @js([
                 'searchUrl' => route('pages.showroom-visits.api.customers'),
                 'oppsUrl'   => route('pages.showroom-visits.api.customer-opportunities', '__ID__'),
             ]))"
             x-init="init()">

            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Book Showroom Visit</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Schedule a customer to come in for flooring selections.</p>
                </div>
                <a href="{{ route('pages.showroom-visits.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Back</a>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-4 text-red-800 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.showroom-visits.store') }}"
                  class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">
                @csrf
                <input type="hidden" name="customer_id" :value="customer ? customer.id : ''">

                {{-- 1. Customer --}}
                <div class="p-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">1. Customer</h2>

                    <template x-if="!customer">
                        <div class="relative">
                            <input type="text" x-model="query" @input.debounce.300ms="search()" @keydown.escape="results = []"
                                   placeholder="Search by name, company, email or phone..."
                                   autocomplete="off"
                                   class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs text-gray-500">Only customers with at least one opportunity are listed.</p>

                            <div x-show="results.length" x-cloak
                                 class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                <template x-for="c in results" :key="c.id">
                                    <button type="button" @click="pickCustomer(c)"
                                            class="w-full text-left px-4 py-2 hover:bg-blue-50 border-b last:border-0">
                                        <div class="text-sm font-medium text-gray-900" x-text="c.label"></div>
                                        <div class="text-xs text-gray-500" x-text="[c.phone, c.email, c.city].filter(Boolean).join(' · ')"></div>
                                    </button>
                                </template>
                            </div>
                            <p x-show="searched && !results.length && query.length >= 2" x-cloak class="mt-2 text-sm text-gray-500">No matching customers.</p>
                        </div>
                    </template>

                    <template x-if="customer">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 border border-blue-200">
                            <div>
                                <div class="text-sm font-semibold text-gray-900" x-text="customer.label"></div>
                                <div class="text-xs text-gray-600" x-text="[customer.phone, customer.email].filter(Boolean).join(' · ')"></div>
                            </div>
                            <button type="button" @click="clearCustomer()" class="text-sm text-blue-700 hover:underline">Change</button>
                        </div>
                    </template>
                </div>

                {{-- 2. Opportunity --}}
                <div class="p-6" x-show="customer" x-cloak>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">2. Opportunity</h2>

                    <p x-show="loadingOpps" class="text-sm text-gray-500">Loading opportunities…</p>
                    <p x-show="!loadingOpps && !opps.length" class="text-sm text-red-600">This customer has no opportunities. Create one first, then book the visit.</p>

                    <div class="space-y-2" x-show="!loadingOpps && opps.length">
                        <template x-for="o in opps" :key="o.id">
                            <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer"
                                   :class="opportunityId == o.id ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:bg-gray-50'">
                                <input type="radio" name="opportunity_id" :value="o.id" x-model="opportunityId" @change="pickOpportunity(o)" class="mt-1">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        <span x-text="o.job_no || ('Opportunity #' + o.id)"></span>
                                        <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium"
                                              :class="['Lost','Closed'].includes(o.status) ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-800'"
                                              x-text="o.status"></span>
                                    </div>
                                    <div class="text-xs text-gray-600" x-text="[o.job_site, o.address].filter(Boolean).join(' — ')"></div>
                                    <div class="text-xs text-gray-400" x-text="'Created ' + o.created"></div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                {{-- 3. Date / time --}}
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4" x-show="opportunityId" x-cloak>
                    <h2 class="md:col-span-3 text-base font-semibold text-gray-900 dark:text-white">3. Date &amp; Time</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date &amp; time</label>
                        <input type="datetime-local" name="scheduled_at" step="900" required
                               value="{{ old('scheduled_at') }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Length</label>
                        <select name="duration_minutes" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                            @foreach ($durations as $d)
                                <option value="{{ $d }}" @selected((int) old('duration_minutes', 60) === $d)>
                                    {{ $d < 60 ? $d . ' min' : ($d / 60) . ' hr' . ($d > 60 ? 's' : '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Host (staff member)</label>
                        <select name="host_employee_id" x-model="hostId" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                            <option value="">— Unassigned —</option>
                            @foreach ($hosts as $h)
                                <option value="{{ $h->id }}">{{ $h->first_name }} {{ $h->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Notes <span class="text-gray-400 font-normal">(internal — not sent to the customer)</span></label>
                        <textarea name="notes" rows="3" placeholder="Rooms, product types they're interested in, budget..."
                                  class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">{{ old('notes') }}</textarea>
                    </div>
                </div>

                {{-- 4. Contact + notifications --}}
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4" x-show="opportunityId" x-cloak>
                    <h2 class="md:col-span-3 text-base font-semibold text-gray-900 dark:text-white">4. Customer Notification</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                        <input type="text" name="contact_name" x-model="contact.name" required
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Email</label>
                        <input type="email" name="contact_email" x-model="contact.email"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Mobile (for text)</label>
                        <input type="text" name="contact_phone" x-model="contact.phone"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input type="checkbox" name="notify_email" value="1" x-model="notifyEmail" @disabled(! $mailEnabled)
                                   class="rounded border-gray-300 text-blue-600">
                            Email confirmation &amp; reminders
                            @unless ($mailEnabled)<span class="text-xs text-red-600">(email notifications are turned off in Settings)</span>@endunless
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input type="checkbox" name="notify_sms" value="1" x-model="notifySms" @disabled(! $smsEnabled)
                                   class="rounded border-gray-300 text-blue-600">
                            Text (SMS) confirmation &amp; reminders
                            @unless ($smsEnabled)<span class="text-xs text-red-600">(SMS is turned off in Settings)</span>@endunless
                        </label>
                        <p class="text-xs text-gray-500">
                            The customer gets a confirmation now, then reminders the day before (4pm) and the morning of (8am).
                            Every message includes a link to cancel or request a different time.
                        </p>
                    </div>
                </div>

                <div class="p-6 flex justify-end gap-2" x-show="opportunityId" x-cloak>
                    <a href="{{ route('pages.showroom-visits.index') }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                        Book Visit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showroomVisitForm(preset, urls) {
            return {
                query: '',
                results: [],
                searched: false,
                customer: null,
                opps: [],
                loadingOpps: false,
                opportunityId: null,
                hostId: @js((string) old('host_employee_id', '')),
                contact: {
                    name:  @js(old('contact_name', '')),
                    email: @js(old('contact_email', '')),
                    phone: @js(old('contact_phone', '')),
                },
                notifyEmail: @js($mailEnabled && (bool) old('notify_email', true)),
                notifySms:   @js($smsEnabled && (bool) old('notify_sms', true)),
                hasOld: @js((bool) old('contact_name')),

                init() {
                    if (preset && preset.customer) {
                        this.pickCustomer(preset.customer, preset.opportunity_id);
                    }
                },

                async search() {
                    if (this.query.length < 2) { this.results = []; this.searched = false; return; }
                    const res = await fetch(urls.searchUrl + '?q=' + encodeURIComponent(this.query), { headers: { 'Accept': 'application/json' } });
                    this.results = res.ok ? await res.json() : [];
                    this.searched = true;
                },

                async pickCustomer(c, preselectOppId = null) {
                    this.customer = c;
                    this.results = [];
                    this.query = '';
                    if (!this.hasOld) {
                        this.contact = { name: c.name || '', email: c.email || '', phone: c.phone || '' };
                    }
                    this.hasOld = false;
                    this.opportunityId = null;
                    this.loadingOpps = true;
                    const res = await fetch(urls.oppsUrl.replace('__ID__', c.id), { headers: { 'Accept': 'application/json' } });
                    this.opps = res.ok ? await res.json() : [];
                    this.loadingOpps = false;

                    const open = this.opps.filter(o => !['Lost', 'Closed'].includes(o.status));
                    const pre = preselectOppId ? this.opps.find(o => o.id == preselectOppId) : (open.length === 1 ? open[0] : null);
                    if (pre) { this.opportunityId = pre.id; this.pickOpportunity(pre); }
                },

                pickOpportunity(o) {
                    // Default the host to the opportunity's sales person
                    if (!this.hostId && o.sales_person_1) this.hostId = String(o.sales_person_1);
                },

                clearCustomer() {
                    this.customer = null;
                    this.opps = [];
                    this.opportunityId = null;
                },
            };
        }
    </script>
</x-app-layout>
