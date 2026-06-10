<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Messages</h2>
            <button type="button" onclick="openNewMessageModal()"
               class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                New Message
            </button>
        </div>
    </x-slot>

    @if ($threads->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-white p-12 text-center text-gray-500">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.068.157 2.148.279 3.238.364.466.037.893.281 1.153.671L12 21l2.652-3.978c.26-.39.687-.634 1.153-.671 1.09-.085 2.17-.207 3.238-.364 1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
            </svg>
            <p class="text-sm">No messages yet. Start a conversation.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <ul class="divide-y divide-gray-100">
                @foreach ($threads as $thread)
                    @php
                        $unread = $thread->unreadCountFor(auth()->id());
                        $latest = $thread->latestMessage;
                    @endphp
                    <li>
                        <a href="{{ route('pages.messages.show', $thread) }}"
                           class="flex items-start gap-4 px-5 py-4 hover:bg-gray-50 {{ $unread > 0 ? 'bg-blue-50' : '' }}">
                            <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-sm font-semibold text-gray-600">
                                {{ strtoupper(substr(optional($latest?->sender)->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="truncate text-sm {{ $unread > 0 ? 'font-semibold text-gray-900' : 'font-medium text-gray-700' }}">
                                        {{ $thread->subject ?? '(no subject)' }}
                                    </span>
                                    <span class="flex-shrink-0 text-xs text-gray-400">
                                        {{ optional($latest)->created_at?->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-xs text-gray-500">
                                        @if ($latest)
                                            <span class="font-medium text-gray-600">{{ $latest->sender->name }}:</span>
                                            {{ Str::limit($latest->body, 80) }}
                                        @endif
                                    </p>
                                    @if ($unread > 0)
                                        <span class="flex-shrink-0 rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">
                                            {{ $unread }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-0.5 flex items-center gap-3">
                                    <p class="text-xs text-gray-400">
                                        {{ $thread->participants->pluck('name')->implode(', ') }}
                                    </p>
                                    @if ($thread->context_label)
                                        <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                                            </svg>
                                            {{ $thread->context_label }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- New Message Modal --}}
    <div id="new-message-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b px-5 py-4">
                <h3 class="text-base font-semibold text-gray-800">New Message</h3>
                <button type="button" onclick="document.getElementById('new-message-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('pages.messages.store') }}" class="p-5 space-y-4">
                @csrf

                {{-- Recipients --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">To</label>
                    <div id="recipient-list" class="flex flex-wrap gap-2 mb-2"></div>
                    <div class="relative" x-data="recipientSearch()" @click.outside="open = false">
                        <input type="text" x-model="query" @input.debounce.300ms="search()"
                               @focus="if(query.length >= 1) open = true"
                               placeholder="Search users…"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <ul x-show="open && results.length > 0" x-cloak
                            class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-for="user in results" :key="user.id">
                                <li @click="add(user)"
                                    class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100"
                                    x-text="user.name + ' (' + user.email + ')'"></li>
                            </template>
                        </ul>
                    </div>
                    <div id="recipient-inputs"></div>
                </div>

                {{-- Subject --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Subject</label>
                    <input type="text" name="subject" id="msg-subject" required maxlength="255"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                           placeholder="Subject">
                </div>

                {{-- Link to Job (optional) --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        Link to Job
                        <span class="ml-1 font-normal text-gray-400">(optional)</span>
                    </label>
                    <div id="job-link-chip" class="mb-2 hidden">
                        <span id="job-link-label" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800">
                            <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                            </svg>
                            <span></span>
                            <button type="button" onclick="clearJobLink()" class="ml-1 text-indigo-500 hover:text-indigo-700">&times;</button>
                        </span>
                    </div>
                    <div class="relative" x-data="jobSearch()" @click.outside="open = false">
                        <input type="text" id="job-search-input" x-model="query" @input.debounce.300ms="search()"
                               @focus="if(query.length >= 1) open = true"
                               placeholder="Search opportunities or sales…"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <ul x-show="open && results.length > 0" x-cloak
                            class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-for="job in results" :key="job.type + job.id">
                                <li @click="select(job)"
                                    class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-100"
                                    x-text="job.label"></li>
                            </template>
                        </ul>
                    </div>
                    <input type="hidden" name="threadable_type" id="threadable-type-input">
                    <input type="hidden" name="threadable_id"   id="threadable-id-input">
                </div>

                {{-- Message body --}}
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Message</label>
                    <textarea name="body" required rows="5"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                              placeholder="Write your message…"></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-1">
                    <button type="button" onclick="document.getElementById('new-message-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Send
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // ── Pre-fill from query params (called from show pages) ─────────────────
    const _preType  = @json($preThreadableType);
    const _preId    = @json($preThreadableId);
    const _preOpen  = @json($preOpen);

    function openNewMessageModal(threadableType, threadableId, threadableLabel) {
        document.getElementById('new-message-modal').classList.remove('hidden');
        if (threadableType && threadableId) {
            setJobLink(threadableType, threadableId, threadableLabel || threadableType + ' #' + threadableId);
        }
    }

    function setJobLink(type, id, label) {
        document.getElementById('threadable-type-input').value = type;
        document.getElementById('threadable-id-input').value   = id;
        document.getElementById('job-search-input').value      = '';
        const chip = document.getElementById('job-link-chip');
        chip.querySelector('span > span').textContent = label;
        chip.classList.remove('hidden');
        // Hide the search input while a job is linked
        document.getElementById('job-search-input').classList.add('hidden');
    }

    function clearJobLink() {
        document.getElementById('threadable-type-input').value = '';
        document.getElementById('threadable-id-input').value   = '';
        document.getElementById('job-link-chip').classList.add('hidden');
        document.getElementById('job-search-input').classList.remove('hidden');
    }

    // Auto-open if arriving from a show page with ?new=1
    if (_preOpen && _preType && _preId) {
        document.addEventListener('DOMContentLoaded', () => {
            openNewMessageModal(_preType, _preId, null);
            // Fetch the label asynchronously
            fetch('/pages/messages/api/jobs?q=' + encodeURIComponent(_preId))
                .then(r => r.json())
                .then(results => {
                    const match = results.find(r => r.type === _preType && String(r.id) === String(_preId));
                    if (match) setJobLink(_preType, _preId, match.label);
                });
        });
    }

    // ── Recipient search (Alpine component) ─────────────────────────────────
    function recipientSearch() {
        return {
            query: '',
            results: [],
            open: false,
            selected: [],

            async search() {
                if (this.query.length < 1) { this.open = false; return; }
                const res = await fetch('/pages/messages/api/users?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.length > 0;
            },

            add(user) {
                if (this.selected.find(u => u.id === user.id)) { this.query = ''; this.open = false; return; }
                this.selected.push(user);
                this.query = '';
                this.open = false;
                this.renderChips();
            },

            remove(id) {
                this.selected = this.selected.filter(u => u.id !== id);
                this.renderChips();
            },

            renderChips() {
                const list   = document.getElementById('recipient-list');
                const inputs = document.getElementById('recipient-inputs');
                list.innerHTML = '';
                inputs.innerHTML = '';
                this.selected.forEach(u => {
                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-800';
                    chip.innerHTML = `${u.name} <button type="button" onclick="window._recipientSearch.remove(${u.id})" class="text-blue-500 hover:text-blue-700">&times;</button>`;
                    list.appendChild(chip);
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = 'recipients[]';
                    inp.value = u.id;
                    inputs.appendChild(inp);
                });
            }
        };
    }

    // ── Job search (Alpine component) ────────────────────────────────────────
    function jobSearch() {
        return {
            query: '',
            results: [],
            open: false,

            async search() {
                if (this.query.length < 1) { this.open = false; return; }
                const res = await fetch('/pages/messages/api/jobs?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.length > 0;
            },

            select(job) {
                setJobLink(job.type, job.id, job.label);
                this.query   = '';
                this.results = [];
                this.open    = false;
            }
        };
    }

    // Expose recipient instance for chip remove buttons
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].selected !== undefined) {
                    window._recipientSearch = el._x_dataStack[0];
                }
            });
        }, 300);
    });
    </script>
</x-app-layout>
