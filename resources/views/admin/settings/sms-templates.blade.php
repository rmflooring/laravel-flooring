<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">SMS Templates</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Customise the SMS message body for each notification type. Keep messages under 160 characters to avoid splitting.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.settings.sms') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        SMS Settings
                    </a>
                    <a href="{{ route('admin.settings') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Back
                    </a>
                </div>
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

            {{-- Tabs --}}
            <div x-data="{ tab: '{{ request('tab', array_key_first(\App\Models\SmsTemplate::TYPES)) }}' }">

                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="-mb-px flex space-x-1 overflow-x-auto" aria-label="Tabs">
                        @foreach (\App\Models\SmsTemplate::TYPES as $key => $label)
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
                @foreach (\App\Models\SmsTemplate::TYPES as $type => $label)
                    @php
                        $saved    = $templates[$type] ?? null;
                        $body     = $saved?->body ?? \App\Models\SmsTemplate::DEFAULTS[$type] ?? '';
                        $tags     = \App\Models\SmsTemplate::TAGS[$type] ?? [];
                        $isCustom = (bool) $saved;
                        $charCount = strlen($body);
                    @endphp

                    <div x-show="tab === '{{ $type }}'" x-cloak class="pt-6 space-y-4">

                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold text-gray-800 dark:text-white">{{ $label }}</h2>
                            @if ($isCustom)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Custom</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Default</span>
                            @endif
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6 space-y-4">
                            <form method="POST" action="{{ route('admin.settings.sms-templates.save', $type) }}" id="sms-save-form-{{ $type }}">
                                @csrf

                                <div x-data="smsCharCounter('{{ $type }}')" x-init="init()">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message Body</label>
                                        <span class="text-xs"
                                            :class="chars > 160 ? 'text-amber-600 font-semibold' : 'text-gray-400'">
                                            <span x-text="chars"></span> chars
                                            <span x-show="chars > 160" class="ml-1 text-amber-600">(spans <span x-text="Math.ceil(chars / 153)"></span> messages)</span>
                                        </span>
                                    </div>
                                    <textarea name="body" rows="4"
                                              x-ref="textarea"
                                              @input="chars = $refs.textarea.value.length"
                                              class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('body', $body) }}</textarea>
                                    @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>

                                {{-- Available tags --}}
                                <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700/50">
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Available tags — click to copy</p>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($tags as $tag)
                                            <code class="px-2 py-0.5 text-xs rounded bg-white border border-gray-200 text-gray-700 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 cursor-pointer select-all"
                                                  onclick="navigator.clipboard.writeText('{{ $tag }}')">{{ $tag }}</code>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Tags are replaced with real values when the SMS is sent.</p>
                                </div>
                            </form>

                            <div class="flex items-center justify-between pt-2">
                                @if ($isCustom)
                                    <form method="POST" action="{{ route('admin.settings.sms-templates.reset', $type) }}"
                                          onsubmit="return confirm('Reset to default template?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:underline dark:text-red-400">
                                            Reset to default
                                        </button>
                                    </form>
                                @else
                                    <span></span>
                                @endif

                                <button type="submit" form="sms-save-form-{{ $type }}"
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

    <script>
        function smsCharCounter(type) {
            return {
                chars: 0,
                init() {
                    this.chars = this.$refs.textarea.value.length;
                }
            }
        }
    </script>
</x-app-layout>
