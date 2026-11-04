<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knowledge Assistant</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Ask about pricing, protocols, policies, or a specific job/order/estimate. Answers are read-only
                    and cite what they're based on where possible — verify anything important.
                    Each chat remembers its own earlier questions, so start a new chat for a new topic.
                </p>
            </div>

            <div x-data="knowledgeChat(@js($queries->values()), @js($active), @js($conversations->values()), @js($showArchived))" x-init="init()"
                 class="flex flex-col lg:flex-row gap-4">

                {{-- Chat list --}}
                <aside class="lg:w-64 shrink-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm flex flex-col lg:h-[65vh]">
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 space-y-2">
                        <a href="{{ route('pages.knowledge-chat.index') }}"
                           class="flex items-center justify-center gap-1.5 w-full px-3 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            <span class="text-base leading-none">+</span> New chat
                        </a>
                        <div class="flex text-xs font-medium rounded-lg bg-gray-100 dark:bg-gray-700 p-0.5">
                            <a href="{{ route('pages.knowledge-chat.index') }}"
                               class="flex-1 text-center rounded-md py-1 {{ $showArchived ? 'text-gray-500 dark:text-gray-400' : 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' }}">Chats</a>
                            <a href="{{ route('pages.knowledge-chat.index', ['archived' => 1]) }}"
                               class="flex-1 text-center rounded-md py-1 {{ $showArchived ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-800 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">Archived</a>
                        </div>
                    </div>

                    <nav class="flex-1 overflow-y-auto p-2 space-y-0.5 max-h-48 lg:max-h-none">
                        <template x-if="conversations.length === 0">
                            <p class="text-xs text-gray-400 text-center py-4" x-text="showArchived ? 'No archived chats.' : 'No chats yet.'"></p>
                        </template>
                        <template x-for="c in conversations" :key="c.id">
                            <a :href="c.url"
                               class="block rounded-md px-2.5 py-2 text-sm"
                               :class="c.id === conversationId
                                   ? 'bg-blue-50 text-blue-800 dark:bg-gray-700 dark:text-white'
                                   : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'">
                                <span class="block truncate" x-text="c.title"></span>
                                <span class="block text-[11px] text-gray-400" x-text="c.updated"></span>
                            </a>
                        </template>
                    </nav>
                </aside>

                {{-- Active chat --}}
                <div class="flex-1 min-w-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm flex flex-col" style="height: 65vh;">

                <div class="flex items-center justify-between gap-3 px-6 py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="title || 'New chat'"></p>
                        <p x-show="archived" class="text-[11px] text-amber-600 dark:text-amber-400">Archived — asking a question here will move it back to Chats.</p>
                    </div>
                    <template x-if="conversationId">
                        <button type="button" @click="toggleArchive()" :disabled="archiving"
                            class="shrink-0 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700 disabled:opacity-50"
                            x-text="archived ? 'Unarchive' : 'Archive'"></button>
                    </template>
                </div>

                <div x-ref="messages" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <template x-if="messages.length === 0">
                        <p class="text-sm text-gray-400 text-center mt-8">Ask a question below to start a new chat.</p>
                    </template>

                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex" :class="msg.sender === 'user' ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] rounded-lg px-4 py-2 text-sm"
                                 :class="msg.sender === 'user'
                                     ? 'bg-blue-600 text-white'
                                     : 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100'">
                                <p class="whitespace-pre-line" x-text="msg.text"></p>

                                <template x-if="msg.sources && msg.sources.length">
                                    <div class="mt-2 pt-2 border-t border-gray-300/50 dark:border-gray-600 flex flex-wrap gap-1.5">
                                        <template x-for="src in msg.sources" :key="src.type + (src.id ?? src.label)">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-white/70 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                <span x-text="src.type === 'knowledge_entry' ? '📄 ' + src.title : '🔎 ' + src.label"></span>
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="msg.sender === 'agent' && msg.queryId">
                                    <div class="mt-1.5 flex items-center gap-2">
                                        <button type="button" @click="feedback(msg, 'up')"
                                            class="text-xs" :class="msg.feedback === 'up' ? 'text-green-600' : 'text-gray-400 hover:text-gray-600'">
                                            👍
                                        </button>
                                        <button type="button" @click="feedback(msg, 'down')"
                                            class="text-xs" :class="msg.feedback === 'down' ? 'text-red-600' : 'text-gray-400 hover:text-gray-600'">
                                            👎
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="loading" class="flex justify-start">
                        <div class="rounded-lg px-4 py-2 text-sm bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            Thinking…
                        </div>
                    </div>
                </div>

                <form @submit.prevent="ask()" class="border-t border-gray-200 dark:border-gray-700 p-4 flex items-end gap-3">
                    <textarea x-model="input" rows="1" @keydown.enter.prevent="$el.closest('form').requestSubmit()"
                        placeholder="Ask a question..."
                        class="flex-1 resize-none bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                    <button type="submit" :disabled="loading || !input.trim()"
                        class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        Ask
                    </button>
                </form>
                </div>
            </div>

        </div>
    </div>

    <script>
        function knowledgeChat(queries, active, conversations, showArchived) {
            return {
                messages: [],
                input: '',
                loading: false,
                archiving: false,
                // null until the first question of a new chat creates one server-side
                conversationId: active ? active.id : null,
                title: active ? active.title : '',
                archived: active ? active.archived : false,
                conversations: conversations || [],
                showArchived: showArchived,
                // Monotonic counter for locally-generated message ids — Date.now() only
                // has millisecond resolution, which can collide if two bubbles are ever
                // pushed in the same tick. A duplicate x-for key crashes Alpine's list
                // diffing for the rest of the page, which is what "thinking then blank"
                // actually was: not a failed request, a client-side render crash.
                localIdSeq: 0,

                nextLocalId(prefix) {
                    this.localIdSeq += 1;
                    return prefix + '-local-' + this.localIdSeq;
                },

                // Last line of defense against duplicate x-for keys: no matter what
                // causes ask() to fire more than once for one action (a browser-specific
                // Enter-key quirk on the textarea turned out to still slip past the
                // requestSubmit() fix), the same id is never pushed twice. A duplicate
                // key crashes Alpine's list diffing for the rest of the page — this
                // makes that structurally impossible instead of chasing every trigger.
                pushMessage(msg) {
                    if (this.messages.some((m) => m.id === msg.id)) return;
                    this.messages.push(msg);
                },

                init() {
                    (queries || []).forEach((q) => {
                        this.pushMessage({ id: 'u-query-' + q.id, sender: 'user', text: q.question });
                        this.pushMessage({
                            id: 'a-query-' + q.id,
                            queryId: q.id,
                            sender: 'agent',
                            text: q.answer,
                            sources: q.source_refs || [],
                            feedback: q.feedback,
                        });
                    });
                    this.$nextTick(() => this.scrollDown());
                },

                async ask() {
                    const question = this.input.trim();
                    if (!question || this.loading) return;

                    this.loading = true;
                    this.pushMessage({ id: this.nextLocalId('u'), sender: 'user', text: question });
                    this.input = '';
                    this.$nextTick(() => this.scrollDown());

                    try {
                        const res = await fetch("{{ route('pages.knowledge-chat.ask') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({ question, conversation_id: this.conversationId }),
                        });
                        const data = await res.json();

                        if (data.conversation) this.syncConversation(data.conversation);

                        if (!res.ok) {
                            this.pushMessage({ id: this.nextLocalId('a'), sender: 'agent', text: data.error || 'Something went wrong.' });
                        } else {
                            this.pushMessage({
                                id: 'a-query-' + data.id,
                                queryId: data.id,
                                sender: 'agent',
                                text: data.answer,
                                sources: data.source_refs || [],
                            });
                        }
                    } catch (e) {
                        console.error('[knowledge-chat] ask() failed', e);
                        this.pushMessage({ id: this.nextLocalId('a'), sender: 'agent', text: 'Something went wrong — please try again.' });
                    }

                    this.loading = false;
                    this.$nextTick(() => this.scrollDown());
                },

                async feedback(msg, value) {
                    if (!msg.queryId) return;
                    msg.feedback = value;

                    await fetch(`/pages/knowledge-chat/${msg.queryId}/feedback`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({ feedback: value }),
                    });
                },

                // Keeps the header, URL and chat list in step after a question — a new
                // chat gets its own URL so a refresh lands back on it.
                syncConversation(conv) {
                    const isNew = this.conversationId !== conv.id;
                    this.conversationId = conv.id;
                    this.title = conv.title;
                    this.archived = conv.archived;
                    if (isNew) history.replaceState(null, '', conv.url);

                    this.conversations = this.conversations.filter((c) => c.id !== conv.id);
                    if (!this.showArchived) this.conversations.unshift(conv);
                },

                async toggleArchive() {
                    if (!this.conversationId || this.archiving) return;
                    this.archiving = true;

                    const action = this.archived ? 'unarchive' : 'archive';
                    const res = await fetch(`/pages/knowledge-chat/chats/${this.conversationId}/${action}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    });

                    if (!res.ok) {
                        this.archiving = false;
                        alert('Could not ' + action + ' this chat — please try again.');
                        return;
                    }

                    // Archived → back to a fresh chat; unarchived → reopen it under Chats
                    window.location = action === 'archive'
                        ? "{{ route('pages.knowledge-chat.index') }}"
                        : `/pages/knowledge-chat/chats/${this.conversationId}`;
                },

                scrollDown() {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                },
            };
        }
    </script>
</x-app-layout>
