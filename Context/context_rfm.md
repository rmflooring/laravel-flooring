# RFM Module Context — RM Flooring / Floor Manager

Updated: 2026-03-29

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
| `resources/views/pages/rfms/create.blade.php` | Create RFM form with Notifications section |
| `resources/views/pages/rfms/edit.blade.php` | Edit RFM form with Notifications section + auto-check JS |
| `resources/views/pages/rfms/show.blade.php` | Read-only RFM detail view with calendar modal |
| `app/Mail/RfmCreatedMail.php` | Create notification — estimator (detailed internal) + PM (customer-facing) |
| `app/Mail/RfmUpdatedMail.php` | Update notification — estimator (change diff + details) + PM (clean summary) |
| `database/migrations/2026_03_12_190221_create_rfms_table.php` | Initial rfms table |
| `database/migrations/2026_03_12_193228_change_flooring_type_to_json_in_rfms_table.php` | Changed flooring_type from string to JSON |
| `database/migrations/2026_03_12_215426_add_site_city_and_postal_to_rfms_table.php` | Added site_city and site_postal_code columns |

### Modified files
| File | What changed |
|------|-------------|
| `routes/web.php` | Added all 6 RFM routes under `opportunities/{opportunity}` prefix |
| `resources/views/pages/opportunities/show.blade.php` | Added RFM section (table + status dropdown + View/Edit links); fixed full address display for Parent Customer and Job Site Customer; renamed "RFQ's" to "RFM's" in Job Transactions card with linked RFM entries |
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
| `deleted_at` | timestamp, nullable | soft delete column — added migration `2026_03_29` |
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
| DELETE | `pages/opportunities/{opportunity}/rfms/{rfm}` | `pages.opportunities.rfms.destroy` | `destroy` | `role_or_permission:admin\|delete rfms` |
| DELETE | `pages/opportunities/{opportunity}/rfms/{rfm}/force` | `pages.opportunities.rfms.force-destroy` | `forceDestroy` | `role:admin` (withTrashed) |

**Note:** All RFM controller methods call `abort_if($rfm->opportunity_id !== $opportunity->id, 404)` to scope RFMs to their parent opportunity.

---

## Permissions by Role

| Role | view rfms | create rfms | edit rfms | delete rfms |
|------|:---------:|:-----------:|:---------:|:-----------:|
| admin | ✓ (bypasses) | ✓ | ✓ | ✓ |
| manager | ✓ | ✓ | ✓ | ✓ |
| estimator | ✓ | ✓ | ✓ | ✓ |
| sales | ✓ | ✓ | — | — |
| reception | ✓ | — | — | — |
| accounting | ✓ | — | — | — |

Force delete (permanent) is admin-only regardless of `delete rfms` permission.

---

## Views

### create.blade.php

Sections (top to bottom, all inside a single `<form>`):

**Job Info (read-only)** — two-column grid:
- Left: Parent Customer name → PM info card (name, phone, email) if a PM is assigned to the opportunity
- Right: Job Site name → Street Address → City + Postal Code (2-col grid), pre-filled from `jobSiteCustomer`

**Measure Details** (editable):
- Estimator dropdown
- Flooring Type checkboxes (multi-select)
- Scheduled Date & Time (`datetime-local`)

**Special Instructions** — full-width textarea

**Notifications** (always visible):
- When `mail_notifications_enabled` is OFF → entire section shaded/disabled with amber message: *"Email notifications are currently disabled. Contact your admin to enable them."*
- When enabled:
  - "Notify estimator" checkbox — **default ON**; live JS hint shows selected estimator's email
  - "Notify Project Manager" checkbox — **default OFF**; shows PM name + email; disabled (greyed) if no PM with email

**SMS Notifications** (always visible):
- When `sms_enabled` OR `sms_notify_rfm_booked` is OFF → shaded/disabled with amber message
- When enabled:
  - "SMS estimator" checkbox — **default ON**; live JS hint shows selected estimator's phone
  - "SMS Project Manager" checkbox — **default ON** if PM has mobile; disabled (greyed) if no PM mobile
- Controller `create()` passes `$emailNotificationsEnabled`, `$smsEnabled`, `$smsRfmBookedEnabled`; estimators query includes `phone`

### edit.blade.php

> **Form structure:** The entire editable form is a single `<form id="rfm-edit-form">`. This was fixed — previously the address fields were in a read-only section outside the form and were not being submitted.

**Delete UI** (header, Alpine.js `x-data="{ showDelete: false }"`):
- Small trash icon button sits beside the Cancel button (only visible to users with `delete rfms`)
- Default state: grey icon, no other UI
- Click → icon turns red, inline strip appears: `Delete this RFM? Yes · No`
- Admin users also see `| Permanent` in the strip for force delete
- **Yes** → soft delete → redirect to opportunity show
- **Permanent** → force delete → redirect to opportunity show (admin only)
- No delete button on the show page — all deletion is via the edit page

**Status** (top, outside the main form) — pill buttons that each submit their own mini PATCH form to `updateStatus`.

**Main form sections:**

**Job Info (read-only display + editable address):**
- Parent Customer name (disabled input)
- PM info card if assigned
- Job Site name (disabled input)
- Street Address, City, Postal Code inputs (pre-filled from saved `rfm` values)

**Measure Details:**
- Estimator dropdown
- Flooring Type checkboxes
- Scheduled Date & Time

**Special Instructions**

**Notifications** (always visible):
- When `mail_notifications_enabled` is OFF → entire section shaded/disabled with amber message
- When enabled:
  - "Notify estimator about this change" checkbox — **default OFF, auto-checked by JS**
    - Watches `estimator_id`, `scheduled_at`, `site_address`, `site_city`, `site_postal_code` for changes
    - Hint text shows estimator's email (updates on dropdown change)
  - "Notify Project Manager about this change" checkbox — **always default OFF**
    - Shows PM name + email; disabled (greyed) if no PM with email
- JS null-guards `notifyEstimatorBox` and `estimatorHint` — safe when email notifications are disabled

**SMS Notifications** (always visible):
- When `sms_enabled` OR `sms_notify_rfm_updated` is OFF → shaded/disabled with amber message
- When enabled:
  - "SMS estimator" checkbox — **default OFF**; hint shows estimator's phone
  - "SMS Project Manager" checkbox — **default OFF**; shows PM mobile; disabled if no PM mobile
- Controller `edit()` passes `$emailNotificationsEnabled`, `$smsEnabled`, `$smsRfmUpdatedEnabled`

### show.blade.php

Read-only view. Includes:
- Status badge
- Job Info: Parent Customer + PM card, Job Site + full address
- Measure Details: estimator, flooring type pills, scheduled date/time
- Special Instructions (hidden if empty)
- Calendar section (shown if `calendar_event_id` is set): clickable link — **"[First Last initial]. scheduled in Calendar for [date/time]"** — opens the Flowbite `event-details-modal`
- Created/updated timestamps at bottom

**Calendar modal:**
- Uses `components.calendar.event-details-modal` (same Flowbite modal used on calendar index page)
- Event data built server-side as `$ceData` array in a `@php` block, then passed via `@json($ceData)`
- **Do not pass a multi-line array literal directly inside `@json()` in a `<script>` tag** — Blade's parser throws a ParseError. Always assign to a variable first.
- `Modal` constructor available globally via Flowbite CDN in `app.blade.php`

---

## Email Notifications

Both `RfmCreatedMail` and `RfmUpdatedMail` are plain PHP classes (not Laravel Mailables). They call `GraphMailService::send()` directly using the Track 1 shared mailbox.

All sends are **best-effort** — wrapped in try/catch in the controller, never block the save. Failed sends are logged to `[RFM]` channel and written to the `mail_log` table with `type: 'rfm_notification'`.

### RfmCreatedMail

**Trigger:** `RfmController::store()` — fires after the MS365 calendar block

**Constructor:** `__construct(Rfm $rfm, Opportunity $opportunity, bool $notifyEstimator = true, bool $notifyPm = false)`

**Estimator email** (internal, detailed):
- Subject: `RFM Scheduled: #JOB-NO Customer Name`
- Body includes: job number, customer, job site, estimator name, scheduled date/time, full address, flooring types, special instructions, link to RFM show page

**PM email** (customer-facing, clean):
- Subject: `RFM Scheduled: #JOB-NO Customer Name`
- Body: greeting by PM name, measurement context sentence, date/time, location, estimator name, site access reminder, signed "RM Flooring"
- No internal fields, no special instructions, no links

### RfmUpdatedMail

**Trigger:** `RfmController::update()` — fires after save, only if at least one notify checkbox was checked

**Constructor:** `__construct(Rfm $rfm, Opportunity $opportunity, array $changes = [], bool $notifyEstimator = false, bool $notifyPm = false)`

**Change detection** (done in controller before `$rfm->update()`):
Compares old vs new values for: `estimator` (name), `scheduled_at`, `site_address`, `site_city`, `site_postal_code`
Each changed field is added to `$changes` as `['Field Label' => ['from' => '...', 'to' => '...']]`

**Estimator email** (internal, detailed):
- Subject: `RFM Updated: #JOB-NO Customer Name`
- Body: intro line, `=== CHANGES ===` block listing each changed field with Was/Now lines, then full current RFM details + link

**PM email** (customer-facing, clean):
- Subject: `Measurement Update: #JOB-NO Customer Name`
- Body: greeting, "updated" context sentence, current date/time + location + estimator, site access reminder
- No change diff, no internal fields, no links

---

## MS365 Calendar Integration

### How it works
On **create** (`store`) and **edit** (`update`), the controller attempts (best-effort) to sync the MS365 calendar event. Three private helpers handle all calendar work:

- `buildRfmEventData(Rfm, Opportunity)` — single source of truth for event payload (title, start, end, location, notes via `CalendarTemplateService`)
- `syncCalendarCreate(Rfm, Opportunity)` — finds the user's connected account + RFM/Measures calendar, calls `GraphCalendarService::createEvent()` + `persistLocalEvent()`, saves `microsoft_calendar_id` + `calendar_event_id` back on the RFM
- `syncCalendarUpdate(Rfm, Opportunity)` — if `calendar_event_id` exists: loads `calendarEvent.externalLink`, calls `GraphCalendarService::updateEvent()` (PATCH), updates local `CalendarEvent` record. If no event exists yet, falls through to `syncCalendarCreate()`.

**Event details:**
- **Title/Notes:** rendered via `CalendarTemplateService` (`rfm_calendar` template)
- **Start:** `scheduled_at`; **End:** `scheduled_at + 2 hours`
- **Location:** street, city, postal code joined by comma

### Important caveats
- **Status changes sync the calendar** — `updateStatus()` calls `syncCalendarDelete()` for `cancelled`/`completed`; calls `syncCalendarUpdate()` for `confirmed`/`pending`.
- **Missing ExternalEventLink self-heals** — if `syncCalendarUpdate` finds a `calendar_event_id` but no `ExternalEventLink` (can happen after server migration or data loss), it falls through to `syncCalendarCreate` to create a fresh event.
- **Calendar group_id is hardcoded** — `b8483c56-fc4b-4734-8011-335b88c7e4ad` in `syncCalendarCreate()`. If the RFM/Measures calendar is ever recreated, this will silently fail (logged as a warning).
- All calendar failures are caught and logged — never block the save.
- On token refresh failure, `GraphCalendarService::ensureAccessToken()` now marks `is_connected = false` + `disconnected_at = now()` on the account before throwing, so future syncs skip cleanly.

---

## Current Status — What Works

- [x] Create RFM (form + validation + DB save)
- [x] MS365 calendar event created on store
- [x] Edit RFM (form pre-filled, DB save — address fields now correctly inside the form)
- [x] Status update via pill buttons (edit page) or inline dropdown (opportunity show page)
- [x] Show RFM detail page (read-only)
- [x] Full address fields (street, city, postal code) on create/edit/show
- [x] Address pre-filled from job site customer on create
- [x] PM info displayed in Job Info section (from opportunity)
- [x] RFM table on opportunity show page with View/Edit links and inline status dropdown
- [x] Permissions seeded and assigned to all roles
- [x] Job Transactions card on opportunity show page — "RFM's" with clickable date links + status badges
- [x] Calendar event modal on RFM show page — estimator name (first + last initial) + date/time, opens Flowbite event details modal
- [x] Email notifications on **create** — estimator (default ON, detailed internal), PM (default OFF, customer-facing); checkboxes shown in Notifications section
- [x] Email notifications on **edit** — estimator (auto-checked by JS when key fields change, shows change diff), PM (always OFF by default); both show target email as hint
- [x] SMS notifications on **edit** — estimator + PM checkboxes (both default OFF); shown only when `sms_notify_rfm_updated` is enabled; phone hint updates on estimator dropdown change
- [x] `RfmUpdatedMail` — new class, separate subjects and bodies for estimator vs PM
- [x] **Mobile RFM view** — `GET /m/rfm/{rfm}` → `mobile.rfms.show`; controller `Mobile\RfmController::show()`; view `resources/views/mobile/rfms/show.blade.php`
- [x] **Mobile photo upload** — `POST /m/rfm/{rfm}/photos` → `mobile.rfms.upload-photos`; uploads to `OpportunityDocument` (same as mobile WO pattern)
- [x] **"Mobile View" button** on desktop RFM show page header (green, links to mobile page)
- [x] **`{{rfm_link}}`** tag — resolves to mobile RFM URL; added to `EmailTemplate::TAGS` + `DEFAULTS` (rfm_created, rfm_updated), `SmsTemplate::TAGS` + `DEFAULTS` (rfm_booked, rfm_reminder), `RfmCreatedMail`/`RfmUpdatedMail` estimator bodies, and SMS `$vars` in `RfmController::store()` and `SendSmsReminders`
- [x] **MS365 calendar sync on edit** — `update()` now calls `syncCalendarUpdate()` which PATCHes the existing event or creates one if missing; event data built via shared `buildRfmEventData()` helper
- [x] **MS365 token expiry notification** — `GraphCalendarService::ensureAccessToken()` marks account disconnected on refresh failure; persistent amber banner in `app.blade.php` prompts reconnect; yellow flash warning shown on RFM show page if calendar sync fails
- [x] **Soft delete** — `Rfm` model uses `SoftDeletes`; `deleted_at` column added via migration
- [x] **Delete RFM** — `destroy()` soft-deletes and cancels MS365 calendar event (best-effort via `syncCalendarDelete()`); redirect to opportunity show
- [x] **Force delete** (admin only) — `forceDestroy()` permanently removes RFM + local `CalendarEvent` record + MS365 event
- [x] **Delete UI** — trash icon toggle on edit page header; inline "Delete? Yes / No / Permanent" strip; no delete button on show page
- [x] **RFM index** — delete button in Action column hidden by default; trash icon toggle in column header reveals/hides them (Alpine.js); `delete rfms` permission gated
- [x] **RFM index** — "Site Address" column renamed "Site Info"; now shows job site customer name (bold) above the address; `jobSiteCustomer` eager-loaded in `index()` query; container widened to `max-w-screen-2xl`
- [x] **RFM index column sorting** — clickable sortable headers via `?sort=field&dir=asc|desc`; sortable columns: `customer_name` (via `leftJoin` on `customers`, uses `COALESCE(NULLIF(company_name,''),name)`), `scheduled_at`, `status`, `site_city`; default order: `scheduled_at DESC`; uses shared `admin.partials.sort-link` partial (▲/▼ indicators, preserves all filters via `withQueryString()`); non-relational filters prefixed with `rfms.` to avoid ambiguous column errors with the join
- [x] **Status change → calendar sync** — `updateStatus()` now syncs MS365: `cancelled`/`completed` → delete event; `confirmed`/`pending` → update event (or recreate if missing)
- [x] **ExternalEventLink self-heal** — `syncCalendarUpdate()` recreates the MS365 event via `syncCalendarCreate()` when the link record is missing (data gap from server migration)

---

## Mobile RFM Page (`/m/rfm/{id}`)

- **Controller**: `app/Http/Controllers/Mobile/RfmController.php`
- **View**: `resources/views/mobile/rfms/show.blade.php`
- **Routes**: `mobile.rfms.show` (GET) + `mobile.rfms.upload-photos` (POST)
- **Permission**: `view rfms` (same as desktop)
- **Sections**: RFM identity card (status badge + scheduled date/time in blue), Job Site card (customer name + Google Maps link), Measure Details (estimator, flooring type pills, PM), Special Instructions (amber, only if set), Add Measure Photos, View Job Photos
- **Photo upload**: stores to `OpportunityDocument` under `opportunities/{id}/` on `public` disk, category `media` — same as mobile WO; redirects to `mobile.opportunity.photos` on success
- RFM has direct `opportunity_id` so no sale lookup needed (simpler than WO path)

---

## What Still Needs to Be Done

### High priority

### Medium priority
3. **RFM → Estimate link** — from the RFM show page, a shortcut to create an estimate pre-filled with job/customer info.

### Low priority
4. **Province/state field** — address has street, city, postal code but no province.
5. **Multiple RFMs per opportunity** — data model supports it; UI doesn't indicate which RFM an estimate came from.

---

## Known Issues / Things to Watch Out For

- **`flooring_type` is JSON** — was a plain string column, migrated to JSON. The model casts it as `array`. Always treat as array, never string.
- **Route order matters** — `rfms/create` must be registered before `rfms/{rfm}` in `web.php`. Already correct — don't reorder.
- **`abort_if` scope check** — every controller method accepting `{rfm}` must include `abort_if($rfm->opportunity_id !== $opportunity->id, 404)`.
- **Calendar group_id is hardcoded** — `b8483c56-fc4b-4734-8011-335b88c7e4ad` in `RfmController::syncCalendarCreate()`.
- **Soft-deleted RFMs** — the index query uses `Rfm::with(...)` which automatically excludes soft-deleted records. No `withTrashed()` scoping needed unless building a trash/restore UI.
- **`@json()` + multi-line arrays** — never pass a multi-line PHP array literal directly into `@json()` inside a `<script>` tag. Build the array in a `@php` block first, then pass the variable.
