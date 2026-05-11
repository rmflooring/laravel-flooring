<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center gap-4">
                <a href="{{ route('admin.audits.index') }}"
                   class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Audit Log
                </a>
            </div>

            {{-- Header card --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Date / Time</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $audit->created_at->format('Y-m-d g:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">User</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">{{ $audit->user?->name ?? 'System' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Record</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900">
                            {{ $modelLabel }}
                            @if ($recordLabel)
                                — {{ $recordLabel }}
                            @else
                                <span class="text-gray-400">#{{ $audit->auditable_id }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-medium">Event</p>
                        @php
                            $eventColor = match ($audit->event) {
                                'created' => 'bg-green-100 text-green-800',
                                'deleted' => 'bg-red-100 text-red-800',
                                default   => 'bg-blue-100 text-blue-800',
                            };
                        @endphp
                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $eventColor }}">
                            {{ ucfirst($audit->event) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Changes table --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-sm font-semibold text-gray-700">Changes</h2>
                </div>

                @php
                    $old = $audit->old_values ?? [];
                    $new = $audit->new_values ?? [];
                    $fields = array_unique(array_merge(array_keys($old), array_keys($new)));
                @endphp

                @if (count($fields) > 0)
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-600 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3 w-1/4">Field</th>
                                <th class="px-6 py-3 w-5/12">Before</th>
                                <th class="px-6 py-3 w-5/12">After</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fields as $field)
                                @php
                                    $before = $old[$field] ?? null;
                                    $after  = $new[$field] ?? null;
                                    $changed = $before !== $after;
                                @endphp
                                <tr class="border-b {{ $changed ? 'bg-amber-50' : 'bg-white' }}">
                                    <td class="px-6 py-3 font-medium text-gray-700">
                                        {{ $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field)) }}
                                    </td>
                                    <td class="px-6 py-3 text-gray-500">
                                        @if ($before === null)
                                            <span class="italic text-gray-400">—</span>
                                        @else
                                            {{ $before }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 {{ $changed ? 'text-gray-900 font-medium' : 'text-gray-500' }}">
                                        @if ($after === null)
                                            <span class="italic text-gray-400">—</span>
                                        @else
                                            {{ $after }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="px-6 py-8 text-center text-gray-500 text-sm">No field changes recorded for this event.</p>
                @endif
            </div>

            {{-- Meta --}}
            <div class="text-xs text-gray-400 space-y-1">
                <p>IP Address: {{ $audit->ip_address ?? '—' }}</p>
                <p>URL: {{ $audit->url ?? '—' }}</p>
            </div>

        </div>
    </div>
</x-app-layout>
