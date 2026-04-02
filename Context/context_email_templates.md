# Email Templates System — RM Flooring / Floor Manager

Updated: 2026-04-02

---

## Overview

A per-user email template system allowing each staff member to customise the subject and body for customer-facing emails. Admin-only templates cover system notification emails (RFM alerts etc.).

Templates support `{{merge_tags}}` that are resolved at send time with live record data.

---

## Database

### `email_templates` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `user_id` | FK → users, nullable | null = system/admin template |
| `type` | string | See types below |
| `subject` | string | Supports merge tags |
| `body` | text | Supports merge tags |
| timestamps | | |
| unique | `(user_id, type)` | One template per user per type |

---

## Template Types

### User-editable (customer-facing)

| Type | Label | Status |
|------|-------|--------|
| `estimate` | Estimate | Active — wired to estimate send flow |
| `sale` | Sale | Active — wired to sale send flow |
| `work_order` | Work Order | Active — wired to WO send flow |
| `purchase_order` | Purchase Order | Stubbed — no PO email template rendering yet |
| `invoice` | Invoice | Stubbed — no invoice email template rendering yet |

### Admin-only (system notifications)

| Type | Label | Notes |
|------|-------|-------|
| `rfm_created_estimator` | RFM Created — Estimator | Fully wired to `RfmCreatedMail` |
| `rfm_created_pm` | RFM Created — PM | Fully wired to `RfmCreatedMail` |
| `rfm_updated_estimator` | RFM Updated — Estimator | Fully wired to `RfmUpdatedMail` (changes diff auto-prepended) |
| `rfm_updated_pm` | RFM Updated — PM | Fully wired to `RfmUpdatedMail` |

> Note: The old `rfm_created` and `rfm_updated` types were replaced with the 4 types above. Both `RfmCreatedMail` and `RfmUpdatedMail` now use `EmailTemplateService` — no more hardcoded bodies. The "what changed" diff block is automatically prepended to the estimator body for updated emails.

---

## Available Merge Tags

### estimate
`{{customer_name}}`, `{{estimate_number}}`, `{{grand_total}}`, `{{job_name}}`, `{{job_no}}`, `{{job_address}}`, `{{job_phone}}`, `{{job_mobile}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{salesperson_name}}`, `{{sender_name}}`, `{{sender_email}}`

### sale
`{{customer_name}}`, `{{sale_number}}`, `{{grand_total}}`, `{{job_name}}`, `{{job_no}}`, `{{job_address}}`, `{{job_phone}}`, `{{job_mobile}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{salesperson_name}}`, `{{sender_name}}`, `{{sender_email}}`

### work_order
`{{customer_name}}`, `{{wo_number}}`, `{{job_name}}`, `{{job_no}}`, `{{job_address}}`, `{{job_phone}}`, `{{job_mobile}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{sender_name}}`, `{{sender_email}}`, `{{wo_link}}`

### purchase_order
`{{customer_name}}`, `{{po_number}}`, `{{job_name}}`, `{{job_no}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{sender_name}}`, `{{sender_email}}`

### invoice
`{{customer_name}}`, `{{invoice_number}}`, `{{grand_total}}`, `{{job_name}}`, `{{job_no}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{sender_name}}`, `{{sender_email}}`

### rfm_created_estimator / rfm_updated_estimator
`{{customer_name}}`, `{{job_no}}`, `{{job_site}}`, `{{rfm_date}}`, `{{rfm_time}}`, `{{job_address}}`, `{{job_phone}}`, `{{job_mobile}}`, `{{flooring_type}}`, `{{special_instructions}}`, `{{estimator_name}}`, `{{estimator_first_name}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{rfm_link}}`

### rfm_created_pm / rfm_updated_pm
`{{customer_name}}`, `{{job_no}}`, `{{job_site}}`, `{{rfm_date}}`, `{{rfm_time}}`, `{{job_address}}`, `{{job_phone}}`, `{{job_mobile}}`, `{{special_instructions}}`, `{{estimator_name}}`, `{{estimator_first_name}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{rfm_link}}`

**RFM-specific tag notes:**
- `{{job_site}}` — `opportunity->jobSiteCustomer` company name or name
- `{{job_phone}}` / `{{job_mobile}}` — from `opportunity->jobSiteCustomer->phone` / `->mobile`
- `{{rfm_link}}` — mobile RFM URL (`/m/rfm/{id}`)
- `{{flooring_type}}` — comma-joined flooring type labels from the RFM

---

## Tag Resolution Notes

- `{{pm_first_name}}` — first word of the stored `pm_name` string (e.g. "Jay Powers" → "Jay")
- `{{customer_name}}` — prefers `homeowner_name`, falls back to `customer_name`
- `{{sender_name}}` / `{{sender_email}}` — the logged-in user's name and email
- `{{salesperson_name}}` — `salesperson_1_employee` first + last name, falls back to sender
- Sale `{{customer_name}}` — reads from `sourceEstimate->homeowner_name` first
- `{{job_phone}}` / `{{job_mobile}}` on estimates — from `homeowner_phone` / `homeowner_mobile`
- `{{job_phone}}` / `{{job_mobile}}` on sales/WOs — from `sale->job_phone` / `sale->job_mobile`

---

## Jobsite Contact Fields

Estimates store: `homeowner_phone`, `homeowner_mobile`, `homeowner_email`
Sales store: `job_phone`, `job_mobile`, `job_email`

The estimate create form pre-fills phone/mobile from `opportunity->jobSiteCustomer->phone/mobile`.
The sale edit form uses `name="homeowner_phone"` / `name="homeowner_mobile"` which map to `job_phone` / `job_mobile` on save.

---

## Service

**`app/Services/EmailTemplateService.php`**

```php
// Get saved template (user or system) or fall back to built-in default
$templateService->getTemplate(?User $user, string $type): array  // ['subject' => ..., 'body' => ...]

// Replace {{tags}} with values
$templateService->render(string $template, array $vars): string
```

Pass `null` for `$user` to get system/admin templates (rfm types).

---

## User-Facing Settings Page

**Route:** `GET /settings/email-templates` → `pages.settings.email-templates.index`
**Controller:** `app/Http/Controllers/Pages/EmailTemplateController.php`
**View:** `resources/views/pages/settings/email-templates.blade.php`

- Tabbed interface — one tab per user-type (Estimate, Sale, Work Order, PO, Invoice)
- Shows **Custom** badge if user has a saved template, **Default** badge if using built-in
- Available tags listed with click-to-copy
- Save Template / Reset to Default actions

**Routes:**
```
GET    /settings/email-templates           pages.settings.email-templates.index
POST   /settings/email-templates/{type}   pages.settings.email-templates.save
DELETE /settings/email-templates/{type}   pages.settings.email-templates.reset
```

---

## Admin Settings Page

**Route:** `GET /admin/settings/email-templates` → `admin.settings.email-templates.index`
**Controller:** `app/Http/Controllers/Admin/AdminEmailTemplateController.php`
**View:** `resources/views/admin/settings/email-templates.blade.php`

- 4 tabs: RFM Created (Estimator), RFM Created (PM), RFM Updated (Estimator), RFM Updated (PM)
- Same Save / Reset pattern as user page

**Routes:**
```
GET    /admin/settings/email-templates           admin.settings.email-templates.index
POST   /admin/settings/email-templates/{type}   admin.settings.email-templates.save
DELETE /admin/settings/email-templates/{type}   admin.settings.email-templates.reset
```

---

## Estimate Send Flow

**Route:** `POST /pages/estimates/{estimate}/send-email`
**Controller method:** `EstimateController::sendEmail()` + `edit()` / `show()`

Tag vars include `job_phone` and `job_mobile` (from `homeowner_phone` / `homeowner_mobile`).

---

## Sale Send Flow

**Route:** `POST /pages/sales/{sale}/send-email`
**Controller method:** `SaleController::sendEmail()` + private `resolveEmailTemplate()`

Tag vars include `job_phone` and `job_mobile` (from `sale->job_phone` / `sale->job_mobile`).

---

## Work Order Send Flow

**Route:** `POST /pages/work-orders/{workOrder}/send-email`
**Controller method:** `WorkOrderController::sendEmail()` + private `resolveEmailTemplate()`

Tag vars include `job_phone` and `job_mobile` (from `sale->job_phone` / `sale->job_mobile`).

---

## RFM Email Flow

**Mailable classes:** `app/Mail/RfmCreatedMail.php`, `app/Mail/RfmUpdatedMail.php`

Both classes use `EmailTemplateService` with separate template types per recipient:
- `RfmCreatedMail` → `rfm_created_estimator` (estimator), `rfm_created_pm` (PM)
- `RfmUpdatedMail` → `rfm_updated_estimator` (estimator), `rfm_updated_pm` (PM)

`RfmUpdatedMail` automatically prepends the "what changed" diff block before the rendered estimator body when `$changes` is non-empty.

Both share a `buildVars()` helper that resolves all tags from the `$rfm` and `$opportunity` objects.

---

## Open Items

1. **PO / Invoice send flows** — template types stubbed, no template rendering wired yet
2. **HTML email bodies** — all sends are currently plain text; future improvement is branded HTML templates
3. **Per-user test send** — available at `/admin/settings/mail` Track 2 section
