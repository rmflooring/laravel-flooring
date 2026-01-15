import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('calendar');
  if (!el) return;

// Load user calendar filter preferences
fetch('/api/user/calendar-preferences', {
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
})
  .then(r => r.json())
  .then(res => {
    if (!res.success || !res.data) return;

    const d = res.data;

    const map = {
      'filter-show-rfm': d.show_rfm,
      'filter-show-installations': d.show_installations,
      'filter-show-warehouse': d.show_warehouse,
      'filter-show-team': d.show_team,
      'filter-show-availability': d.show_availability,
    };

    Object.entries(map).forEach(([id, val]) => {
      const el = document.getElementById(id);
      if (!el) return;

      if (val === null) return; // allow defaults later
      el.checked = !!val;
    });
  })
  .catch(err => console.error('Failed to load calendar prefs', err));

function saveCalendarPrefs() {
  const payload = {
    show_rfm: document.getElementById('filter-show-rfm')?.checked ?? null,
    show_installations: document.getElementById('filter-show-installations')?.checked ?? null,
    show_warehouse: document.getElementById('filter-show-warehouse')?.checked ?? null,
    show_team: document.getElementById('filter-show-team')?.checked ?? null,
    show_availability: document.getElementById('filter-show-availability')?.checked ?? null,
  };

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

  return fetch('/api/user/calendar-preferences', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
    },
    body: JSON.stringify(payload),
  })
    .then(r => r.json())
    .then(res => {
      if (!res.success) console.warn('Saving calendar prefs failed', res);
      return res;
    })
    .catch(err => {
      console.error('Failed to save calendar prefs', err);
    });
}

// Bind change events
[
  'filter-show-rfm',
  'filter-show-installations',
  'filter-show-warehouse',
  'filter-show-team',
  'filter-show-availability',
].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('change', async () => {
  await saveCalendarPrefs();
  calendar.refetchEvents();
});
});
	
  // Modal elements
  const modalTitle = document.getElementById('event-modal-title');
  const modalStart = document.getElementById('event-modal-start');
  const modalEnd = document.getElementById('event-modal-end');
  const modalLocation = document.getElementById('event-modal-location');
  const modalDescription = document.getElementById('event-modal-description');
  const modalProvider = document.getElementById('event-modal-provider');

  const calendar = new Calendar(el, {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },
events: async (info, successCallback, failureCallback) => {
  try {
    // Read filter checkboxes
    const showRfm = document.getElementById('filter-show-rfm')?.checked;
    const showInst = document.getElementById('filter-show-installations')?.checked;
    const showWh = document.getElementById('filter-show-warehouse')?.checked;
    const showTeam = document.getElementById('filter-show-team')?.checked;

    // NOTE: For now, these IDs are placeholders until we map real Microsoft calendar IDs.
    // We will replace these with your real external_calendar_id values in the next step.
    const ids = [];

    if (showRfm) ids.push(window.FM_CALENDAR_IDS?.rfm);
    if (showInst) ids.push(window.FM_CALENDAR_IDS?.installations);
    if (showWh) ids.push(window.FM_CALENDAR_IDS?.warehouse);
    if (showTeam) ids.push(window.FM_CALENDAR_IDS?.team);

    const calendarIds = ids.filter(Boolean).join(',');

    const url = calendarIds
      ? `/pages/calendar/events/feed?calendar_ids=${encodeURIComponent(calendarIds)}`
      : `/pages/calendar/events/feed`;

    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    successCallback(data);
  } catch (err) {
    console.error(err);
    failureCallback(err);
  }
},
    height: 'auto',
    editable: false,
    selectable: true,
    selectMirror: true,

    eventClick: (info) => {
      info.jsEvent.preventDefault();

      const event = info.event;
      const ext = event.extendedProps || {};

      const fmt = (d) => {
        if (!d) return '';
        return new Intl.DateTimeFormat(undefined, {
          weekday: 'short',
          year: 'numeric',
          month: 'short',
          day: '2-digit',
          hour: 'numeric',
          minute: '2-digit',
        }).format(d);
      };

      if (modalTitle) modalTitle.textContent = event.title || '';
      if (modalStart) modalStart.textContent = fmt(event.start);
      if (modalEnd) modalEnd.textContent = fmt(event.end);

      if (modalLocation) modalLocation.textContent = ext.location || '';
      if (modalDescription) modalDescription.textContent = ext.description || '';
      if (modalProvider) modalProvider.textContent = ext.provider || '';

      const modalInstance =
        window.FlowbiteInstances?.getInstance('Modal', 'event-details-modal');

      if (!modalInstance) {
        console.warn('[calendar] Flowbite modal instance not found');
        return;
      }

      modalInstance.show();
    },
  });

  calendar.render();
});
