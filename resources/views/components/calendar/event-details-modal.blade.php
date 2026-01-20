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
