<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Incoming Leads</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Web leads from coquitlamflooring.ca and other sources.</p>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{ session('error') }}</div>
            @endif

            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
                    <li class="me-2">
                        <a href="{{ route('pages.leads.index', ['status' => 'pending']) }}"
                           class="inline-flex items-center gap-2 justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'pending' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Pending
                            @if ($pendingCount > 0)
                                <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="me-2">
                        <a href="{{ route('pages.leads.index', ['status' => 'approved']) }}"
                           class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'approved' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Approved
                        </a>
                    </li>
                    <li class="me-2">
                        <a href="{{ route('pages.leads.index', ['status' => 'denied']) }}"
                           class="inline-flex items-center justify-center p-4 border-b-2 rounded-t-lg
                                  {{ $status === 'denied' ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                            Denied
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                @if ($leads->isEmpty())
                    <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No {{ $status }} leads.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Phone</th>
                                    <th class="px-4 py-3">Service Type</th>
                                    <th class="px-4 py-3">Timeline</th>
                                    <th class="px-4 py-3">Source</th>
                                    <th class="px-4 py-3">Received</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($leads as $lead)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                            {{ $lead->name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="tel:{{ $lead->phone }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                                {{ $lead->phone }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">{{ $lead->service_type ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ $lead->timeline ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $lead->source }}</td>
                                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $lead->created_at->diffForHumans() }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($lead->status === 'pending')
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">Pending</span>
                                            @elseif ($lead->status === 'approved')
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Approved</span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">Denied</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('pages.leads.show', $lead) }}"
                                               class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($leads->hasPages())
                        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                            {{ $leads->links() }}
                        </div>
                    @endif
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
