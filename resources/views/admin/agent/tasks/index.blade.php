<x-app-layout>
<div class="py-8">
<div class="max-w-screen-xl mx-auto sm:px-6 lg:px-8 space-y-6">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Tasks</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Queue of tasks triggered via email or chat for the Floor Manager AI Agent</p>
        </div>
    </div>

    @if (session('status'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.agent.tasks.index') }}" class="flex flex-wrap items-center gap-3">
        <select name="status"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="">All Statuses</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <select name="task_type"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="">All Types</option>
            @foreach ($taskTypes as $type)
                <option value="{{ $type }}" @selected(request('task_type') === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
        <select name="source"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="">All Sources</option>
            @foreach ($sources as $source)
                <option value="{{ $source }}" @selected(request('source') === $source)>{{ ucfirst($source) }}</option>
            @endforeach
        </select>
        <button type="submit"
                class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            Filter
        </button>
        @if (array_filter(request()->only(['status', 'task_type', 'source'])))
            <a href="{{ route('admin.agent.tasks.index') }}"
               class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Requester</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Opportunity</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Received</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($tasks as $task)
                    @php
                        $statusBadge = match($task->status) {
                            'queued'                 => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                            'pending_clarification'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'pending_confirmation'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                            'completed'              => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'failed'                 => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                            'ignored'                => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                            default                  => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer"
                        onclick="window.location='{{ route('admin.agent.tasks.show', $task) }}'">
                        <td class="px-4 py-3 font-medium">
                            <a href="{{ route('admin.agent.tasks.show', $task) }}" class="text-blue-600 hover:underline dark:text-blue-400">#{{ $task->id }}</a>
                        </td>
                        <td class="px-4 py-3">{{ ucfirst($task->source) }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $task->requester?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-400">{{ $task->requester_email }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $task->task_type ? ucwords(str_replace('_', ' ', $task->task_type)) : '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($task->opportunity)
                                <a href="{{ route('pages.opportunities.show', $task->opportunity) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $task->opportunity->job_no }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                {{ ucwords(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                            {{ $task->created_at->timezone('America/Vancouver')->format('M j, Y g:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">No agent tasks found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($tasks->hasPages())
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                {{ $tasks->links() }}
            </div>
        @endif
    </div>

</div>
</div>
</x-app-layout>
