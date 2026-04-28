<x-admin-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">QuickBooks Online</h1>
                    <p class="text-sm text-gray-600 mt-1">Connect Floor Manager to your QuickBooks Online company to sync bills, invoices, vendors, and customers.</p>
                </div>
                <a href="{{ route('admin.settings') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Settings</a>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Connection Status --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Connection Status</h2>

                @if ($connection && $connection->realm_id && $connection->refresh_token)
                    {{-- Connected --}}
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Connected
                                </span>
                                <span class="text-xs text-gray-500 uppercase tracking-wide font-medium">
                                    {{ strtoupper($connection->environment) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-700">
                                Company ID (Realm): <span class="font-mono font-medium">{{ $connection->realm_id }}</span>
                            </p>
                            @if ($connection->connectedBy)
                                <p class="text-sm text-gray-500">
                                    Connected by {{ $connection->connectedBy->name }}
                                    on {{ $connection->connected_at?->format('M j, Y \a\t g:i a') }}
                                </p>
                            @endif
                            @if ($connection->token_expires_at)
                                <p class="text-xs text-gray-400">
                                    Token expires: {{ $connection->token_expires_at->format('M j, Y g:i a') }}
                                    @if ($connection->isExpired())
                                        <span class="text-red-600 font-medium">(expired — will auto-refresh on next sync)</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.settings.quickbooks.disconnect') }}"
                              onsubmit="return confirm('Disconnect QuickBooks Online? Existing synced records will not be affected.')">
                            @csrf
                            <button type="submit"
                                    class="text-sm text-red-600 hover:text-red-800 border border-red-200 hover:border-red-400 rounded px-3 py-1.5 transition">
                                Disconnect
                            </button>
                        </form>
                    </div>
                @else
                    {{-- Not connected --}}
                    <div class="flex items-center justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    Not Connected
                                </span>
                                <span class="text-xs text-gray-400 uppercase tracking-wide font-medium">
                                    {{ strtoupper(config('services.quickbooks.environment', 'sandbox')) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500">No QuickBooks company is currently linked.</p>
                        </div>
                        <a href="{{ route('admin.settings.quickbooks.connect') }}"
                           class="inline-flex items-center gap-2 bg-[#2CA01C] hover:bg-[#238c15] text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Connect to QuickBooks
                        </a>
                    </div>
                @endif
            </div>

            {{-- Account Mapping --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-1">Account & Item Mapping</h2>
                <p class="text-sm text-gray-500 mb-4">Configure the QBO accounts and items used when syncing bills and invoices. Find IDs in QuickBooks → Chart of Accounts or Products &amp; Services → hover the row → note the ID in the URL.</p>

                <form method="POST" action="{{ route('admin.settings.quickbooks.save-settings') }}" class="space-y-6">
                    @csrf

                    {{-- Expense Accounts --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-100">Expense Accounts <span class="text-xs font-normal text-gray-400">(used for bill / AP line items)</span></h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Product <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="qbo_ap_product_account_id"
                                       value="{{ $settings['qbo_ap_product_account_id'] }}"
                                       placeholder="e.g. 63"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Freight <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="qbo_ap_freight_account_id"
                                       value="{{ $settings['qbo_ap_freight_account_id'] }}"
                                       placeholder="e.g. 64"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Labour <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="qbo_ap_labour_account_id"
                                       value="{{ $settings['qbo_ap_labour_account_id'] }}"
                                       placeholder="e.g. 65"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- Income Items --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-1 border-b border-gray-100">Income Items <span class="text-xs font-normal text-gray-400">(QBO product/service items used for invoice lines)</span></h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Material <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="qbo_income_material_item_id"
                                       value="{{ $settings['qbo_income_material_item_id'] }}"
                                       placeholder="e.g. 1"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Freight <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="qbo_income_freight_item_id"
                                       value="{{ $settings['qbo_income_freight_item_id'] }}"
                                       placeholder="e.g. 2"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Labour <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="qbo_income_labour_item_id"
                                       value="{{ $settings['qbo_income_labour_item_id'] }}"
                                       placeholder="e.g. 3"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

            {{-- What will sync --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-3">What syncs to QuickBooks</h2>
                <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <span><strong>Vendors</strong> — pushed when a bill is synced for the first time</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <span><strong>Customers</strong> — pushed when an invoice is synced for the first time</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <span><strong>Bills (AP)</strong> — manually pushed from the bill detail page</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-indigo-400 shrink-0"></span>
                        <span><strong>Invoices (AR)</strong> — manually pushed from the invoice detail page</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="w-2 h-2 rounded-full bg-blue-300 shrink-0"></span>
                        <span><strong>Payments</strong> — pulled from QuickBooks back into Floor Manager (coming soon)</span>
                    </div>
                </div>
            </div>

            {{-- Recent Sync Log --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Recent Sync Activity</h2>

                @if ($recentLogs->isEmpty())
                    <p class="text-sm text-gray-400 italic">No sync activity yet.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-100">
                            <thead>
                                <tr class="text-xs text-gray-500 uppercase tracking-wide">
                                    <th class="pb-2 text-left font-medium">When</th>
                                    <th class="pb-2 text-left font-medium">Entity</th>
                                    <th class="pb-2 text-left font-medium">Direction</th>
                                    <th class="pb-2 text-left font-medium">QBO ID</th>
                                    <th class="pb-2 text-left font-medium">Status</th>
                                    <th class="pb-2 text-left font-medium">Message</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach ($recentLogs as $log)
                                    <tr>
                                        <td class="py-2 text-gray-500 whitespace-nowrap">
                                            {{ $log->created_at->format('M j, g:i a') }}
                                        </td>
                                        <td class="py-2 font-medium text-gray-700 whitespace-nowrap">
                                            {{ ucfirst($log->entity_type) }}
                                            @if ($log->entity_id)
                                                <span class="text-gray-400">#{{ $log->entity_id }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 whitespace-nowrap">
                                            @if ($log->direction === 'push')
                                                <span class="text-indigo-600">↑ Push</span>
                                            @else
                                                <span class="text-blue-600">↓ Pull</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-gray-500 font-mono text-xs">
                                            {{ $log->qbo_id ?? '—' }}
                                        </td>
                                        <td class="py-2 whitespace-nowrap">
                                            @if ($log->status === 'success')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">success</span>
                                            @elseif ($log->status === 'error')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">error</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ $log->status }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-gray-500 text-xs max-w-xs truncate">
                                            {{ $log->message ?? '—' }}
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
</x-admin-layout>
