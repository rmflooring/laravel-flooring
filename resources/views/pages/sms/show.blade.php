<x-app-layout>
    <div class="py-2" x-data="{
        showLinkModal: false,
        showCreateModal: false,
        customerSearch: '',
        customerResults: [],
        selectedCustomer: null,
        async searchCustomers() {
            if (this.customerSearch.length < 1) { this.customerResults = []; return; }
            const res = await fetch('/pages/opportunities/api/parent-customers?q=' + encodeURIComponent(this.customerSearch));
            this.customerResults = await res.json();
        },
        selectCustomer(c) {
            this.selectedCustomer = c;
            this.customerResults = [];
            this.customerSearch = c.label;
        }
    }">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <a href="{{ route('pages.sms.index') }}"
                   class="mb-1 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    SMS Inbox
                </a>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $conversation->displayName() }}</h1>
                    @if ($conversation->isArchived())
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-400">Archived</span>
                    @endif
                </div>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2 flex-wrap">
                    <span>{{ $conversation->phone }}</span>
                    @if ($conversation->opportunity)
                        &middot;
                        <a href="{{ route('pages.opportunities.show', $conversation->opportunity) }}"
                           class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                            </svg>
                            {{ $conversation->opportunity->job_no }}
                        </a>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @if ($conversation->isUnknown())
                    <button @click="showLinkModal = true"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                        </svg>
                        Link to Customer
                    </button>
                    <button @click="showCreateModal = true"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Opportunity
                    </button>
                @endif

                @if ($conversation->isArchived())
                    <form method="POST" action="{{ route('pages.sms.unarchive', $conversation) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20 9-1.995-2.808A2.25 2.25 0 0 0 16.137 5.25H3.863a2.25 2.25 0 0 0-1.868 1.002L.125 9m19.875 0h-19.75M.125 9v10.875A2.25 2.25 0 0 0 2.25 22.25h19.5A2.25 2.25 0 0 0 24 19.875V9M.125 9H24"/>
                            </svg>
                            Restore to Inbox
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('pages.sms.archive', $conversation) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                            </svg>
                            Archive
                        </button>
                    </form>
                @endif

                @role('admin')
                <form method="POST" action="{{ route('pages.sms.destroy', $conversation) }}"
                      onsubmit="return confirm('Permanently delete this conversation and all its messages? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:bg-gray-700 dark:border-red-700 dark:text-red-400 dark:hover:bg-red-900/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                        </svg>
                        Delete
                    </button>
                </form>
                @endrole
            </div>
        </div>

        @if ($conversation->isUnknown())
            <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 flex items-center gap-2 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                </svg>
                Unknown number — link this conversation to an existing customer or create a new opportunity.
            </div>
        @endif

        <div class="flex flex-col gap-4 max-w-3xl">

            {{-- Message thread --}}
            <div id="sms-thread" class="space-y-4">
                @forelse ($conversation->messages as $message)
                    @php $isOutbound = $message->isOutbound(); @endphp
                    <div class="flex {{ $isOutbound ? 'justify-end' : 'justify-start' }} gap-3">
                        @if (! $isOutbound)
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600 dark:bg-gray-600 dark:text-gray-300">
                                {{ $conversation->isUnknown() ? '?' : strtoupper(substr($conversation->customer->name ?? '?', 0, 1)) }}
                            </div>
                        @endif
                        <div class="max-w-lg">
                            <div class="{{ $isOutbound ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-800 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100' }} rounded-2xl px-4 py-3 shadow-sm">
                                <p class="whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                            </div>
                            <p class="mt-1 text-xs text-gray-400 {{ $isOutbound ? 'text-right' : '' }}">
                                @if ($isOutbound)
                                    {{ $message->sentBy?->name ?? 'Staff' }}
                                @else
                                    {{ $conversation->displayName() }}
                                @endif
                                &middot; {{ $message->created_at->diffForHumans() }}
                            </p>
                        </div>
                        @if ($isOutbound)
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                                {{ strtoupper(substr($message->sentBy?->name ?? auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-sm text-gray-400 dark:text-gray-500 py-8">No messages yet.</p>
                @endforelse
            </div>

            {{-- Reply box --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700">
                @if ($conversation->customer?->sms_opted_out)
                    <div class="p-4 text-sm text-red-700 bg-red-50 rounded-xl dark:bg-red-900/20 dark:text-red-300">
                        This customer has opted out of SMS (sent STOP). You cannot send them messages.
                    </div>
                @else
                    <form method="POST" action="{{ route('pages.sms.reply', $conversation) }}" class="p-4">
                        @csrf
                        <textarea name="body" required rows="3" maxlength="1600"
                                  class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:border-blue-400"
                                  placeholder="Type a message…"></textarea>
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">SMS · Max 1600 chars</span>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                                </svg>
                                Send SMS
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>

        {{-- Link to Customer Modal --}}
        <div x-show="showLinkModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="showLinkModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Link to Existing Customer</h3>
                </div>
                <div class="px-6 py-4 space-y-4">
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search customers</label>
                        <input type="text" x-model="customerSearch"
                               @input.debounce.300ms="searchCustomers()"
                               placeholder="Type a name…"
                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none">
                        <ul x-show="customerResults.length > 0" x-cloak
                            class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:bg-gray-700 dark:border-gray-600">
                            <template x-for="c in customerResults" :key="c.id">
                                <li @click="selectCustomer(c)"
                                    class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-white"
                                    x-text="c.label"></li>
                            </template>
                        </ul>
                    </div>
                    <p x-show="selectedCustomer" class="text-sm text-green-700 dark:text-green-400">
                        Selected: <span class="font-semibold" x-text="selectedCustomer?.label"></span>
                    </p>
                </div>
                <form method="POST" action="{{ route('pages.sms.link-customer', $conversation) }}">
                    @csrf
                    <input type="hidden" name="customer_id" :value="selectedCustomer?.id">
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" @click="showLinkModal = false; selectedCustomer = null; customerSearch = ''; customerResults = []"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" :disabled="!selectedCustomer"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            Link Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Create Opportunity Modal --}}
        <div x-show="showCreateModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="showCreateModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md" @click.stop>
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Create New Opportunity</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Phone {{ $conversation->phone }} will be saved with the new customer.</p>
                </div>
                <form method="POST" action="{{ route('pages.sms.create-opportunity', $conversation) }}">
                    @csrf
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer / Opportunity Name</label>
                            <input type="text" name="opportunity_name" required
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                   placeholder="e.g. John Smith — Flooring Install">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Notes
                                <span class="font-normal text-gray-400">(optional)</span>
                            </label>
                            <textarea name="notes" rows="3"
                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                                      placeholder="Any additional context…"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button type="button" @click="showCreateModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Create Opportunity
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        window.scrollTo(0, document.body.scrollHeight);
    });
    </script>
</x-app-layout>
