# Project Context — RM Flooring Internal Platform

## System Overview
Internal operations platform for RM Flooring using **Laravel 12**.

Core modules (current):
- Opportunities (CRUD + job site customer)
- Estimates (admin UI + room totals + opportunity link)
- Documents & Media (opportunity docs + labels + archive/restore + bulk actions)
- Calendar (FullCalendar + Outlook-style modal UI)
- Microsoft 365 Integration (OAuth2 + group calendars + sync)
- Users & Roles (Spatie Permission)
- Employees (roles + departments)
- Admin (tax groups + tax index pages)

---

## Rules (working style)
1. **Flowbite UI required** for all pages/components
2. **One step at a time**
3. **No guessing — ask and verify**
4. Everything documented
5. Context files used for resuming

---

## Estimates System — Major Update (Jan 2026)

### Edit Estimate View (Fully Live + Editable)

The edit estimate page is now feature‑complete and behaves like create mode.

#### Now supported
- Editable **salesperson 1 & 2** via employee dropdowns
- Editable **status workflow** with dropdown
- Visual **status badge colors**
  - Draft → gray
  - Sent → blue
  - Revised → amber
  - Approved → green
  - Rejected → red
- All product / freight / labour dropdowns work on **existing rows** (not just newly added rooms)
- Room totals + estimate totals recalc live

#### JS Fix (critical)
Existing room cards must be initialized on page load:

- Product type dropdown
- Manufacturer
- Style
- Color
- Freight dropdown
- Labour type + description

This is done in `estimate_mock.js` via:

```
document.querySelectorAll('.room-card').forEach(...)
```

Without this, edit rows appear filled but are not interactive.

---

### Status Handling

#### Blade
- Status now uses a `<select>` instead of static text
- Displays Flowbite‑styled badge for visual clarity

#### Controller
- Status validated via:

```
status => required|in:draft,sent,revised,approved,rejected
```

- Saved directly in update() using:

```
'status' => $data['status']
```

---

### Salespeople Support

#### Database
New columns added to `estimates` table:

- salesperson_1_employee_id
- salesperson_2_employee_id

With foreign keys to `employees.id`.

#### Blade
Dropdowns now populated from Employee model.

#### Controller
Employees loaded in edit():

```
$employees = Employee::orderBy('first_name')->orderBy('last_name')->get();
```

---

## Recent Migrations Applied on Live Server

Confirmed executed:

- make_calendar_event_id_nullable_on_external_event_links
- update_product_styles_prices_to_two_decimals
- add_default_prices_to_product_lines
- create_freight_items_table
- add_salespeople_to_estimates_table
- add_salespeople_foreign_keys_to_estimates_table
- add_salesperson_employee_ids_to_estimates_table

Live DB is now fully in sync with GitHub.

---

## Deployment (Live Server Verified Flow)

Path:
```
/var/www/fm.rmflooring.ca/laravel
```

Manual safe deploy steps used successfully:

```
git stash push -u

git pull origin main

php artisan migrate --force

php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

(Then test in browser)

---

## Calendar System (Stable)

### Frontend
- FullCalendar
- Flowbite Outlook‑style modals
- Entry: resources/js/pages/calendar.js

### Backend
- CalendarEventController
- Microsoft Graph integration

### Critical Fixes Implemented
- Safe cross‑calendar move (copy → delete)
- All‑day timezone normalization
- Reliable refetch using named event source `fm-feed`
- Proper modal close handling

---

## When Resuming Work

Say:
**"Resume from project_context.md"**

---

## Golden Rules (do not break)

- Always Flowbite UI
- One step at a time
- Never assume schema, routes, or file paths
- Ask how to verify when unsure
- Keep context updated


---

## GL Accounts Module (Admin) — Jan 2026

### Structure

GL Accounts use a two‑level classification system:

- **Account Types** (Asset, Liability, Equity, Income, Expense)
- **Detail Types** (sub‑categories tied to each Account Type)

Example (Assets):
- Cash and Cash Equivalents
- Accounts Receivable
- Inventory
- Fixed Assets
- Prepaid Expenses

### Important Behavior

Detail Types are **loaded dynamically via AJAX** based on selected Account Type:

Route:
```
admin.gl_accounts.detail_types
```

Controller filters by:

```
account_type_id
status = active
```

If no detail types exist for an account type, the dropdown returns:

```
[]
```

(This is expected behavior — not a bug)

### Common Debug Insight

If Detail Type dropdown appears empty but AJAX succeeds:

✔ JS working  
✔ Routes working  
✔ Permissions working  

❗ Data simply not seeded yet

Confirm with Tinker:

```
DetailType::where('account_type_id', X)->get();
```

### UX Improvement Implemented

When no results are returned:

Dropdown shows:

```
No detail types available for this account type
```

instead of appearing broken.

---
