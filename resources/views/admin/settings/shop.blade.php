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

                        <div class="pt-6 border-t border-gray-100 dark:border-gray-700">
                            <h2 class="text-base font-semibold text-gray-800 dark:text-white mb-1">Web Orders (rmflooring.ca online shop)</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Paid online orders appear under Sales → Web Orders. Customers are notified by email and text automatically.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="web_order_alert_emails" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New-order alert emails</label>
                                    <textarea id="web_order_alert_emails" name="web_order_alert_emails" rows="3" placeholder="one email per line" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('web_order_alert_emails', $webOrder['web_order_alert_emails']) }}</textarea>
                                    @error('web_order_alert_emails')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="web_order_alert_sms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New-order alert texts (SMS)</label>
                                    <textarea id="web_order_alert_sms" name="web_order_alert_sms" rows="3" placeholder="one phone number per line" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('web_order_alert_sms', $webOrder['web_order_alert_sms']) }}</textarea>
                                    @error('web_order_alert_sms')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="web_order_hold_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pickup hold (days)</label>
                                    <input type="number" min="1" id="web_order_hold_days" name="web_order_hold_days" value="{{ old('web_order_hold_days', $webOrder['web_order_hold_days']) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-1 text-xs text-gray-500">A reminder is sent if an order hasn’t been picked up this many days after it’s ready.</p>
                                </div>
                                <div>
                                    <label for="web_order_storage_fee_after_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Storage fees start after (days)</label>
                                    <input type="number" min="1" id="web_order_storage_fee_after_days" name="web_order_storage_fee_after_days" value="{{ old('web_order_storage_fee_after_days', $webOrder['web_order_storage_fee_after_days']) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="web_order_storage_fee_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Storage fee wording</label>
                                    <input type="text" id="web_order_storage_fee_text" name="web_order_storage_fee_text" value="{{ old('web_order_storage_fee_text', $webOrder['web_order_storage_fee_text']) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-1 text-xs text-gray-500">e.g. “a storage fee of $5 per day” — shown in the policy.</p>
                                </div>
                                <div>
                                    <label for="web_order_forfeit_after_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Order forfeited after (days)</label>
                                    <input type="number" min="1" id="web_order_forfeit_after_days" name="web_order_forfeit_after_days" value="{{ old('web_order_forfeit_after_days', $webOrder['web_order_forfeit_after_days']) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    @error('web_order_forfeit_after_days')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <p class="mt-4 text-sm text-gray-700 dark:text-gray-300"><span class="font-medium">Customers see:</span>
                                Orders are held for pickup for {{ $webOrder['web_order_hold_days'] }} days. Orders not picked up within {{ $webOrder['web_order_storage_fee_after_days'] }} days of being ready may be charged {{ $webOrder['web_order_storage_fee_text'] }}, and orders not picked up within {{ $webOrder['web_order_forfeit_after_days'] }} days are forfeited without refund.</p>
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
