# Estimate Create Route Refactor – Progress Log

## 🎯 Goal
Move Create Estimate workflow from temporary `mock-create` routes to a clean production-ready path:

**/pages/estimates/create**

While keeping admin routes focused only on admin operations.

---

## ✅ What’s Already Done

### 1. Blade & JS cleanup
- Renamed `mock-create.blade.php` → `create.blade.php`
- Renamed `estimate_mock.js` → `estimate.js`
- Updated all script references
- Removed leftover mock files and duplicate blade copies

---

### 2. Real Create Estimate route added (Pages area)

Route now exists and works:

```
GET /pages/estimates/create
Route name: pages.estimates.create
```

Supports:
```
/pages/estimates/create?opportunity_id=1
```

Loads:
- Opportunity data
- Employees list
- Default tax group
- Tax groups for modal

---

### 3. Admin create route also exists (optional)

```
GET /admin/estimates/create
```

But primary workflow is now under `/pages` (staff flow).

---

## 🧠 Important Fix Learned

Laravel was mistakenly treating `/admin/estimates/create` as `{estimate}` because numeric constraints were missing.

Solution going forward (best practice):

```php
->whereNumber('estimate')
```

on all `{estimate}` routes.

---

## 📌 Current Working URLs

### ✅ Preferred workflow
```
/pages/estimates/create
```

### ⚠ Temporary legacy (still exists for now)
```
/pages/estimates/mock-create
/admin/estimates/mock-create
```

---

## ⏭ Next Steps (To Do Later — safely & cleanly)

### ✔ Update links
Change all buttons and navigation from:

```
/mock-create
```

to:

```
/pages/estimates/create
```

---

### ✔ Remove legacy mock routes
Safely delete:

```php
/admin/estimates/mock-create
/pages/estimates/mock-create
```

once all links are migrated.

---

### ✔ Keep admin area clean
Admin should only handle:
- index
- store
- edit
- update

UI creation flow lives under `/pages`.

---

## ✅ Current Status

Create Estimate refactor is stable and working.
No breaking changes.
Ready for link migration when continuing.

---

(Last updated during route refactor session — safe stopping point)

