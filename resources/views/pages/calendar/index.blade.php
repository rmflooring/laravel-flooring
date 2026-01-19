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
    <div id="calendar" tabindex="-11 class="min-h-[700px]"></div>
</div>

    </div>
	
	<!-- Event Details Modal (Flowbite) -->
<div id="event-details-modal" tabindex="-1" aria-hidden="true"
     data-modal-target="event-details-modal"
     data-modal-placement="center"
     role="dialog"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">
                    Event Details
                </h3>
                <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                        data-modal-hide="event-details-modal">
                    <span class="sr-only">Close modal</span>
                    ✕
                </button>
            </div>

            <div class="p-4 md:p-5 space-y-4">
                <div>
                    <div class="text-sm text-gray-500">Title</div>
                    <div id="event-modal-title" class="text-base font-medium text-gray-900"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Start</div>
                        <div id="event-modal-start" class="text-sm text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">End</div>
                        <div id="event-modal-end" class="text-sm text-gray-900"></div>
                    </div>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Location</div>
                    <div id="event-modal-location" class="text-sm text-gray-900 whitespace-pre-wrap"></div>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Description</div>
                    <div id="event-modal-description" class="text-sm text-gray-900 whitespace-pre-wrap"></div>
                </div>

                <div>
                    <div class="text-sm text-gray-500">Provider</div>
                    <div id="event-modal-provider" class="text-sm text-gray-900"></div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 p-4 md:p-5 border-t rounded-b">
                <button type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg"
                        data-modal-hide="event-details-modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
	
	<!-- Event Editor Modal (Create/Edit) -->
<div id="event-editor-modal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <!-- Header -->
            <div class="flex items-start justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900" id="event-editor-title">
                    New event
                </h3>
                <button
  type="button"
  id="event-editor-close"
  class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg"
  data-modal-hide="event-editor-modal">
  Close
</button>

			<button
  type="button"
  id="event-editor-modal-init"
  data-modal-target="event-editor-modal"
  data-modal-toggle="event-editor-modal"
  class="hidden"
  tabindex="-1"
></button>

            </div>

            <!-- Body -->
			<div class="p-4 md:p-5 space-y-4">
			  <input type="hidden" id="event-editor-id" value="">
                <!-- Calendar selector -->
                <div>
                    <label for="event-editor-calendar" class="block mb-1 text-sm font-medium text-gray-900">
                        Calendar
                    </label>
                    <select id="event-editor-calendar"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <!-- We will populate options from your existing calendars (next step) -->
                    </select>
                </div>

                <!-- Title -->
                <div>
                    <label for="event-editor-subject" class="block mb-1 text-sm font-medium text-gray-900">
                        Title
                    </label>
                    <input type="text" id="event-editor-subject"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           placeholder="Event title">
                </div>

                <!-- All-day -->
                <div class="flex items-center gap-2">
                    <input id="event-editor-all-day" type="checkbox"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded">
                    <label for="event-editor-all-day" class="text-sm text-gray-900">
                        All day
                    </label>
                </div>

                <!-- Start/End -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label for="event-editor-start" class="block mb-1 text-sm font-medium text-gray-900">
                            Start
                        </label>
                        <input type="datetime-local" id="event-editor-start"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    <div>
                        <label for="event-editor-end" class="block mb-1 text-sm font-medium text-gray-900">
                            End
                        </label>
                        <input type="datetime-local" id="event-editor-end"
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                </div>

                <!-- Location -->
                <div>
                    <label for="event-editor-location" class="block mb-1 text-sm font-medium text-gray-900">
                        Location
                    </label>
                    <input type="text" id="event-editor-location"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                           placeholder="Location">
                </div>

                <!-- Notes -->
                <div>
                    <label for="event-editor-notes" class="block mb-1 text-sm font-medium text-gray-900">
                        Notes
                    </label>
                    <textarea id="event-editor-notes" rows="4"
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                              placeholder="Notes"></textarea>
                </div>

                <!-- Error text -->
                <p id="event-editor-error" class="hidden text-sm text-red-600"></p>
            </div>

            <!-- Footer -->
     <div class="flex items-center justify-between gap-2 p-4 md:p-5 border-t rounded-b">
  <button type="button"
          id="event-editor-delete"
          class="hidden px-4 py-2 text-sm font-medium text-white bg-red-700 hover:bg-red-800 rounded-lg">
    Delete
  </button>

  <div class="flex items-center gap-2">
    <button type="button" id="event-editor-cancel"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg"
            data-modal-hide="event-editor-modal">
      Cancel
    </button>

    <button type="button"
            id="event-editor-save"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-700 hover:bg-blue-800 rounded-lg">
      Save
    </button>
  </div>
</div>


	
<script>
  window.FM_CALENDAR_DB_IDS = {
    installations: 1
  };
</script>
	
<script>
  window.FM_CALENDAR_IDS = {
    rfm: "AAMkADk2ZDM0MGFkLTMzYjQtNDNkZi04OWIyLTdlZjM0MzM2NGRmYgAuAAAAAADpPYsSY_zuTpZ2LzXNKrbDAQAeWWT8nElrTawoN6lH_dyBAAAAAAENAAA=",
    installations: "AQMkAGUwYjhiODRmLTZlNWYtNDVlOS05NmRiLTU2MWE4Yjg1YmQ2NwAuAAADTDgl105wX0_e_MYHme9wfQEAE9NQlOKrCUq3Sw5J8p2MnQAAAgENAAAA",
    warehouse: "AAMkADAyOTk0N2Q0LTgzNzktNGI2YS05OWQ1LTNlNDAzYTdjYjYzZgAuAAAAAAAUPMnMWVPoRKwz4Jf5r0i5AQCp3YGUkZUORaNavigyjBHoAAAAAAENAAA=",
    team: "AQMkAGY4OWY4YzA0LWQwMjktNGQ5NC04OAE0LTU2YmE0ZjkyMgBkYjEALgAAA3KoSc-SpQ9Klvv0r6DcdEwBAOTXpvHltW9Jo7DlWHGrGNwAAAIBDQAAAA=="
  };
</script>
	
<script>
  window.FM_CALENDAR_OPTIONS = [
    { id: 24, label: "RM – RFM / Measures" },
    { id: 25, label: "RM – Installations" },
    { id: 26, label: "RM – Warehouse" },
    { id: 22, label: "Team RM" },
  ];

  window.FM_CREATE_EVENT_URL = "{{ route('pages.calendar.events.store') }}";
  window.FM_MICROSOFT_SYNC_NOW_URL = "{{ route('pages.microsoft.syncNow') }}";
  window.FM_UPDATE_EVENT_URL_TEMPLATE = "{{ url('/pages/calendar/events') }}/__ID__";
</script>

	@vite('resources/js/pages/calendar.js')
</x-app-layout>
