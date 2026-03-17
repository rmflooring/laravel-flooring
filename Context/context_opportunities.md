# Opportunities Module – Project Context

## Overview
We have built a full Opportunities CRUD flow inside the Laravel app under the `/pages/opportunities` namespace.

Includes:
- Create Opportunity
- Edit Opportunity
- Show Opportunity
- Index (List) with filters, sorting, pagination, and search

Routes:
Route::resource('opportunities', OpportunityController::class);

---

## Index Page (pages/opportunities/index.blade.php)

### Filters
- Search (`q`)
- Status (`status`)
- Parent Customer
- Project Manager (auto-submit)
- Sort dropdown

### Sorting
- updated_desc (default)
- updated_asc
- job_no_asc
- job_no_desc

### UX Improvements
- Status column narrower
- Sort column wider
- Responsive grid layout

---

## Create + Edit Pages

### Sales Person Dropdowns (Employees)

Replaced text inputs with employee-powered dropdowns.

Stored values:
- `sales_person_1` → Employee ID
- `sales_person_2` → Employee ID

Controller:
```php
$employees = Employee::orderBy('first_name')->get(['id','first_name']);
```

Blade:
```blade
<option value="{{ $e->id }}">{{ $e->first_name }}</option>
```

Edit page uses `@selected()` for prefill.

---

## Show Page

### Sales Person Display
IDs converted to names using:

```php
$salesPeople = Employee::whereIn('id', [...])->get()->keyBy('id');
```

### Clickable Estimates
Each estimate links to edit:

```blade
route('admin.estimates.edit', $estimate->id)
```

### Estimate Sorting
- Newest → Oldest
- Oldest → Newest
Sorted in blade using `created_at`

---

## Current State

✔ Employee dropdowns working  
✔ Edit preselect working  
✔ Show name mapping working  
✔ Clickable estimates  
✔ Sortable estimate list  
✔ Filter layout cleaned  
✔ Live server synced  

---

## Next Ideas

- Salesperson filter on index
- Default salesperson = logged-in user
- FK migration for salesperson columns
- Live search
- Saved filters

---

## Working Rules

- One step at a time
- Never assume
- Always verify via Tinker or blade output

---

## Recent Work (2026-02-21)

### Add Sales to Opportunity Show
**Goal:** Show Sales linked to an Opportunity on `resources/views/pages/opportunities/show.blade.php`, inside the existing “Job Transactions” table (Sales column).

**Confirmed DB Link:**
- `sales.opportunity_id` exists (direct relationship).

**Controller change (OpportunityController@show):**
- After loading the Opportunity + relationships, load Sales:
  - `$sales = Sale::where('opportunity_id', $opportunity->id)->latest('updated_at')->get();`
- Pass to view:
  - `return view('pages.opportunities.show', compact('opportunity', 'salesPeople', 'sales'));`
- Ensure controller includes:
  - `use App\Models\Sale;`

**Blade change (Job Transactions table):**
- Replace the Sales column placeholder (`—`) with a list of sales:
  - Show `sale_number` (fallback `Sale #id`) and totals.
  - Total display priority: `revised_contract_total` → `locked_grand_total` → `grand_total`.
  - Optional chips/labels: Locked (`locked_at`), status (`status`), invoicing state (`is_fully_invoiced`, `invoiced_total`).
- Link target for now: `route('pages.sales.edit', $sale->id)` (we can switch to a “show” route later if preferred).

**Deferred:**
- Add “Sort Newest/Oldest” toggle for Sales if needed later.
- Add “Create Sale” button on Opportunity show once the desired create flow/route is confirmed.

---

## Bug Fix: Create Job Site modal loses form state (2026-03-16)

**Problem:** Clicking “+ Create Job Site” on the create opportunity page, filling the modal, and submitting redirected back to a fresh create page — losing all previously entered fields (job no, status, sales persons, parent customer).

**Second bug:** New job site was never being tied to the parent customer because `#job_site_parent_id` hidden input in the modal was never populated by JS, so new customers were saved with `parent_id = null`. This caused them to be hidden in the job site dropdown and prevented auto-selection.

**Fix — `CustomerController::store()`** (`app/Http/Controllers/Admin/CustomerController.php`):
- On `redirect_to` redirect, appends `?new_js_id={customer->id}` directly to the URL (replaces unreliable session flash approach)

**Fix — create opportunity blade** (`resources/views/pages/opportunities/create.blade.php`):
- Modal form submit listener: serializes current form state (`_job_no`, `_status`, `_sp1`, `_sp2`, `_parent_id`, `_pm_id`) into the `redirect_to` URL as query params before submitting
- `restoreFormState()` on page load: reads those URL params and pre-fills all fields; restores parent → triggers `filterJobSites(new_js_id)` and `loadProjectManagers(pm_id)`
- `syncModalParent()`: keeps `#job_site_parent_id` hidden input (and display field) in sync with the selected parent at all times — fixes the root cause of job sites being created without a parent
- `filterJobSites(preselectJobSiteId)`: accepts optional ID to auto-select after filtering
- `loadProjectManagers(preselectId)`: accepts optional ID to auto-select after async load

---

## Insurance fields on job site customers (2026-03-17)

Added 5 insurance-related fields to the `customers` table, only relevant for job site (child) customers:

| Field | Column | Type |
|-------|--------|------|
| Insurance Co. | `insurance_company` | string, nullable |
| Adjuster | `adjuster` | string, nullable |
| Policy # | `policy_number` | string, nullable |
| Claim # | `claim_number` | string, nullable |
| DOL (Date of Loss) | `dol` | date, nullable |

**Migration:** `2026_03_17_181658_add_insurance_fields_to_customers_table`

**Model:** `app/Models/Customer.php` — all 5 fields added to `$fillable`

**Controllers updated:**
- `CustomerController::store()` + `update()` — validation + save
- `JobSiteCustomerController::store()` + `update()` — validation + save (store also now saves full address fields it was previously missing)
- `OpportunityController::create()` + `edit()` — 5 new fields added to `get([...])` so Alpine.js `jobSiteData` includes them

**Views updated:**
- `admin/customers/create.blade.php` — "Insurance Details" section added below Notes; uses Alpine.js `x-show="hasParent"` (hidden until a parent customer is selected from the dropdown)
- `pages/opportunities/create.blade.php` — insurance fields added to the Create Job Site modal
- `pages/opportunities/edit.blade.php` — insurance fields added to both the Create Job Site modal and the Edit Job Site modal; Alpine.js `form` object and `openEdit()` updated to pre-populate from `jobSiteData`

**RFM show page (2026-03-17):**
- Job No. added in bold below the "Job Info" section heading
- Job Site block redesigned to match the opportunity show page style: bordered card with name, phone, mobile, email, then address lines below

**Opportunity show page (2026-03-17):**
- Mobile number added to the Job Site Customer card (between Phone and Email)

