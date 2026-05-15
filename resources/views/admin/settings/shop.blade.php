<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Shop Settings</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Configure how quote requests from shop.rmflooring.ca are handled.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.settings.email-templates.index') }}?tab=shop_quote_confirmation"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Edit Confirmation Template
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

            {{-- Settings form --}}
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6">
                <form method="POST" action="{{ route('admin.settings.shop.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">

                        <div>
                            <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-4">Quote Request Notifications</h2>

                            <div class="space-y-4">
                                <div>
                                    <label for="shop_quote_notify_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Notification Email
                                    </label>
                                    <input type="email"
                                           id="shop_quote_notify_email"
                                           name="shop_quote_notify_email"
                                           value="{{ old('shop_quote_notify_email', $notifyEmail) }}"
                                           placeholder="reception@rmflooring.ca"
                                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('shop_quote_notify_email') border-red-500 @enderror">
                                    @error('shop_quote_notify_email')
                                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        Internal email address that receives a copy of every new quote request.
                                        Leave blank to use the default mail from address.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                            <div class="rounded-lg bg-blue-50 border border-blue-200 dark:bg-blue-900/20 dark:border-blue-700 p-4 text-sm text-blue-800 dark:text-blue-300 space-y-1">
                                <p class="font-medium">What happens when a quote is submitted:</p>
                                <ul class="list-disc list-inside space-y-1 text-blue-700 dark:text-blue-400">
                                    <li>A new Customer is created in Floor Manager (or matched by email if they already exist)</li>
                                    <li>A new Opportunity is opened for that customer with status <strong>New</strong> and Measure required</li>
                                    <li>An internal notification is sent to the address above</li>
                                    <li>A confirmation email is sent to the customer (editable via <a href="{{ route('admin.settings.email-templates.index') }}?tab=shop_quote_confirmation" class="underline">System Email Templates</a>)</li>
                                </ul>
                            </div>
                        </div>

                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
