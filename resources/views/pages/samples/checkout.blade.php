<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Check Out Samples</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Pick who it's going to, then search and add samples.</p>
                </div>
                <a href="{{ route('pages.samples.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    ← Back
                </a>
            </div>

            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200 dark:bg-red-900/20 dark:text-red-300">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.samples.checkout.store') }}"
                  x-data="checkoutBuilder(@js($cartItems))"
                  @submit="if (!isValid) $event.preventDefault()">
                @csrf

                {{-- 1. Who it's going to --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mb-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Who's Checking Out</h2>

                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" @click="type = 'customer'"
                                :class="type === 'customer' ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'border-gray-200 bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                                class="px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-colors">
                            Customer
                        </button>
                        <button type="button" @click="type = 'staff'"
                                :class="type === 'staff' ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                                class="px-4 py-2.5 rounded-lg border-2 text-sm font-medium transition-colors">
                            Staff / Site
                        </button>
                    </div>
                    <input type="hidden" name="checkout_type" :value="type">

                    {{-- Customer fields --}}
                    <div x-show="type === 'customer'" x-cloak class="space-y-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>

                            {{-- Selected customer chip --}}
                            <div x-show="customerId" x-cloak
                                 class="flex items-center justify-between bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg px-3 py-2 mb-2">
                                <span class="text-sm font-medium text-blue-900 dark:text-blue-200" x-text="customerName"></span>
                                <button type="button" @click="clearCustomer()"
                                        class="text-blue-500 hover:text-blue-700 dark:text-blue-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Search --}}
                            <div class="relative" x-show="! customerId" x-cloak>
                                <input type="text" x-model="customerQuery" @input.debounce.300ms="searchCustomer()"
                                       placeholder="Search customers by name, company, or phone… (leave blank for walk-in)"
                                       autocomplete="off"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">

                                <div x-show="customerQuery.length > 0 && customerResults.length > 0" x-cloak
                                     class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                    <template x-for="result in customerResults" :key="result.id">
                                        <button type="button" @click="selectCustomer(result)"
                                                class="w-full flex items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-600 border-b border-gray-100 dark:border-gray-600 last:border-0">
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="result.label"></span>
                                            <span class="text-xs text-gray-400 flex-shrink-0 ml-2" x-text="result.phone"></span>
                                        </button>
                                    </template>
                                </div>
                                <div x-show="customerQuery.length > 0 && ! customerSearching && customerResults.length === 0" x-cloak
                                     class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg px-4 py-2.5 text-sm text-gray-400">
                                    No customers match "<span x-text="customerQuery"></span>" — fill in the fields below for a walk-in.
                                </div>
                            </div>

                            <input type="hidden" name="customer_id" x-model="customerId">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input type="text" name="customer_name" x-model="customerName"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                                <input type="tel" name="customer_phone" x-model="customerPhone"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                                <input type="email" name="customer_email" x-model="customerEmail"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                        </div>
                    </div>

                    {{-- Staff fields --}}
                    <div x-show="type === 'staff'" x-cloak class="pt-2 border-t border-gray-100 dark:border-gray-700">
                        <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Staff Member</label>
                        <select name="user_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            <option value="">— Select staff —</option>
                            @foreach ($staffUsers as $user)
                                <option value="{{ $user->id }}" @selected($user->id === auth()->id())>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- 2. Search and add samples --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mb-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Samples &amp; Sets</h2>

                    <div class="relative">
                        <input type="text" x-model="query" @input.debounce.300ms="search()"
                               placeholder="Search by sample #, set #, style, color, or manufacturer…"
                               autocomplete="off"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">

                        {{-- Results dropdown --}}
                        <div x-show="query.length > 0 && results.length > 0" x-cloak
                             class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                            <template x-for="result in results" :key="result.cart_key">
                                <button type="button" @click="addItem(result)"
                                        class="w-full flex items-center justify-between px-4 py-2.5 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-600 border-b border-gray-100 dark:border-gray-600 last:border-0">
                                    <span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="result.display_id"></span>
                                        <span x-show="result.type === 'set'" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">SET</span>
                                        <span class="text-gray-500 dark:text-gray-400" x-text="' — ' + (result.manufacturer || '') + ' ' + (result.style_name || '')"></span>
                                    </span>
                                    <span class="text-xs text-gray-400 flex-shrink-0 ml-2" x-text="result.type === 'set' ? 'checked out as a set' : result.available_qty + ' available'"></span>
                                </button>
                            </template>
                        </div>
                        <div x-show="query.length > 0 && !searching && results.length === 0" x-cloak
                             class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg px-4 py-2.5 text-sm text-gray-400">
                            No available samples or sets match "<span x-text="query"></span>".
                        </div>
                    </div>

                    {{-- Cart --}}
                    <div x-show="cart.length === 0" x-cloak class="text-sm text-gray-400 py-4 text-center">
                        Nothing added yet — search above to add samples or sets.
                    </div>

                    <table x-show="cart.length > 0" x-cloak class="w-full text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-gray-500 bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <th class="px-4 py-2 text-left">Item</th>
                                <th class="px-4 py-2 text-right">Available</th>
                                <th class="px-4 py-2 text-right w-28">Qty</th>
                                <th class="px-4 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="item in cart" :key="item.cart_key">
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        <span x-text="item.display_id"></span>
                                        <span x-show="item.type === 'set'" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">SET</span>
                                        <div class="text-xs font-normal text-gray-500 dark:text-gray-400" x-text="(item.manufacturer || '') + ' ' + (item.style_name || '')"></div>
                                        <input type="hidden" :name="item.type === 'set' ? 'set_ids[]' : 'sample_ids[]'" :value="item.id">
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-500" x-text="item.type === 'set' ? '—' : item.available_qty"></td>
                                    <td class="px-4 py-3 text-right">
                                        <template x-if="item.type === 'set'">
                                            <span class="text-gray-400">1</span>
                                        </template>
                                        <template x-if="item.type !== 'set'">
                                            <input type="number" x-model.number="item.qty" :name="'qty[' + item.id + ']'"
                                                   min="1" :max="item.available_qty"
                                                   class="w-20 text-right bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="removeItem(item.cart_key)"
                                                class="text-gray-400 hover:text-red-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- 3. Checkout details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4 mb-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Checkout Details</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Due Back</label>
                            <input type="date" name="due_back_at"
                                   value="{{ old('due_back_at', now()->addDays($defaultDays)->format('Y-m-d')) }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Destination / Notes</label>
                            <input type="text" name="destination"
                                   placeholder="e.g. Job site: 123 Main St, Trade show, etc."
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" :disabled="!isValid"
                            :class="isValid ? 'bg-blue-700 hover:bg-blue-800' : 'bg-gray-300 cursor-not-allowed'"
                            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-4 focus:ring-blue-300">
                        <span x-text="cart.length > 0 ? 'Check Out ' + cart.length + ' Item' + (cart.length === 1 ? '' : 's') : 'Check Out'"></span>
                    </button>
                    <a href="{{ route('pages.samples.index') }}"
                       class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutBuilder', (initialCart) => ({
            type: 'customer',
            customerId: '',
            customerName: '',
            customerPhone: '',
            customerEmail: '',
            customerQuery: '',
            customerResults: [],
            customerSearching: false,
            query: '',
            results: [],
            searching: false,
            cart: (initialCart || []).map(s => ({ ...s, qty: 1 })),

            get isValid() { return this.cart.length > 0; },

            searchCustomer() {
                if (! this.customerQuery) { this.customerResults = []; return; }
                this.customerSearching = true;
                fetch('{{ route('pages.samples.checkout.customers.search') }}?q=' + encodeURIComponent(this.customerQuery))
                    .then(r => r.json())
                    .then(data => {
                        this.customerResults = data;
                        this.customerSearching = false;
                    })
                    .catch(() => { this.customerSearching = false; });
            },

            selectCustomer(customer) {
                this.customerId    = customer.id;
                this.customerName  = customer.label;
                this.customerPhone = customer.phone || '';
                this.customerEmail = customer.email || '';
                this.customerQuery = '';
                this.customerResults = [];
            },

            clearCustomer() {
                this.customerId    = '';
                this.customerName  = '';
                this.customerPhone = '';
                this.customerEmail = '';
            },

            search() {
                if (! this.query) { this.results = []; return; }
                this.searching = true;
                fetch('{{ route('pages.samples.checkout.search') }}?q=' + encodeURIComponent(this.query))
                    .then(r => r.json())
                    .then(data => {
                        const inCart = new Set(this.cart.map(c => c.cart_key));
                        this.results = data.filter(r => ! inCart.has(r.cart_key));
                        this.searching = false;
                    })
                    .catch(() => { this.searching = false; });
            },

            addItem(item) {
                if (this.cart.some(c => c.cart_key === item.cart_key)) return;
                this.cart.push({ ...item, qty: 1 });
                this.query = '';
                this.results = [];
            },

            removeItem(cartKey) {
                this.cart = this.cart.filter(c => c.cart_key !== cartKey);
            },
        }));
    });
    </script>
</x-app-layout>
