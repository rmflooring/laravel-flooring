{{-- WO Calendar Event Details Modal --}}
{{-- Same style as the calendar event-editor-modal, but for configuring WO calendar event details --}}
<div id="wo-calendar-modal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full inset-0 h-[calc(100%-1rem)] max-h-full flex items-start justify-center">

    <div class="relative p-4 w-full max-w-3xl">
        <div class="relative bg-white rounded-xl shadow border border-gray-200 overflow-hidden">

            {{-- Top bar (calendar label) --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-white">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-600 flex-shrink-0"></span>
                    <span class="text-base font-medium text-gray-900 truncate">RM – Installations</span>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="flex items-center justify-between px-5 py-3 bg-white">
                <button type="button" id="wo-cal-apply"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 13l4 4L19 7" />
                    </svg>
                    Apply
                </button>

                <button type="button" id="wo-cal-discard"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 font-medium text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Discard
                </button>
            </div>

            {{-- Body --}}
            <div class="px-5 pb-5">

                {{-- Title row --}}
                <div class="flex items-start gap-3 py-4 border-t border-gray-100">
                    <div class="mt-1 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6v12m6-6H6" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <input type="text" id="wo-cal-title"
                               class="w-full border-0 p-0 text-4xl font-light text-gray-800 placeholder-gray-300 focus:ring-0"
                               placeholder="Add a title">
                        <div class="mt-2 h-px bg-gray-200"></div>
                    </div>
                </div>

                {{-- Attendees row --}}
                <div class="flex items-start gap-3 py-4">
                    <div class="mt-1 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H2v-2a4 4 0 015-3.87m10-5.13a4 4 0 10-8 0 4 4 0 008 0zm-10 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div id="wo-cal-attendees" class="flex flex-wrap gap-2 min-h-[2rem] items-center">
                            {{-- populated by JS --}}
                        </div>
                        <div class="mt-2 h-px bg-gray-200"></div>
                    </div>
                </div>

                {{-- Date/Time row --}}
                <div class="flex items-start gap-3 py-4">
                    <div class="mt-1 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Start</label>
                                <input type="datetime-local" id="wo-cal-start"
                                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">End</label>
                                <input type="datetime-local" id="wo-cal-end"
                                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location row --}}
                <div class="flex items-center gap-3 py-4">
                    <div class="text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 11c1.105 0 2-.895 2-2a2 2 0 10-4 0c0 1.105.895 2 2 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 21s7-4.35 7-11a7 7 0 10-14 0c0 6.65 7 11 7 11z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <input type="text" id="wo-cal-location"
                               class="w-full border-0 p-0 text-lg text-gray-800 placeholder-gray-300 focus:ring-0"
                               placeholder="Add a location">
                        <div class="mt-2 h-px bg-gray-200"></div>
                    </div>
                </div>

                {{-- Notes row --}}
                <div class="flex items-start gap-3 py-4">
                    <div class="mt-1 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <textarea id="wo-cal-notes" rows="4"
                                  class="w-full border-0 p-0 text-lg text-gray-800 placeholder-gray-300 focus:ring-0 resize-none"
                                  placeholder="Add a description"></textarea>
                        <div class="mt-2 h-px bg-gray-200"></div>
                    </div>
                </div>

            </div>

            {{-- Hidden Flowbite init trigger --}}
            <button type="button"
                    id="wo-calendar-modal-init"
                    data-modal-target="wo-calendar-modal"
                    data-modal-toggle="wo-calendar-modal"
                    class="hidden"
                    tabindex="-1"></button>

        </div>
    </div>
</div>
