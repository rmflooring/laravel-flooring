# Installer Portal — Dev Context

Updated: 2026-03-24

---

## Overview

A mobile-first portal for subcontractor installers to access their work orders, update statuses, add notes, and upload/view job site photos. Installers log in through the same auth system as staff but are redirected to their own dashboard.

---

## Authentication & Access

- Installers use the standard login page
- `AuthenticatedSessionController::store()` checks `hasRole('installer')` → redirects to `installer.dashboard`
- The `installer` Spatie role is created in the DB and assigned automatically when linking a user to an installer record via the admin UI
- The `installer` role has the `view work orders` permission (migration `2026_03_24_040007_grant_view_work_orders_to_installer_role`)

---

## Data Model Changes

### `installers` table
- Added `user_id` (nullable unique FK → users, nullOnDelete) via migration `2026_03_24_030109_add_user_id_to_installers_table`
- `Installer::user()` belongsTo User via `user_id`

### `work_orders` table
- Added `installer_notes` (nullable text, after `notes`) via migration `2026_03_24_030109_add_installer_notes_to_work_orders_table`
- Set by the installer via the mobile WO update-status form; preserved across status updates (only updated when new notes submitted)

### New WO statuses (added 2026-03-24)
`WorkOrder::INSTALLER_STATUSES` — the subset installers can set:
```
in_progress, partial, site_not_ready, needs_levelling, needs_attention, completed
```
Full `STATUSES` and `STATUS_LABELS` updated to include: `partial`, `site_not_ready`, `needs_levelling`, `needs_attention`

---

## Routes

```php
// Installer portal (auth + verified + role:installer)
GET  /installer           → installer.dashboard   → Installer\DashboardController::index()
POST /installer/wo/{workOrder}/status → installer.wo.update-status → Installer\WorkOrderController::updateStatus()

// Mobile photo gallery (auth + verified)
GET  /m/opportunity/{opportunity}/photos → mobile.opportunity.photos → Mobile\PhotoGalleryController::show()
```

---

## Controllers

### `app/Http/Controllers/Installer/DashboardController.php`
- Resolves `Installer::where('user_id', auth()->id())->first()`
- Shows "not linked" warning if no installer found
- Queries WOs assigned to this installer:
  - `$today` — `scheduled_date = today`, non-terminal statuses
  - `$upcoming` — future dates, non-terminal statuses, ordered by date
  - `$past` — completed/cancelled/needs_attention OR past dates; 30-day default; `show_all=1` toggle
- Passes: `$installer`, `$today`, `$upcoming`, `$past`, `$showAll`

### `app/Http/Controllers/Installer/WorkOrderController.php`
- `updateStatus()`:
  1. Finds installer by `user_id = auth()->id()`; 403 if WO's `installer_id` doesn't match
  2. Validates `status` against `INSTALLER_STATUSES`; `installer_notes` optional text max 2000
  3. Updates WO; only overwrites `installer_notes` if new notes submitted
  4. Calls `notifyTeam()` → Track 1 email to `team@rmflooring.ca`
  5. Redirects to `mobile.work-orders.show` with success flash

### `app/Http/Controllers/Mobile/PhotoGalleryController.php`
- Loads `OpportunityDocument` records (`category = media`) with `creator` eager-loaded
- Maps storage URLs using `Storage::disk('public')->url($doc->path)`
- Returns `mobile.photos.show` view with `$media` collection + `$opportunity`

---

## Views

### `resources/views/installer/dashboard.blade.php`
Uses `x-mobile-layout`. Sections:
- Flash message (success)
- "Not linked" amber warning (if no installer record found)
- Header card: company name + today's date
- **Today** section (blue highlighted WO cards)
- **Upcoming** section
- **Nothing scheduled** placeholder (if both empty)
- **Past Jobs** section with "Show All" / "Show Recent" toggle

### `resources/views/installer/_wo-card.blade.php`
Partial included from dashboard. Props: `$wo`, `$statusColors`, `$highlight`.
- Date block (month abbrev + day number, large)
- Customer name + status badge
- WO number + scheduled time
- Job address (truncated)
- `installer_notes` preview (amber italic, truncated)
- Links to `mobile.work-orders.show`

### `resources/views/mobile/photos/show.blade.php`
Uses `x-mobile-layout`. Full-screen mobile photo gallery:
- 2-col thumbnail grid; each tile has uploader name + date overlay
- `openPhoto(el)` opens fullscreen lightbox
- Lightbox: top bar (uploader + date), image viewport, bottom nav (prev / counter / next)
- Swipe support via `lbTouchStart` / `lbTouchEnd` (40px threshold)

### Mobile WO show page additions (`resources/views/mobile/work-orders/show.blade.php`)
Three new cards added below the Print PDF card:
1. **Add Job Photos** (emerald) — file upload form → `mobile.work-orders.upload-photos`
2. **View Job Photos** (indigo) — links to `mobile.opportunity.photos`
3. **Update Status** (blue, collapsible Alpine.js) — only shown to installers whose record matches the WO; 2-col radio grid + notes textarea + Save button

---

## Email Notifications

On every installer status update, `notifyTeam()` sends a Track 1 email to `team@rmflooring.ca`:
- Subject: `WO {number} Completed — {customer}` or `WO {number} — {status}: {customer}`
- Body: WO#, installer, customer, address, status, optional notes, staff view URL
- Wrapped in try/catch — notification failure never blocks the status save

---

## Permissions

| Permission | How granted |
|---|---|
| `view work orders` | Assigned to `installer` role via migration `2026_03_24_040007` |
| Portal routes | Gated by `role:installer` middleware |

---

## Admin UI — Linking Users to Installers

In `/admin/users` (create or edit):
- **User Type** radio: Staff / Installer
- When **Installer** selected: dropdown of installer records (already-linked to another user shown disabled)
- On save: `installer` role synced; `installers.user_id` updated
- Switching back to Staff: `installer` role removed; `installers.user_id` cleared
- The `installer` role is hidden from the staff roles checklist

---

## Resume Prompt

> Read CLAUDE.md and Context/context_installer_portal.md. I want to continue working on the installer portal.
