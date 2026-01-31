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
