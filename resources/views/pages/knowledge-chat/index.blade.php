<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knowledge Assistant</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Ask about pricing, protocols, policies, or a specific job/order/estimate. Answers are read-only
                    and cite what they're based on where possible — verify anything important.
                </p>
            </div>

            <div x-data="knowledgeChat({{ $recent->reverse()->values()->toJson() }})" x-init="init()"
                 class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm flex flex-col" style="height: 65vh;">

                <div x-ref="messages" class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    <template x-if="messages.length === 0">
                        <p class="text-sm text-gray-400 text-center mt-8">No questions yet — ask something below to get started.</p>
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
                    <textarea x-model="input" rows="1" @keydown.enter.prevent="ask()"
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

    <script>
        function knowledgeChat(recent) {
            return {
                messages: [],
                input: '',
                loading: false,

                init() {
                    (recent || []).forEach((q) => {
                        this.messages.push({ id: 'u' + q.id, sender: 'user', text: q.question });
                        this.messages.push({
                            id: 'a' + q.id,
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

                    this.messages.push({ id: 'u' + Date.now(), sender: 'user', text: question });
                    this.input = '';
                    this.loading = true;
                    this.$nextTick(() => this.scrollDown());

                    try {
                        const res = await fetch("{{ route('pages.knowledge-chat.ask') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({ question }),
                        });
                        const data = await res.json();

                        if (!res.ok) {
                            this.messages.push({ id: 'a' + Date.now(), sender: 'agent', text: data.error || 'Something went wrong.' });
                        } else {
                            this.messages.push({
                                id: 'a' + data.id,
                                queryId: data.id,
                                sender: 'agent',
                                text: data.answer,
                                sources: data.source_refs || [],
                            });
                        }
                    } catch (e) {
                        this.messages.push({ id: 'a' + Date.now(), sender: 'agent', text: 'Something went wrong — please try again.' });
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

                scrollDown() {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                },
            };
        }
    </script>
</x-app-layout>
