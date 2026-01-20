<!-- Event Editor Modal (Outlook-style Create/Edit) -->
<div id="event-editor-modal" tabindex="-1" aria-hidden="true"
     class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 w-full inset-0 h-[calc(100%-1rem)] max-h-full flex items-start justify-center">

    <div class="relative p-4 w-full max-w-3xl">
        <div class="relative bg-white rounded-xl shadow border border-gray-200 overflow-hidden">

            {{-- Top bar (calendar dropdown + expand icon) --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 bg-white">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-blue-600"></span>

                    <select id="event-editor-calendar"
                            class="min-w-0 max-w-[22rem] truncate bg-transparent border-0 p-0 text-base font-medium text-gray-900 focus:ring-0">
                        {{-- populated by JS --}}
                    </select>
                </div>

                <button type="button"
                        class="text-gray-500 hover:text-gray-900 rounded-lg p-2"
                        title="Open in new window"
                        aria-label="Open in new window">
                    {{-- simple “expand” icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7v7m0-7L10 14m-1 7H3V9" />
                    </svg>
                </button>
            </div>

            {{-- Action bar (Save / Discard) --}}
            <div class="flex items-center justify-between px-5 py-3 bg-white">
                <button type="button"
                        id="event-editor-save"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                    {{-- floppy icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 7a2 2 0 012-2h10l4 4v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 5v6h8V5" />
                    </svg>
                    Save
                </button>

                <button type="button"
                        id="event-editor-cancel"
                        data-modal-hide="event-editor-modal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-gray-700 hover:bg-gray-100 font-medium">
                    {{-- trash-ish icon for “Discard” like your screenshot --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-3h4m-7 3h10" />
                    </svg>
                    Discard
                </button>
            </div>

            {{-- Body --}}
            <div class="px-5 pb-5">
                <input type="hidden" id="event-editor-id" value="">

                {{-- Title row --}}
                <div class="flex items-start gap-3 py-4 border-t border-gray-100">
                    <div class="mt-1 text-gray-400">
                        {{-- “sparkle/plus” placeholder icon --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 6v12m6-6H6" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <input type="text" id="event-editor-subject"
                               class="w-full border-0 p-0 text-4xl font-light text-gray-800 placeholder-gray-300 focus:ring-0"
                               placeholder="Add a title">
                        <div class="mt-2 h-px bg-gray-200"></div>
                    </div>
                </div>

                {{-- Attendees placeholder row (UI only for now) --}}
                <div class="flex items-center gap-3 py-4">
                    <div class="text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a4 4 0 00-5-3.87M9 20H2v-2a4 4 0 015-3.87m10-5.13a4 4 0 10-8 0 4 4 0 008 0zm-10 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="text-lg text-gray-300 select-none">Invite required attendees</div>
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
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-center">
                            <input type="datetime-local" id="event-editor-start"
                                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:ring-blue-500 focus:border-blue-500">

                            <input type="datetime-local" id="event-editor-end"
                                   class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:ring-blue-500 focus:border-blue-500">

                            <label class="inline-flex items-center gap-3 select-none">
                                <span class="text-sm text-gray-700">All day</span>
                                <input id="event-editor-all-day" type="checkbox" class="sr-only">
                                <span class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition">
                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition translate-x-1"
                                          id="event-editor-all-day-knob"></span>
                                </span>
                            </label>
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
                        <input type="text" id="event-editor-location"
                               class="w-full border-0 p-0 text-lg text-gray-800 placeholder-gray-300 focus:ring-0"
                               placeholder="Add a room or location">
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
                        <textarea id="event-editor-notes" rows="4"
                                  class="w-full border-0 p-0 text-lg text-gray-800 placeholder-gray-300 focus:ring-0 resize-none"
                                  placeholder="Add a description"></textarea>
                        <div class="mt-2 h-px bg-gray-200"></div>
                    </div>
                </div>

                {{-- Error --}}
                <p id="event-editor-error" class="hidden text-sm text-red-600 mt-2"></p>

                {{-- Bottom info + more options (UI only) --}}
                <div class="flex items-start gap-3 py-4 mt-2">
                    <div class="mt-1 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm text-gray-600">
                        An invitation won't be sent to group members unless you invite them.
                        <a href="#" class="text-blue-600 hover:underline">Invite members</a>
                    </div>
                    <button type="button" class="text-blue-600 hover:underline text-sm">
                        More options
                    </button>
                </div>

                {{-- Delete button stays available for edit mode (your JS already toggles it) --}}
                <div class="pt-2">
                    <button type="button"
                            id="event-editor-delete"
                            class="hidden px-4 py-2 text-sm font-medium text-white bg-red-700 hover:bg-red-800 rounded-lg">
                        Delete
                    </button>
                </div>
            </div>

            {{-- Hidden init button (Flowbite programmatic open) --}}
            <button
              type="button"
              id="event-editor-modal-init"
              data-modal-target="event-editor-modal"
              data-modal-toggle="event-editor-modal"
              class="hidden"
              tabindex="-1"
            ></button>

        </div>
    </div>
</div>

{{-- Tiny switch behavior (no dependency) --}}
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const cb = document.getElementById('event-editor-all-day');
    const knob = document.getElementById('event-editor-all-day-knob');
    if (!cb || !knob) return;

    const paint = () => {
      const track = knob.parentElement;
      if (!track) return;
      if (cb.checked) {
        track.classList.remove('bg-gray-200');
        track.classList.add('bg-blue-600');
        knob.classList.remove('translate-x-1');
        knob.classList.add('translate-x-5');
      } else {
        track.classList.add('bg-gray-200');
        track.classList.remove('bg-blue-600');
        knob.classList.add('translate-x-1');
        knob.classList.remove('translate-x-5');
      }
    };

    cb.addEventListener('change', paint);
    paint();
  });
</script>
