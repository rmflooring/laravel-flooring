{{-- resources/views/pages/opportunities/documents/show-generated.blade.php --}}
<x-app-layout>
    <div class="max-w-screen-lg mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Breadcrumb --}}
        <nav class="mb-4 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
               class="hover:text-blue-600 dark:hover:text-blue-400">
                Opportunity #{{ $opportunity->job_no ?? $opportunity->id }}
            </a>
            <span>/</span>
            <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
               class="hover:text-blue-600 dark:hover:text-blue-400">Documents</a>
            <span>/</span>
            <span class="text-gray-800 dark:text-white">{{ $document->original_name }}</span>
        </nav>

        @if (session('success'))
            <div class="mb-4 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400" role="alert">
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-center rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400" role="alert">
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Header toolbar --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $document->original_name }}</h1>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    @if ($template)
                        Template: <span class="font-medium">{{ $template->name }}</span>
                        &nbsp;·&nbsp;
                    @endif
                    Created {{ $document->created_at?->format('M j, Y g:ia') }}
                    @if ($document->updated_at && $document->updated_at->ne($document->created_at))
                        &nbsp;·&nbsp; Updated {{ $document->updated_at->format('M j, Y g:ia') }}
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('pages.opportunities.documents.edit-generated', [$opportunity->id, $document->id]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                    Edit Fields
                </a>

                <button type="button"
                        onclick="window.dispatchEvent(new CustomEvent('open-doc-email-modal'))"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 dark:focus:ring-emerald-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    Send
                </button>

                <a href="{{ route('pages.opportunities.documents.pdf', [$opportunity->id, $document->id]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                    </svg>
                    Print / PDF
                </a>

                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600">
                    ← Documents
                </a>
            </div>
        </div>

        {{-- Document preview --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            {{-- Preview label --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-2.5 dark:border-gray-700">
                <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Document Preview</span>
                <span class="inline-flex items-center rounded bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    Generated
                </span>
            </div>

            {{-- Rendered content --}}
            <div class="p-6 sm:p-10">
                <div class="mx-auto max-w-[800px] rounded border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-600 dark:bg-gray-900"
                     style="font-family: DejaVu Sans, sans-serif; font-size: 13px; line-height: 1.5; color: #111;">
                    {!! $document->rendered_body !!}
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================ --}}
    {{-- Send Email Modal                                             --}}
    {{-- ============================================================ --}}
    <div x-data="{
            open: false,
            toEmail: '{{ $jobSiteEmail }}',
            customTo: '',
            selected: '{{ $jobSiteEmail ? 'jobsite' : ($pmEmail ? 'pm' : 'custom') }}',
            get finalTo() { return this.selected === 'custom' ? this.customTo : this.toEmail; },
            select(val, email) { this.selected = val; this.toEmail = email; }
         }"
         @open-doc-email-modal.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(0,0,0,0.5)">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl dark:bg-gray-800" @click.outside="open = false">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h5 class="text-base font-semibold text-gray-800 dark:text-white">Send Document</h5>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl leading-none">&times;</button>
            </div>
            <form method="POST" action="{{ route('pages.opportunities.documents.send-email', [$opportunity->id, $document->id]) }}">
                @csrf
                <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">

                    {{-- To --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">To</label>

                        <div class="flex flex-wrap gap-2 mb-2">
                            @if ($jobSiteEmail)
                                <button type="button"
                                        @click="select('jobsite', '{{ $jobSiteEmail }}')"
                                        :class="selected === 'jobsite' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                    Job Site — {{ $jobSiteEmail }}
                                </button>
                            @endif

                            @if ($pmEmail)
                                <button type="button"
                                        @click="select('pm', '{{ $pmEmail }}')"
                                        :class="selected === 'pm' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    PM — {{ $pmEmail }}
                                </button>
                            @endif

                            <button type="button"
                                    @click="select('custom', ''); $nextTick(() => $refs.customToInput.focus())"
                                    :class="selected === 'custom' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium border rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                Custom
                            </button>
                        </div>

                        <template x-if="selected !== 'custom'">
                            <div class="w-full bg-gray-100 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-700" x-text="toEmail"></div>
                        </template>
                        <template x-if="selected === 'custom'">
                            <input type="email" x-ref="customToInput" x-model="customTo"
                                   placeholder="Enter email address"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </template>

                        <input type="hidden" name="to" :value="finalTo">

                        @if (! $jobSiteEmail && ! $pmEmail)
                            <p class="mt-1.5 text-xs text-yellow-700 dark:text-yellow-400">No job site or PM email on file. Enter a custom recipient.</p>
                        @endif
                    </div>

                    {{-- CC --}}
                    <div x-data="{ ccEmails: [
                            @foreach ($customerContacts->where('email', '<>', '') as $contact)
                                @break($loop->index >= 5)
                            @endforeach
                        ], ccInput: '' }">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            CC <span class="text-xs text-gray-400 font-normal">(optional)</span>
                        </label>

                        @if ($customerContacts->where('email', '<>', '')->isNotEmpty())
                            <p class="mb-1.5 text-xs text-gray-500 dark:text-gray-400">Quick-add from contacts:</p>
                            <div class="flex flex-wrap gap-1.5 mb-2">
                                @foreach ($customerContacts->where('email', '<>', '') as $contact)
                                    <button type="button"
                                            @click="if (!ccEmails.includes('{{ $contact->email }}')) { ccEmails.push('{{ $contact->email }}'); }"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full border border-gray-300 bg-white text-gray-700 hover:border-blue-400 hover:text-blue-700 transition-colors dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                        {{ $contact->name }}{{ $contact->title ? ' · ' . $contact->title : '' }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-1.5 mb-2" x-show="ccEmails.length > 0">
                            <template x-for="(email, i) in ccEmails" :key="i">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">
                                    <span x-text="email"></span>
                                    <input type="hidden" name="cc[]" :value="email">
                                    <button type="button" @click="ccEmails.splice(i, 1)" class="text-blue-400 hover:text-blue-600 leading-none ml-1">&times;</button>
                                </span>
                            </template>
                        </div>
                        <div class="flex gap-2">
                            <input type="email" x-model="ccInput"
                                   @keydown.enter.prevent="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                   placeholder="cc@example.com"
                                   class="flex-1 bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <button type="button"
                                    @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                    class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                Add
                            </button>
                        </div>
                    </div>

                    {{-- Subject --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Subject</label>
                        <input type="text" name="subject" value="{{ $emailSubject }}"
                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Message --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Message</label>
                        <textarea name="body" rows="6"
                                  class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $emailBody }}</textarea>
                    </div>

                    {{-- Attachment --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Attachment</label>
                        <a href="{{ route('pages.opportunities.documents.pdf', [$opportunity->id, $document->id]) }}"
                           target="_blank"
                           class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ Str::slug($document->original_name) }}.pdf</span>
                            <span class="text-xs text-gray-400 ml-1">— click to preview</span>
                        </a>
                    </div>

                    <p class="text-xs text-gray-400">
                        @if(auth()->user()->microsoftAccount?->mail_connected)
                            Sending from <strong>{{ auth()->user()->microsoftAccount->email }}</strong> via your personal MS365 account (Track 2).
                        @else
                            Sending via shared mailbox (Track 1). Connect your MS365 account in Settings for personal sending.
                        @endif
                    </p>

                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="open = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-transparent rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300">
                        Send Document
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- ============================================================ --}}

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (sessionStorage.getItem('openDocSendModal') === '1') {
                sessionStorage.removeItem('openDocSendModal');
                window.dispatchEvent(new CustomEvent('open-doc-email-modal'));
            }
        });
    </script>

</x-app-layout>
