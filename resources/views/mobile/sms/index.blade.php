{{-- resources/views/mobile/sms/index.blade.php --}}
<x-mobile-layout title="Messages">

    @include('mobile.sms.partials.flash')

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">SMS</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">Messages</p>
        </div>
        <a href="{{ route('mobile.sms.create') }}"
           class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm active:bg-blue-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New
        </a>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2">
        @foreach (['inbox' => 'Inbox', 'unread' => 'Unread', 'archived' => 'Archived'] as $val => $label)
            <a href="{{ route('mobile.sms.index', $val === 'inbox' ? [] : ['tab' => $val]) }}"
               class="flex-1 py-1.5 text-center text-xs font-medium rounded-lg border
                      {{ $tab === $val
                          ? 'bg-blue-600 border-blue-600 text-white'
                          : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }}">
                {{ $label }}
                @if ($val === 'unread' && $unreadCount > 0)
                    <span class="ml-0.5">({{ $unreadCount > 99 ? '99+' : $unreadCount }})</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Conversations --}}
    @if ($conversations->isEmpty())
        <div class="text-center py-12 text-gray-400 dark:text-gray-500">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/>
            </svg>
            <p class="text-sm">
                @if ($tab === 'unread') No unread messages
                @elseif ($tab === 'archived') No archived conversations
                @else No conversations yet
                @endif
            </p>
        </div>
    @else
        <div class="space-y-2">
            @foreach ($conversations as $conversation)
                @php
                    $last    = $conversation->latestMessage->first();
                    $unread  = $conversation->unread_count > 0;
                    $name    = $conversation->displayName();
                    $initial = $conversation->isUnknown() ? '#' : strtoupper(mb_substr($name, 0, 1));
                @endphp
                <a href="{{ route('mobile.sms.show', $conversation) }}"
                   class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3 shadow-sm active:bg-gray-50">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 text-sm font-semibold"
                         style="{{ $unread ? 'background:#2563eb;color:#fff;' : 'background:#e5e7eb;color:#4b5563;' }}">
                        {{ $initial }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm truncate text-gray-900 dark:text-white {{ $unread ? 'font-bold' : 'font-semibold' }}">{{ $name }}</span>
                            <span class="text-xs flex-shrink-0 {{ $unread ? 'text-blue-600 font-semibold' : 'text-gray-400' }}">
                                {{ $conversation->last_message_at?->isToday()
                                    ? $conversation->last_message_at->format('g:i A')
                                    : $conversation->last_message_at?->format('M j') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <p class="text-xs truncate {{ $unread ? 'text-gray-800 dark:text-gray-200 font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                @if ($last?->isOutbound())<span class="text-gray-400">You: </span>@endif{{ $last?->previewText() ?: '—' }}
                            </p>
                            @if ($unread)
                                <span class="flex-shrink-0 rounded-full px-1.5 text-xs font-bold text-white" style="background:#2563eb;min-width:1.25rem;text-align:center;">
                                    {{ $conversation->unread_count }}
                                </span>
                            @endif
                        </div>
                        @if ($conversation->isUnknown() || $conversation->customer?->name)
                            <p class="text-xs text-gray-400 truncate mt-0.5">
                                {{ $conversation->isUnknown() ? 'Unknown number' : $conversation->phone }}
                                @if ($conversation->channel !== 'voipms') &middot; reminders line @endif
                            </p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        @if ($conversations->hasPages())
            <div class="flex items-center justify-between pt-2">
                @if ($conversations->onFirstPage())
                    <span></span>
                @else
                    <a href="{{ $conversations->previousPageUrl() }}" class="text-sm font-medium text-blue-600">&larr; Newer</a>
                @endif
                <span class="text-xs text-gray-400">Page {{ $conversations->currentPage() }} of {{ $conversations->lastPage() }}</span>
                @if ($conversations->hasMorePages())
                    <a href="{{ $conversations->nextPageUrl() }}" class="text-sm font-medium text-blue-600">Older &rarr;</a>
                @else
                    <span></span>
                @endif
            </div>
        @endif
    @endif

</x-mobile-layout>
