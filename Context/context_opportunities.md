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

