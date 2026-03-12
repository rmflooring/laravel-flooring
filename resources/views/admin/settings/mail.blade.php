<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mail Settings</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure the shared mailbox used for system email notifications.</p>
                </div>
                <a href="{{ route('admin.settings') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    Back
                </a>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between dark:bg-green-900/30 dark:text-green-200 dark:border-green-700">
                    <span>{{ session('success') }}</span>
                    <button type="button" onclick="this.closest('div').remove()" class="text-green-900 dark:text-green-200 text-sm font-medium">✕</button>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">

                {{-- Track 1: Shared Mailbox --}}
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 dark:text-gray-400">Track 1 — Shared Mailbox</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Used for internal system notifications (e.g. RFM alerts). Emails are sent from this address via Microsoft Graph using the RM Flooring Azure app.
                    </p>

                    <form method="POST" action="{{ route('admin.settings.mail.update') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="mail_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Shared Mailbox From Address
                            </label>
                            <input type="email"
                                   id="mail_from_address"
                                   name="mail_from_address"
                                   value="{{ old('mail_from_address', $mailFromAddress) }}"
                                   placeholder="team@rmflooring.ca"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('mail_from_address') border-red-500 @enderror">
                            @error('mail_from_address')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                Must be a shared mailbox address the Azure app has <code class="font-mono">Mail.Send</code> permission for.
                            </p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Track 2: placeholder --}}
                <div class="p-6">
                    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 dark:text-gray-400">Track 2 — Per-User MS365 Accounts</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Per-user email (for customer-facing estimates and invoices) — coming soon.
                    </p>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
