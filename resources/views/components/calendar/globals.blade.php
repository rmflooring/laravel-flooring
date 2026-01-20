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
