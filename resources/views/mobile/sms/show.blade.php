{{-- resources/views/mobile/sms/show.blade.php --}}
<x-mobile-layout :title="$conversation->displayName()">

    @include('mobile.sms.partials.flash')

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3 shadow-sm">
        <div class="flex items-center justify-between gap-2">
            <a href="{{ route('mobile.sms.index', $conversation->isArchived() ? ['tab' => 'archived'] : []) }}"
               class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Messages
            </a>
            <div class="flex items-center gap-2">
                <a href="tel:{{ $conversation->phone }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gray-200 dark:border-gray-600 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    Call
                </a>
                <form method="POST" action="{{ route($conversation->isArchived() ? 'mobile.sms.unarchive' : 'mobile.sms.archive', $conversation) }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-600 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                        {{ $conversation->isArchived() ? 'Restore' : 'Archive' }}
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-2 text-base font-bold text-gray-900 dark:text-white">{{ $conversation->displayName() }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ $conversation->phone }}
            @if ($conversation->channel !== 'voipms') &middot; reminders line @endif
            @if ($conversation->isArchived()) &middot; <span class="font-semibold">Archived</span> @endif
        </p>
        @if ($conversation->opportunity && auth()->user()->can('view opportunities'))
            <a href="{{ route('mobile.opportunity.show', $conversation->opportunity) }}"
               class="mt-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
               style="background:#e0f2fe;color:#075985;">
                Job {{ $conversation->opportunity->job_no ?: '#' . $conversation->opportunity->id }} &rarr;
            </a>
        @endif
        @if ($conversation->isUnknown())
            <p class="mt-2 rounded-lg px-3 py-2 text-xs" style="background:#fefce8;color:#854d0e;">
                Unknown number. Link it to a customer from the desktop SMS inbox.
            </p>
        @endif
    </div>

    {{-- Thread --}}
    <div class="space-y-3">
        @forelse ($conversation->messages as $message)
            @php $out = $message->isOutbound(); @endphp
            <div class="flex {{ $out ? 'justify-end' : 'justify-start' }}">
                <div style="max-width:85%;">
                    <div class="rounded-2xl px-4 py-2.5 shadow-sm {{ $out ? 'text-white' : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-100' }}"
                         style="{{ $out ? 'background:#2563eb;border-bottom-right-radius:6px;' : 'border-bottom-left-radius:6px;' }}">
                        @if ($message->hasMedia())
                            <div class="flex flex-wrap gap-1.5 {{ trim((string) $message->body) !== '' ? 'mb-2' : '' }}">
                                @foreach ($message->media as $i => $item)
                                    @php $mediaUrl = route('mobile.sms.media', [$message, $i]); @endphp
                                    @if (in_array($item['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp']))
                                        <a href="{{ $mediaUrl }}" target="_blank">
                                            <img src="{{ $mediaUrl }}" alt="Picture" loading="lazy"
                                                 class="rounded-lg object-cover" style="max-height:14rem;max-width:100%;">
                                        </a>
                                    @elseif (str_starts_with($item['mime'], 'video/'))
                                        <video src="{{ $mediaUrl }}" controls preload="metadata" class="rounded-lg" style="max-height:14rem;max-width:100%;"></video>
                                    @else
                                        <a href="{{ $mediaUrl }}" target="_blank" class="text-sm underline">📎 Open attachment</a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @if (trim((string) $message->body) !== '')
                            <p class="whitespace-pre-wrap text-sm" style="overflow-wrap:anywhere;">{{ $message->body }}</p>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-400 {{ $out ? 'text-right' : '' }}">
                        @if ($out){{ $message->sentBy?->name ?? 'Staff' }} &middot; @endif
                        {{ $message->created_at->isToday() ? $message->created_at->format('g:i A') : $message->created_at->format('M j, g:i A') }}
                        @if ($message->unconfirmed)
                            &middot; <span class="font-medium" style="color:#d97706;">Unconfirmed</span>
                        @elseif ($message->failed)
                            &middot; <span class="font-medium" style="color:#dc2626;">Not delivered — please resend</span>
                        @endif
                    </p>
                </div>
            </div>
        @empty
            <p class="text-center text-sm text-gray-400 py-8">No messages yet.</p>
        @endforelse
    </div>

    {{-- Reply box, pinned to the bottom of the screen --}}
    <div id="sms-reply" class="sticky bottom-0 px-4 pt-2 pb-4 bg-gray-100 dark:bg-gray-900" style="margin-left:-1rem;margin-right:-1rem;">
        @if ($conversation->customer?->sms_opted_out)
            <div class="rounded-xl px-4 py-3 text-sm" style="background:#fef2f2;color:#991b1b;">
                This customer has opted out of SMS (sent STOP). You can't send them messages.
            </div>
        @else
            <form method="POST" action="{{ route('mobile.sms.reply', $conversation) }}" enctype="multipart/form-data"
                  class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-3"
                  onsubmit="const b=this.querySelector('[type=submit]'); b.disabled=true; b.textContent='Sending…';">
                @csrf
                <textarea name="body" rows="2" maxlength="1600"
                          class="w-full resize-none rounded-xl border border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-2 text-base focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                          placeholder="Type a message…">{{ old('body') }}</textarea>
                <div class="flex items-end justify-between gap-2">
                    <div class="min-w-0">
                        @if ($conversation->channel === 'voipms')
                            @include('pages.sms.partials.photo-picker')
                        @else
                            <p class="mt-2 text-xs text-gray-400">Reminders line · text only</p>
                        @endif
                    </div>
                    <button type="submit"
                            class="mt-2 inline-flex flex-shrink-0 items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white active:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                        </svg>
                        Send
                    </button>
                </div>
            </form>
        @endif
    </div>

    <script>
        // Open at the newest message
        document.addEventListener('DOMContentLoaded', () => window.scrollTo(0, document.body.scrollHeight));
    </script>
</x-mobile-layout>
