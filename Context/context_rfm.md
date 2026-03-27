# RFM Module Context — RM Flooring / Floor Manager

Updated: 2026-03-13

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

**Notifications:**
- "Notify estimator" checkbox — **default ON**
  - Live JS hint below shows the selected estimator's email (updates on dropdown change)
  - If estimator has no email on record, hint says so
- "Notify Project Manager" checkbox — **default OFF**
  - Shows PM name and email as hint text
  - Rendered as disabled (greyed out) if no PM with an email is assigned to the opportunity

### edit.blade.php

> **Form structure:** The entire editable form is a single `<form id="rfm-edit-form">`. This was fixed — previously the address fields were in a read-only section outside the form and were not being submitted.

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

**Notifications:**
- "Notify estimator about this change" checkbox — **default OFF, auto-checked by JS**
  - JS watches `estimator_id`, `scheduled_at`, `site_address`, `site_city`, `site_postal_code`
  - Auto-checks this box the moment any of those fields differ from their original saved values
  - Hint text shows the estimator's email address (updates on dropdown change)
- "Notify Project Manager about this change" checkbox — **always default OFF**
  - Staff must manually check this — PM notification is always a deliberate decision
  - Shows PM name and email as hint text
  - Rendered as disabled if no PM with an email is assigned

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
When an RFM is **created** (`store`), the controller attempts (best-effort) to:

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
- **Edit does NOT sync back to MS365** — editing an RFM updates the DB record but does NOT update the calendar event. Known gap.
- **Status changes do NOT touch the calendar event** — cancelling an RFM does not cancel the MS365 event.
- **Calendar is hardcoded by group_id** — if the RFM/Measures calendar is ever recreated, this will silently fail (logged as a warning).
- All calendar failures are caught and logged — never block the save.

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
- [x] `RfmUpdatedMail` — new class, separate subjects and bodies for estimator vs PM
- [x] **Mobile RFM view** — `GET /m/rfm/{rfm}` → `mobile.rfms.show`; controller `Mobile\RfmController::show()`; view `resources/views/mobile/rfms/show.blade.php`
- [x] **Mobile photo upload** — `POST /m/rfm/{rfm}/photos` → `mobile.rfms.upload-photos`; uploads to `OpportunityDocument` (same as mobile WO pattern)
- [x] **"Mobile View" button** on desktop RFM show page header (green, links to mobile page)
- [x] **`{{rfm_link}}`** tag — resolves to mobile RFM URL; added to `EmailTemplate::TAGS` + `DEFAULTS` (rfm_created, rfm_updated), `SmsTemplate::TAGS` + `DEFAULTS` (rfm_booked, rfm_reminder), `RfmCreatedMail`/`RfmUpdatedMail` estimator bodies, and SMS `$vars` in `RfmController::store()` and `SendSmsReminders`

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
1. **Sync MS365 calendar event on edit** — when date, estimator, address, or flooring type changes, update the existing MS365 event via Graph API using `rfm->calendarEvent->external_id`.
2. **Delete RFM + cancel calendar event** — add a delete route/button and permission. On delete (or status → cancelled), delete or cancel the MS365 event. No `delete rfms` permission exists yet — add to seeder before building the route.

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
- **Calendar group_id is hardcoded** — `b8483c56-fc4b-4734-8011-335b88c7e4ad` in `RfmController::store()`.
- **No `delete rfms` permission yet** — add to `PermissionsSeeder` and `RolesSeeder` before building the delete route.
- **`@json()` + multi-line arrays** — never pass a multi-line PHP array literal directly into `@json()` inside a `<script>` tag. Build the array in a `@php` block first, then pass the variable.
