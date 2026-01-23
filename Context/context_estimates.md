# Context — Estimates Dropdown Permissions Fix

## Date
2026-01-23

## Problem
Dropdowns in the Estimate UI (product type, manufacturer, product line, styles/colors) were **not working for non-admin users**.

- Admin users: everything worked
- Non-admin users: dropdowns were empty
- Browser showed **403 Forbidden** errors

## Root Cause
The Estimate UI JavaScript was calling **admin-only routes**:

- `/admin/estimates/api/product-types`
- `/admin/estimates/api/product-lines`
- `/admin/estimates/api/manufacturers`
- `/admin/product-lines/{id}/product-styles`

These routes are protected by the `admin` middleware. Regular users correctly received 403 responses, so dropdown data never loaded.

Some URLs were:
- **Hardcoded in JS** (`estimate_mock.js`)
- Others injected via **Blade globals** (`window.FM_*` variables)

Fixing only one side was not sufficient — both had to be addressed.

## Investigation Highlights
- Confirmed JS loaded correctly for non-admin users
- Network tab showed 403 responses on admin endpoints
- Verified route middleware via `php artisan route:list -v`
- Found admin-only APIs grouped under `Route::prefix('admin')->middleware('admin')`

## Solution Overview

### 1. Created Non-Admin, Permission-Based API Routes
Added new read-only endpoints outside the admin group, protected by permissions:

```php
Route::prefix('estimates/api')
    ->middleware(['auth', 'permission:create estimates'])
    ->group(function () {
        Route::get('product-types', [Admin\EstimateController::class, 'apiProductTypes']);
        Route::get('product-lines', [Admin\EstimateController::class, 'apiProductLines']);
        Route::get('manufacturers', [Admin\EstimateController::class, 'apiManufacturers']);
        Route::get('product-lines/{product_line}/product-styles', [Admin\ProductStyleController::class, 'index']);
    });
```

### 2. Reused Existing Controllers
- `ProductStyleController@index` already supported JSON via:

```php
if (request()->wantsJson()) { ... }
```

No new controller logic was required.

### 3. Updated JavaScript URLs
In `public/assets/js/estimates/estimate_mock.js`:

- Replaced all `/admin/estimates/api/...` calls with `/estimates/api/...`
- Replaced:

```js
/admin/product-lines/${id}/product-styles
```

with:

```js
/estimates/api/product-lines/${id}/product-styles
```

### 4. Updated Blade Globals
In `resources/views/admin/estimates/mock-create.blade.php`:

- Removed references to `route('admin.*')`
- Switched to non-admin API paths (e.g. `/estimates/api/manufacturers`)

## Result
- Non-admin users can now fully use the Estimate UI
- All dropdowns load correctly
- All API calls return **200 OK**
- Admin routes remain locked down

## Architecture Outcome
- **Admin routes**: CRUD & management UI
- **Estimate UI**: permission-based read APIs
- **Security**: enforced server-side via permissions
- **JS**: role-agnostic, clean, and reusable

## Notes for Future Work
- Consider naming the new estimate API routes
- Reuse these APIs for both create/edit estimate pages
- Optionally extract estimate APIs into a dedicated controller

---
This context captures the full investigation and fix so work can resume without re-debugging.

