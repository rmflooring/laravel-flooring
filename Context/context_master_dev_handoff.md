# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard
Updated: 2026-04-02 (session 46)

## Working style rules
- Flowbite UI required for all new pages/components.
- One step at a time.
- Do not guess routes, schema, file paths, or controller methods.
- Verify with route:list / logs / schema before changing architecture.
- Keep context files updated after meaningful progress.

---

## File Storage

Full details: `Context/context_storage.md`

### Summary
- **Primary**: NFS-mounted WD My Cloud NAS (`192.168.1.143:/nfs/app_storage` → `/mnt/nas_storage`)
- **Symlink**: `storage/app/public` → `/mnt/nas_storage` — Laravel uses `public` disk, unaware of NFS
- **Mirror**: All uploads auto-mirrored to `richard@rmflooring.ca` OneDrive via queued `MirrorFileToOneDrive` job
- **Folder naming**: `opportunities/{JobSiteName} - {job_no}/` — method: `Opportunity::storageFolderName()`
- **Health monitoring**: `nas:check-health` runs every 5 min, red banner + email alert if NAS goes offline
- **Queue worker**: `laravel-queue.service` systemd service (must be running for OneDrive mirror to work)
- **PHP config**: `/etc/php/8.3/fpm/php.ini` — adjust `upload_max_filesize` / `post_max_size` for large uploads

---

## System overview
Internal operations platform for RM Flooring using Laravel 12.

Current core modules:
- Opportunities
- RFMs (Requests for Measure) — see `Context/context_rfm.md`
- Estimates
- Sales
- Purchase Orders — see `Context/context_purchase_orders.md`
- Installers — see `Context/context_installers.md`
- **Installer Portal** (mobile) — see `Context/context_installer_portal.md`
- Documents / Media — see `Context/context_storage.md`
- Calendar (MS365 integration)
- Email system (Track 1 + Track 2) — see `Context/context_graph_mail.md`
- Email Templates (per-user + admin system) — see `Context/context_email_templates.md`
- Calendar Entry Templates (admin) — `app/Models/CalendarTemplate.php`, `app/Services/CalendarTemplateService.php`, `admin.settings.calendar-templates.*`
- **SMS Notifications** (Twilio) — see `Context/context_sms.md`
- **Document Templates** (admin-managed, PDF generation) — see `Context/context_document_templates.md`
- **Invoices** (progress billing, payments) — see `Context/context_invoices.md`
- Users / Roles / Employees
- Admin pages (tax groups, settings, email management)
- **Inventory / Warehouse / Pick Tickets** — see `Context/context_warehouse_pick_tickets.md`

---

## Email system summary
Full details in `Context/context_graph_mail.md`.

### Track 1 — Working
- Shared mailbox `reception@rmflooring.ca` via Graph API client credentials
- Used for: RFM notifications (create + edit), test sends
- Admin portal at `/admin/settings/mail`
- Controlled by `app_settings` table keys: `mail_from_address`, `mail_from_name`, `mail_reply_to`, `mail_notifications_enabled`
- All sends logged to `mail_log` table with `track=1`

### Track 2 — Working ✓
- Per-user delegated OAuth — each staff member's personal `@rmflooring.ca` MS365 account
- Admin connects each user from `/admin/settings/mail` Track 2 table (Connect button → OAuth flow)
- Per-user **Send Test** button confirmed working on live server
- Token stored encrypted on `microsoft_accounts` (`mail_connected`, `mail_connected_at` columns)
- `GraphMailService::sendAsUser(User $user, ...)` — active and tested
- Auto token refresh built in; marks `mail_connected=false` if refresh fails
- All sends logged to `mail_log` with `track=2` and `sent_from=user_email`
- **Wired into**: estimates (edit + show pages), sales (edit + show pages)
- **Not yet wired into**: invoices (module not built yet)

### Send modal features (estimates + sales)
- **To** field: quick-select buttons — Job Site email, PM email (if present), Custom text input
- **CC** field: Alpine.js pill-based multi-address CC (optional). Add via text input + Enter/Add button; remove with ✕
- `cc[]` submitted as array; validated as `nullable|email` per item; passed to `GraphMailService` as `ccRecipients` in Graph payload
- Sale modals load PM email via `opportunity.projectManager` eager-load → `$pmEmail` passed to view
- Sale `$homeownerEmail` resolves from `job_email` first, falls back to `sourceEstimate->homeowner_email`

### GraphMailService signatures
Both `send()` and `sendAsUser()` accept optional `?string $icsContent = null` as the last parameter. When provided, a `text/calendar; method=REQUEST` attachment (`invite.ics`) is added alongside any PDF attachment.

### Fallback pattern (with CC)
```php
$cc   = array_filter($request->input('cc', []));
$sent = $user->microsoftAccount?->mail_connected
    ? $mailer->sendAsUser($user, $to, $subject, $body, $type, $attachment, $cc ?: null)
    : false;
if (! $sent) {
    $mailer->send($to, $subject, $body, $type, null, $attachment, $cc ?: null); // Track 1 fallback
}
```

### Azure app permissions (same app registration for both) — both confirmed ✓
- `Mail.Send` Application — Track 1 ✓
- `Mail.Send` Delegated — Track 2 ✓ (granted for RM Flooring tenant)

### Azure redirect URIs configured
- `http://localhost/admin/settings/mail/callback` — local dev
- `https://fm.rmflooring.ca/admin/settings/mail/callback` — production
- `http://localhost/pages/settings/integrations/microsoft/callback` — calendar local
- `https://fm.rmflooring.ca/pages/settings/integrations/microsoft/callback` — calendar production

---

## RFM module summary
Full details in `Context/context_rfm.md`.

- RFMs (Requests for Measure) — scheduled site visits before producing an estimate
- Belong to an Opportunity; one opportunity can have many RFMs
- Routes: 6 routes nested under `pages/opportunities/{opportunity}/rfms/`
- MS365 calendar event created on RFM store (best-effort, non-blocking)
- Show page: clickable calendar event modal (estimator name + scheduled time)
- Job Transactions card on opportunity show: RFMs listed as clickable links
- Email notifications on create: estimator (default ON) + PM (default OFF), checkbox-driven
- Email notifications on edit: estimator auto-checked when key fields change, PM always OFF by default
- **Mobile RFM page** (2026-03-27): `GET /m/rfm/{rfm}` → `mobile.rfms.show`; controller `Mobile\RfmController`; view `resources/views/mobile/rfms/show.blade.php`
  - Shows: status, scheduled date/time, site address + Google Maps link, estimator, flooring types, PM, special instructions
  - Add Measure Photos (tap to upload → stored as `OpportunityDocument` category `media`)
  - View Job Photos (links to `mobile.opportunity.photos`)
  - "Mobile View" green button added to desktop RFM show page header
- **`{{rfm_link}}`** tag (2026-03-27): resolves to mobile RFM URL; available in email templates (rfm_created, rfm_updated) and SMS templates (rfm_booked, rfm_reminder); injected into hardcoded mail bodies and all SMS `$vars`
- **Calendar sync on edit (2026-03-29)**: `RfmController::update()` now calls `syncCalendarUpdate()` — PATCHes existing MS365 event or creates one if missing. Shared `buildRfmEventData()` helper used by both create and update paths. See `context_rfm.md` for full details.
- **MS365 token expiry notification (2026-03-29)**: `GraphCalendarService::ensureAccessToken()` marks `is_connected = false` + `disconnected_at = now()` on refresh failure. Persistent amber banner in `app.blade.php` shown whenever the user's MS account was once connected but is now disconnected (links to Settings → Integrations). Yellow `session('warning')` flash shown on RFM/WO/PO save if calendar event creation/update fails.
- **Email calendar invites (2026-03-27)**: `ICalService` generates RFC 5545 `.ics` attachments. Admin toggles `rfm_email_calendar_invite` + `wo_email_calendar_invite` on mail settings page control whether invites are included. RFM estimator emails attach invite (UID `rfm-{id}@rmflooring.ca`, +2h duration). WO installer emails attach invite when `scheduled_date` set (UID `wo-{id}@rmflooring.ca`, +4h duration). Same UID on updates overwrites the existing calendar event in the recipient's client. `GraphMailService::send()` and `sendAsUser()` accept optional `?string $icsContent = null`.

### RFM open items
1. ~~Sync MS365 calendar event when RFM is edited~~ ✓ Done (session 39)
2. ~~Delete RFM route + cancel/delete calendar event on delete~~ ✓ Done (session 40)
3. RFM → Estimate creation shortcut from the show page
4. **Auto-confirm RFM on calendar invite acceptance** — when estimator accepts the calendar invite, RFM status should automatically change from `pending` → `confirmed`. Requires Microsoft Graph change notifications (webhooks): subscribe to the estimator's calendar, listen for `created`/`updated` events matching the RFM event UID (`rfm-{id}@rmflooring.ca`), check `responseStatus.response === 'accepted'`, then update `rfm.status`. Needs a publicly accessible webhook endpoint + subscription renewal (Graph subscriptions expire max 3 days for calendar resources).

### RFM delete (session 40, 2026-03-29)
- `Rfm` model uses `SoftDeletes`; `deleted_at` added via migration
- `destroy()` — soft delete, cancels MS365 calendar event via `syncCalendarDelete()` (best-effort)
- `forceDestroy()` — admin only, permanently removes RFM + local `CalendarEvent` record + MS365 event
- Routes: `DELETE .../rfms/{rfm}` (`delete rfms` permission) + `DELETE .../rfms/{rfm}/force` (admin, `withTrashed`)
- **Delete UI**: trash icon toggle on **edit page** header only — grey by default, turns red on click, reveals inline "Delete? Yes / No / Permanent" strip; no delete button on show page
- **Index page**: delete buttons hidden by default; trash icon toggle in Action column header shows/hides them (Alpine.js `showDelete`); `x-cloak` prevents flash on load
- **Site Info column** (index): renamed from "Site Address"; now shows job site customer name (bold) + address below; `jobSiteCustomer` eager-loaded in `index()`; container widened to `max-w-screen-2xl`

---

## Sales module summary
Sales are contractual job records created from approved estimates.

Confirmed sales routes:
- `pages.sales.index` → `/pages/sales`
- `pages.sales.show` → `/pages/sales/{sale}`
- `pages.sales.edit` → `/pages/sales/{sale}/edit`
- `pages.sales.profits.save-costs` → `POST /pages/sales/{sale}/profits/save-costs`

### Delete Estimates (session 14, 2026-03-16)
- `EstimateController::destroy()` — blocks if a linked sale exists (`Sale::where('source_estimate_id', ...)->exists()`); otherwise hard-deletes (rooms + items cascade at DB level)
- Route: `DELETE pages/estimates/{estimate}` → `pages.estimates.destroy` (middleware: `permission:create estimates`)
- `Estimate` model: added `sale()` hasOne relationship
- Index: `with(['sale:id,source_estimate_id'])` eager-loaded; Delete button only shown when `$estimate->sale` is null
- When a sale is deleted the estimate becomes deletable again

### Delete Sales (session 41, 2026-03-29) — updated from session 14
- `Sale` model now uses `SoftDeletes`; `deleted_at` added via migration `2026_03_29_121400`
- `destroy()` — soft delete; blocked if any PO or WO has **ever** been created (`withTrashed()` check)
- `forceDestroy()` — admin only, permanent delete; same guard applies
- `getDeleteBlockReason(Sale)` — private shared helper used by both methods + `edit()` to pre-compute block state
- Routes: `DELETE pages/sales/{sale}` (`delete sales` permission) + `DELETE pages/sales/{sale}/force` (admin, `withTrashed`)
- Old route was gated on `permission:create estimates` — now properly gated on `role_or_permission:admin|delete sales`
- `delete sales` permission assigned to admin + manager via migration `2026_03_29_121400`
- **Delete UI**: trash icon toggle in sale **edit page** header — greyed/disabled with tooltip when blocked; active toggle when safe; inline "Delete Sale #X? Yes · No / Permanent (admin)" strip
- No delete button on sale show page
- Index: `withCount(['purchaseOrders', 'workOrders'])`; Delete button only shown when both counts are 0
- Success redirect to `pages.sales.index` with sale number in message

### Estimate → Sale transfer fix (session 14, 2026-03-16)
- `convertToSale()` was missing homeowner contact fields on older sales
- Correct mapping: `'homeowner_name' => $estimate->homeowner_name`, `'job_phone' => $estimate->homeowner_phone`, `'job_email' => $estimate->homeowner_email` (estimate uses `homeowner_*`; sale table uses `job_phone`/`job_email`)
- Sale model uses `$guarded = ['id', 'sale_number']` — all other fields mass-assignable

### Sale show page — details card (session 14, 2026-03-16)
- Two-column layout: left = Customer, PM, Job Name, Job# (bold, larger text); right = "Job Site" block with homeowner_name, job_address (`whitespace-pre-line`), job_phone, job_email (mailto)
- `buildJobAddress()` stores address as street + `\n` + city/province/postal — `whitespace-pre-line` renders it on 2 lines

Financial display logic:
- Revised Contract Total = `revised_contract_total` → `locked_grand_total` → `grand_total`
- Locked if `locked_at` is not null
- Fully invoiced if `is_fully_invoiced = true`
- Partially invoiced if `invoiced_total > 0` and not fully invoiced

---

## Profits / cost tracking
Costs must flow: Catalog → Estimate → Sale

Required source fields:
- Products: `product_styles.cost_price`
- Labour: `labour_items.cost`
- Freight: `freight_items.cost_price`

Required persisted fields on line items: `cost_price`, `cost_total`

Shared profits modal: `resources/views/components/modals/profits-modal.blade.php`
- Opens from Estimate page or Sale page
- AJAX save wired separately per context (`pages.estimates.profits.save-costs` / `pages.sales.profits.save-costs`)

### Profits open items
- Recalculate sale-level rollups after modal save (total material/labour/freight cost, profit, margin)
- Improve modal UX (Profit $, Margin %, grouped subtotals, better save-state UI)
- Estimate-side cost flow: Blade row templates must include cost fields; JS autofill should populate from catalog; estimate → sale conversion must copy costs reliably

---

## Estimates & Sales — completed this session (2026-03-13 session 3)

### Route cleanup: admin → pages
- All estimate routes moved from `/admin/estimates/` → `/pages/estimates/`
- Estimate views still live in `resources/views/admin/estimates/` (no move needed)
- All API endpoints now at `pages.estimates.api.*` (e.g. `pages.estimates.api.manufacturers`)
- `estimates/create` route must appear BEFORE `estimates/{estimate}` in routes/web.php (static before wildcard)

### Estimate/Sale JS API URLs fixed
- `public/assets/js/estimates/estimate.js` — all `/estimates/api/` hardcoded paths updated to `/pages/estimates/api/`
- `public/assets/js/sales/sale.js` — same fix
- Both create + edit blades (`admin/estimates/create.blade.php`, `admin/estimates/edit.blade.php`, `pages/sales/edit.blade.php`) set `window.FM_CATALOG_*` vars using `route('pages.estimates.api.*')` named routes

### Manufacturer query bug
- `->where('manufacturer', '!=', '')` generates broken SQL in Laravel/MariaDB (`= '\!='`)
- Fixed to `->where('manufacturer', '<>', '')` in `EstimateController::apiManufacturers` and `apiStyles`

### Dropdown UX fix
- All dropdowns (manufacturer, product-line/style, colour/product-style, freight) now show ALL available options when clicked/focused
- Previously `applyFilter()` was called on open — this filtered to the selected value, showing only 1 option
- Fixed to call `render(allItems)` on open; `applyFilter()` still fires on text input

### Product type dropdown — width + unit display (session 20, 2026-03-20)
- Sale edit blade product type input was `w-32` (128px) — names truncated to "C...", "H..." etc.
- Fixed to `w-44` (176px) in all 3 locations (existing rows, existing-room row template, new-room room template); dropdown div changed from `w-full` → `w-44` to match
- Estimate edit blade was already `w-44` — no change needed
- Product type dropdown in both `estimate.js` and `sale.js` now shows abbreviated unit code (`SF`, `SY`, `EA`) instead of full label ("Square Feet", "Square Yard") — uses `unitCode.toUpperCase()`

### PDF attachments on emails
- `barryvdh/laravel-dompdf` installed and wired
- `EstimateController::previewPdf()` and `SaleController::previewPdf()` — inline browser preview
- Routes: `GET /pages/estimates/{estimate}/pdf` and `GET /pages/sales/{sale}/pdf`
- PDF is auto-attached when sending estimate/sale email (base64 encoded via Graph API fileAttachment)
- PDF templates: `resources/views/pdf/estimate.blade.php`, `resources/views/pdf/sale.blade.php`
- Room headers: blue (`#1d4ed8`), logo: `height:100px; max-width:320px`

### Admin branding settings
- Route: `GET/PUT /admin/settings/branding`, `POST /admin/settings/branding/logo`
- Controller: `app/Http/Controllers/Admin/BrandingController.php`
- View: `resources/views/admin/settings/branding.blade.php`
- Fields: company_name, tagline, street address, city, province, postal, phone, email, website, logo
- Logo stored in `storage/public/branding/`, embedded as base64 data URI in PDFs
- Settings stored in `app_settings` table as key/value with key prefix `branding_*`

### Create estimate form
- "Homeowner" label renamed to "Site Info"
- Address split into: Street, City, Province, Postal Code (separate fields, pre-filled from job site customer)
- Controller `buildJobAddress()` assembles them into `job_address` on save
- Validation accepts `job_street`, `job_city`, `job_province`, `job_postal`

### Job site edit on opportunity edit page
- "Edit Job Site" button appears when a job site is selected
- Opens Alpine.js modal pre-filled with child customer fields
- Submits `PATCH /pages/job-sites/{customer}` → `JobSiteCustomerController::update()`
- `$jobSiteCustomers` query in `OpportunityController::edit()` must select all address fields

### Print buttons
- Added to estimate edit + show pages (opens PDF preview in new tab)
- Added to sale edit + show pages

---

## Calendar / Microsoft sync — bugs fixed (session 7, 2026-03-14)
Full details in `Context/context-calendar.md`.

- **Sync duplicate entry bug:** `syncNow()` `updateOrCreate` was searching by 4 keys; unique constraint is on `(provider, external_event_id)` only → fixed lookup keys.
- **Group calendar 404 (multi-user):** Users who subscribe to RM group calendars in Outlook but are NOT M365 group members get personal-subscription records with `group_id = null`; sync used the wrong `me/calendars/{id}` endpoint → 404. Fix: `discoverCalendars` cleans up personal-subscription duplicates when the same account already has a group record for that name. For non-member accounts, group records are copied from any account that has `group_id` set so the correct `groups/{group_id}/calendar/events` endpoint is used.
- **Known RM group IDs (full GUIDs):** Team RM `451694e6-e1d4-4b5b-9c11-6cee3c9c8ca9`, RFM/Measures `b8483c56-fc4b-4734-8011-335b88c7e4ad`, Installations `a6890136-56b9-42fc-ac2b-8e05c98c0e8c`, Warehouse `4bfd495c-4df2-4eaa-9d8c-987c4ef23b02`.
- **If a user gets 404 on group calendars:** Re-run Discover — it will stamp `group_id` onto existing enabled records in-place. If Discover doesn't fix it, check Azure AD group membership then re-discover.
- **Server migration 404 fix (session 43, 2026-04-01):** After migrating to rmserver2, all `microsoft_calendars` records had `group_id = null` because `discoverCalendars` keyed `updateOrCreate` on the group's `calendar_id` (which differs from the personal-subscription `calendar_id` in `me/calendars`), creating new disabled records instead of updating existing enabled ones. Fixed `discoverCalendars` to resolve existing records by priority: (1) matching `group_id`, (2) name-match on personal subscription records, (3) `calendar_id` match — then updates in-place, preserving `is_enabled`. DB was patched manually via tinker before the code fix was deployed.
- **New server path:** `/var/www/myapp` (was `/var/www/fm.rmflooring.ca/laravel/` on rmserver); DB name unchanged (`fm_laravel`).

---

## Work Orders module summary
Full details in `Context/context_work_orders.md`.

- WOs represent scheduled installation tasks assigned to an **Installer** (not a user) for a sale
- WO items = **labour-type sale items** with editable qty, cost, and `wo_notes`; qty tracking prevents over-scheduling
- WO number auto-generated: `{seq}-{sale_number}` (e.g. `3-8`), plain integers, no year prefix
- Tables: `work_order_items` + `work_order_item_materials` (links labour items to related material sale items)
- Create form: **room cards** with material checkboxes so installer sees which products go with each labour task
- Show page + PDF: items **grouped by room card** with house icon header
- Statuses: created → scheduled (requires installer + date) → in_progress → completed; any → cancelled
- Calendar sync: events go to **"RM – Installations" group calendar** (`group_id = a6890136-56b9-42fc-ac2b-8e05c98c0e8c`)
  - Best-effort, uses logged-in user's MS account to auth
  - Create on store, update/delete on changes
- PDF via DomPDF (`resources/views/pdf/work-order.blade.php`)
- Email to installer via Track 1 (shared mailbox) with PDF attached; `sent_at` stamped
- Permissions: view/create/edit/delete work orders → admin, coordinator, estimator, sales (view only: reception)
- WO card on sale **edit** page (below PO card); WO section on sale show page; Sale Status page fully wired
- **WO Staging / Pick Tickets (session 14–16, 2026-03-16)**: "Stage Work Order" button on WO show page creates a `staged` PickTicket with all linked material items. `staged` is a new PickTicket status (orange badge). `PickTicketService::createFromWorkOrder()` handles creation. `inventory_allocation_id` made nullable on `pick_ticket_items`. **No stock check on staging** — the inventory allocation stock check was removed in session 16; staging does not require formal allocations (receipts only need to be in the warehouse).
- **Calendar opt-out bug fix (session 14)**: `$request->boolean('sync_calendar', true)` → changed default to `false` in both `store()` and `update()` — unchecked checkbox posts no value, so `true` default was always overriding the user's opt-out.
- **Mobile WO view + QR code (2026-03-23)**: Route `GET /m/wo/{workOrder}` → `mobile.work-orders.show`; controller `Mobile\WorkOrderController::show()`; view `resources/views/mobile/work-orders/show.blade.php` (schedule, job site with Maps link, items by room). WO PDF footer has QR code as SVG base64 `<img>` (not inline SVG — conflicts with global `table {}` CSS; not PNG — no Imagick). `{{wo_link}}` tag added to `work_order` email template type; `WorkOrderController::resolveEmailTemplate()` now wires up `EmailTemplateService` for the WO send modal (was previously hardcoded).
- **Installer portal (2026-03-24)**: See `Context/context_installer_portal.md`. Mobile dashboard for installers showing Today/Upcoming/Past WOs. Installers can update WO status (new statuses: `partial`, `site_not_ready`, `needs_levelling`, `needs_attention`) + add optional notes. Status changes notify `team@rmflooring.ca` via Track 1. Mobile photo gallery at `/m/opportunity/{id}/photos`. Add/view photos from mobile WO page. `installer` role granted `view work orders` permission. User admin updated with User Type (Staff/Installer) and installer record linker.
- **Calendar entry templates (2026-03-24)**: Admin can customize MS365 calendar event titles/body for WO installations, PO pickups, and RFM entries. `CalendarTemplate` model, `CalendarTemplateService::renderTemplate()`. All 3 calendar-creating controllers now use the service (falls back to previous hardcoded defaults). Tags include `{{pm_name}}`, `{{pm_first_name}}` and job/WO/PO context tags. Admin UI at Settings → Calendar Entry Templates.

---

## Sale Status page summary
Full details in `Context/context_sale_status.md`.

- Read-mostly status page per sale: `GET pages/sales/{sale}/status` → `SaleStatusController@show`
- Sections: progress bar, 5 stat cards, PO table, Work Orders placeholder, Material Coverage with dot/badge system
- Coverage dots match PO items to sale items via `purchase_order_items.sale_item_id`
- Overall status badge: Ready / In progress / Needs action / Not started (derived from PO data only)
- Work Orders and "From inventory" coverage are structural placeholders — not yet wired
- Permission: `view sale status` → admin, sales, estimator, coordinator
- `coordinator` role added to system in this session

---

## Sidebar nav update (session 11 → 15, 2026-03-15)

- All expandable groups (Vendors, Products, Labour, People, Chart of Accounts, Tax Management) converted from **hover flyouts** to **Alpine.js click-toggle accordions**
- Reason: sidebar is now scrollable (`overflow-y: auto` on the nav `<ul>`); hover flyouts using `absolute left-full` were clipped by the overflow container — CSS cannot have `overflow-y: auto` and `overflow-x: visible` on the same element
- Sidebar inner div is `flex flex-col`; nav `<ul>` has `flex-1 overflow-y-auto min-h-0` so logo stays pinned and nav scrolls
- Each accordion uses `x-data="{ open: false }"` with `@click="open = !open"` and `x-show="open"` on the sub-list; chevron rotates 90° when open (`:class="open ? 'rotate-90' : ''"`)
- Tax Management accordion includes "Tax Overview" as first sub-item (links to `admin.tax.index`)
- **Document Labels** link added to admin section (see Document Labels CRUD below)
- See `Context/context_installers.md` for Installers details

---

## Customer Contacts (session 42, 2026-03-29)

- Additional named contacts per customer (e.g. project coordinators, office managers)
- Model: `app/Models/CustomerContact.php` — fields: `customer_id`, `name`, `title`, `email`, `phone`, `notes`, `created_by`, `updated_by`
- Migration: `2026_03_29_160000_create_customer_contacts_table`
- `Customer::contacts()` hasMany, ordered by name
- Controller: `app/Http/Controllers/Admin/CustomerContactController.php` — store, update, destroy
- Routes (under admin middleware, gated `edit customers`):
  - `POST   admin/customers/{customer}/contacts` → `admin.customers.contacts.store`
  - `PUT    admin/customers/{customer}/contacts/{contact}` → `admin.customers.contacts.update`
  - `DELETE admin/customers/{customer}/contacts/{contact}` → `admin.customers.contacts.destroy`
- **Customer show page**: Contacts card between "Customer Details" and Opportunities tables; inline Add form (Alpine `showAdd`); per-row inline Edit/Remove (Alpine `editing`)
- **Customer edit page**: Same contacts management section below the main form + "Back to Customer" button in header
- **Email send modals** — all 5 updated (estimate show/edit, sale show/edit, WO show):
  - When `$customerContacts` has contacts with emails, a "Quick-add from contacts:" chip row appears in the CC section
  - Clicking a chip adds the email to `ccEmails` (no duplicates); chips show `Name · Title`
  - `$customerContacts` resolved from `opportunity->parentCustomer->contacts` in each controller
  - Eager-loaded via `opportunity.parentCustomer.contacts` in: `EstimateController::show/edit`, `SaleController::show/edit`, `WorkOrderController::show`

---

## Document Labels CRUD (session 15, 2026-03-15)

- Model: `app/Models/OpportunityDocumentLabel.php` — fields: `name`, `is_active`, `created_by`, `updated_by`
- Controller: `app/Http/Controllers/Admin/OpportunityDocumentLabelController.php` — index, store, edit, update, destroy
- Routes: `admin/opportunity-document-labels` → `admin.opportunity_document_labels.*` (only: index, store, edit, update, destroy); gated by `role_or_permission:admin|manage document labels`
- Views: `resources/views/admin/opportunity_document_labels/index.blade.php` + `edit.blade.php`
  - Index: inline create form at top + table with name, active badge, document count, edit/delete actions
  - Delete blocked if label has assigned documents (shows "In use" instead); deactivate via edit instead
  - Filter: search by name, toggle show inactive
- Sidebar: "Document Labels" link in admin section

---

## Document Templates (session 36–37, 2026-03-27)
Full details in `Context/context_document_templates.md`.

- Admin-managed printable templates with HTML body + `{{merge_tags}}`
- Staff generate PDFs from the opportunity Documents tab → saved as `generated_document` category `OpportunityDocument`
- Model: `app/Models/DocumentTemplate.php` — `OPPORTUNITY_TAGS` (18 tags) + `SALE_TAGS` (2 tags incl. `{{flooring_items_table}}`)
- Service: `app/Services/DocumentTemplateService.php` — `render()` + `buildFlooringTable()`
- PDF template: `resources/views/pdf/document-template.blade.php` — DejaVu Sans, branding logo/header/footer
- Admin CRUD: `admin.document-templates.*` (index/create/edit/update/destroy); gated `role_or_permission:admin`
- Admin edit page has **client-side Preview button** — replaces tags with sample values in a simulated PDF layout; script wrapped in `@verbatim`/`@endverbatim`
- `opportunity_documents.template_id` nullable FK links generated docs back to their template
- Generate modal: Alpine.js on documents index; conditional sale dropdown appears when `needs_sale = true`
- Print action on generated docs streams the stored PDF inline via `reprint()` route
- 3 starter templates seeded: Front File Label, Flooring Selection Sign-Off (needs_sale), Work Authorization Form
- Front File Label layout mirrors RFM show page: blue header (Job#/customer), job name strip, left=Customer+PM / right=Job Site, measure details fill-in lines, special instructions (amber), notes
- Admin sidebar + admin settings page both have "Document Templates" links
- ⚠️ Blade parses `{{tags}}` everywhere — use `@{{tag}}` in text, `@verbatim` in `<script>` blocks, avoid in CSS comments
- **Insurance tags (session 37)**: `{{insurance_company}}`, `{{adjuster}}`, `{{policy_number}}`, `{{claim_number}}`, `{{dol}}` — resolve from `jobSiteCustomer` columns; `{{dol}}` formatted as `M j, Y`

## Invoice Module (session 38, 2026-03-28)
Full details in `Context/context_invoices.md`.

### Overview
Invoices are created from an approved sale by selecting a subset of sale items (supports deposit/progress billing). Multiple invoices per sale allowed.

### Invoice numbering
`YYYY-NNN` — sequential per calendar year, global (not per-sale). Zero-padded to 3 digits. Resets each year. Generated in `Invoice::booted()`.

### Data Model
- `payment_terms` — `name`, `days` (nullable), `description`, `is_active`; reusable on vendors/subcontractors later
- `invoices` — `invoice_number`, `sale_id`, `payment_term_id`, `status` (draft/sent/paid/overdue/partially_paid/voided), `due_date`, `customer_po_number`, `notes`, `subtotal`, `tax_amount`, `grand_total`, `amount_paid`, `sent_at`, `voided_at`, `void_reason`; soft deletes
- `invoice_rooms` — `invoice_id`, `sale_room_id` (ref only, no FK), `name`, `sort_order`
- `invoice_items` — `invoice_id`, `invoice_room_id`, `sale_item_id` (ref only, no FK), `item_type`, `label`, `quantity`, `unit`, `sell_price`, `line_total`, `tax_rate`, `tax_amount`, `tax_group_id`, `sort_order`
- `invoice_payments` — `invoice_id`, `amount`, `payment_date`, `payment_method` (cash/cheque/e-transfer/credit_card/other), `reference_number`, `notes`, `recorded_by`

### Tax approach
Tax is snapshotted from `sale.tax_rate_percent` at invoice creation. Each invoice item stores `tax_rate` and `tax_amount`. `sale.tax_rate_percent` is a float percentage (e.g. `2.456`); convert to decimal (`/ 100`) for calculations.

### Partial invoicing
- `InvoiceService::getInvoicedQtyBySaleItem()` returns already-invoiced qty per sale_item_id across all non-voided invoices
- Create form shows remaining qty per item; fully-invoiced items are greyed out with checkmark
- Items with partial invoicing show remaining available qty

### Sale status sync
`InvoiceService::syncSaleInvoiceStatus()` recalculates `invoiced_total` and `is_fully_invoiced` on Sale after any invoice create/void/payment. Sale status auto-updates to `partially_invoiced` or `invoiced` when appropriate.

### Permissions
`view invoices`, `create invoices`, `edit invoices`, `delete invoices` — seeded to `admin` + `coordinator` via migration `2026_03_28_000006`. Added to `PermissionsSeeder` for re-seeding.

### Payment Terms admin
- Route: `admin/payment-terms` → `admin.payment-terms.*` (index, store, edit, update, destroy); gated `admin`
- Controller: `app/Http/Controllers/Admin/PaymentTermController.php`
- Views: `resources/views/admin/payment-terms/index.blade.php` + `edit.blade.php`
- Sidebar: "Payment Terms" link in admin section (above Document Labels)
- Delete blocked if term has invoices; deactivate via edit instead

### Key Files
- Models: `app/Models/Invoice.php`, `InvoiceRoom.php`, `InvoiceItem.php`, `InvoicePayment.php`, `PaymentTerm.php`
- Service: `app/Services/InvoiceService.php` — `createFromSale()`, `recalculateTotals()`, `recalculateAfterPayment()`, `syncSaleInvoiceStatus()`, `getInvoicedQtyBySaleItem()`
- Controller: `app/Http/Controllers/Pages/InvoiceController.php`
- Views: `resources/views/pages/invoices/create.blade.php`, `show.blade.php`, `edit.blade.php`
- PDF: `resources/views/pdf/invoice.blade.php` (loads branding from `app_settings` directly like change-order PDF)
- Sale model: added `invoices()` hasMany + `activeInvoices()` hasMany (excludes voided)
- Sale show page: Invoices section added after Change Orders; eager-loads `invoices` in `SaleController::show()`

### Routes (all under `pages` middleware group)
- `GET  pages/sales/{sale}/invoices/create` → `pages.sales.invoices.create`
- `POST pages/sales/{sale}/invoices` → `pages.sales.invoices.store`
- `GET  pages/sales/{sale}/invoices/{invoice}` → `pages.sales.invoices.show`
- `GET  pages/sales/{sale}/invoices/{invoice}/edit` → `pages.sales.invoices.edit`
- `PUT  pages/sales/{sale}/invoices/{invoice}` → `pages.sales.invoices.update`
- `POST pages/sales/{sale}/invoices/{invoice}/void` → `pages.sales.invoices.void`
- `GET  pages/sales/{sale}/invoices/{invoice}/pdf` → `pages.sales.invoices.pdf`
- `POST pages/sales/{sale}/invoices/{invoice}/send-email` → `pages.sales.invoices.send-email`
- `POST pages/sales/{sale}/invoices/{invoice}/payments` → `pages.sales.invoices.payments.store`
- `DELETE pages/sales/{sale}/invoices/{invoice}/payments/{payment}` → `pages.sales.invoices.payments.destroy`

### Invoice show page features
- Status badge, financial summary (total / paid / balance due), voided date + reason
- Line items grouped by room with room subtotals
- Payments table with remove button
- Add Payment modal (pre-fills balance due amount)
- Void modal with optional reason
- Send Email modal (auto-attaches PDF; marks status `sent` if was `draft`)
- Print/PDF button

### Open items
- Index page for invoices (list all invoices across all sales)
- Sidebar link for invoices
- Overdue auto-detection (cron/scheduler to flip `sent` → `overdue` when due_date passes)
- Invoice edit should allow re-selecting line items (currently only edits header fields)

---

## RFMs index page (session 37, 2026-03-27)
- `RfmController::index()` — search (customer, estimator, job#, address), status, estimator dropdown, flooring type (`whereJsonContains`), scheduled date range; paginated 25/page
- Route: `GET pages/rfms` → `pages.rfms.index` (middleware: `role_or_permission:admin|view rfms`)
- View: `resources/views/pages/rfms/index.blade.php` — columns: Customer+PM, Job (linked), Site Address, Flooring type badges, Estimator, Scheduled, Status, Calendar sync, View button
- Sidebar: "RFMs" link added between Opportunities and Estimates

---

## Purchase Orders & Work Orders index pages (session 10, 2026-03-15)

- Added `PurchaseOrderController::index()` and `WorkOrderController::index()` with search + filter support
- New routes: `GET pages/purchase-orders` → `pages.purchase-orders.index` and `GET pages/work-orders` → `pages.work-orders.index` (both gated by `view` permission)
- New blades: `pages/purchase-orders/index.blade.php` and `pages/work-orders/index.blade.php`
  - Filters: search (number, vendor/installer name, sale#), status dropdown, date from/to
  - WO index View link routes to `pages.sales.work-orders.show` (needs both `{sale}` and `{workOrder}`)
- Dashboard: Work Orders and Purchase Orders cards now link to their index pages (solid indigo/emerald styling, no longer "Coming soon")

---

## Purchase Orders module summary
Full details in `Context/context_purchase_orders.md`.

- POs are raised against a Sale to order material items from vendors
- Multiple POs per sale allowed (different vendors); only `material` type items included
- PO number auto-generated: `{seq}-{sale_number}` (e.g. `3-8`) or just `{seq}` for stock POs; plain integers, no year prefix
- Sale number auto-generated as plain integer (e.g. `8`); no year prefix
- Statuses: `pending`, `ordered` (requires vendor order number), `received`, `cancelled`
- Fulfillment methods: delivery to site, warehouse, custom address, or pickup
- **Pickup scheduling**: when fulfillment = `pickup`, a date/time is captured and synced to RM Warehouse group calendar (`group_id = 4bfd495c-4df2-4eaa-9d8c-987c4ef23b02`); best-effort
- Installer-linked vendors are **excluded** from the vendor dropdown
- Each PO item has `po_notes` (pre-filled from sale item, editable on create and edit); shown in PDF
- **Qty tracking**: system tracks total ordered qty per sale item across all non-cancelled POs
  — fully-ordered items cannot be added to new POs; partial orders show remaining qty
- Soft delete available to users with `delete purchase orders` permission
- Force (permanent) delete available to admin role only
- PDF via DomPDF (`resources/views/pdf/purchase-order.blade.php`)
- Email to vendor via Track 1 (shared mailbox) with PDF attached
- PO summary card shown on Sale show + edit pages and Opportunity show page

### Sale email → status update (session 24, 2026-03-22)
- `sent` added to `sales.status` enum via migration `2026_03_22_214508_add_sent_status_to_sales_table`
- Full enum now: `open, sent, approved, scheduled, in_progress, on_hold, completed, partially_invoiced, invoiced, cancelled`
- `SaleController::sendEmail()` now sets `status = 'sent'` on successful send (mirrors `EstimateController`)
- `SaleController::update()` validation and `index()` `$statusOptions` both include `sent`
- Sale edit blade: `sent` → sky-blue badge (`bg-sky-200 text-sky-800`), label "Sent"

---

### Purchase Orders — Stock POs (session 13, 2026-03-15)
- `sale_id`, `opportunity_id` nullable on `purchase_orders`; `sale_item_id` nullable on `purchase_order_items`
- New routes: `GET/POST pages/purchase-orders/create` (stock PO), `GET pages/purchase-orders/catalog-search`
- New views: `create-stock.blade.php` (per-row catalog typeahead, vendor-filtered) + `edit-stock.blade.php`
- Catalog search filters by `product_lines.vendor_id`; auto-fills item_name, cost_price, unit
- `edit()` routes to `edit-stock` for no-sale POs; `update()` skips qty validation for stock POs
- `destroy()`/`forceDestroy()` redirect to index (not sale) for stock POs
- PDF, show, index blades all null-safe for missing sale; vendor column bug fixed (`company_name`)
- `+ Create PO` button on PO index; "Stock PO" label on show/PDF

### Purchase Orders — Bug fix (session 14, 2026-03-16)
- **Pickup scheduling fields** in `create.blade.php` and `create-stock.blade.php` now have `:disabled="fulfillmentMethod !== 'pickup'"` — prevents `pickup_time` (which had default `09:00`) from submitting when fulfillment is not "pickup", fixing a validation error that blocked PO creation.
- Removed redundant `required_with` rules from sale-PO `store()` validation.

### Sale status — approved + PO gate (session 22, 2026-03-20)
- Added `approved` to `sales.status` enum (migration `2026_03_20_180051_add_approved_status_to_sales_table`)
- `SaleController::update()` now validates and saves `status`; `statusOptions` in index updated to include `approved`
- Sale edit blade status dropdown fixed — was using wrong estimate-style statuses (`draft/sent/revised/approved/rejected`) that never saved; replaced with correct sale statuses with proper color badges
- `PurchaseOrderController::create()` and `store()` both gate on `$sale->status === 'approved'` — redirects to sale show with error if not approved
- All "+ Create PO" buttons (show ×2, edit ×2, status page ×1) show greyed-out disabled state with tooltip when status is not `approved`

### Purchase Orders — Vendor locking bug fix (session 21, 2026-03-20)
- Items with no `product_line_id` (typed manually or added pre-session-8) were not auto-selecting vendor or shading as "Wrong vendor" on PO create page
- Added 3rd fallback in `itemVendorMap` building: look up `ProductLine` by manufacturer + style name text (with `whereNotNull('vendor_id')`)
- Full fallback order: (1) productStyle.vendor_id, (2) productLine.vendor_id, (3) ProductLine text lookup, (4) vendor company_name fuzzy match
- File: `app/Http/Controllers/Pages/PurchaseOrderController.php` → `create()`

### Purchase Order open items
- No invoice/payment tracking against POs yet
- No "received items" partial-receive workflow yet

---

## Change Orders (session 26, 2026-03-23)

### Overview
Change Orders (CO) allow a homeowner/job site customer to request scope changes to an approved sale before any materials are ordered. The CO snapshots the current sale state, tracks deltas as the user edits the sale, and produces a printable/emailable document for homeowner approval.

### Business Rules
- CO can only be created on an `approved` sale with no ordered/received POs and no scheduled/in-progress WOs
- Any non-cancelled PO (including `pending`) blocks CO creation
- Deleting an ordered/received PO does NOT re-enable CO creation (checked via `withTrashed()`)
- While a CO is `draft` or `sent`: sale status = `change_in_progress`, PO/WO creation is blocked
- CO approved → sale re-locks at new totals, `revised_contract_total` updated, status returns to `approved`
- CO cancelled → sale items reverted from snapshot, original totals restored, status returns to `approved`
- Multiple COs per sale allowed (sequentially, never concurrent)

### Data Model
- `sale_change_orders` — main CO record (`co_number` = `CO-{seq}-{sale_number}`)
  - `status`: draft | sent | approved | rejected | cancelled
  - `original_pretax_total`, `original_tax_amount`, `original_grand_total` — sale totals at CO creation
  - `locked_pretax_total`, `locked_tax_amount`, `locked_grand_total` — frozen on approval
  - `sent_at`, `approved_at`, `approved_by`
- `sale_change_order_rooms` — snapshot of rooms at CO creation; `sale_room_id` links back to live room
- `sale_change_order_items` — snapshot of items; `sale_item_id` is a plain nullable integer (NO FK constraint — see below)
- `sales.status` enum includes `change_in_progress`

### Critical Design Decision — Positional Delta Matching
**Problem**: `SaleController::update()` does a full delete+recreate of all items on every save (`SaleItem::where('sale_room_id', $id)->delete()` then recreates). This gives items new IDs on every save, making ID-based FK links useless.

**Why FK was dropped**: MariaDB `ON DELETE SET NULL` fired on every save, wiping all `sale_item_id` values on snapshot items. Additionally, `ON DELETE SET NULL` does not reliably roll back within failed transactions in MariaDB.

**Solution**: `ChangeOrderService::calculateDelta()` uses **positional sort_order matching** — snapshot items and current items are both sorted by `sort_order` and matched index-by-index within each room. This is robust regardless of how items are saved.

### Delta Calculation (`ChangeOrderService::calculateDelta()`)
- Matches snapshot rooms → current rooms via `sale_room_id`
- Matches snapshot items → current items positionally by `sort_order` index (NOT by ID)
- If snap item exists but no current item at that index → "removed"
- If no snap item but current item exists at that index → "added"
- Both exist → compare `line_total` and label → "changed" or "unchanged"
- New rooms with no snapshot counterpart (new `sale_room_id` not in snapshot) → "added"
- Returns: `rooms[]` with `rows[]` (status: added/removed/changed/unchanged), plus `orig_grand_total`, `new_grand_total`, `grand_delta`

### Revert Logic (`ChangeOrderService::revert()`)
- For each snapshot room: deletes ALL current items then rebuilds from snapshot data directly
- Does NOT rely on `sale_item_id` — safe regardless of FK state
- Guards `whereNotIn` with empty array check (empty array would match ALL rooms)
- Cancels the CO, re-locks sale at original totals

### Key Files
- Service: `app/Services/ChangeOrderService.php` — create (snapshot), calculateDelta, approve, revert, markSent
- Controller: `app/Http/Controllers/Pages/ChangeOrderController.php`
- Models: `app/Models/SaleChangeOrder.php`, `SaleChangeOrderRoom.php`, `SaleChangeOrderItem.php`
- Views: `resources/views/pages/change-orders/create.blade.php`, `show.blade.php`
- PDF: `resources/views/pdf/change-order.blade.php`
- Migrations: `2026_03_23_025836_patch_change_order_tables_add_missing_fields`, `2026_03_23_033518_drop_fk_on_sale_change_order_items_sale_item_id`

### Routes
All nested under `pages/sales/{sale}/change-orders/`:
- `pages.sales.change-orders.create` / `.store` / `.show` / `.approve` / `.cancel` / `.pdf`
- Permission: `role_or_permission:admin|edit estimates`

### CO Section on Sale Show Page
- Shows table of all COs with status, original total, delta (approved COs only), revised total
- "New Change Order" button: only shown when status = `approved`, no active COs, no blocking POs
- Shows "CO in progress" label when active CO exists; "Blocked — POs exist" when ordered POs present

### CO on Sales Index Page
- "Has Change Orders" checkbox filter (`has_co=1`) — filters to sales with at least one CO
- CO # search added to main search field (searches `co_number` via `whereHas('changeOrders', ...)`)
- Status dropdown includes `change_in_progress` with label "Change In Progress"
- CO badge in status column: amber linked badge for active draft/sent CO, grey count badge for closed COs

### PDF Notes
- Template: `resources/views/pdf/change-order.blade.php`
- **Important**: inline Blade `@if/@endif` on same line as `{{ }}` output causes ParseError in DomPDF context — use ternary expressions instead: `{{ $phone ? ' · '.$phone : '' }}`

### Open Items
- Email CO to homeowner (send modal + Graph mail, same pattern as sale/estimate)
- Mark CO as "sent" / track `sent_at` / homeowner approval date via email flow
- `change_in_progress` badge in sale **edit** page status dropdown (index already done)

---

## Calendar Entry Templates (session 29, 2026-03-24)

Admin-only setting to customise the title and notes for MS365 calendar events created by the system.

### Model / Service
- Model: `app/Models/CalendarTemplate.php` — `TYPES`, `TAGS`, `DEFAULTS` constants; `type` (unique), `title_template`, `notes_template`
- Service: `app/Services/CalendarTemplateService.php` — `getTemplate(string $type)`, `render(string $template, array $vars)`, `renderTemplate(string $type, array $vars)`
- Table: `calendar_templates` (migration `2026_03_24_020654`)

### Three template types
- `work_order_calendar` — WO Installation (RM – Installations calendar)
- `po_pickup_calendar` — PO Pickup / Delivery (RM – Warehouse calendar)
- `rfm_calendar` — RFM / Measure (RM – RFM/Measures calendar)

### Available tags per type
- WO: `{{wo_number}}`, `{{installer_name}}`, `{{installer_first_name}}`, `{{customer_name}}`, `{{sale_number}}`, `{{job_address}}`, `{{items_summary}}`, `{{wo_notes}}`, `{{pm_name}}`, `{{pm_first_name}}`
- PO pickup: `{{po_number}}`, `{{vendor_name}}`, `{{sale_number}}`, `{{customer_name}}`, `{{special_instructions}}`, `{{pm_name}}`, `{{pm_first_name}}`
- RFM: `{{customer_name}}`, `{{estimator_name}}`, `{{job_number}}`, `{{flooring_type}}`, `{{site_address}}`, `{{special_instructions}}`, `{{pm_name}}`, `{{pm_first_name}}`

### Routes & UI
- Routes: `admin.settings.calendar-templates.index` / `.save` / `.reset` (GET, POST, DELETE)
- View: `resources/views/admin/settings/calendar-templates.blade.php` — tab UI mirroring email templates
- Linked from admin settings page button "Calendar Entry Templates"

### Wired into controllers
- `WorkOrderController::buildEventData()` — uses `CalendarTemplateService::renderTemplate('work_order_calendar', $vars)`
- `PurchaseOrderController::buildPickupEventData()` — uses `renderTemplate('po_pickup_calendar', $vars)`
- `RfmController::store()` — uses `renderTemplate('rfm_calendar', $vars)`; `$opportunity->loadMissing(['projectManager'])` added before vars

---

## SMS Notifications (session 31, 2026-03-25)
Full details in `Context/context_sms.md`.

- Provider: **Twilio** (`twilio/sdk ^8.11`)
- `SmsService` — wraps Twilio, normalizes phone to E.164, logs every send to `sms_log`, never throws
- `SmsTemplateService` — `getBody()`, `render()`, `renderTemplate()` — same pattern as CalendarTemplateService
- `SmsTemplate` model — `TYPES`, `TAGS`, `DEFAULTS` constants for 4 notification types
- `SmsLog` model — `sms_log` table; mirrors `mail_log`
- `users` table: `phone` + `mobile` columns added; UserController + create/edit views updated
- `work_orders.sms_reminder_sent_at` + `rfms.sms_reminder_sent_at` — nullable timestamps; prevent duplicate reminders
- Artisan command: `sms:send-reminders` — queries WOs/RFMs scheduled tomorrow, sends reminders, stamps sent_at
- Scheduler: `routes/console.php` — runs daily at `sms_reminder_time` setting (default `16:00`)

### Notification types + triggers
| Type | Trigger | Recipients |
|------|---------|------------|
| `wo_scheduled` | WO status → `scheduled` in store() or update() | PM (`projectManager.mobile`), Installer (`installer.mobile`) |
| `wo_reminder` | `sms:send-reminders` cron | PM, Installer, Homeowner (`job_phone`) |
| `rfm_booked` | `RfmController::store()` | Estimator (`employee.phone`), PM (`projectManager.mobile`), Customer (`parentCustomer.mobile` → `.phone`) |
| `rfm_updated` | `RfmController::update()` | Estimator, PM, Customer — gated by per-checkbox `sms_notify_estimator` / `sms_notify_pm` in edit form |
| `rfm_reminder` | `sms:send-reminders` cron | Estimator, PM, Customer (`customer.mobile`) |

### Admin pages
- `GET /admin/settings/sms` → `admin.settings.sms` — credentials, toggles, recipient checkboxes, test send, send log
- `GET /admin/settings/sms-templates` → `admin.settings.sms-templates.index` — tab UI with live char counter, copy-to-clipboard tags

### Changes (session 32)
- **Homeowner added to WO Scheduled**: `sendScheduledSms()` now sends to `sale.job_phone` (fallback `sourceEstimate.homeowner_phone`) when `homeowner` is in `sms_wo_scheduled_to`
- **Customer added to RFM Booked**: `RfmController::store()` now sends to `parentCustomer.mobile` (fallback `.phone`) when `customer` is in `sms_rfm_booked_to`
- **Toggle save bug fixed**: `SmsSettingsController::update()` used `$request->has()` — always true due to hidden input fields. Fixed to `$request->input(...) === '1'`

### Changes (session 38, 2026-03-29)
- **SMS on RFM edit**: `edit()` now passes `smsRfmUpdatedEnabled` + includes `phone` in estimator query; edit blade has SMS Notifications section (estimator + PM checkboxes, both default OFF); phone hint updates on estimator dropdown change — mirrors create form pattern
- **Disabled state for notification sections (RFM create + edit)**: Both email and SMS notification sections are now always visible. When the respective admin setting is off (`mail_notifications_enabled` / `sms_enabled` + specific type toggle), the section renders shaded (`opacity-50`, `cursor-not-allowed`, all checkboxes disabled) with an amber message: "X notifications are currently disabled. Contact your admin to enable them." Controllers pass `$emailNotificationsEnabled`, `$smsEnabled`; `$smsRfmBookedEnabled` / `$smsRfmUpdatedEnabled` now require both global + specific toggle. Edit blade JS null-guards `notifyEstimatorBox` and `estimatorHint`.

### Open items
- Manual on-demand SMS send button on WO show page
- Consider adding `mobile` to `Employee` model for estimator mobile sends (currently uses `employee.phone`)

---

## Opportunities — Status guard rails + auto-approve (session 34, 2026-03-26)

- `status_reason` (nullable text) added to `opportunities` table — shown as textarea on edit/create when status is Lost or Closed; displayed on show page
- **Guard rail — Lost**: `OpportunityController::update()` blocks setting status to `Lost` if any non-cancelled sale exists. Returns back with error: "X active jobs — cancel all before marking Lost." Edit blade also shows an inline Alpine.js amber warning before submit.
- **Auto-approve cascade**: `EstimateController::update()` — when estimate status is set to `approved`:
  - Linked sale (`source_estimate_id`) → set to `approved` (skips if already approved/cancelled)
  - Linked opportunity (`opportunity_id`) → set to `Approved` (skips if already Approved/Lost/Closed)
- Opportunity statuses (string, not enum): `New`, `In Progress`, `Awaiting Site Measure`, `Estimate Sent`, `Approved`, `Lost`, `Closed`

---

## Vendor Reps (session 28, 2026-03-23 / session 33, 2026-03-25)

- Pivot table `vendor_vendor_rep` already existed (`vendor_id`, `vendor_rep_id`)
- `Vendor` model already had `reps()` belongsToMany
- **Added**: `VendorRep::vendors()` belongsToMany inverse relationship
- **Updated**: `VendorRepController` — `create()` passes all vendors; `store()` syncs selected vendor via pivot; `edit()` passes vendors + current vendor ID; `update()` syncs pivot (clears if blank)
- **Updated**: create + edit blades — vendor dropdown added (optional, "— No vendor —" default)
- Vendor assignment is optional; sync enforces one vendor per rep (clearing removes the link)
- **Show page** (session 33): `VendorRepController::show()` added; view at `resources/views/admin/vendor_reps/show.blade.php` — two-column layout (contact details + notes | record info); vendor name links to `admin.vendors.show`
- **Index** (session 33): rep name is a clickable link to show page; email is a `mailto:` link

---

## RFC / RTV modules (session 24, 2026-03-22)

### RFC — Request for Customer Return
- Model: `CustomerReturn`, items: `CustomerReturnItem`
- Number format: `RFC-{seq}-{sale_number}` (e.g. `RFC-1-13`) or `RFC-{seq}` if no sale linked
- Flow: `draft` → `receive` (creates `InventoryReceipt` per item, inventory ↑, updates PT `returned_qty`, recalculates PT status)
- Routes: `pages.inventory.rfc.*` — view: `view rfcs`, create/edit/receive/delete: `create rfcs`
- Views: `resources/views/pages/inventory/rfc/` (index, create, show, edit)
- Controller: `app/Http/Controllers/Pages/CustomerReturnController.php`
- Service: `app/Services/CustomerReturnService.php`

### RTV — Return to Vendor
- Model: `InventoryReturn`, items: `InventoryReturnItem`
- Number format: `RTV-{seq}-{sale_number}` (e.g. `RTV-1-13`) or `RTV-{seq}` if no sale/PO linked
- Flow: `draft` → `ship` → `resolved`
  - **Ship** (`ReturnToVendorService::ship()`): negative `InventoryTransaction`, reduces/deletes `InventoryAllocations` on the receipt, increments `PurchaseOrderItem.returned_quantity` (PO-sourced only)
  - **Resolve** (`ReturnToVendorService::resolve()`): records outcome (`credit_note`, `replacement`, `refund`). For `credit_note` + `apply_to_sale_cost = true`: reduces `order_qty` on the linked `SaleItem` by `quantity_returned` (original `quantity` untouched); uses raw DB update to bypass the `SaleItem` saving hook. Stores `credit_received` as audit record.
- Vendor auto-detection for RFC-sourced receipts: `sale_item_id → PurchaseOrderItem → PurchaseOrder → vendor`
- Routes: `pages.inventory.rtv.*`
- Views: `resources/views/pages/inventory/rtv/` (index, create, show, edit)
- Controller: `app/Http/Controllers/Pages/ReturnToVendorController.php`
- Service: `app/Services/ReturnToVendorService.php`

### Profits page — Vendor Credits integration (session 24)
- `SaleController::showProfits()` now loads `InventoryReturnItem` records where `sale_item_id` is in the sale's items AND `apply_to_sale_cost = true` AND `cost_applied_at != null` → passed as `$vendorCredits` and `$vendorCreditBySaleItem` (keyed by sale_item_id)
- **Individual item rows**: unchanged — still show `qty × cost_price` (clean math, no confusion)
- **Room footer**: new "Vendor Credit" row (green, −$X.XX) appears per room when a credit applies; Room Profit and Margin reflect the credit (`sell − cost + credit`); `data-room-vendor-credit` attribute on room card div drives JS recalculation
- **Profit Summary card**: JS `recalculateRoom()` reads `data-room-vendor-credit` and applies it to profit (not cost total); `updateProfitSummaryHeader()` sums `data-room-profit` values directly (already credit-adjusted) — Total Cost shown as `sell − adjusted_profit`
- **Vendor Credits card**: new card below room cards (green left border) — shows RTV # (linked), sale item name, qty returned, unit cost, credit value (+$X.XX) per credit; total at footer
- Key files: `app/Http/Controllers/Pages/SaleController.php` (showProfits), `resources/views/pages/profits/show.blade.php`

---

## Inventory enhancements (session 23, 2026-03-20)

### Product ID visibility + search
- **Inventory show page** (`resources/views/pages/inventory/show.blade.php`): Added "Product ID" row to the Record details sidebar card — shown only when `product_style_id` is set on the receipt
- **Inventory index page** (`resources/views/pages/inventory/index.blade.php`):
  - Added "Product ID" filter input (`product_style_id`) in the filter bar
  - Added `Product ID: {id}` sub-line under each item name in the table (only when set)
  - Updated clear button and empty-state conditions to include the new filter
- **`InventoryController::index()`**: added `$productStyleId` from request, `->when($productStyleId, ...)` filter on `product_style_id`, passed to view

---

## Key file locations

| What | Where |
|------|-------|
| Routes | `routes/web.php` |
| Models | `app/Models/` |
| Admin controllers | `app/Http/Controllers/Admin/` |
| Pages controllers | `app/Http/Controllers/Pages/` |
| Mail classes | `app/Mail/` |
| Mail service | `app/Services/GraphMailService.php` |
| Email template service | `app/Services/EmailTemplateService.php` |
| Email template model | `app/Models/EmailTemplate.php` |
| User email templates page | `resources/views/pages/settings/email-templates.blade.php` |
| Admin email templates page | `resources/views/admin/settings/email-templates.blade.php` |
| Calendar service | `app/Services/GraphCalendarService.php` |
| Main layout | `resources/views/layouts/app.blade.php` |
| Email portal | `resources/views/admin/settings/mail.blade.php` |
| Profits page | `resources/views/pages/profits/show.blade.php` |
| Estimate builder JS | `public/assets/js/estimates/estimate.js` |
| Sale builder JS | `public/assets/js/sales/sale.js` |
| PDF templates | `resources/views/pdf/estimate.blade.php`, `resources/views/pdf/sale.blade.php`, `resources/views/pdf/purchase-order.blade.php`, `resources/views/pdf/work-order.blade.php`, `resources/views/pdf/change-order.blade.php` |
| PO controller | `app/Http/Controllers/Pages/PurchaseOrderController.php` |
| PO views | `resources/views/pages/purchase-orders/` |
| PO models | `app/Models/PurchaseOrder.php`, `app/Models/PurchaseOrderItem.php` |
| WO controller | `app/Http/Controllers/Pages/WorkOrderController.php` |
| WO views | `resources/views/pages/work-orders/` |
| WO models | `app/Models/WorkOrder.php`, `app/Models/WorkOrderItem.php` |
| Pick ticket controller | `app/Http/Controllers/Pages/WarehousePickTicketController.php` |
| Pick ticket views | `resources/views/pages/warehouse/pick-tickets/` |
| Pick ticket PDF | `resources/views/pdf/pick-ticket.blade.php` |
| Pick ticket models | `app/Models/PickTicket.php`, `app/Models/PickTicketItem.php` |
| Inventory controller | `app/Http/Controllers/Pages/InventoryController.php` |
| Inventory views | `resources/views/pages/inventory/` |
| Inventory models | `app/Models/InventoryReceipt.php`, `app/Models/InventoryAllocation.php` |
| Inventory/PT services | `app/Services/InventoryService.php`, `app/Services/PickTicketService.php` |
| Installer controller | `app/Http/Controllers/Admin/InstallerController.php` |
| Installer views | `resources/views/admin/installers/` |
| Installer model | `app/Models/Installer.php` |
| Branding controller | `app/Http/Controllers/Admin/BrandingController.php` |
| Branding settings view | `resources/views/admin/settings/branding.blade.php` |
| Document template model | `app/Models/DocumentTemplate.php` |
| Document template service | `app/Services/DocumentTemplateService.php` |
| Document template controller (admin) | `app/Http/Controllers/Admin/DocumentTemplateController.php` |
| Document template views (admin) | `resources/views/admin/document-templates/` |
| Document template PDF | `resources/views/pdf/document-template.blade.php` |
| Invoice controller | `app/Http/Controllers/Pages/InvoiceController.php` |
| Invoice service | `app/Services/InvoiceService.php` |
| Invoice models | `app/Models/Invoice.php`, `InvoiceRoom.php`, `InvoiceItem.php`, `InvoicePayment.php` |
| Invoice views | `resources/views/pages/invoices/` |
| Invoice PDF | `resources/views/pdf/invoice.blade.php` |
| Payment terms controller (admin) | `app/Http/Controllers/Admin/PaymentTermController.php` |
| Payment terms views (admin) | `resources/views/admin/payment-terms/` |

---

## Product Catalog enhancements (session 8, 2026-03-14)
Full details in `Context/project-context-product-pricing.md`.

### Product Lines — new fields
- `unit_id` (nullable FK → `unit_measures`) — unit of measure for the line; auto-filled from product type's `sold_by_unit` on create
- `width` (decimal 8,2) — product width in inches
- `length` (decimal 8,2) — product length in inches
- Views widened to `max-w-screen-2xl`; Unit / Width / Length inputs added to create + edit forms

### Product Styles — new fields
- `units_per` (decimal 8,2) — units per box/pack
- `thickness` (varchar 50) — free-text, e.g. "3mm", "12mil"
- `use_box_qty` (boolean, default false) — triggers box quantity prompt in estimates/sales
- `status` now has three options: `active`, `inactive`, `dropped` — "dropped" = orange badge, used for discontinued styles

### Box Quantity Modal + Prompt (`estimate.js`, `sale.js`)
Shared modal: `resources/views/components/modals/box-qty-modal.blade.php`
- Included on estimate create, estimate edit, and sale edit pages
- Modal uses `style="display:none"` (NOT Tailwind `hidden`) to avoid flash on page load
- When user enters a qty that doesn't fill complete boxes → modal prompts "round up to X?"
- Two trigger paths: (1) `focusout` on qty field; (2) immediate check when a style with `use_box_qty` is selected if qty already filled
- JS stores `window._boxQtyPendingInput` + `window._boxQtyPendingValue`; confirm updates the input and re-dispatches `input` event

### Estimate/Sale edit — dropdown auto-restore fix
**Problem:** Existing line item rows on edit pages couldn't auto-load product line / style dropdowns because no IDs were stored — only text values.

**Solution:**
1. Migration `2026_03_14_240000_add_product_ids_to_estimate_and_sale_items.php` — added `product_line_id` and `product_style_id` (nullable FK) to both `estimate_items` and `sale_items`
2. Edit blades: existing rows now render `data-product-line-id`, `data-product-style-id`, `data-use-box-qty`, `data-units-per` attributes + hidden form inputs (`js-product-line-id-input`, `js-product-style-id-input`)
3. `estimate.js` / `sale.js` — `selectFromButton` in style dropdown writes to `.js-product-line-id-input`; color dropdown writes to `.js-product-style-id-input`
4. `EstimateController` `store()` + `update()` and `SaleController` `update()` now save `product_line_id` + `product_style_id`
5. `EstimateController::edit()` and `SaleController::edit()` now eager-load `rooms.items.productStyle`
6. `initProductTypeDropdownForRoom()` in both JS files now auto-resolves `data-product-type-id` for existing rows by matching the text value against the loaded product types list (async, runs once on init)

### Bug fixes (session 8)
- **Tax group 404:** `loadTaxGroupRate` URL was `/pages/estimates/api/tax-groups/` — corrected to `/estimates/api/tax-groups/`
- **Box qty modal flash on page load:** Modal div had both `hidden` and `flex` Tailwind classes; `flex` overrode `hidden` in generated CSS. Fixed: removed `flex` from static classes, use `style="display:none"` as initial state.

---

## Tax summary card fixes (session 9, 2026-03-15)

Fixed tax label and totals not displaying correctly on page load in both estimate edit and sale edit pages.

### Root causes found
1. **Tax label** hardcoded as `"Tax (G)"` in both edit blades — never showed the real group name until the async fetch completed
2. **`estimate_edit.js` / `sale_edit.js` `updateEstimateTotals()`** read from `tax_amount_input` (= `"0"` at load time) instead of recalculating tax from the FM globals — overwrote grand total without tax
3. **Race condition** — `loadTaxGroupRate()` was called early in the `DOMContentLoaded` callback, but the `*_edit.js` sync `recalcFromRow` loop ran after it (also in DOMContentLoaded) and reset the display to $0 tax before the async fetch resolved
4. **`sale.js` fetch URL wrong** — `loadTaxGroupRate` was fetching `/pages/estimates/api/tax-groups/` (404) instead of `/estimates/api/tax-groups/`, causing every tax load to silently fail for sales

### Fixes applied
- **Both edit blades** — pre-render the tax label server-side: `$taxGroups->firstWhere('id', $sale/estimate->tax_group_id)` → `Tax (GST/HST)` shown immediately
- **`estimate.js` / `sale.js`** — wrapped `loadTaxGroupRate()` call in `setTimeout(0)` so it fires after all DOMContentLoaded handlers (including `*_edit.js`) complete their sync work
- **`estimate_edit.js` / `sale_edit.js` `updateEstimateTotals()`** — now recalculates tax from `window.FM_CURRENT_GST_PERCENT` / `FM_CURRENT_PST_PERCENT` / `FM_CURRENT_OTHER_TAXES` globals (same as the `estimate.js`/`sale.js` versions); also updates `.estimate-tax-value` span
- **`sale.js`** — fixed fetch URL to `/estimates/api/tax-groups/${id}/rate`

### Architecture note
`estimate_edit.js` and `sale_edit.js` each have their own `updateEstimateTotals()` inside an IIFE. These are separate from the versions inside `estimate.js`/`sale.js` DOMContentLoaded callbacks. Both versions now calculate tax identically from FM globals. The FM globals (`FM_CURRENT_GST_PERCENT`, etc.) are authoritative — set by `loadTaxGroupRate()` after the API fetch resolves.

---

## Bug fixes (session 13, 2026-03-15)
- **Estimate create — room names saving as null**: create blade posted `rooms[N][name]` but controller read `rooms[N][room_name]` → fixed input name to `room_name`
- **PO index vendor column blank**: blade used `vendor->name` (doesn't exist) → fixed to `vendor->company_name`

## Work Order & Estimate/Sale fixes (session 14, 2026-03-15)

### WO calendar opt-out checkbox
- Create + edit forms: "Add/Sync to RM – Installations calendar" checkbox, default checked
- Unchecking on edit removes the existing calendar event on save
- Controller: `store()` and `update()` gate all calendar sync on `$request->boolean('sync_calendar', true)`

### WO calendar event title
- Changed from `wo_number · job_name` to `{installer first word} - {homeowner_name}`
- Falls back to `customer_name` then `job_name` if `homeowner_name` is empty

### Sale homeowner fields not saving
- `homeowner_name`, `job_phone`, `job_email` columns were missing from `sales` table
- Migration `2026_03_15_214341_add_homeowner_fields_to_sales_table` adds them
- `SaleController::update()` now validates + saves `homeowner_name`, `homeowner_phone`, `homeowner_email`
- Fixed `old()` keys in sale edit blade for phone/email fields

### Estimate store() missing homeowner fields
- `EstimateController::store()` was not validating or saving `homeowner_name`, `homeowner_phone`, `homeowner_email`
- Fixed: added to validation rules and `Estimate::create()` call
- `update()` already handled these correctly

---

## Pick Ticket enhancements (session 15, 2026-03-16)

Full details in `Context/context_warehouse_pick_tickets.md`.

### Pick Ticket PDF
- New route: `GET warehouse/pick-tickets/{pickTicket}/pdf` → `pages.warehouse.pick-tickets.pdf`
- Template: `resources/views/pdf/pick-ticket.blade.php` — matches WO/PO style; items grouped by room
- "Print PDF" button always visible in PT show page header

### Room column bug fix
- PT show blade was using `->room->name` (wrong) → fixed to `->room->room_name`

### Staging modal (replaces direct form POST)
- "Stage Work Order" button → modal with warehouse notes textarea + "Staged by: {you}" info box
- `staging_notes` saved to `pick_tickets` table (migration `2026_03_16_260000_...`)
- "Staged by" meta bar shown on WO page when PT is active

### Unstaging
- New `POST warehouse/pick-tickets/{pickTicket}/unstage` route → `WarehousePickTicketController::unstage()`
- Unstage modal: shows staged-by info, reason textarea, "Unstaged by: {you}" box
- Stamps `unstaged_by`, `unstaged_at`, `unstage_reason` on PT; sets status = `cancelled`
- `PickTicketService::unstage()` added
- Timeline card on PT show: displays "Unstaged by {name}" entry + reason section
- `PickTicket::unstagedBy()` relationship added

### Partial delivery (per-item qty tracking)
- New `delivered_qty decimal(10,2) default 0` on `pick_ticket_items` (migration `2026_03_16_270000_...`)
- New status `partially_delivered` (yellow badge) between staged and delivered
- `PickTicketService::deliver()` rewritten — accepts `$itemQtys` array, updates `delivered_qty` per item, determines full vs partial
- "Record Delivery" modal shows per-item table with "Ordered / Delivered / Remaining / Delivering Now" columns; inputs pre-fill with remaining qty
- WO show page "Mark Delivered" → changed to "Record Delivery" link to PT show page (full UX there)
- PT items table: Ordered | Delivered | Remaining columns (replaces single Qty column)

### Key files
| File | Role |
|------|------|
| `app/Models/PickTicket.php` | Added `unstaged_at` cast, `unstagedBy()`, `partially_delivered` status |
| `app/Models/PickTicketItem.php` | Added `delivered_qty` cast |
| `app/Services/PickTicketService.php` | `createFromWorkOrder()` + `deliver()` updated; `unstage()` added |
| `app/Http/Controllers/Pages/WarehousePickTicketController.php` | `pdf()`, `unstage()` added; `updateStatus()` updated |
| `resources/views/pages/warehouse/pick-tickets/show.blade.php` | Delivery modal, unstage modal, items table, timeline |
| `resources/views/pages/warehouse/pick-tickets/_status-badge.blade.php` | `partially_delivered` badge added |
| `resources/views/pages/work-orders/show.blade.php` | Stage modal, unstage modal, staging meta bar |
| `resources/views/pdf/pick-ticket.blade.php` | New PDF template |

---

## Pick Ticket enhancements (session 16, 2026-03-16)

Full details in `Context/context_warehouse_pick_tickets.md`.

### Staging stock check removed
- `WorkOrderController::stagePickTicket()` previously blocked staging if any material's `InventoryAllocation` totals didn't cover `sale_item.quantity`
- Removed: staged PTs have no allocation requirement; staging just means "preparing this WO"
- Also removed unused `use App\Models\InventoryAllocation` import from `WorkOrderController`

### Partial returns (per-item qty tracking)
- New `returned_qty decimal(10,2) default 0` on `pick_ticket_items` (migration `2026_03_16_280000_...`)
- `PickTicketItem` model: `returned_qty` added to casts
- `PickTicketService::returnTicket()` rewritten — accepts `$itemQtys` array + `$returnNotes`:
  - Adds submitted qty to each item's `returned_qty` (capped at `delivered_qty`)
  - If net-at-site (delivered − returned) > 0 for any item → status = `partially_delivered`
  - If all items fully returned → status = `returned`, clears `released_at` on allocations
- `WarehousePickTicketController::updateStatus()` updated: `return_notes` validation added, passes item qtys + return_notes to `returnTicket()`

### "Return Items" modal on PT show page
- Replaces the old "Mark Returned" direct-form-submit button
- Available for `delivered` and `partially_delivered` statuses
- Per-item table: Delivered | Prev. Returned | At Site | **Returning Now** (input, pre-fills 0)
- Return notes field; "Returned by: {user} · {date}" info bar
- Items table updated: **Returned** + **At Site** columns appear when any `returned_qty > 0`; Remaining = ordered − at-site

### Key files changed
| File | Change |
|------|--------|
| `app/Models/PickTicketItem.php` | Added `returned_qty` cast |
| `app/Services/PickTicketService.php` | `returnTicket()` rewritten for partial returns |
| `app/Http/Controllers/Pages/WarehousePickTicketController.php` | `return_notes` validation; passes item qtys to service |
| `app/Http/Controllers/Pages/WorkOrderController.php` | Removed staging stock check + unused import |
| `resources/views/pages/warehouse/pick-tickets/show.blade.php` | "Return Items" modal + updated items table |
| `database/migrations/2026_03_16_280000_...` | `returned_qty` column on `pick_ticket_items` |

---

## Customers — Insurance fields + UI fixes (session 19, 2026-03-17)

- **5 insurance fields** added to `customers` table: `insurance_company`, `adjuster`, `policy_number`, `claim_number`, `dol` (date)
- Fields are for job site (child) customers only — not top-level parent customers
- **`customers/create.blade.php`** — "Insurance Details" section shown via Alpine.js `x-show="hasParent"` when a parent is selected
- **Opportunity create + edit modals** — both the Create Job Site and Edit Job Site modals include the insurance fields; edit modal pre-populates via Alpine.js `openEdit()`
- **`JobSiteCustomerController::store()`** also now saves full address fields (address2, city, province, postal_code, mobile) that were previously missing
- **RFM show page** — Job No. now displayed in bold under "Job Info"; Job Site card redesigned to match opportunity show style (name, phone, mobile, email, address)
- **Opportunity show page** — Mobile added to Job Site Customer card

---

## Customers module (session 18, 2026-03-16)

### Guard rails + deactivate
- `Customer` model: added `opportunitiesAsParent()` (FK: `parent_customer_id`) and `opportunitiesAsJobSite()` (FK: `job_site_customer_id`) relationships
- `CustomerController::destroy()` — blocks deletion if customer has any linked opportunities (either role) or child customers; redirects back with error message
- `CustomerController::deactivate()` — sets `customer_status = 'inactive'`; route: `POST admin/customers/{customer}/deactivate` → `admin.customers.deactivate` (gated by `edit customers`)
- Index blade: `withCount(['opportunitiesAsParent', 'opportunitiesAsJobSite', 'children'])` passed from controller; actions column shows **Deactivate** (yellow) when `$hasActivity`, **Delete** (red) only when no activity
- Index blade: widened to `max-w-screen-2xl`; added success/error flash banners

### Customer show page
- Route: `GET admin/customers/{customer}` → `admin.customers.show` (already existed in routes)
- Controller: `CustomerController::show()` — loads opportunities as parent (with rfms, estimates, purchaseOrders) + as job site (with parentCustomer) + sales via opportunity_ids
- View: `resources/views/admin/customers/show.blade.php`
  - Customer details card (type, status, phone, email, address, parent customer link, notes)
  - Opportunities table (as main customer) — job#, status, clickable RFM/Estimate/PO links, View button
  - Opportunities table (as job site) — same + shows main customer link
  - Sales table — sale#, job name, status, customer name, View button
  - Sections hidden when empty; count badges per section
  - "No activities" fallback when customer has nothing linked
- Index blade: "View" link added to actions column

### Key facts
- Estimates and Sales have no direct `customer_id` — they link to customers via `opportunity_id` → opportunity → `parent_customer_id` / `job_site_customer_id`
- `customer_status` field already existed on `customers` table — no migration needed for deactivate

---

## Opportunity create — job site modal fix (session 17, 2026-03-16)

- **Problem 1:** Creating a job site from the opportunity create page redirected back to a blank form, losing all entered data.
- **Problem 2:** New job site customers were saved with `parent_id = null` because `#job_site_parent_id` hidden input was never wired to the parent select — so they were hidden in the job site dropdown and couldn't be auto-selected.
- **Fix:** `CustomerController::store()` now appends `?new_js_id={id}` to the redirect URL. Blade JS saves all form state as query params before modal submits, restores it on reload, wires `#job_site_parent_id` via `syncModalParent()`, and auto-selects the new job site via `filterJobSites(newJobSiteId)`.
- **Key files:** `app/Http/Controllers/Admin/CustomerController.php`, `resources/views/pages/opportunities/create.blade.php`

---

**To continue RFC / RTV work:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md. I want to continue working on the RFC / RTV modules. One step at a time.

---

## Global input formatters (session 31, 2026-03-25)

Two global JS scripts auto-format inputs on blur, loaded via `resources/views/layouts/app.blade.php`.

### Phone formatter — `public/assets/js/phone-format.js`
- Class: `phone-input`
- Strips non-digits; formats 10-digit as `604-555-1234`; 11-digit (1+) as `1-604-555-1234`

### Postal code formatter — `public/assets/js/postal-format.js`
- Class: `postal-input`
- Strips spaces, uppercases, reformats `A1A1A1` → `A1A 1A1` on blur
- Dispatches `input` event after formatting so Alpine `x-model` reactive data stays in sync
- For Alpine `x-model` fields, use inline `@blur` Alpine directive instead of relying on the class (avoids reactivity race): `@blur="(function(){ var c = form.postal_code.replace(/\s/g,'').toUpperCase(); if(/^[A-Z]\d[A-Z]\d[A-Z]\d$/.test(c)){ form.postal_code = c.slice(0,3)+' '+c.slice(3); } })()"`
- Applied to: RFM create/edit, Opportunity create/edit (×2 — one Alpine @blur, one postal-input class), Estimate create, Customer create/edit, Vendor create/edit, Installer create/edit, Employee create/edit, Branding settings

---

## Resume prompts for next chat

**To continue email work (RFM templates, HTML bodies, invoice send flow):**
> Read CLAUDE.md and Context/context_email_templates.md and Context/context_graph_mail.md. I want to continue the email system. One step at a time.

**To continue RFM module work:**
> Read CLAUDE.md and Context/context_rfm.md. Next priority: sync MS365 calendar event when an RFM is edited.

**To continue profits / cost tracking:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md. I want to improve the profits modal and sale-level cost rollups. One step at a time.

**To continue estimates/sales work:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md. I want to continue working on estimates and sales. One step at a time.


**To continue Purchase Orders work:**
> Read CLAUDE.md and Context/context_purchase_orders.md. I want to continue working on the Purchase Orders module. One step at a time.

**To continue Work Orders work:**
> Read CLAUDE.md and Context/context_work_orders.md. I want to continue working on the Work Orders module. One step at a time.

**To continue product catalog / estimates / sales work:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md and Context/project-context-product-pricing.md. I want to continue working on the product catalog or estimate/sale builder. One step at a time.

**To continue warehouse / pick ticket work:**
> Read CLAUDE.md and Context/context_warehouse_pick_tickets.md. I want to continue working on the warehouse/pick ticket module. One step at a time.

**To continue customers module work:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md. I want to continue working on the Customers module. One step at a time.

**To start a fresh feature:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md, then tell me the current state of the system before we begin.
