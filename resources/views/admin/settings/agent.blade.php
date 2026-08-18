<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Agent Settings</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure the inbound sender allowlist, rate limit, and admin BCC notifications for the Floor Manager AI Agent.</p>
                </div>
                <div class="flex items-center gap-3">
                    @if (Route::has('admin.agent.tasks.index'))
                        <a href="{{ route('admin.agent.tasks.index') }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                            Agent Tasks
                        </a>
                    @endif
                    <a href="{{ route('admin.settings') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Back
                    </a>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button type="button" onclick="this.closest('div').remove()" class="text-green-900 text-sm font-medium">✕</button>
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                    <button type="button" onclick="this.closest('div').remove()" class="text-red-900 text-sm font-medium">✕</button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.settings.agent.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Notifications --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notifications &amp; Rate Limit</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Admin Notification Email</label>
                            <input type="email" name="admin_notification_email" value="{{ old('admin_notification_email', $settings->admin_notification_email) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="admin@rmflooring.ca">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Where BCC copies go, for the task types enabled below.</p>
                            @error('admin_notification_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Rate Limit (tasks / sender / hour)</label>
                            <input type="number" min="1" name="rate_limit_per_sender_per_hour" value="{{ old('rate_limit_per_sender_per_hour', $settings->rate_limit_per_sender_per_hour) }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @error('rate_limit_per_sender_per_hour') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Sender Allowlist --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sender Allowlist</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Only emails forwarded from these domains or addresses are processed. One per line.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Allowed Domains</label>
                            <textarea name="allowed_sender_domains" rows="6"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="rmflooring.ca">{{ old('allowed_sender_domains', implode("\n", $settings->allowed_sender_domains ?? [])) }}</textarea>
                            @error('allowed_sender_domains') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Allowed Addresses</label>
                            <textarea name="allowed_sender_addresses" rows="6"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 font-mono dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="someone@gmail.com">{{ old('allowed_sender_addresses', implode("\n", $settings->allowed_sender_addresses ?? [])) }}</textarea>
                            @error('allowed_sender_addresses') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- BCC matrix --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Admin BCC per Task Type</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">When enabled, a silent BCC of the requester's confirmation email is sent to the admin notification email above. New task types default to off.</p>
                    </div>

                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($taskTypes as $taskType)
                            <div class="flex items-center justify-between py-3">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', $taskType)) }}</span>
                                <label class="inline-flex items-center cursor-pointer gap-2">
                                    <input type="hidden" name="bcc[{{ $taskType }}]" value="0">
                                    <input type="checkbox" name="bcc[{{ $taskType }}]" value="1" class="sr-only peer"
                                        {{ old('bcc.' . $taskType, $bccEnabled[$taskType] ?? false) ? 'checked' : '' }}>
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 dark:bg-gray-700 dark:peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        Save Settings
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
