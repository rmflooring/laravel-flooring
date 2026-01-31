# Tax Groups Module – Context & Progress

## Date
2026-01-12

## Goal
Implement a full CRUD system for **Tax Groups** in the admin area, using Flowbite + Tailwind, permission-based access, and a separate **Default Tax Group** system.

---

## Existing Related Tables
User already had:
- `tax_agencies`
- `tax_rates`

We added:
- `tax_rate_groups`
- `tax_rate_group_items` (pivot: many-to-many between groups and rates)
- `default_tax` (single-row table to store the default tax group)

MariaDB is being used.

---

## Design Decisions
- A **Tax Group** can contain multiple Tax Rates
- Tax Group has:
  - name
  - description
  - notes
  - soft delete (archived)
  - created_by / updated_by
- Default tax group is stored in a **separate table** (`default_tax`) rather than a column
- Only one default group at a time (single-row pattern)

---

## Routes
Added under admin, permission-based:

```php
Route::resource('tax-groups', \App\Http\Controllers\Admin\TaxGroupController::class)
    ->middleware('role_or_permission:admin|view tax groups')
    ->names([
        'index'   => 'tax_groups.index',
        'create'  => 'tax_groups.create',
        'store'   => 'tax_groups.store',
        'show'    => 'tax_groups.show',
        'edit'    => 'tax_groups.edit',
        'update'  => 'tax_groups.update',
        'destroy' => 'tax_groups.destroy',
    ]);
```

---

## Controller
Controller created:

```
app/Http/Controllers/Admin/TaxGroupController.php
```

### Implemented Methods
- index()
- create()
- store()
- edit()
- update()
- destroy() → soft delete (archive)

### Notes
- `destroy()` sets `deleted_at`
- `update()` does NOT archive
- Default group is set via checkbox (`make_default`)
- Pivot table is rebuilt on update

---

## Views
Flowbite + Tailwind based.

### Create
```
resources/views/admin/tax_groups/create.blade.php
```

### Edit
```
resources/views/admin/tax_groups/edit.blade.php
```

Important fix: The edit page originally had a nested form that caused accidental archiving. This was fixed.

---

## Current Status
CRUD is working:
- Create ✅
- Edit ✅
- Update ✅
- Archive (soft delete) ✅

---

## Next Steps (To Do)
In priority order:

1. Improve Index Page
   - Show total combined rate
   - Show included tax rates
   - Default badge
   - Archived badge
   - Show archived toggle

2. Restore Functionality
   - restore() method
   - restore route
   - Restore button in UI

3. Permissions
   - view tax groups
   - create tax groups
   - edit tax groups
   - delete tax groups

4. Optional
   - Permanent delete
   - Confirmation modals

---

## User Preferences
- One step at a time
- No assumptions
- Ask before implementing
- Flowbite + Tailwind only
- Permission-based access

---

Resume point: **Index UI improvements or Restore feature**

