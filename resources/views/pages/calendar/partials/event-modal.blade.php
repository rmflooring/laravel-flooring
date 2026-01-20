{{-- resources/views/pages/calendar/partials/event-modal.blade.php --}}
<div id="event-details-modal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full inset-0 h-[calc(100%-1rem)] max-h-full">

    <div class="relative p-4 w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">

            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b rounded-t">
                <h3 class="text-lg font-semibold text-gray-900">Event</h3>
                <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center"
                        data-modal-hide="event-details-modal">
                    <span class="sr-only">Close</span>
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-4 space-y-4">
                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Title</div>
                    <div id="event-modal-title" class="text-base text-gray-900"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">Start</div>
                        <div id="event-modal-start" class="text-sm text-gray-900"></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wider text-gray-500">End</div>
                        <div id="event-modal-end" class="text-sm text-gray-900"></div>
                    </div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Location</div>
                    <div id="event-modal-location" class="text-sm text-gray-900"></div>
                </div>

                <div>
                    <div class="text-xs uppercase tracking-wider text-gray-500">Notes</div>
                    <div id="event-modal-description" class="text-sm text-gray-900 whitespace-pre-wrap"></div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="text-xs uppercase tracking-wider text-gray-500">Provider</div>
                    <div id="event-modal-provider" class="text-xs text-gray-700"></div>
                </div>

                {{-- Hidden fields JS can use --}}
                <input type="hidden" id="event-modal-event-id" value="">
                <input type="hidden" id="event-modal-calendar-id" value="">
                <input type="hidden" id="event-modal-original-calendar-id" value="">
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2 p-4 border-t rounded-b">
                <button type="button"
                        id="event-modal-delete"
                        class="text-white bg-red-600 hover:bg-red-700 font-medium rounded-lg text-sm px-4 py-2">
                    Delete
                </button>

                <button type="button"
                        id="event-modal-edit"
                        class="text-white bg-blue-600 hover:bg-blue-700 font-medium rounded-lg text-sm px-4 py-2">
                    Edit
                </button>

                <button type="button"
                        class="text-gray-700 bg-gray-100 hover:bg-gray-200 font-medium rounded-lg text-sm px-4 py-2"
                        data-modal-hide="event-details-modal">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>
