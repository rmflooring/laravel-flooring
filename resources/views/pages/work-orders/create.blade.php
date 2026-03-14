{{-- resources/views/pages/work-orders/create.blade.php --}}
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
                <h1 class="text-2xl font-bold text-gray-900">Create work order</h1>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('pages.sales.work-orders.store', $sale) }}"
                  x-data="woCreate()">
                @csrf

                <div class="bg-white border border-gray-200 rounded-lg shadow-sm divide-y divide-gray-100">

                    {{-- Work type --}}
                    <div class="px-6 py-5 space-y-1">
                        <label for="work_type" class="block text-sm font-medium text-gray-700">
                            Work type <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="work_type" name="work_type"
                               value="{{ old('work_type') }}"
                               placeholder="e.g. Tile installation, Subfloor prep"
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
                                @change="updateCalendarHint()"
                                class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">— Unassigned —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}"
                                    {{ old('assigned_to_user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to_user_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Scheduled date + time --}}
                    <div class="px-6 py-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label for="scheduled_date" class="block text-sm font-medium text-gray-700">
                                    Scheduled date
                                </label>
                                <input type="date" id="scheduled_date" name="scheduled_date"
                                       value="{{ old('scheduled_date') }}"
                                       x-model="scheduledDate"
                                       @change="updateCalendarHint()"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('scheduled_date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="space-y-1">
                                <label for="scheduled_time" class="block text-sm font-medium text-gray-700">
                                    Scheduled time
                                </label>
                                <input type="time" id="scheduled_time" name="scheduled_time"
                                       value="{{ old('scheduled_time') }}"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('scheduled_time')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Calendar sync hint --}}
                        <p class="mt-2 text-xs"
                           x-show="assigneeId && scheduledDate"
                           x-cloak
                           style="color:#2563eb">
                            A calendar event will be created for the assigned user when this work order is saved.
                        </p>
                    </div>

                    {{-- Notes --}}
                    <div class="px-6 py-5 space-y-1">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea id="notes" name="notes" rows="4"
                                  placeholder="Any special instructions or context..."
                                  class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>
                    </div>

                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-4 focus:ring-gray-300">
                        Create work order
                    </button>
                </div>

            </form>
        </div>
    </div>

<script>
function woCreate() {
    return {
        assigneeId: '{{ old('assigned_to_user_id', '') }}',
        scheduledDate: '{{ old('scheduled_date', '') }}',
        updateCalendarHint() {
            // hint shown via x-show binding — no extra logic needed
        },
    };
}
</script>
</x-admin-layout>
