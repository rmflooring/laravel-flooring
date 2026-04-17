{{-- Sent Email Preview Modal --}}
{{-- Usage: <x-modals.sent-email-modal :type="'estimate'" :related-id="$estimate->id" /> --}}
{{-- Trigger from anywhere on the page: window.dispatchEvent(new Event('open-sent-email-modal')) --}}

@props([
    'type'      => null,  // 'estimate', 'sale', 'work_order', 'purchase_order', 'invoice', 'change_order'
    'relatedId' => null,
])

<div
    x-data="sentEmailModal('{{ $type }}', {{ $relatedId ?? 'null' }})"
    @open-sent-email-modal.window="open()"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-gray-900/50"
        @click="close()"
        x-cloak
    ></div>

    {{-- Modal --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-cloak
    >
        <div class="relative w-full max-w-2xl max-h-[90vh] flex flex-col rounded-lg bg-white shadow-xl dark:bg-gray-800">

            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Email Sent</h3>
                </div>
                <button @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            {{-- Loading state --}}
            <div x-show="loading" class="flex items-center justify-center p-12">
                <svg class="animate-spin h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 5.373 0 0 12h4z"></path>
                </svg>
            </div>

            {{-- Not found state --}}
            <div x-show="!loading && !found" class="flex flex-col items-center justify-center p-12 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">No email record found for this document.</p>
                <p class="text-xs mt-1 text-gray-400">Emails sent before this feature was enabled won't appear here.</p>
            </div>

            {{-- Email content --}}
            <div x-show="!loading && found" class="flex flex-col overflow-hidden">

                {{-- Email meta --}}
                <div class="px-5 py-4 space-y-2 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                    <div class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1.5 text-sm">
                        <span class="text-gray-500 dark:text-gray-400 font-medium pt-0.5">From</span>
                        <span class="text-gray-800 dark:text-gray-200" x-text="email.from"></span>

                        <span class="text-gray-500 dark:text-gray-400 font-medium pt-0.5">To</span>
                        <span class="text-gray-800 dark:text-gray-200" x-text="email.to"></span>

                        <template x-if="email.cc">
                            <span class="text-gray-500 dark:text-gray-400 font-medium pt-0.5">CC</span>
                        </template>
                        <template x-if="email.cc">
                            <span class="text-gray-800 dark:text-gray-200" x-text="email.cc"></span>
                        </template>

                        <span class="text-gray-500 dark:text-gray-400 font-medium pt-0.5">Subject</span>
                        <span class="text-gray-800 dark:text-gray-200 font-medium" x-text="email.subject"></span>

                        <span class="text-gray-500 dark:text-gray-400 font-medium pt-0.5">Sent</span>
                        <span class="text-gray-600 dark:text-gray-400 text-xs pt-0.5" x-text="email.sent_at"></span>

                        <template x-if="email.attachment_name">
                            <span class="text-gray-500 dark:text-gray-400 font-medium pt-0.5">Attachment</span>
                        </template>
                        <template x-if="email.attachment_name">
                            <span>
                                <template x-if="email.pdf_url">
                                    <a :href="email.pdf_url" target="_blank"
                                       class="inline-flex items-center gap-1.5 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span x-text="email.attachment_name"></span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </template>
                                <template x-if="!email.pdf_url">
                                    <span class="inline-flex items-center gap-1.5 text-gray-700 dark:text-gray-300 text-sm">
                                        <svg class="w-4 h-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        <span x-text="email.attachment_name"></span>
                                    </span>
                                </template>
                            </span>
                        </template>
                    </div>
                </div>

                {{-- Email body --}}
                <div class="px-5 py-4 overflow-y-auto flex-1 min-h-0">
                    <p class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide font-medium mb-2">Message</p>
                    <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed"
                         x-text="email.body"></div>
                </div>

                {{-- Footer --}}
                <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                    <span class="text-xs text-gray-400 dark:text-gray-500"
                          x-text="email.track === 2 ? 'Sent via your personal MS365 account' : 'Sent via shared mailbox'"></span>
                    <button @click="close()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                        Close
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

@once
<script>
function sentEmailModal(type, id) {
    return {
        show: false,
        loading: false,
        found: false,
        email: {},

        open() {
            this.show    = true;
            this.loading = true;
            this.found   = false;
            this.email   = {};

            fetch(`/pages/mail-log/${type}/${id}`)
                .then(r => r.json())
                .then(data => {
                    this.found   = data.found;
                    this.email   = data;
                    this.loading = false;
                })
                .catch(() => {
                    this.found   = false;
                    this.loading = false;
                });
        },

        close() {
            this.show = false;
        },
    };
}
</script>
@endonce
