<x-mobile-layout :title="'Check Out – ' . $sample->sample_id">

    @php $style = $sample->productStyle; @endphp

    {{-- Back --}}
    <a href="{{ route('mobile.samples.show', $sample->sample_id) }}"
       class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $sample->sample_id }} – {{ $style->name }}
    </a>

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 px-4 py-3">
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('mobile.samples.checkout.store', $sample->sample_id) }}"
          x-data="checkoutForm()">
        @csrf

        {{-- Type toggle --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Checkout Type</h2>

            <div class="grid grid-cols-2 gap-3">
                <button type="button" @click="type = 'customer'"
                        :class="type === 'customer' ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'border-gray-200 bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                        class="flex flex-col items-center gap-1.5 px-4 py-3 rounded-xl border-2 text-sm font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    Customer
                </button>
                <button type="button" @click="type = 'staff'"
                        :class="type === 'staff' ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300'"
                        class="flex flex-col items-center gap-1.5 px-4 py-3 rounded-xl border-2 text-sm font-medium transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                    Staff / Site
                </button>
            </div>
            <input type="hidden" name="checkout_type" :value="type">
        </div>

        {{-- Customer fields --}}
        <div x-show="type === 'customer'" x-cloak
             class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Customer</h2>

            {{-- Existing customer dropdown --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Existing Customer</label>
                <select name="customer_id" x-model="customerId"
                        @change="fillCustomer($event.target)"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <option value="">— Walk-in / Enter manually —</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                                data-phone="{{ $customer->phone }}"
                                data-email="{{ $customer->email }}"
                                data-name="{{ $customer->company_name ?: $customer->name }}">
                            {{ $customer->company_name ?: $customer->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="border-t border-gray-100 dark:border-gray-700 pt-4 space-y-3">
                <p class="text-xs text-gray-400 uppercase tracking-wide">Contact Info</p>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                    <input type="text" name="customer_name" x-model="customerName"
                           placeholder="Customer name"
                           class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone <span class="text-gray-400 font-normal">(for SMS reminders)</span></label>
                    <input type="tel" name="customer_phone" x-model="customerPhone"
                           placeholder="604-555-0123"
                           class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-gray-400 font-normal">(for email reminders)</span></label>
                    <input type="email" name="customer_email" x-model="customerEmail"
                           placeholder="customer@email.com"
                           class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>
            </div>
        </div>

        {{-- Staff fields --}}
        <div x-show="type === 'staff'" x-cloak
             class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Staff Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Staff Member</label>
                <select name="user_id"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    <option value="">— Select staff —</option>
                    @foreach ($staffUsers as $user)
                        <option value="{{ $user->id }}" @selected($user->id === auth()->id())>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Destination / Notes</label>
                <input type="text" name="destination"
                       placeholder="e.g. Job site: 123 Main St, Trade show, etc."
                       class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
            </div>
        </div>

        {{-- Qty + Due date --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Checkout Details</h2>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Quantity <span class="text-xs text-gray-400">({{ $sample->available_qty }} available)</span>
                    </label>
                    <input type="number" name="qty_checked_out" value="{{ old('qty_checked_out', 1) }}"
                           min="1" max="{{ $sample->available_qty }}"
                           class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Back</label>
                    <input type="date" name="due_back_at"
                           value="{{ old('due_back_at', now()->addDays($defaultDays)->format('Y-m-d')) }}"
                           class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <button type="submit"
                class="flex items-center justify-center w-full gap-2 px-6 py-3.5 text-base font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Confirm Checkout
        </button>

    </form>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkoutForm', () => ({
            type: 'customer',
            customerId: '',
            customerName: '',
            customerPhone: '',
            customerEmail: '',

            fillCustomer(select) {
                const opt = select.options[select.selectedIndex];
                this.customerName  = opt.dataset.name  || '';
                this.customerPhone = opt.dataset.phone || '';
                this.customerEmail = opt.dataset.email || '';
            }
        }));
    });
    </script>

</x-mobile-layout>
