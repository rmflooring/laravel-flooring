<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Management</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure and monitor the Floor Manager email system.</p>
                </div>
                <a href="{{ route('admin.settings') }}"
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

            {{-- ================================================================ --}}
            {{-- Section 1: Track 1 — Shared Mailbox Settings                    --}}
            {{-- ================================================================ --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Track 1</span>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Shared Mailbox — System Notifications</h2>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Sends internal alerts (e.g. RFM scheduled) from a shared mailbox via Microsoft Graph using the RM Flooring Azure app registration.
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="p-6 space-y-4">
                    @csrf

                    {{-- Enabled toggle --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg dark:bg-gray-700/50">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Email Notifications</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">When disabled, no notification emails will be sent regardless of other settings.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="mail_notifications_enabled" value="1" class="sr-only peer"
                                   {{ $mailNotificationsEnabled ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- From Address --}}
                        <div>
                            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                From Address
                            </label>
                            <input type="email"
                                   id="mail_from_address"
                                   name="mail_from_address"
                                   value="{{ old('mail_from_address', $mailFromAddress) }}"
                                   placeholder="reception@rmflooring.ca"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('mail_from_address') border-red-500 @enderror">
                            @error('mail_from_address')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Must be a valid Exchange Online mailbox with <code class="font-mono">Mail.Send</code> app permission.</p>
                        </div>

                        {{-- From Name --}}
                        <div>
                            <label for="mail_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                From Display Name
                            </label>
                            <input type="text"
                                   id="mail_from_name"
                                   name="mail_from_name"
                                   value="{{ old('mail_from_name', $mailFromName) }}"
                                   placeholder="RM Flooring Notifications"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('mail_from_name') border-red-500 @enderror">
                            @error('mail_from_name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Reply-To --}}
                        <div>
                            <label for="mail_reply_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Reply-To Address
                            </label>
                            <input type="email"
                                   id="mail_reply_to"
                                   name="mail_reply_to"
                                   value="{{ old('mail_reply_to', $mailReplyTo) }}"
                                   placeholder="noreply@rmflooring.ca"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('mail_reply_to') border-red-500 @enderror">
                            @error('mail_reply_to')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Does not need to be a real inbox — replies will bounce silently.</p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

            {{-- ================================================================ --}}
            {{-- Section 2: Send Test Email                                       --}}
            {{-- ================================================================ --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Send Test Email</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Verify your Track 1 configuration is working without creating an RFM.</p>
                </div>
                <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="p-6">
                    @csrf
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label for="test_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Send test to</label>
                            <input type="email"
                                   id="test_to"
                                   name="test_to"
                                   value="{{ old('test_to', auth()->user()->email) }}"
                                   placeholder="you@example.com"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('test_to') border-red-500 @enderror">
                            @error('test_to')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 whitespace-nowrap">
                            Send Test
                        </button>
                    </div>
                </form>
            </div>

            {{-- ================================================================ --}}
            {{-- Section 3: Email Log                                             --}}
            {{-- ================================================================ --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Email Log</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Last 50 system emails sent or attempted.</p>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ $mailLogs->count() }} entries</span>
                </div>

                @if ($mailLogs->isEmpty())
                    <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        No emails logged yet.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">To</th>
                                    <th class="px-4 py-3">Subject</th>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($mailLogs as $log)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                            {{ $log->created_at->format('M j, Y g:i A') }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->to }}</td>
                                        <td class="px-4 py-3">
                                            <span class="block truncate max-w-xs" title="{{ $log->subject }}">{{ $log->subject }}</span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $log->type }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            @if ($log->status === 'sent')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Sent</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300"
                                                      title="{{ $log->error }}">Failed</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- ================================================================ --}}
            {{-- Section 4: Track 2 — Per-User MS365 Accounts                    --}}
            {{-- ================================================================ --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300">Track 2</span>
                        <h2 class="text-sm font-semibold text-gray-800 dark:text-white">Per-User MS365 Accounts — Customer-Facing Email</h2>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">Coming Soon</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Each staff member can connect their personal MS365 account to send customer-facing emails (estimates, invoices) from their own address. OAuth delegated flow — coming in a future update.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">MS365 Account</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Connected</th>
                                <th class="px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($users as $user)
                                @php $account = $user->microsoftAccount; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-800 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                                        {{ $account?->email ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($account && $account->is_connected)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Connected</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Not Connected</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $account?->connected_at?->format('M j, Y') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button"
                                                disabled
                                                title="Track 2 email — coming soon"
                                                class="px-3 py-1.5 text-xs font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed opacity-60 dark:bg-gray-700 dark:text-gray-500 dark:border-gray-600">
                                            Disconnect
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
