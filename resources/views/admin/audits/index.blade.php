<x-app-layout>
    <div class="py-6">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Audit Log</h1>
                    <p class="text-sm text-gray-600 mt-1">Track all changes made to estimates, sales, invoices, and payments.</p>
                </div>
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('admin.audits.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Record Type</label>
                        <select name="model_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Types</option>
                            @foreach ($modelLabels as $class => $label)
                                <option value="{{ $class }}" @selected(request('model_type') === $class)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">User</label>
                        <select name="user_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Users</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event</label>
                        <select name="event" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">All Events</option>
                            <option value="created" @selected(request('event') === 'created')>Created</option>
                            <option value="updated" @selected(request('event') === 'updated')>Updated</option>
                            <option value="deleted" @selected(request('event') === 'deleted')>Deleted</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    <div class="md:col-span-1 flex items-end gap-2">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            Filter
                        </button>
                    </div>

                    <div class="md:col-span-12 flex items-center justify-between pt-1 border-t border-gray-100">
                        <span class="text-sm text-gray-600">
                            Showing <span class="font-semibold">{{ $audits->firstItem() ?? 0 }}</span>
                            to <span class="font-semibold">{{ $audits->lastItem() ?? 0 }}</span>
                            of <span class="font-semibold">{{ $audits->total() }}</span> entries
                        </span>
                        <a href="{{ route('admin.audits.index') }}" class="text-sm text-blue-600 hover:underline">Reset filters</a>
                    </div>

                </div>
            </form>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3">Date / Time</th>
                                <th class="px-6 py-3">User</th>
                                <th class="px-6 py-3">Event</th>
                                <th class="px-6 py-3">Record Type</th>
                                <th class="px-6 py-3">Fields Changed</th>
                                <th class="px-6 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($audits as $audit)
                                @php
                                    $eventColor = match ($audit->event) {
                                        'created' => 'bg-green-100 text-green-800',
                                        'deleted' => 'bg-red-100 text-red-800',
                                        default   => 'bg-blue-100 text-blue-800',
                                    };
                                    $label = $modelLabels[$audit->auditable_type] ?? class_basename($audit->auditable_type);
                                    $changedFields = array_keys($audit->new_values ?? []);
                                @endphp
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 font-medium">
                                        {{ $audit->created_at->format('Y-m-d') }}<br>
                                        <span class="text-xs text-gray-500">{{ $audit->created_at->format('g:i A') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $audit->user?->name ?? 'System' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $eventColor }}">
                                            {{ ucfirst($audit->event) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-gray-900">{{ $label }}</span>
                                        <span class="text-xs text-gray-400 ml-1">#{{ $audit->auditable_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 text-xs">
                                        @if (count($changedFields) > 0)
                                            {{ implode(', ', array_slice($changedFields, 0, 4)) }}
                                            @if (count($changedFields) > 4)
                                                <span class="text-gray-400">+{{ count($changedFields) - 4 }} more</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.audits.show', $audit) }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">No audit records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-white">
                    {{ $audits->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
