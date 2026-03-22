# Email Templates System — RM Flooring / Floor Manager

Updated: 2026-03-13

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
| `work_order` | Work Order | Stubbed — no WO module yet |
| `purchase_order` | Purchase Order | Stubbed — no PO module yet |
| `invoice` | Invoice | Stubbed — no invoice module yet |

### Admin-only (system notifications)

| Type | Label | Notes |
|------|-------|-------|
| `rfm_created` | RFM Created | Not yet wired — RFM uses hardcoded Mail classes |
| `rfm_updated` | RFM Updated | Not yet wired |

---

## Available Merge Tags

### estimate
`{{customer_name}}`, `{{estimate_number}}`, `{{grand_total}}`, `{{job_name}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{salesperson_name}}`, `{{sender_name}}`, `{{sender_email}}`

### sale
`{{customer_name}}`, `{{sale_number}}`, `{{grand_total}}`, `{{job_name}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{salesperson_name}}`, `{{sender_name}}`, `{{sender_email}}`

### work_order
`{{customer_name}}`, `{{wo_number}}`, `{{job_name}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{sender_name}}`, `{{sender_email}}`

### purchase_order
`{{customer_name}}`, `{{po_number}}`, `{{job_name}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{sender_name}}`, `{{sender_email}}`

### invoice
`{{customer_name}}`, `{{invoice_number}}`, `{{grand_total}}`, `{{job_name}}`, `{{job_address}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{sender_name}}`, `{{sender_email}}`

### rfm_created / rfm_updated
`{{customer_name}}`, `{{rfm_date}}`, `{{rfm_time}}`, `{{job_address}}`, `{{estimator_name}}`, `{{pm_name}}`, `{{special_instructions}}`

---

## Tag Resolution Notes

- `{{pm_first_name}}` — first word of the stored `pm_name` string (e.g. "Jay Powers" → "Jay")
- `{{customer_name}}` — prefers `homeowner_name`, falls back to `customer_name`
- `{{sender_name}}` / `{{sender_email}}` — the logged-in user's name and email
- `{{salesperson_name}}` — `salesperson_1_employee` first + last name, falls back to sender
- Sale `{{customer_name}}` — reads from `sourceEstimate->homeowner_name` first
- Sale `to` address — reads from `sourceEstimate->homeowner_email` (no homeowner fields on Sale directly)

---

## Service

**`app/Services/EmailTemplateService.php`**

```php
// Get user's saved template or built-in default
$templateService->getTemplate(?User $user, string $type): array  // ['subject' => ..., 'body' => ...]

// Replace {{tags}} with values
$templateService->render(string $template, array $vars): string
```

---

## User-Facing Settings Page

**Route:** `GET /settings/email-templates` → `pages.settings.email-templates.index`
**Controller:** `app/Http/Controllers/Pages/EmailTemplateController.php`
**View:** `resources/views/pages/settings/email-templates.blade.php`

- Tabbed interface — one tab per user-type (Estimate, Sale, Work Order, PO, Invoice)
- Shows **Custom** badge if user has a saved template, **Default** badge if using built-in
- Available tags listed with click-to-copy
- Save Template / Reset to Default actions
- Accessible from sidebar dropdown → **Email Templates**

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

- Tabbed interface — RFM Created, RFM Updated
- Same Save / Reset pattern as user page
- Linked from `/admin/settings` → **System Email Templates**

**Routes:**
```
GET    /admin/settings/email-templates           admin.settings.email-templates.index
POST   /admin/settings/email-templates/{type}   admin.settings.email-templates.save
DELETE /admin/settings/email-templates/{type}   admin.settings.email-templates.reset
```

---

## Estimate Send Flow

**Route:** `POST /pages/estimates/{estimate}/send-email` → `pages.estimates.send-email`
**Controller method:** `EstimateController::sendEmail()`

### Flow
1. Staff clicks **Send Email** button (purple) on the estimate edit or show page
2. Alpine.js modal opens with:
   - **To** — quick-select buttons: Job Site email, PM email (if present), Custom text input
   - **CC** — add/remove multiple CC addresses as pills (optional)
   - Subject and Body pre-filled from user's saved template
3. Staff can adjust any field before sending
4. On submit:
   - Track 2 attempted if `user->microsoftAccount->mail_connected`
   - Falls back to Track 1 shared mailbox on failure or if not connected
   - Estimate `status` updated to `sent` on success
5. Flash success/error on redirect back

### Tag vars resolved in `EstimateController::edit()`
```php
'customer_name'   => homeowner_name ?: customer_name
'estimate_number' => estimate_number
'grand_total'     => formatted with $ and 2 decimal places
'job_name'        => job_name
'job_address'     => job_address
'pm_name'         => pm_name
'pm_first_name'   => first word of pm_name
'salesperson_name'=> salesperson1Employee full name ?: auth user name
'sender_name'     => auth()->user()->name
'sender_email'    => auth()->user()->email
```

---

## Sale Send Flow

**Route:** `POST /pages/sales/{sale}/send-email` → `pages.sales.send-email`
**Controller method:** `SaleController::sendEmail()` + private `resolveEmailTemplate()`

### Flow
Same modal/fallback/CC pattern as estimates. Available on both:
- **Edit page:** `resources/views/pages/sales/edit.blade.php`
- **Show page:** `resources/views/pages/sales/show.blade.php`

**To** field quick-select: Job Site email (`sale->job_email` fallback to `sourceEstimate->homeowner_email`), PM email (`sale->opportunity->projectManager->email`), Custom.
`SaleController::show()` and `edit()` both eager-load `opportunity.projectManager` and pass `$pmEmail` to the view.

Sale status is **not** changed on send (sale statuses are workflow states: open, scheduled, etc.).

### Tag vars resolved in `SaleController::resolveEmailTemplate()`
```php
'customer_name'   => sourceEstimate->homeowner_name ?: customer_name
'sale_number'     => sale_number
'grand_total'     => formatted
'job_name'        => job_name
'job_address'     => job_address
'pm_name'         => pm_name
'pm_first_name'   => first word of pm_name
'salesperson_name'=> salesperson1Employee full name ?: auth user name
'sender_name'     => auth()->user()->name
'sender_email'    => auth()->user()->email
```

---

## Open Items

1. **Wire RFM templates** — `rfm_created` / `rfm_updated` admin templates not yet connected to the `RfmController` send logic (currently uses hardcoded `RfmCreatedMail` / `RfmUpdatedMail` classes)
2. **Work Order / PO / Invoice send flows** — template types stubbed, send UI not built (modules don't exist yet)
3. **HTML email bodies** — all sends are currently plain text; future improvement is branded HTML templates
4. **Per-user test send** — available at `/admin/settings/mail` Track 2 section (green **Send Test** button per connected user)
