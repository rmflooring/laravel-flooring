<script>
  window.FM_CALENDAR_DB_IDS = {
    installations: 1
  };
</script>

<script>
  window.FM_CALENDAR_IDS = @json($groupCalendarIds ?? []);
</script>

<script>
  window.FM_CALENDAR_OPTIONS = @json($calendarOptions ?? []);

  window.FM_CREATE_EVENT_URL = "{{ route('pages.calendar.events.store') }}";
  window.FM_MICROSOFT_SYNC_NOW_URL = "{{ route('pages.microsoft.syncNow') }}";
  window.FM_UPDATE_EVENT_URL_TEMPLATE = "{{ url('/pages/calendar/events') }}/__ID__";
</script>
