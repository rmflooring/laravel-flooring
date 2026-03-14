# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard
Updated: 2026-03-14 (session 8)

## Working style rules
- Flowbite UI required for all new pages/components.
- One step at a time.
- Do not guess routes, schema, file paths, or controller methods.
- Verify with route:list / logs / schema before changing architecture.
- Keep context files updated after meaningful progress.

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
- Documents / Media
- Calendar (MS365 integration)
- Email system (Track 1 + Track 2) — see `Context/context_graph_mail.md`
- Email Templates (per-user + admin system) — see `Context/context_email_templates.md`
- Users / Roles / Employees
- Admin pages (tax groups, settings, email management)

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
- **Wired into**: estimates (edit page), sales (edit + show pages)
- **Not yet wired into**: invoices (module not built yet)

### Fallback pattern (for when wiring Track 2 into estimates/invoices)
```php
$sent = $user->microsoftAccount?->mail_connected
    ? $mailer->sendAsUser($user, $to, $subject, $body, $type)
    : false;
if (! $sent) {
    $mailer->send($to, $subject, $body, $type); // Track 1 fallback
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

### RFM open items
1. Sync MS365 calendar event when RFM is edited
2. Delete RFM route + cancel/delete calendar event on cancel/delete
3. RFM → Estimate creation shortcut from the show page

---

## Sales module summary
Sales are contractual job records created from approved estimates.

Confirmed sales routes:
- `pages.sales.index` → `/pages/sales`
- `pages.sales.show` → `/pages/sales/{sale}`
- `pages.sales.edit` → `/pages/sales/{sale}/edit`
- `pages.sales.profits.save-costs` → `POST /pages/sales/{sale}/profits/save-costs`

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
- **Known RM group IDs:** Team RM `451694e6`, RFM/Measures `b8483c56`, Installations `a6890136`, Warehouse `4bfd495c`.
- **If a user gets 404 on group calendars:** Ensure their Azure AD account is a member of the relevant M365 groups, then re-run Discover.

---

## Work Orders module summary
Full details in `Context/context_work_orders.md`.

- WOs represent scheduled installation tasks assigned to an **Installer** (not a user) for a sale
- WO items = **labour-type sale items** with editable qty and cost; qty tracking prevents over-scheduling
- WO number auto-generated: `WO-YYYY-NNNN` sequential per year
- New table: `work_order_items` (mirrors PO items pattern but for labour)
- Statuses: created → scheduled (requires installer + date) → in_progress → completed; any → cancelled
- Calendar sync: events go to **"RM – Installations" group calendar** (`group_id = a6890136-56b9-42fc-ac2b-8e05c98c0e8c`)
  - Best-effort, uses logged-in user's MS account to auth
  - Create on store, update/delete on changes
- PDF via DomPDF (`resources/views/pdf/work-order.blade.php`)
- Email to installer via Track 1 (shared mailbox) with PDF attached; `sent_at` stamped
- `GraphCalendarService` has `updateEvent()` and `deleteEvent()` methods
- Permissions: view/create/edit/delete work orders → admin, coordinator, estimator, sales (view only: reception)
- WO section on sale show page (below POs); Sale Status page fully wired with WO data

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

## Purchase Orders module summary
Full details in `Context/context_purchase_orders.md`.

- POs are raised against a Sale to order material items from vendors
- Multiple POs per sale allowed (different vendors); only `material` type items included
- PO number auto-generated: `PO-YYYY-NNNN` sequential per year
- Statuses: `pending`, `ordered` (requires vendor order number), `received`, `cancelled`
- Fulfillment methods: delivery to site, warehouse, custom address, or pickup
- **Qty tracking**: system tracks total ordered qty per sale item across all non-cancelled POs
  — fully-ordered items cannot be added to new POs; partial orders show remaining qty
- Soft delete available to users with `delete purchase orders` permission
- Force (permanent) delete available to admin role only
- PDF via DomPDF (`resources/views/pdf/purchase-order.blade.php`)
- Email to vendor via Track 1 (shared mailbox) with PDF attached
- PO summary card shown on Sale show + edit pages and Opportunity show page

### Purchase Order open items
- No invoice/payment tracking against POs yet
- No "received items" partial-receive workflow yet

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
| PDF templates | `resources/views/pdf/estimate.blade.php`, `resources/views/pdf/sale.blade.php`, `resources/views/pdf/purchase-order.blade.php`, `resources/views/pdf/work-order.blade.php` |
| PO controller | `app/Http/Controllers/Pages/PurchaseOrderController.php` |
| PO views | `resources/views/pages/purchase-orders/` |
| PO models | `app/Models/PurchaseOrder.php`, `app/Models/PurchaseOrderItem.php` |
| WO controller | `app/Http/Controllers/Pages/WorkOrderController.php` |
| WO views | `resources/views/pages/work-orders/` |
| WO models | `app/Models/WorkOrder.php`, `app/Models/WorkOrderItem.php` |
| Installer controller | `app/Http/Controllers/Admin/InstallerController.php` |
| Installer views | `resources/views/admin/installers/` |
| Installer model | `app/Models/Installer.php` |
| Branding controller | `app/Http/Controllers/Admin/BrandingController.php` |
| Branding settings view | `resources/views/admin/settings/branding.blade.php` |

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

**To start a fresh feature:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md, then tell me the current state of the system before we begin.
