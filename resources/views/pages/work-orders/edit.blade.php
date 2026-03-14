{{-- resources/views/pages/work-orders/edit.blade.php --}}
<x-admin-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div>
                <nav class="flex items-center gap-1.5 text-sm text-gray-500 mb-2">
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="inline-flex items-center gap-1 hover:text-gray-700 font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Sale {{ $sale->sale_number ?? ('#' . $sale->id) }}
                    </a>
                    @if ($sale->customer_name)
                        <span class="text-gray-300">·</span>
                        <span>{{ $sale->customer_name }}</span>
                    @endif
                </nav>
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $workOrder->wo_number }}</h1>

                    @php
                        $statusColors = [
                            'created'     => 'bg-gray-100 text-gray-700',
                            'scheduled'   => 'bg-blue-100 text-blue-800',
                            'in_progress' => 'bg-amber-100 text-amber-800',
                            'completed'   => 'bg-green-100 text-green-800',
                            'cancelled'   => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColors[$workOrder->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $workOrder->status_label }}
                    </span>

                    @if ($workOrder->calendar_synced)
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                            On calendar
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-50 text-gray-500 border border-gray-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                            Not synced
                        </span>
                    @endif
                </div>
            </div>

            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Status transitions --}}
            @if ($workOrder->status !== 'cancelled')
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Update status</p>
                    <div class="flex flex-wrap gap-2" x-data="woStatus('{{ $workOrder->status }}')">

                        @php
                            $transitions = [
                                'created'     => ['scheduled'],
                                'scheduled'   => ['in_progress', 'created'],
                                'in_progress' => ['completed', 'scheduled'],
                                'completed'   => [],
                            ];
                            $available = $transitions[$workOrder->status] ?? [];
                        @endphp

                        @foreach (WorkOrder::STATUSES as $s)
                            @if ($s === $workOrder->status)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border-2 cursor-default
                                    {{ $statusColors[$s] ?? 'bg-gray-100 text-gray-700' }}"
                                    style="border-color: currentColor; opacity:0.8">
                                    {{ WorkOrder::STATUS_LABELS[$s] }} ✓
                                </span>
                            @elseif ($s === 'cancelled')
                                <button type="button"
                                        @click="confirmCancel()"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 text-red-700 border border-red-200 hover:bg-red-100">
                                    Cancel WO
                                </button>
                            @elseif (in_array($s, $available))
                                <button type="button"
                                        @click="setStatus('{{ $s }}')"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                                    → {{ WorkOrder::STATUS_LABELS[$s] }}
                                </button>
                            @endif
                        @endforeach

                        {{-- Hidden status input updated by Alpine --}}
                        <input type="hidden" name="_status_intent" x-bind:value="pendingStatus">

                        {{-- Cancel confirmation --}}
                        <div x-show="showCancelConfirm" x-cloak
                             class="w-full mt-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                            <p class="font-medium mb-2">Cancel this work order?</p>
                            <p class="text-xs mb-3">The calendar event (if any) will also be removed.</p>
                            <div class="flex gap-2">
                                <button type="button"
                                        @click="showCancelConfirm = false"
                                        class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                    Keep
                                </button>
                                <button type="button"
                                        @click="setStatus('cancelled'); showCancelConfirm = false"
                                        class="px-3 py-1.5 text-xs font-medium text-white rounded-lg"
                                        style="background:#dc2626">
                                    Yes, cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main form --}}
            <form method="POST"
                  action="{{ route('pages.sales.work-orders.update', [$sale, $workOrder]) }}"
                  x-data="woEdit()"
                  id="wo-edit-form">
                @csrf
                @method('PUT')

                {{-- Status is driven by the status panel above, or keeps current if not changed --}}
                <input type="hidden" name="status" id="status-field" value="{{ $workOrder->status }}">

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm divide-y divide-gray-100">

                    {{-- Work type --}}
                    <div class="px-6 py-5 space-y-1">
                        <label for="work_type" class="block text-sm font-medium text-gray-700">
                            Work type <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="work_type" name="work_type"
                               value="{{ old('work_type', $workOrder->work_type) }}"
                               class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500 @error('work_type') border-red-500 @enderror">
                        @error('work_type')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Assigned to --}}
                    <div class="px-6 py-5 space-y-1">
                        <label for="assigned_to_user_id" class="block text-sm font-medium text-gray-700">
                            Assigned to
                        </label>
                        <select id="assigned_to_user_id" name="assigned_to_user_id"
                                x-model="assigneeId"
                                class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Unassigned —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('assigned_to_user_id', $workOrder->assigned_to_user_id) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Scheduled date + time --}}
                    <div class="px-6 py-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label for="scheduled_date" class="block text-sm font-medium text-gray-700">
                                    Scheduled date
                                </label>
                                <input type="date" id="scheduled_date" name="scheduled_date"
                                       value="{{ old('scheduled_date', $workOrder->scheduled_date?->format('Y-m-d')) }}"
                                       x-model="scheduledDate"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="space-y-1">
                                <label for="scheduled_time" class="block text-sm font-medium text-gray-700">
                                    Scheduled time
                                </label>
                                <input type="time" id="scheduled_time" name="scheduled_time"
                                       value="{{ old('scheduled_time', $workOrder->scheduled_time) }}"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        {{-- Calendar sync hint --}}
                        <div class="mt-2 text-xs" x-show="calendarHintVisible()" x-cloak>
                            @if ($workOrder->calendar_synced)
                                <span style="color:#2563eb">Calendar event will be updated on save.</span>
                            @else
                                <span style="color:#2563eb">A calendar event will be created for the assigned user on save.</span>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="px-6 py-5 space-y-1">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="notes" name="notes" rows="4"
                                  class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('notes', $workOrder->notes) }}</textarea>
                    </div>

                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-4">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('pages.sales.show', $sale) }}"
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </a>

                        @can('delete work orders')
                        <form method="POST"
                              action="{{ route('pages.sales.work-orders.destroy', [$sale, $workOrder]) }}"
                              onsubmit="return confirm('Delete this work order? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50">
                                Delete
                            </button>
                        </form>
                        @endcan
                    </div>

                    <button type="submit"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
                        Save changes
                    </button>
                </div>

            </form>
        </div>
    </div>

<script>
function woStatus(currentStatus) {
    return {
        pendingStatus: currentStatus,
        showCancelConfirm: false,
        setStatus(s) {
            this.pendingStatus = s;
            document.getElementById('status-field').value = s;
            document.getElementById('wo-edit-form').submit();
        },
        confirmCancel() {
            this.showCancelConfirm = true;
        },
    };
}

function woEdit() {
    return {
        assigneeId: '{{ old('assigned_to_user_id', $workOrder->assigned_to_user_id ?? '') }}',
        scheduledDate: '{{ old('scheduled_date', $workOrder->scheduled_date?->format('Y-m-d') ?? '') }}',
        calendarHintVisible() {
            return this.assigneeId !== '' && this.scheduledDate !== '';
        },
    };
}
</script>
</x-admin-layout>
