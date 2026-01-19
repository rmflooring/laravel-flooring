# Calendar Auto-Sync Optimization – Context File

## Current Behavior (as of this session)
- Calendar create flow now automatically triggers **Sync Now** after a successful event creation.
- This pulls events from Microsoft Graph into the local database.
- After sync completes, the calendar feed is refreshed.
- There is a visible delay (a few seconds), which is normal given the current implementation.

## Why the Delay Exists
The current Sync Now endpoint:
- Loops through **all enabled calendars**
- Fetches events from Microsoft Graph for each
- Upserts them into the local database

This is correct behavior, but it is heavier than necessary when only a single calendar was modified.

---

## Planned Improvement (Future Work)
Optimize the sync flow so that:

### 1. Backend
Modify the Sync Now endpoint to accept:
```
?microsoft_calendar_id=XX
```

When provided, the backend should:
- Sync **only that calendar**
- Skip all other calendars

### 2. Frontend
When creating/editing/deleting an event:
- Pass the selected calendar ID to Sync Now
- Example:
```
POST /settings/integrations/microsoft/sync-now?microsoft_calendar_id=25
```

### 3. Result
- Much faster sync
- No unnecessary API calls
- No unnecessary DB upserts
- Better perceived performance

---

## Current Implementation Status
- Auto-sync after CREATE: ✅
- Auto-sync after EDIT: ⏳ (not yet wired)
- Auto-sync after DELETE: ⏳ (not yet wired)
- Single-calendar sync optimization: ⏳ (planned)

---

## Files Involved
### Frontend
- `resources/js/pages/calendar.js`

### Blade
- `resources/views/pages/calendar/index.blade.php`

### Backend
- Microsoft sync controller: `MicrosoftCalendarConnectController@syncNow`

---

## Notes
- We intentionally kept the current implementation simple and correct.
- Performance optimizations will be applied later.
- This context exists so we can safely return to this change later without rediscovering the reasoning.

---

End of context file.

