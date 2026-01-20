<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <div class="bg-white border border-gray-200 rounded-lg p-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Calendar</h1>
                <p class="text-sm text-gray-600 mt-1">
                    View, create, edit, and manage events.
                </p>
            </div>

            <a href="{{ route('pages.settings.integrations.microsoft.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800">
                Microsoft Integration
            </a>
        </div>

{{-- Calendar Filters (per-user) --}}
<div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Calendar Filters</h2>
            <p class="text-xs text-gray-600">These filters are saved for your user.</p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <label class="inline-flex items-center gap-2">
                <input id="filter-show-rfm" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <span class="text-sm text-gray-800">RFM / Measures</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input id="filter-show-installations" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <span class="text-sm text-gray-800">Installations</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input id="filter-show-warehouse" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <span class="text-sm text-gray-800">Warehouse</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input id="filter-show-team" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <span class="text-sm text-gray-800">Team / Showroom</span>
            </label>

            <label class="inline-flex items-center gap-2">
                <input id="filter-show-availability" type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                <span class="text-sm text-gray-800">My Availability</span>
            </label>
        </div>
    </div>
</div>
		
{{-- Calendar --}}
<div class="bg-white border border-gray-200 rounded-lg p-4">
    <div id="calendar" tabindex="-1" class="min-h-[700px]"></div>
</div>

    </div>
	
{{-- Modals --}}
@include('components.calendar.event-details-modal')
@include('components.calendar.event-editor-modal')
@include('components.calendar.globals')

@vite('resources/js/pages/calendar.js')

</x-app-layout>
