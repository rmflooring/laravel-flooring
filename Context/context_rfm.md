# RFM Module Context — RM Flooring / Floor Manager

Updated: 2026-03-12

---

## What is an RFM?

A **Request for Measure** (RFM) is a scheduled site visit where an estimator goes to the job site to measure the space before producing an estimate. RFMs belong to an Opportunity and sit between the Opportunity and Estimate stages in the job lifecycle:

```
Opportunity → RFM → Estimate → Sale → Invoice
```

One opportunity can have multiple RFMs (e.g. re-schedules, or multiple site visits for large jobs).

---

## Files Created or Modified

### New files
| File | Purpose |
|------|---------|
| `app/Models/Rfm.php` | Rfm model — statuses, flooring types, relationships, audit hooks |
| `app/Http/Controllers/Pages/RfmController.php` | CRUD controller — create, store, show, edit, update, updateStatus |
| `resources/views/pages/rfms/create.blade.php` | Create RFM form |
| `resources/views/pages/rfms/edit.blade.php` | Edit RFM form |
| `resources/views/pages/rfms/show.blade.php` | Read-only RFM detail view |
| `database/migrations/2026_03_12_190221_create_rfms_table.php` | Initial rfms table |
| `database/migrations/2026_03_12_193228_change_flooring_type_to_json_in_rfms_table.php` | Changed flooring_type from string to JSON |
| `database/migrations/2026_03_12_215426_add_site_city_and_postal_to_rfms_table.php` | Added site_city and site_postal_code columns |

### Modified files
| File | What changed |
|------|-------------|
| `routes/web.php` | Added all 6 RFM routes under `opportunities/{opportunity}` prefix |
| `resources/views/pages/opportunities/show.blade.php` | Added RFM section (table + status dropdown + View/Edit links); fixed full address display for Parent Customer and Job Site Customer |
| `database/seeders/PermissionsSeeder.php` | Added `view rfms`, `create rfms`, `edit rfms` permissions |
| `database/seeders/RolesSeeder.php` | Assigned RFM permissions to roles |

---

## Database Schema

### Table: `rfms`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint, PK | |
| `opportunity_id` | FK → opportunities | cascade delete |
| `estimator_id` | FK → employees | required |
| `parent_customer_id` | FK → customers, nullable | copied from opportunity at create time |
| `job_site_customer_id` | FK → customers, nullable | copied from opportunity at create time |
| `site_address` | string, nullable | street address — editable, pre-filled from job site customer |
| `site_city` | string, nullable | city — pre-filled from job site customer |
| `site_postal_code` | string, nullable | postal code — pre-filled from job site customer |
| `flooring_type` | JSON | array of strings e.g. `["Carpet", "Tile"]` |
| `scheduled_at` | datetime | required |
| `special_instructions` | text, nullable | |
| `status` | string | default `pending`; see lifecycle below |
| `microsoft_calendar_id` | FK → microsoft_calendars, nullable | set after Graph API call succeeds |
| `calendar_event_id` | FK → calendar_events, nullable | local CalendarEvent record |
| `created_by` | FK → users, nullable | set automatically on create |
| `updated_by` | FK → users, nullable | updated automatically on save |
| `created_at` / `updated_at` | timestamps | |

### Status lifecycle
```
pending → confirmed → completed
                    ↘ cancelled
```
Constants defined in `Rfm::STATUSES = ['pending', 'confirmed', 'completed', 'cancelled']`

### Flooring types
Constants defined in `Rfm::FLOORING_TYPES`:
`Carpet`, `Hardwood`, `Vinyl / LVP`, `Tile`, `Laminate`, `Other`

---

## Routes

All routes are nested under `pages/opportunities/{opportunity}/` and carry the `pages.` name prefix.

| Method | URI | Route name | Controller method | Middleware |
|--------|-----|-----------|-------------------|-----------|
| GET | `pages/opportunities/{opportunity}/rfms/create` | `pages.opportunities.rfms.create` | `create` | `role_or_permission:admin\|create rfms` |
| POST | `pages/opportunities/{opportunity}/rfms` | `pages.opportunities.rfms.store` | `store` | `role_or_permission:admin\|create rfms` |
| GET | `pages/opportunities/{opportunity}/rfms/{rfm}` | `pages.opportunities.rfms.show` | `show` | `role_or_permission:admin\|view rfms` |
| GET | `pages/opportunities/{opportunity}/rfms/{rfm}/edit` | `pages.opportunities.rfms.edit` | `edit` | `role_or_permission:admin\|edit rfms` |
| PATCH | `pages/opportunities/{opportunity}/rfms/{rfm}` | `pages.opportunities.rfms.update` | `update` | `role_or_permission:admin\|edit rfms` |
| PATCH | `pages/opportunities/{opportunity}/rfms/{rfm}/status` | `pages.opportunities.rfms.updateStatus` | `updateStatus` | `role_or_permission:admin\|edit rfms` |

**Note:** All RFM controller methods call `abort_if($rfm->opportunity_id !== $opportunity->id, 404)` to scope RFMs to their parent opportunity.

---

## Permissions by Role

| Role | view rfms | create rfms | edit rfms |
|------|:---------:|:-----------:|:---------:|
| admin | ✓ (bypasses) | ✓ | ✓ |
| estimator | ✓ | ✓ | ✓ |
| sales | ✓ | ✓ | — |
| reception | ✓ | — | — |
| accounting | ✓ | — | — |

---

## Views

### create.blade.php / edit.blade.php
Layout has two sections:

**Job Info (read-only)** — two-column grid:
- Left: Parent Customer name → PM info card (name, phone, email) if a PM is assigned to the opportunity
- Right: Job Site name → Street Address input → City + Postal Code inputs (2-col grid)
  - On create: address fields pre-filled from `jobSiteCustomer->address/city/postal_code`
  - On edit: address fields pre-filled from saved `rfm->site_address/city/postal_code`

**Measure Details** (editable):
- Estimator dropdown (from employees table)
- Flooring Type checkboxes (multi-select)
- Scheduled Date & Time (datetime-local input)

**Special Instructions** — full-width textarea

### show.blade.php
Read-only view of all RFM fields. Layout mirrors create/edit Job Info structure. Includes:
- Status badge
- Job Info: Parent Customer + PM, Job Site + full address
- Measure Details: estimator, flooring type pills, scheduled date/time
- Special Instructions (hidden if empty)
- Calendar sync indicator (shown if `calendar_event_id` is set)
- Created/updated timestamps at bottom

### edit.blade.php (extra)
Status section at top — clickable pill buttons that submit a PATCH to `updateStatus` individually (no page reload required beyond form submit). Each button is its own mini form.

---

## MS365 Calendar Integration

### How it works
When an RFM is **created** (`store`), the controller attempts (best-effort — never blocks the save) to:

1. Find a connected `MicrosoftAccount` for the current user
2. Find the `MicrosoftCalendar` with `group_id = 'b8483c56-fc4b-4734-8011-335b88c7e4ad'` (the "RM–RFM/Measures" calendar)
3. Build a calendar event:
   - **Title:** `RFM #[job_no]: [Customer] – [Flooring Types]`
   - **Start:** `scheduled_at`
   - **End:** `scheduled_at + 2 hours`
   - **Location:** full address (street, city, postal code joined by comma)
   - **Notes:** Estimator name, full address, special instructions
4. Call `GraphCalendarService::createEvent()` to push to MS365
5. Call `GraphCalendarService::persistLocalEvent()` to create a local `CalendarEvent` record
6. Save `microsoft_calendar_id` and `calendar_event_id` back on the RFM

### Important caveats
- **Edit does NOT sync back to MS365** — editing an RFM (changing date, estimator, etc.) updates the DB record but does NOT update the calendar event in MS365. This is a known gap — needs to be implemented.
- **Status changes do NOT touch the calendar event** — cancelling an RFM does not delete or update the MS365 event.
- **Calendar is hardcoded by group_id** — if the RFM/Measures calendar is ever recreated or its ID changes, this will silently fail (logged as a warning).
- All calendar failures are caught and logged — they never cause a 500 or block the RFM from saving.

---

## Current Status — What Works

- [x] Create RFM from opportunity (form + validation + DB save)
- [x] MS365 calendar event created on RFM store
- [x] Edit RFM (form pre-filled, DB save)
- [x] Status update via dropdown (opportunity show) or pill buttons (edit page)
- [x] Show RFM detail page (read-only)
- [x] Full address fields (street, city, postal code) on create/edit/show
- [x] Address pre-filled from job site customer on create
- [x] PM info displayed in Job Info section (from opportunity)
- [x] RFM table on opportunity show page with View/Edit links and inline status dropdown
- [x] Permissions seeded and assigned to all roles

---

## What Still Needs to Be Done

### High priority
1. **Sync MS365 calendar event on edit** — when the RFM's date, estimator, address, or flooring type changes, update the existing calendar event via Graph API. Use `rfm->calendarEvent->external_id` to identify the event.
2. **Delete RFM + cancel calendar event** — add a delete route/button. On delete (or status → cancelled), delete or cancel the MS365 event.

### Medium priority
3. **Delete route** — currently there is no way to delete an RFM through the UI.
4. **Notification/reminder** — optionally notify the assigned estimator when an RFM is created or updated.
5. **RFM → Estimate link** — once a measure is completed, there should be a path to create an estimate directly from the RFM (pre-filling job/customer info).

### Low priority
6. **Province/state field** — address currently has street, city, postal code but no province. May be needed for completeness.
7. **Multiple RFMs per opportunity** — already supported in the data model, but the UI doesn't indicate which RFM an estimate was created from.

---

## Known Issues / Things to Watch Out For

- **`flooring_type` is JSON** — was originally a plain string column, then migrated to JSON. The model casts it as `array`. Always treat it as an array, never a string.
- **Route order matters** — `rfms/create` must be registered before `rfms/{rfm}` in `web.php` to prevent `create` being interpreted as an `{rfm}` wildcard. This is already correct.
- **`abort_if` scope check** — every controller method that accepts an `{rfm}` parameter must include `abort_if($rfm->opportunity_id !== $opportunity->id, 404)`. Don't skip this.
- **Calendar group_id is hardcoded** — `b8483c56-fc4b-4734-8011-335b88c7e4ad` is the RM–RFM/Measures calendar. This is in `RfmController::store` and will need updating if the calendar changes.
- **No `delete rfms` permission yet** — the permission doesn't exist in the seeder. Add it before building the delete route.
