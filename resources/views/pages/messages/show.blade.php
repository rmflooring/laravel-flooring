<x-app-layout>
    <x-slot name="header">
        <div class="flex items-start justify-between gap-4">
            <div>
                <a href="{{ route('pages.messages.index') }}"
                   class="mb-1 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Inbox
                </a>
                <h2 class="text-xl font-semibold text-gray-800">{{ $thread->subject ?? '(no subject)' }}</h2>
                <p class="mt-0.5 text-sm text-gray-500">
                    With: {{ $thread->participants->where('id', '<>', auth()->id())->pluck('name')->implode(', ') }}
                    @if ($thread->context_label)
                        &middot;
                        @if ($thread->context_url)
                            <a href="{{ $thread->context_url }}"
                               class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/>
                                </svg>
                                {{ $thread->context_label }}
                            </a>
                        @else
                            <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700">{{ $thread->context_label }}</span>
                        @endif
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="flex flex-col gap-4 max-w-3xl">
        {{-- Message thread --}}
        <div class="space-y-4">
            @foreach ($thread->messages as $message)
                @php $isMine = $message->sender_id === auth()->id(); @endphp
                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }} gap-3">
                    @if (!$isMine)
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600">
                            {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="max-w-lg">
                        <div class="{{ $isMine ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-800' }} rounded-2xl px-4 py-3 shadow-sm">
                            <p class="whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                        </div>
                        <p class="mt-1 text-xs text-gray-400 {{ $isMine ? 'text-right' : '' }}">
                            {{ $isMine ? 'You' : $message->sender->name }} &middot; {{ $message->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if ($isMine)
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">
                            {{ strtoupper(substr($message->sender->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Reply box --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('pages.messages.reply', $thread) }}" class="p-4">
                @csrf
                <textarea name="body" required rows="3"
                          class="w-full resize-none rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                          placeholder="Reply…"></textarea>
                <div class="mt-2 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                        </svg>
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Scroll to bottom of messages on load
    document.addEventListener('DOMContentLoaded', () => {
        window.scrollTo(0, document.body.scrollHeight);
    });
    </script>
</x-app-layout>
