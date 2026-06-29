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

---

## Bug: syncNow() Created Orphaned CalendarEvent Rows on Every Sync Cycle

**Diagnosed and fixed: 2026-06-29**

### Root Cause

`syncNow()` in `MicrosoftCalendarConnectController` had a mismatch between its lookup logic and its upsert logic:

```php
// LOOKUP — correctly scoped to this user's account (4 columns)
ExternalEventLink::where('provider', 'microsoft')
    ->where('microsoft_account_id', $account->id)
    ->where('external_calendar_id', $cal->calendar_id)
    ->where('external_event_id', $externalEventId)
    ->first();

// UPSERT — NOT user-scoped (only 2 columns in the match keys)
ExternalEventLink::updateOrCreate(
    ['provider' => 'microsoft', 'external_event_id' => $externalEventId],
    ['microsoft_account_id' => $account->id, ...]
);
```

Because `microsoft_account_id` was in the **update payload** rather than the **match keys**, two users syncing the same shared calendar event would each fail to find the other's link. The upsert would bounce ownership of the single `ExternalEventLink` row between users — while creating a brand-new orphaned `CalendarEvent` row for the loser on every sync cycle.

Compounding this: the original migration defined a `UNIQUE(provider, external_event_id)` constraint that **was never applied to the live database**, so there was no DB-level guard preventing runaway inserts.

### Scale of Damage

By the time the bug was fixed, `calendar_events` had accumulated **~5.56 million orphaned rows** (no linked `ExternalEventLink`). The most recent orphans had `created_at = 2026-05-10 18:50:55`. These rows are inert but waste space. A separate cleanup pass will remove them once the fix is confirmed stable.

### Fix Applied

**Migration** `2026_06_29_200001_fix_external_event_links_unique_constraint`:
- Added `UNIQUE(provider, microsoft_account_id, external_event_id)` named `eel_provider_account_event_unique`
- The 2-column unique was not present in the live DB so nothing needed to be dropped

**Controller** `MicrosoftCalendarConnectController::syncNow()` (~line 445):
- Moved `microsoft_account_id` from the update payload into the match keys array, making the upsert consistent with the lookup above it

```php
// AFTER FIX
ExternalEventLink::updateOrCreate(
    [
        'provider'             => 'microsoft',
        'microsoft_account_id' => $account->id,
        'external_event_id'    => $externalEventId,
    ],
    [
        'external_calendar_id' => $cal->calendar_id,
        'calendar_event_id'    => $calendarEvent->id,
        'last_synced_at'       => now(),
    ]
);
```

### Verification Baseline (pre-fix sync)

| Metric | Value |
|--------|-------|
| Orphaned `CalendarEvent` count | 5,564,766 |
| Latest orphan `created_at` | 2026-05-10 18:50:55 |
| Latest `ExternalEventLink.last_synced_at` | 2026-05-10 18:51:03 |

After the next sync cycle, re-run these tinker queries to confirm no new orphans:

```php
// Count must stay at 5,564,766
\App\Models\CalendarEvent::doesntHave('externalLink')->count();

// Latest created_at must stay at 2026-05-10 18:50:55
\App\Models\CalendarEvent::doesntHave('externalLink')
    ->orderByDesc('created_at')->limit(5)->get(['id','owner_user_id','title','created_at']);

// last_synced_at must advance (proves updateOrCreate hits existing rows)
\App\Models\ExternalEventLink::orderByDesc('last_synced_at')
    ->limit(5)->get(['id','microsoft_account_id','calendar_event_id','last_synced_at']);
```

### Other Code Audited

All other `ExternalEventLink` write sites (`CalendarEventController`, `GraphCalendarService::persistLocalEvent()`, `RfmController`, `PurchaseOrderController`, `WorkOrderController`) use `create()` only — called once per event immediately after `createEvent()` pushes a new Graph event. No second copy of this bug exists.

### Cleanup (deferred)

Do not run any DELETE on `calendar_events` until the fix has been confirmed stable across at least one full sync cycle. The orphaned rows are inert and will be cleaned up as a separate step.

---

End of context file.

