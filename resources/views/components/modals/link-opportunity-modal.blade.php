{{-- Link (standalone) Estimate to an existing Opportunity --}}
{{-- Usage: <x-modals.link-opportunity-modal :estimate="$estimate" /> --}}
{{-- Trigger from anywhere on the page: window.dispatchEvent(new Event('open-link-opportunity-modal')) --}}

@props([
    'estimate' => null,
])

<div
    x-data="linkOpportunityModal()"
    @open-link-opportunity-modal.window="open()"
    @keydown.escape.window="close()"
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
        <div class="relative w-full max-w-lg max-h-[85vh] flex flex-col rounded-lg bg-white shadow-xl dark:bg-gray-800">

            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Link to Opportunity</h3>
                <button @click="close()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                              clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>

            <div class="p-4 flex-shrink-0">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                    Search by job #, customer, or job site name. Linking will replace this estimate's customer/job
                    fields with that opportunity's current info.
                </p>
                <input type="text" x-model="query" @input.debounce.300ms="search()"
                       placeholder="Search opportunities…" autofocus
                       class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>

            {{-- Results --}}
            <div class="px-4 pb-4 overflow-y-auto flex-1 min-h-0">
                <div x-show="loading" class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 5.373 0 0 12h4z"></path>
                    </svg>
                </div>

                <p x-show="!loading && searched && results.length === 0" class="text-sm text-gray-500 dark:text-gray-400 text-center py-8">
                    No matching opportunities found.
                </p>

                <template x-for="opp in results" :key="opp.id">
                    <button type="button" @click="select(opp.id)"
                            class="w-full text-left px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 mb-2">
                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="opp.label"></div>
                        <div class="text-xs text-gray-500 dark:text-gray-400" x-show="opp.sub" x-text="opp.sub"></div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5" x-text="opp.status"></div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Hidden form — submitted programmatically when an opportunity is selected --}}
    <form x-ref="linkForm" method="POST" action="{{ route('pages.estimates.link-opportunity', $estimate) }}" class="hidden">
        @csrf
        <input type="hidden" name="opportunity_id" x-ref="opportunityIdInput">
    </form>
</div>

@once
<script>
function linkOpportunityModal() {
    return {
        show: false,
        loading: false,
        searched: false,
        query: '',
        results: [],

        open() {
            this.show = true;
            this.query = '';
            this.results = [];
            this.searched = false;
            this.search();
        },

        close() {
            this.show = false;
        },

        search() {
            this.loading = true;
            fetch(`{{ route('pages.estimates.api.opportunities.search') }}?q=${encodeURIComponent(this.query)}`)
                .then(r => r.json())
                .then(data => {
                    this.results = data;
                    this.searched = true;
                    this.loading = false;
                })
                .catch(() => {
                    this.results = [];
                    this.searched = true;
                    this.loading = false;
                });
        },

        select(id) {
            this.$refs.opportunityIdInput.value = id;
            this.$refs.linkForm.submit();
        },
    };
}
</script>
@endonce
