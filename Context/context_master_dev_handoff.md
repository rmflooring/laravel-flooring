# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard
Updated: 2026-03-16 (session 15)

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

### Delete Estimates (session 14, 2026-03-16)
- `EstimateController::destroy()` — blocks if a linked sale exists (`Sale::where('source_estimate_id', ...)->exists()`); otherwise hard-deletes (rooms + items cascade at DB level)
- Route: `DELETE pages/estimates/{estimate}` → `pages.estimates.destroy` (middleware: `permission:create estimates`)
- `Estimate` model: added `sale()` hasOne relationship
- Index: `with(['sale:id,source_estimate_id'])` eager-loaded; Delete button only shown when `$estimate->sale` is null
- When a sale is deleted the estimate becomes deletable again

### Delete Sales (session 14, 2026-03-16)
- `SaleController::destroy()` — blocks if POs exist OR work orders exist; otherwise hard-deletes (sale_rooms + sale_items cascade at DB level)
- Route: `DELETE pages/sales/{sale}` → `pages.sales.destroy` (middleware: `permission:create estimates`)
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
- **WO Staging / Pick Tickets (session 14, 2026-03-16)**: "Stage Work Order" button on WO show page creates a `staged` PickTicket with all linked material items. Stock check blocks staging if any material doesn't have sufficient inventory allocated. `staged` is a new PickTicket status (orange badge). `PickTicketService::createFromWorkOrder()` handles creation. Warehouse pick ticket show page updated with staged → deliver action and WO details card. `inventory_allocation_id` made nullable on `pick_ticket_items`.
- **Calendar opt-out bug fix (session 14)**: `$request->boolean('sync_calendar', true)` → changed default to `false` in both `store()` and `update()` — unchecked checkbox posts no value, so `true` default was always overriding the user's opt-out.

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

**To start a fresh feature:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md, then tell me the current state of the system before we begin.
