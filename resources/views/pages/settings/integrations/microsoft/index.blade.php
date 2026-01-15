{{-- resources/views/pages/settings/integrations/microsoft/index.blade.php --}}
<x-app-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h1 class="text-2xl font-semibold text-gray-900">Microsoft Calendar Integration</h1>
            <p class="text-sm text-gray-600 mt-1">
                Connect Microsoft 365 to discover calendars and enable sync.
            </p>

            @if (session('success'))
                <div class="mt-4 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mt-4 p-4 rounded-lg border border-red-200 bg-red-50 text-red-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('microsoft.connect') }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-700 text-white text-sm font-medium rounded-lg hover:bg-blue-800">
                    Connect Microsoft
                </a>

                <form method="POST" action="{{ route('microsoft.calendars.discover') }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                        Discover Calendars
                    </button>
                </form>
				<form method="POST" action="{{ route('microsoft.syncNow') }}">
    @csrf
    <button type="submit"
        class="inline-flex items-center px-4 py-2 bg-emerald-700 text-white text-sm font-medium rounded-lg hover:bg-emerald-800">
        Sync Now
    </button>
</form>

            </div>
        </div>
{{-- Calendars --}}
<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-900">Discovered Calendars</h2>
    <p class="text-sm text-gray-600 mt-1">
        These calendars were fetched from Microsoft. Next we’ll add enable/disable toggles.
    </p>

    @if ($calendars->count() < 1)
        <div class="mt-4 p-4 rounded-lg border border-gray-200 bg-gray-50 text-gray-700 text-sm">
            No calendars found yet. Click <span class="font-medium">Discover Calendars</span>.
        </div>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white">
            <table class="min-w-full text-sm text-left text-gray-700 table-auto">
                <thead class="text-xs uppercase bg-gray-50 text-gray-600">
                    <tr>
    <th class="px-6 py-3 w-56">Name</th>
    <th class="px-6 py-3 w-28">Primary</th>
    <th class="px-6 py-3 w-44">Enabled</th>
    <th class="px-6 py-3">Calendar ID</th>
</tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($calendars as $cal)
                        <tr>
    <td class="px-6 py-4 font-medium text-gray-900">
        {{ $cal->name }}
    </td>

    <td class="px-6 py-4">
        @if ($cal->is_primary)
            <span class="inline-flex items-center px-2 py-1 rounded-md bg-blue-50 text-blue-700 text-xs font-medium">
                Primary
            </span>
        @else
            <span class="text-gray-400">—</span>
        @endif
    </td>

    <td class="px-6 py-4">
        <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox"
                   class="sr-only peer microsoft-calendar-toggle"
                   data-url="{{ route('pages.settings.integrations.microsoft.calendars.update', $cal->id) }}"
                   @checked($cal->is_enabled)>
            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:bg-blue-600">
                <div class="absolute top-[2px] left-[2px] w-5 h-5 bg-white rounded-full transition-all peer-checked:translate-x-full"></div>
            </div>
            <span class="ml-3 text-sm text-gray-700">
                {{ $cal->is_enabled ? 'Enabled' : 'Disabled' }}
            </span>
        </label>
    </td>

    <td class="px-6 py-4 font-mono text-xs text-gray-600 break-all">
        {{ $cal->calendar_id }}
    </td>
</tr>

                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('.microsoft-calendar-toggle');

    toggles.forEach((toggle) => {
        toggle.addEventListener('change', async (e) => {
            const url = toggle.dataset.url;
            const isEnabled = toggle.checked ? 1 : 0;

            try {
                const res = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ is_enabled: isEnabled }),
                });

                if (!res.ok) throw new Error('Request failed: ' + res.status);

                const data = await res.json();
				
				

                // Update label text next to the switch
                const labelSpan = toggle.closest('label')?.querySelector('span.ml-3');
                if (labelSpan) {
                    labelSpan.textContent = data.is_enabled ? 'Enabled' : 'Disabled';
                }
            } catch (err) {
                // Revert toggle on failure
                toggle.checked = !toggle.checked;
                alert('Failed to update calendar. Please try again.');
                console.error(err);
            }
        });
    });
});
</script>

</x-app-layout>
