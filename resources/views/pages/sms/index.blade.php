<x-app-layout>
    <div x-data="{
        showCompose: false,
        customerSearch: '',
        customerResults: [],
        selectedCustomer: null,
        phone: '',
        async searchCustomers() {
            if (this.customerSearch.length < 1) { this.customerResults = []; return; }
            const res = await fetch('{{ route('pages.sms.api.customers') }}?q=' + encodeURIComponent(this.customerSearch));
            this.customerResults = await res.json();
        },
        selectCustomer(c) {
            this.selectedCustomer = c;
            this.phone = c.phone || '';
            this.customerResults = [];
            this.customerSearch = c.label;
        },
        reset() {
            this.showCompose = false;
            this.customerSearch = '';
            this.customerResults = [];
            this.selectedCustomer = null;
            this.phone = '';
        }
    }">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">SMS Inbox</h1>
                @if ($totalUnread > 0)
                    <span class="inline-flex items-center rounded-full bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white">
                        {{ $totalUnread }} unread
                    </span>
                @endif
            </div>
            <button @click="showCompose = true"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New SMS
            </button>
        </div>

        {{-- Tabs --}}
        <div class="flex items-center gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
            <a href="{{ route('pages.sms.index') }}"
               class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $view === 'active' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Active
            </a>
            <a href="{{ route('pages.sms.index', ['view' => 'archived']) }}"
               class="px-4 py-2 text-sm font-medium border-b-2 -mb-px flex items-center gap-1.5 {{ $view === 'archived' ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                Archived
                @if ($archivedCount > 0)
                    <span class="rounded-full bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $archivedCount }}</span>
                @endif
            </a>
        </div>

        @if ($conversations->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-12 text-center text-gray-500 dark:bg-gray-800 dark:border-gray-700">
                <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/>
                </svg>
                <p class="text-sm">No SMS conversations yet.</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Start one with the New SMS button, or wait for a customer to text in.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($conversations as $conversation)
                        @php
                            $latest    = $conversation->messages->first();
                            $isUnknown = $conversation->isUnknown();
                            $initials  = $isUnknown
                                ? '?'
                                : strtoupper(substr($conversation->customer->name ?? '?', 0, 1));
                        @endphp
                        <li class="group relative">
                            <a href="{{ route('pages.sms.show', $conversation) }}"
                               class="flex items-start gap-4 px-5 py-4 pr-24 hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $conversation->unread_count > 0 ? 'bg-blue-50 dark:bg-blue-900/10' : '' }}">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full {{ $isUnknown ? 'bg-gray-300 dark:bg-gray-600' : 'bg-blue-100 dark:bg-blue-900' }} text-sm font-semibold {{ $isUnknown ? 'text-gray-600 dark:text-gray-300' : 'text-blue-700 dark:text-blue-300' }}">
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="truncate text-sm {{ $conversation->unread_count > 0 ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300' }}">
                                            {{ $conversation->displayName() }}
                                        </span>
                                        <span class="flex-shrink-0 text-xs text-gray-400">
                                            {{ $conversation->last_message_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                            @if ($latest)
                                                @if ($latest->isOutbound())
                                                    <span class="font-medium text-gray-600 dark:text-gray-300">You:</span>
                                                @endif
                                                {{ Str::limit($latest->body, 80) }}
                                            @else
                                                <span class="italic">No messages</span>
                                            @endif
                                        </p>
                                        @if ($conversation->unread_count > 0)
                                            <span class="flex-shrink-0 rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">
                                                {{ $conversation->unread_count }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        @if ($isUnknown)
                                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                Unknown
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ $conversation->phone }}</span>
                                        @if ($conversation->opportunity)
                                            <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                                                </svg>
                                                {{ Str::limit($conversation->opportunity->job_no, 30) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>

                            {{-- Row actions (visible on hover) --}}
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                @if ($conversation->isArchived())
                                    <form method="POST" action="{{ route('pages.sms.unarchive', $conversation) }}">
                                        @csrf
                                        <button type="submit" title="Restore to inbox"
                                                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                                            Restore
                                        </button>
                                    </form>
                                    @role('admin')
                                    <form method="POST" action="{{ route('pages.sms.destroy', $conversation) }}"
                                          onsubmit="return confirm('Permanently delete this conversation and all its messages? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete permanently"
                                                class="rounded-lg border border-red-300 bg-white px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:bg-gray-700 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                                            Delete
                                        </button>
                                    </form>
                                    @endrole
                                @else
                                    <form method="POST" action="{{ route('pages.sms.archive', $conversation) }}">
                                        @csrf
                                        <button type="submit" title="Archive conversation"
                                                class="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                                            Archive
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($conversations->hasPages())
                <div class="mt-4">
                    {{ $conversations->links() }}
                </div>
            @endif
        @endif

        {{-- Compose Modal --}}
        <div x-show="showCompose" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="reset()">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">New SMS</h3>
                    <button @click="reset()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('pages.sms.compose') }}">
                    @csrf
                    <div class="px-6 py-4 space-y-4">

                        {{-- Customer search --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Customer
                                <span class="font-normal text-gray-400">(optional — search to auto-fill phone)</span>
                            </label>
                            <div class="relative">
                                <input type="text" x-model="customerSearch"
                                       @input.debounce.300ms="searchCustomers()"
                                       @focus="if(customerSearch.length >= 1) searchCustomers()"
                                       placeholder="Search by name or phone…"
                                       autocomplete="off"
                                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                                <ul x-show="customerResults.length > 0" x-cloak
                                    class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:bg-gray-700 dark:border-gray-600">
                                    <template x-for="c in customerResults" :key="c.id">
                                        <li @click="selectCustomer(c)"
                                            class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white">
                                            <span x-text="c.label"></span>
                                            <span class="ml-1 text-xs text-gray-400" x-text="c.phone ? '· ' + c.phone : '· no phone'"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            <input type="hidden" name="customer_id" :value="selectedCustomer?.id">
                        </div>

                        {{-- Phone number --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone number</label>
                            <input type="text" name="phone" x-model="phone" required
                                   placeholder="e.g. 604-555-1234"
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        </div>

                        {{-- Message --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Message</label>
                            <textarea name="body" required rows="4" maxlength="1600"
                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                      placeholder="Type your message…"></textarea>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Max 1600 characters</p>
                        </div>

                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" @click="reset()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                            </svg>
                            Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
