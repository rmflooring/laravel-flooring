import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('calendar');
  if (!el) return;

  // -----------------------------
  // Helpers
  // -----------------------------
  const getCsrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  async function syncNowSilently() {
    try {
      const csrf = getCsrf();
      const url = window.FM_MICROSOFT_SYNC_NOW_URL;

      if (!url) {
        console.warn('[calendar] FM_MICROSOFT_SYNC_NOW_URL is not set');
        return { success: false };
      }

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
      });

      const json = await res.json().catch(() => ({}));

      if (!res.ok) {
        console.warn('[calendar] sync-now failed', res.status, json);
        return { success: false, status: res.status, json };
      }

      return { success: true, json };
    } catch (err) {
      console.warn('[calendar] sync-now error', err);
      return { success: false, error: err };
    }
  }

  // Month view often gives "YYYY-MM-DD" (date only). TimeGrid gives "YYYY-MM-DDTHH:mm:ss".
  // datetime-local expects "YYYY-MM-DDTHH:mm"
  const toLocalInputFromStr = (str, fallbackTime = '09:00') => {
    if (!str) return '';
    if (str.length === 10) return `${str}T${fallbackTime}`;
    return str.slice(0, 16);
  };

  const toDatetimeLocal = (d) => {
    if (!d) return '';
    const dt = new Date(d);
    const pad = (n) => String(n).padStart(2, '0');
    return (
      `${dt.getFullYear()}-` +
      `${pad(dt.getMonth() + 1)}-` +
      `${pad(dt.getDate())}T` +
      `${pad(dt.getHours())}:` +
      `${pad(dt.getMinutes())}`
    );
  };

  const focusCalendar = () => {
    const safe = document.getElementById('calendar');
    if (safe && typeof safe.focus === 'function') safe.focus();
  };

  // Fix aria-hidden warning: move focus outside modal BEFORE Flowbite hides it
  const closeBtn = document.getElementById('event-editor-close');
  if (closeBtn) closeBtn.addEventListener('click', focusCalendar);

  const cancelBtn = document.getElementById('event-editor-cancel');
  if (cancelBtn) cancelBtn.addEventListener('click', focusCalendar);

  document.addEventListener('hidden.tw.modal', (e) => {
    if (e?.target?.id !== 'event-editor-modal') return;
    focusCalendar();
  });

  // -----------------------------
  // Load / Save calendar filter prefs
  // -----------------------------
  fetch('/api/user/calendar-preferences', {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  })
    .then((r) => r.json())
    .then((res) => {
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
        const cb = document.getElementById(id);
        if (!cb) return;
        if (val === null) return;
        cb.checked = !!val;
      });
    })
    .catch((err) => console.error('Failed to load calendar prefs', err));

  function saveCalendarPrefs() {
    const payload = {
      show_rfm: document.getElementById('filter-show-rfm')?.checked ?? null,
      show_installations: document.getElementById('filter-show-installations')?.checked ?? null,
      show_warehouse: document.getElementById('filter-show-warehouse')?.checked ?? null,
      show_team: document.getElementById('filter-show-team')?.checked ?? null,
      show_availability: document.getElementById('filter-show-availability')?.checked ?? null,
    };

    const csrf = getCsrf();

    return fetch('/api/user/calendar-preferences', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify(payload),
    })
      .then((r) => r.json())
      .then((res) => {
        if (!res.success) console.warn('Saving calendar prefs failed', res);
        return res;
      })
      .catch((err) => console.error('Failed to save calendar prefs', err));
  }

  // -----------------------------
  // Calendar init
  // -----------------------------
  const calendar = new Calendar(el, {
    plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
    timeZone: 'America/Vancouver',
    initialView: 'dayGridMonth',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay',
    },

eventSources: [
  {
    id: 'fm-feed',
    events: async (info, successCallback, failureCallback) => {
      try {
        console.log('[events feed called]', { start: info.startStr, end: info.endStr });
        const showRfm = document.getElementById('filter-show-rfm')?.checked;
        const showInst = document.getElementById('filter-show-installations')?.checked;
        const showWh = document.getElementById('filter-show-warehouse')?.checked;
        const showTeam = document.getElementById('filter-show-team')?.checked;

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
  },
],


    height: 'auto',
    editable: false,
    selectable: true,
    selectMirror: true,

    // -----------------------------
    // Create (select)
    // -----------------------------
    select: (selectionInfo) => {
      const modalEl = document.getElementById('event-editor-modal');
      const titleEl = document.getElementById('event-editor-title');
      const calEl = document.getElementById('event-editor-calendar');
      const subjectEl = document.getElementById('event-editor-subject');
      const allDayEl = document.getElementById('event-editor-all-day');
      const startEl = document.getElementById('event-editor-start');
      const endEl = document.getElementById('event-editor-end');
      const locEl = document.getElementById('event-editor-location');
      const notesEl = document.getElementById('event-editor-notes');
      const errEl = document.getElementById('event-editor-error');
      const idEl = document.getElementById('event-editor-id');

      if (!modalEl || !calEl || !subjectEl || !startEl || !endEl) {
        console.warn('[calendar] event editor modal elements missing');
        selectionInfo.view.calendar.unselect();
        return;
      }

      // New event mode
      if (idEl) idEl.value = '';
      document.getElementById('event-editor-delete')?.classList.add('hidden');

      if (errEl) {
        errEl.textContent = '';
        errEl.classList.add('hidden');
      }

      if (titleEl) titleEl.textContent = 'New event';

      // Populate calendar dropdown
      const options = Array.isArray(window.FM_CALENDAR_OPTIONS) ? window.FM_CALENDAR_OPTIONS : [];
      calEl.innerHTML = '';
      options.forEach((opt) => {
        const o = document.createElement('option');
        o.value = String(opt.id);
        o.textContent = opt.label || String(opt.id);
        calEl.appendChild(o);
      });

      // Clear fields
      subjectEl.value = '';
      if (locEl) locEl.value = '';
      if (notesEl) notesEl.value = '';

      // All-day default (month view often implies all-day)
      if (allDayEl) {
        const viewType = selectionInfo.view?.type || '';
        const defaultAllDay = !!selectionInfo.allDay && viewType.startsWith('dayGrid');
        allDayEl.checked = defaultAllDay;
      }

      // Prefill start/end using startStr/endStr (timezone-safe)
      const startVal = toLocalInputFromStr(selectionInfo.startStr, '09:00');
      let endVal = toLocalInputFromStr(selectionInfo.endStr, '10:00');

      // Default to 1 hour for timed selections if missing/short (<= 30 mins)
      if (!selectionInfo.allDay && startVal) {
        const startDate = new Date(startVal);
        const endDate = endVal ? new Date(endVal) : null;
        const diffMs = endDate ? endDate.getTime() - startDate.getTime() : 0;

        if (!endDate || diffMs <= 30 * 60 * 1000) {
          const bumped = new Date(startDate.getTime() + 60 * 60 * 1000);
          endVal = toDatetimeLocal(bumped);
        }
      }

      startEl.value = startVal || '';
      endEl.value = endVal || '';

      document.getElementById('event-editor-modal-init')?.click();
    },

    // -----------------------------
    // Edit (click)
    // -----------------------------
    eventClick: (info) => {
      info.jsEvent.preventDefault();

//log 
console.log('[eventClick]', {
  id: info.event.id,
  title: info.event.title,
  start: info.event.start,
  end: info.event.end,
  allDay: info.event.allDay,
  extendedProps: info.event.extendedProps
});
// end log. 

      const event = info.event;
      const ext = event.extendedProps || {};

      const titleEl = document.getElementById('event-editor-title');
      const errEl = document.getElementById('event-editor-error');
      const idEl = document.getElementById('event-editor-id');
      const calEl = document.getElementById('event-editor-calendar');
      const subjectEl = document.getElementById('event-editor-subject');
      const locEl = document.getElementById('event-editor-location');
      const notesEl = document.getElementById('event-editor-notes');
      const startEl = document.getElementById('event-editor-start');
      const endEl = document.getElementById('event-editor-end');
      const allDayEl = document.getElementById('event-editor-all-day');

      if (titleEl) titleEl.textContent = 'Edit event';
      if (errEl) errEl.classList.add('hidden');

      if (idEl) idEl.value = event.id || '';
      if (subjectEl) subjectEl.value = event.title || '';
      if (locEl) locEl.value = ext.location || '';
      if (notesEl) notesEl.value = ext.description || '';

      // Populate calendar dropdown (so it always has options)
      const options = Array.isArray(window.FM_CALENDAR_OPTIONS) ? window.FM_CALENDAR_OPTIONS : [];
      if (calEl) {
        calEl.innerHTML = '';
        options.forEach((opt) => {
          const o = document.createElement('option');
          o.value = String(opt.id);
          o.textContent = opt.label || String(opt.id);
          calEl.appendChild(o);
        });

        // Select the calendar if provided by feed as extendedProp
        const calId = ext.microsoft_calendar_id || '';
        if (calId) calEl.value = String(calId);
      }

		if (allDayEl) allDayEl.checked = !!event.allDay;

		if (event.allDay) {
		  // Use raw dates from feed to avoid timezone shifts
		  const sd = ext.start_date; // 'YYYY-MM-DD'
		  const ed = ext.end_date;   // 'YYYY-MM-DD'

		  if (startEl) startEl.value = sd ? `${sd}T00:00` : '';
		  if (endEl) endEl.value = ed ? `${ed}T00:00` : '';
		} else {
		  if (startEl) startEl.value = toDatetimeLocal(event.start);
		  if (endEl) endEl.value = toDatetimeLocal(event.end);
		}

      // Show delete in edit mode
      document.getElementById('event-editor-delete')?.classList.remove('hidden');

      const modalInstance = window.FlowbiteInstances?.getInstance('Modal', 'event-editor-modal');
      if (modalInstance) modalInstance.show();
      else document.getElementById('event-editor-modal-init')?.click();
    },
  });

  // -----------------------------
  // Filter change events
  // -----------------------------
  [
    'filter-show-rfm',
    'filter-show-installations',
    'filter-show-warehouse',
    'filter-show-team',
    'filter-show-availability',
  ].forEach((id) => {
    const cb = document.getElementById(id);
    if (!cb) return;
    cb.addEventListener('change', async () => {
      await saveCalendarPrefs();
      calendar.refetchEvents();
    });
  });

  // -----------------------------
  // Save handler (Create + Edit)
  // -----------------------------
  (function bindEventEditorSave() {
    const saveBtn = document.getElementById('event-editor-save');
    const idEl = document.getElementById('event-editor-id');

    if (!saveBtn) return;

    saveBtn.addEventListener('click', async (e) => {
  e.preventDefault();
  e.stopPropagation();
      const id = (idEl?.value || '').trim();
      const isEdit = !!id;

      const calEl = document.getElementById('event-editor-calendar');
      const subjectEl = document.getElementById('event-editor-subject');
      const allDayEl = document.getElementById('event-editor-all-day');
      const startEl = document.getElementById('event-editor-start');
      const endEl = document.getElementById('event-editor-end');
      const locEl = document.getElementById('event-editor-location');
      const notesEl = document.getElementById('event-editor-notes');
      const errEl = document.getElementById('event-editor-error');

      const showError = (msg) => {
        if (!errEl) return;
        errEl.textContent = msg || 'Save failed.';
        errEl.classList.remove('hidden');
      };

      if (errEl) {
        errEl.textContent = '';
        errEl.classList.add('hidden');
      }

      const title = (subjectEl?.value || '').trim();
      const startVal = startEl?.value || '';
      let endVal = endEl?.value || '';
      const isAllDay = !!allDayEl?.checked;

      // Always require microsoft_calendar_id (backend update uses it too)
const microsoftCalendarId = parseInt(calEl?.value || '0', 10);

if (!microsoftCalendarId || Number.isNaN(microsoftCalendarId)) {
  showError('Please choose a calendar.');
  return;
}

      if (!title) {
        showError('Title is required.');
        return;
      }

      if (!startVal) {
        showError('Start is required.');
        return;
      }

      // If end missing, default to +1 hour (timed)
      if (!endVal) {
        const d = new Date(startVal);
        d.setHours(d.getHours() + 1);
        endVal = toDatetimeLocal(d);
        if (endEl) endEl.value = endVal;
      }

      if (!endVal) {
        showError('End is required.');
        return;
      }

      const payload = {
  microsoft_calendar_id: microsoftCalendarId, // always send
  title,
  start: startVal,
  end: endVal,
  location: (locEl?.value || '').trim(),
  notes: notesEl?.value || '',
  is_all_day: isAllDay ? 1 : 0,
};

      const csrf = getCsrf();

      const originalText = saveBtn.textContent;
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      try {
        const updateTpl = window.FM_UPDATE_EVENT_URL_TEMPLATE || '/pages/calendar/events/__ID__';

const url = isEdit
  ? updateTpl.replace('__ID__', encodeURIComponent(id))
  : (window.FM_CREATE_EVENT_URL || '/pages/calendar/events');

        const method = isEdit ? 'PATCH' : 'POST';

        const res = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          },
          body: JSON.stringify(payload),
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw new Error(json?.message || json?.error?.message || `Save failed (HTTP ${res.status}).`);
        }

        document.getElementById('event-editor-close')?.click();

		calendar.unselect();
		calendar.getEventSourceById('fm-feed')?.refetch();

		await syncNowSilently();
		calendar.getEventSourceById('fm-feed')?.refetch();
      } catch (err) {
        console.error(err);
        showError(err?.message || 'Save failed. Check console for details.');
      } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = originalText || 'Save';
      }
    });
  })();

  // -----------------------------
  // Delete handler
  // -----------------------------
  (function bindEventEditorDelete() {
    const deleteBtn = document.getElementById('event-editor-delete');
    const idEl = document.getElementById('event-editor-id');

    if (!deleteBtn || !idEl) return;

    deleteBtn.addEventListener('click', async () => {
      const id = (idEl.value || '').trim();
      if (!id) return;

      const ok = window.confirm('Delete this event permanently?');
      if (!ok) return;

      const csrf = getCsrf();

      try {
        const res = await fetch(`/pages/calendar/events/${encodeURIComponent(id)}`, {
          method: 'DELETE',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            Accept: 'application/json',
          },
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          throw new Error(json?.message || 'Delete failed.');
        }

        document.getElementById('event-editor-close')?.click();

        calendar.unselect();
        calendar.refetchEvents();

        await syncNowSilently();
        calendar.refetchEvents();
      } catch (err) {
        console.error(err);
        alert(err?.message || 'Delete failed.');
      }
    });
  })();

window.__FC = calendar;

  calendar.render();
});
