<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h1 class="text-2xl font-semibold text-gray-900">Synced Calendar Events</h1>
            <p class="text-sm text-gray-600 mt-1">
                These events were pulled from Microsoft and stored locally.
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <table class="min-w-full text-sm text-left text-gray-700">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-6 py-3">Title</th>
                        <th class="px-6 py-3">Start</th>
                        <th class="px-6 py-3">End</th>
                        <th class="px-6 py-3">Location</th>
                        <th class="px-6 py-3">Timezone</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $event->title }}
                            </td>
                            <td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($event->starts_at)->format('M d, Y g:i A') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ \Carbon\Carbon::parse($event->ends_at)->format('M d, Y g:i A') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $event->location ?? '—' }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $event->timezone }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                                No events found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>
