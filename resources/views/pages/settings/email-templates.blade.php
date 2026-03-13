<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Templates</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Customise the subject and body for customer-facing emails sent from your account.
                        Use the tags listed below each template to insert dynamic values.
                    </p>
                </div>
                <a href="{{ route('profile.edit') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    Back
                </a>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between dark:bg-green-900/30 dark:text-green-200 dark:border-green-700">
                    <span>{{ session('success') }}</span>
                    <button type="button" onclick="this.closest('div').remove()" class="text-green-900 dark:text-green-200 text-sm font-medium">✕</button>
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg flex items-center justify-between dark:bg-red-900/30 dark:text-red-200 dark:border-red-700">
                    <span>{{ session('error') }}</span>
                    <button type="button" onclick="this.closest('div').remove()" class="text-red-900 dark:text-red-200 text-sm font-medium">✕</button>
                </div>
            @endif

            {{-- Tab nav --}}
            <div x-data="{ tab: '{{ request('tab', array_key_first(\App\Models\EmailTemplate::USER_TYPES)) }}' }">

                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-1 overflow-x-auto" aria-label="Tabs">
                        @foreach (\App\Models\EmailTemplate::USER_TYPES as $key => $label)
                            <button type="button"
                                    @click="tab = '{{ $key }}'"
                                    :class="tab === '{{ $key }}'
                                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                                    class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors">
                                {{ $label }}
                            </button>
                        @endforeach
                    </nav>
                </div>

                {{-- Template panels --}}
                @foreach (\App\Models\EmailTemplate::USER_TYPES as $type => $label)
                    @php
                        $saved    = $templates[$type] ?? null;
                        $default  = \App\Models\EmailTemplate::DEFAULTS[$type] ?? ['subject' => '', 'body' => ''];
                        $subject  = $saved?->subject ?? $default['subject'];
                        $body     = $saved?->body     ?? $default['body'];
                        $tags     = \App\Models\EmailTemplate::TAGS[$type] ?? [];
                        $isCustom = (bool) $saved;
                    @endphp

                    <div x-show="tab === '{{ $type }}'" x-cloak class="pt-6 space-y-4">

                        {{-- Status badge --}}
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold text-gray-800 dark:text-white">{{ $label }} Template</h2>
                            @if ($isCustom)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Custom</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Default</span>
                            @endif
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6 space-y-4">
                            <form method="POST" action="{{ route('pages.settings.email-templates.save', $type) }}" id="save-form-{{ $type }}">
                                @csrf

                                {{-- Subject --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject</label>
                                    <input type="text" name="subject" value="{{ old('subject', $subject) }}"
                                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>

                                {{-- Body --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Body</label>
                                    <textarea name="body" rows="10"
                                              class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('body', $body) }}</textarea>
                                </div>

                                {{-- Available tags --}}
                                <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700/50">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Available tags</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($tags as $tag)
                                            <code class="px-2 py-0.5 text-xs rounded bg-white border border-gray-200 text-gray-700 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 cursor-pointer select-all"
                                                  onclick="navigator.clipboard.writeText('{{ $tag }}')">{{ $tag }}</code>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Click a tag to copy it.</p>
                                </div>
                            </form>

                            <div class="flex items-center justify-between pt-2">
                                @if ($isCustom)
                                    <form method="POST" action="{{ route('pages.settings.email-templates.reset', $type) }}"
                                          onsubmit="return confirm('Reset to default template?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-sm text-red-600 hover:underline dark:text-red-400">
                                            Reset to default
                                        </button>
                                    </form>
                                @else
                                    <span></span>
                                @endif

                                <button type="submit" form="save-form-{{ $type }}"
                                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                    Save Template
                                </button>
                            </div>
                        </div>

                    </div>
                @endforeach

            </div>

        </div>
    </div>
</x-app-layout>
