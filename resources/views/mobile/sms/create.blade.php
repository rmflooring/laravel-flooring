{{-- resources/views/mobile/sms/create.blade.php --}}
<x-mobile-layout title="New Message">

    @include('mobile.sms.partials.flash')

    <div class="flex items-center justify-between">
        <p class="text-lg font-bold text-gray-900 dark:text-white">New Message</p>
        <a href="{{ route('mobile.sms.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 dark:text-blue-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Messages
        </a>
    </div>

    <form method="POST" action="{{ route('mobile.sms.compose') }}" enctype="multipart/form-data"
          class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-4"
          x-data="{
              search: @js(old('customer_label', $customer?->company_name ? $customer->company_name . ' (' . $customer->name . ')' : $customer?->name)),
              results: [],
              selected: @js(old('customer_id', $customer?->id)),
              phone: @js(old('phone', $customer ? ($customer->mobile ?: $customer->phone) : '')),
              async find() {
                  if (this.search.length < 2) { this.results = []; return; }
                  const res = await fetch('{{ route('mobile.sms.api.customers') }}?q=' + encodeURIComponent(this.search),
                      { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
                  this.results = res.ok ? await res.json() : [];
              },
              pick(c) {
                  this.selected = c.id; this.search = c.label; this.results = [];
                  if (c.phone) this.phone = c.phone;
              },
              clear() { this.selected = null; this.search = ''; this.results = []; }
          }"
          onsubmit="const b=this.querySelector('[type=submit]'); b.disabled=true; b.textContent='Sending…';">
        @csrf

        {{-- Customer (optional) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Customer <span class="font-normal text-gray-400">(optional)</span>
            </label>
            <div class="relative">
                <input type="text" x-model="search" @input.debounce.300ms="selected = null; find()"
                       placeholder="Search by name or phone…" autocomplete="off"
                       class="w-full rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <button type="button" x-show="selected" x-cloak @click="clear()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 text-lg leading-none px-1">&times;</button>
                <ul x-show="results.length" x-cloak
                    class="absolute z-20 mt-1 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:bg-gray-700 dark:border-gray-600"
                    style="max-height:15rem;">
                    <template x-for="c in results" :key="c.id">
                        <li @click="pick(c)" class="cursor-pointer px-3 py-2.5 text-sm border-b border-gray-100 dark:border-gray-600 dark:text-white">
                            <span x-text="c.label"></span>
                            <span class="block text-xs text-gray-400" x-text="c.phone || 'no phone on file'"></span>
                        </li>
                    </template>
                </ul>
            </div>
            <input type="hidden" name="customer_id" :value="selected">
            <input type="hidden" name="customer_label" :value="selected ? search : ''">
        </div>

        {{-- Phone --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone number</label>
            <input type="tel" name="phone" x-model="phone" required placeholder="e.g. 604-555-1234"
                   class="w-full rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2.5 text-base focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <p class="mt-1 text-xs text-gray-400">New conversations go out from the main line.</p>
        </div>

        {{-- Message --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
            <textarea name="body" rows="4" maxlength="1600"
                      class="w-full resize-none rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-base focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                      placeholder="Type a message…">{{ old('body') }}</textarea>
            @include('pages.sms.partials.photo-picker')
        </div>

        <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white active:bg-blue-700">
            Send Message
        </button>
    </form>

</x-mobile-layout>
