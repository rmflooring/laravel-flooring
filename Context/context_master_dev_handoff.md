# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard
Updated: 2026-03-14 (session 4)

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
| PDF templates | `resources/views/pdf/estimate.blade.php`, `resources/views/pdf/sale.blade.php`, `resources/views/pdf/purchase-order.blade.php` |
| PO controller | `app/Http/Controllers/Pages/PurchaseOrderController.php` |
| PO views | `resources/views/pages/purchase-orders/` |
| PO models | `app/Models/PurchaseOrder.php`, `app/Models/PurchaseOrderItem.php` |
| Branding controller | `app/Http/Controllers/Admin/BrandingController.php` |
| Branding settings view | `resources/views/admin/settings/branding.blade.php` |

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

**To start a fresh feature:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md, then tell me the current state of the system before we begin.
