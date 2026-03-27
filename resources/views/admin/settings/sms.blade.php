<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">SMS Notifications</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure Twilio SMS settings and per-notification toggles.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.settings.sms-templates.index') }}"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        SMS Templates
                    </a>
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

            {{-- Tabs --}}
            <div x-data="{ tab: 'config' }">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                        <li class="me-2">
                            <button @click="tab = 'config'"
                                :class="tab === 'config' ? 'text-blue-600 border-blue-600 border-b-2' : 'text-gray-500 hover:text-gray-700 border-transparent border-b-2'"
                                class="inline-block p-4 rounded-t-lg">
                                Configuration
                            </button>
                        </li>
                        <li class="me-2">
                            <button @click="tab = 'log'"
                                :class="tab === 'log' ? 'text-blue-600 border-blue-600 border-b-2' : 'text-gray-500 hover:text-gray-700 border-transparent border-b-2'"
                                class="inline-block p-4 rounded-t-lg">
                                Send Log
                                <span class="ms-1 inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-blue-800 bg-blue-100 rounded-full">
                                    {{ $smsLogs->count() }}
                                </span>
                            </button>
                        </li>
                    </ul>
                </div>

                {{-- Configuration Tab --}}
                <div x-show="tab === 'config'" x-cloak>
                    <form method="POST" action="{{ route('admin.settings.sms.update') }}">
                        @csrf

                        {{-- Twilio Credentials --}}
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-5">
                            <div class="flex items-center justify-between">
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Twilio Credentials</h2>
                                <label class="inline-flex items-center cursor-pointer gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">SMS Enabled</span>
                                    <input type="hidden" name="sms_enabled" value="0">
                                    <input type="checkbox" name="sms_enabled" value="1" class="sr-only peer"
                                        {{ $smsEnabled ? 'checked' : '' }}>
                                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 dark:bg-gray-700 dark:peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Account SID</label>
                                    <input type="text" name="sms_account_sid" value="{{ $smsAccountSid }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                    @error('sms_account_sid') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Auth Token</label>
                                    <input type="password" name="sms_auth_token" value="{{ $smsAuthToken }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Your Twilio Auth Token"
                                        autocomplete="new-password">
                                    @error('sms_auth_token') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">From Number</label>
                                    <input type="text" name="sms_from_number" value="{{ $smsFromNumber }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="+18335551234">
                                    <p class="mt-1 text-xs text-gray-500">Your Twilio toll-free or long-code number in E.164 format.</p>
                                    @error('sms_from_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Daily Reminder Send Time</label>
                                    <input type="time" name="sms_reminder_time" value="{{ $smsReminderTime }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <p class="mt-1 text-xs text-gray-500">Time of day for reminders (WO + RFM).</p>
                                </div>
                            </div>
                        </div>

                        {{-- Notification Toggles --}}
                        <div class="mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 space-y-6">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notification Settings</h2>

                            @php
                                $notifications = [
                                    [
                                        'key'       => 'wo_scheduled',
                                        'label'     => 'Work Order Scheduled',
                                        'desc'      => 'Sent when a Work Order status is set to Scheduled.',
                                        'toggle'    => $notifyWoScheduled,
                                        'to_key'    => 'sms_wo_scheduled_to',
                                        'to_val'    => $woScheduledTo,
                                        'recipients'=> ['pm' => 'Project Manager', 'installer' => 'Installer', 'homeowner' => 'Homeowner'],
                                    ],
                                    [
                                        'key'       => 'wo_reminder',
                                        'label'     => 'Work Order Day-Before Reminder',
                                        'desc'      => 'Sent automatically the day before a scheduled installation.',
                                        'toggle'    => $notifyWoReminder,
                                        'to_key'    => 'sms_wo_reminder_to',
                                        'to_val'    => $woReminderTo,
                                        'recipients'=> ['pm' => 'Project Manager', 'installer' => 'Installer', 'homeowner' => 'Homeowner'],
                                    ],
                                    [
                                        'key'       => 'rfm_booked',
                                        'label'     => 'RFM Booked',
                                        'desc'      => 'Sent when a new Request for Measure is created.',
                                        'toggle'    => $notifyRfmBooked,
                                        'to_key'    => 'sms_rfm_booked_to',
                                        'to_val'    => $rfmBookedTo,
                                        'recipients'=> ['estimator' => 'Estimator', 'pm' => 'Project Manager', 'customer' => 'Customer'],
                                    ],
                                    [
                                        'key'       => 'rfm_updated',
                                        'label'     => 'RFM Updated',
                                        'desc'      => 'Sent when an RFM is edited and saved.',
                                        'toggle'    => $notifyRfmUpdated,
                                        'to_key'    => 'sms_rfm_updated_to',
                                        'to_val'    => $rfmUpdatedTo,
                                        'recipients'=> ['estimator' => 'Estimator', 'pm' => 'Project Manager', 'customer' => 'Customer'],
                                    ],
                                    [
                                        'key'       => 'rfm_reminder',
                                        'label'     => 'RFM Day-Before Reminder',
                                        'desc'      => 'Sent automatically the day before a scheduled RFM appointment.',
                                        'toggle'    => $notifyRfmReminder,
                                        'to_key'    => 'sms_rfm_reminder_to',
                                        'to_val'    => $rfmReminderTo,
                                        'recipients'=> ['estimator' => 'Estimator', 'pm' => 'Project Manager', 'customer' => 'Customer'],
                                    ],
                                ];
                            @endphp

                            @foreach($notifications as $notif)
                                @php $currentRecipients = array_filter(explode(',', $notif['to_val'])); @endphp
                                <div class="border border-gray-100 dark:border-gray-700 rounded-lg p-4" x-data="{ open: {{ $notif['toggle'] ? 'true' : 'false' }} }">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $notif['label'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $notif['desc'] }}</p>
                                        </div>
                                        <label class="inline-flex items-center cursor-pointer shrink-0">
                                            <input type="hidden" name="sms_notify_{{ $notif['key'] }}" value="0">
                                            <input type="checkbox" name="sms_notify_{{ $notif['key'] }}" value="1"
                                                class="sr-only peer"
                                                x-model="open"
                                                {{ $notif['toggle'] ? 'checked' : '' }}>
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 dark:bg-gray-700 dark:peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div x-show="open" x-cloak class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Send to:</p>
                                        <div class="flex flex-wrap gap-4">
                                            @foreach($notif['recipients'] as $rkey => $rlabel)
                                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                                    <input type="checkbox" name="{{ $notif['to_key'] }}[]" value="{{ $rkey }}"
                                                        {{ in_array($rkey, $currentRecipients) ? 'checked' : '' }}
                                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    {{ $rlabel }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 rounded-lg dark:bg-blue-600 dark:hover:bg-blue-700">
                                Save Settings
                            </button>
                        </div>
                    </form>

                    {{-- Test Send --}}
                    <div class="mt-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Send Test SMS</h2>
                        <form method="POST" action="{{ route('admin.settings.sms.test') }}" class="flex items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Mobile Number</label>
                                <input type="text" name="test_number"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="+16135551234">
                                @error('test_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:ring-gray-300 rounded-lg dark:bg-gray-600 dark:hover:bg-gray-700">
                                Send Test
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Send Log Tab --}}
                <div x-show="tab === 'log'" x-cloak>
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                        @if($smsLogs->isEmpty())
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                                No SMS messages sent yet.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3">To</th>
                                            <th class="px-4 py-3">Type</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3">Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($smsLogs as $log)
                                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                                    {{ $log->created_at->format('M j, Y g:ia') }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap font-mono text-gray-700 dark:text-gray-300">
                                                    {{ $log->to }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ $log->type ?? '—' }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($log->status === 'sent')
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Sent</span>
                                                    @else
                                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Failed</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                                    @if($log->status === 'failed' && $log->error)
                                                        <span class="text-red-600 dark:text-red-400 text-xs">{{ $log->error }}</span>
                                                    @else
                                                        {{ $log->body }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
