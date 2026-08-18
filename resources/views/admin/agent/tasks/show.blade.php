<x-app-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <a href="{{ route('admin.agent.tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">&larr; Back to Agent Tasks</a>
                    <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Agent Task #{{ $task->id }}</h1>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full {{ $statusBadge }}">
                    {{ ucwords(str_replace('_', ' ', $task->status)) }}
                </span>
            </div>

            {{-- Flash --}}
            @if (session('status'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{ session('error') }}</div>
            @endif

            {{-- Task details card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Task Details</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Received {{ $task->created_at->timezone('America/Vancouver')->format('M j, Y g:i A') }} &middot; Source: {{ ucfirst($task->source) }}</p>
                    </div>

                    @if ($task->status === 'completed')
                        @if ($task->undone_at)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Undone {{ $task->undone_at->timezone('America/Vancouver')->format('M j, Y g:i A') }}
                            </span>
                        @elseif ($canUndo)
                            <form method="POST" action="{{ route('admin.agent.tasks.undo', $task) }}"
                                  onsubmit="return confirm('Undo this task\'s action?')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center rounded-lg border border-red-300 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
                                    Undo
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400" title="Undo isn't supported for this action type">
                                Undo not available for this action type
                            </span>
                        @endif
                    @endif
                </div>
                <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Requester</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $task->requester?->name ?? '—' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $task->requester_email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Task Type</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $task->task_type ? ucwords(str_replace('_', ' ', $task->task_type)) : '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Opportunity</p>
                        <p class="mt-0.5 text-sm">
                            @if ($task->opportunity)
                                <a href="{{ route('pages.opportunities.show', $task->opportunity) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $task->opportunity->job_no }}</a>
                            @else
                                <span class="text-gray-400">Not resolved</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Confidence Score</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ $task->confidence_score !== null ? number_format($task->confidence_score, 2) : '—' }}</p>
                    </div>

                    @if ($task->extracted_intent)
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Extracted Intent / Summary</p>
                            <p class="mt-0.5 text-sm text-gray-900 dark:text-white whitespace-pre-line">{{ $task->extracted_intent }}</p>
                        </div>
                    @endif

                    <div class="sm:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Raw Content</p>
                        <p class="mt-0.5 text-sm text-gray-900 dark:text-white whitespace-pre-line bg-gray-50 dark:bg-gray-900/40 rounded-lg p-3 border border-gray-200 dark:border-gray-700">{{ $task->raw_content ?: '—' }}</p>
                    </div>

                    @if (!empty($task->attachments))
                        <div class="sm:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Attachments</p>
                            <ul class="mt-0.5 text-sm text-gray-900 dark:text-white list-disc list-inside">
                                @foreach ($task->attachments as $attachment)
                                    <li>{{ $attachment['original_name'] ?? 'file' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Clarification thread --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Thread</h2>
                </div>
                <div class="px-6 py-4 space-y-4">
                    @forelse ($task->messages as $message)
                        <div class="flex {{ $message->sender === 'user' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%] rounded-lg px-4 py-2 text-sm
                                {{ $message->sender === 'user'
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-900 dark:bg-gray-700 dark:text-gray-100' }}">
                                <p class="whitespace-pre-line">{{ $message->body }}</p>
                                <p class="mt-1 text-[11px] {{ $message->sender === 'user' ? 'text-blue-100' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $message->sender === 'user' ? 'User' : 'Agent' }} &middot; {{ $message->created_at->timezone('America/Vancouver')->format('M j, g:i A') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No messages logged for this task.</p>
                    @endforelse
                </div>

                @if ($task->status === 'pending_clarification')
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <form method="POST" action="{{ route('admin.agent.tasks.reply', $task) }}" class="space-y-3">
                            @csrf
                            <label for="body" class="block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reply</label>
                            <textarea id="body" name="body" rows="3" required
                                      class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                      placeholder="Answer the agent's question…">{{ old('body') }}</textarea>
                            @error('body')
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Send Reply
                            </button>
                        </form>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
