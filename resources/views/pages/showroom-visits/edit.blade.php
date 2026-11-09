{{-- resources/views/pages/showroom-visits/edit.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $visit->status === 'change_requested' ? 'Reschedule' : 'Edit' }} Showroom Visit</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ $visit->contact_name }} &mdash; Job #{{ $visit->opportunity?->job_no ?: $visit->opportunity_id }}
                    </p>
                </div>
                <a href="{{ route('pages.showroom-visits.show', $visit) }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Back</a>
            </div>

            @if ($visit->status === 'change_requested')
                <div class="mb-4 p-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 text-sm">
                    <strong>Customer's request:</strong>
                    {!! nl2br(e($visit->change_request_preferred)) !!}
                    @if ($visit->change_request_note)<br><em>{!! nl2br(e($visit->change_request_note)) !!}</em>@endif
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 text-red-800 bg-red-50 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.showroom-visits.update', $visit) }}"
                  class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:divide-gray-700">
                @csrf
                @method('PATCH')

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Date &amp; time</label>
                        <input type="datetime-local" name="scheduled_at" step="900" required
                               value="{{ old('scheduled_at', $visit->scheduled_at->format('Y-m-d\TH:i')) }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Length</label>
                        <select name="duration_minutes" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                            @foreach (collect($durations)->push((int) $visit->duration_minutes)->unique()->sort() as $d)
                                <option value="{{ $d }}" @selected((int) old('duration_minutes', $visit->duration_minutes) === $d)>
                                    {{ $d < 60 ? $d . ' min' : ($d / 60) . ' hr' . ($d > 60 ? 's' : '') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Host (staff member)</label>
                        <select name="host_employee_id" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                            <option value="">— Unassigned —</option>
                            @foreach ($hosts as $h)
                                <option value="{{ $h->id }}" @selected((string) old('host_employee_id', $visit->host_employee_id) === (string) $h->id)>{{ $h->first_name }} {{ $h->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Notes <span class="text-gray-400 font-normal">(internal)</span></label>
                        <textarea name="notes" rows="3" class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">{{ old('notes', $visit->notes) }}</textarea>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Name</label>
                        <input type="text" name="contact_name" required value="{{ old('contact_name', $visit->contact_name) }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $visit->contact_email) }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Mobile (for text)</label>
                        <input type="text" name="contact_phone" value="{{ old('contact_phone', $visit->contact_phone) }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-3 space-y-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input type="checkbox" name="notify_email" value="1" @checked(old('notify_email', $visit->notify_email)) @disabled(! $mailEnabled)
                                   class="rounded border-gray-300 text-blue-600">
                            Email confirmation &amp; reminders
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input type="checkbox" name="notify_sms" value="1" @checked(old('notify_sms', $visit->notify_sms)) @disabled(! $smsEnabled)
                                   class="rounded border-gray-300 text-blue-600">
                            Text (SMS) confirmation &amp; reminders
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-gray-100 pt-2">
                            <input type="checkbox" name="send_update" value="1" checked class="rounded border-gray-300 text-blue-600">
                            If the date/time changed, send the customer an updated confirmation
                        </label>
                    </div>
                </div>

                <div class="p-6 flex justify-end gap-2">
                    <a href="{{ route('pages.showroom-visits.show', $visit) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">Save</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
