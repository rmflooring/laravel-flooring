<x-app-layout>
<div class="py-8">
<div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Signing Requests</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">E-signature requests sent to clients</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
    @endif

    {{-- Stats Strip --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-800 rounded-lg p-5">
            <p class="text-sm text-amber-600 dark:text-amber-400">Pending</p>
            <p class="mt-1 text-3xl font-bold text-amber-700 dark:text-amber-400">{{ $counts['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-green-200 dark:border-green-800 rounded-lg p-5">
            <p class="text-sm text-green-600 dark:text-green-400">Signed</p>
            <p class="mt-1 text-3xl font-bold text-green-700 dark:text-green-400">{{ $counts['signed'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 rounded-lg p-5">
            <p class="text-sm text-red-600 dark:text-red-400">Expired</p>
            <p class="mt-1 text-3xl font-bold text-red-700 dark:text-red-400">{{ $counts['expired'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Cancelled</p>
            <p class="mt-1 text-3xl font-bold text-gray-700 dark:text-gray-300">{{ $counts['cancelled'] }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.signing-requests.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
               placeholder="Search client name or email…"
               class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-64">
        <select name="status"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="">All Statuses</option>
            <option value="pending"   @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
            <option value="signed"    @selected(($filters['status'] ?? '') === 'signed')>Signed</option>
            <option value="expired"   @selected(($filters['status'] ?? '') === 'expired')>Expired</option>
            <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
        </select>
        <button type="submit"
                class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            Filter
        </button>
        @if (array_filter($filters))
            <a href="{{ route('admin.signing-requests.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Document Type</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Sent</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3 text-center">Reminders</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($signingRequests as $req)
                    @php
                        $statusBadge = match($req->status) {
                            'pending'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'signed'    => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'expired'   => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            default     => 'bg-gray-100 text-gray-600',
                        };
                        $typeLabel = $req->document_type === 'flooring_selection'
                            ? 'Flooring Selection'
                            : 'Work Auth';
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <td class="px-4 py-3 font-medium">{{ $typeLabel }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $req->client_name }}</div>
                            <div class="text-xs text-gray-400">{{ $req->client_email }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                            {{ $req->sent_at->timezone('America/Vancouver')->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs {{ $req->isPending() && $req->expires_at->isPast() ? 'text-red-500 font-medium' : 'text-gray-500' }}">
                            {{ $req->expires_at->timezone('America/Vancouver')->format('M j, Y') }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $req->reminder_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 flex-wrap">

                                @if ($req->isSigned())
                                    <a href="{{ route('admin.signing-requests.download', $req) }}"
                                       class="inline-flex items-center rounded-lg border border-green-300 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                                        Download Signed
                                    </a>
                                @endif

                                @if ($req->isPending())
                                    <form method="POST" action="{{ route('admin.signing-requests.resend', $req) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-blue-300 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                                            Resend
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.signing-requests.cancel', $req) }}"
                                          onsubmit="return confirm('Cancel this signing request?')">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
                                            Cancel
                                        </button>
                                    </form>
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">No signing requests found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($signingRequests->hasPages())
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                {{ $signingRequests->links() }}
            </div>
        @endif
    </div>

</div>
</div>
</x-app-layout>
