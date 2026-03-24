# Installers Module Context

Updated: 2026-03-24

---

## Overview

Installers are subcontractor profiles used to track installation crews and companies. They are a separate entity from Vendors but can be optionally linked to an existing Vendor record with `vendor_type = 'Subcontractor'` for cross-reference.

---

## Data Model

**Table:** `installers`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `user_id` | nullable unique FK → users | Links to a User account for installer portal login; nullOnDelete |
| `vendor_id` | FK → vendors | Nullable. Links to a subcontractor vendor. |
| `company_name` | string | Required |
| `contact_name` | string | Nullable |
| `phone` | string | Nullable |
| `mobile` | string | Nullable |
| `email` | string | Nullable |
| `address` | string | Nullable |
| `address2` | string | Nullable |
| `city` | string | Nullable |
| `province` | string(2) | Nullable. Canadian province code. |
| `postal_code` | string(10) | Nullable |
| `account_number` | string | Nullable |
| `gst_number` | string | Nullable |
| `terms` | string | Nullable. e.g. Net 30, COD |
| `gl_cost_account_id` | FK → gl_accounts | Nullable. Default GL cost account. |
| `gl_sale_account_id` | FK → gl_accounts | Nullable. Default GL sale account. |
| `status` | string | `active` (default) or `inactive` |
| `notes` | text | Nullable |
| `created_by` | FK → users | Auto-set on create |
| `updated_by` | FK → users | Auto-set on update |
| `timestamps` | | |

---

## Model

`app/Models/Installer.php`

Relationships:
- `belongsTo(User, 'user_id')` — `user()` — linked portal user account
- `belongsTo(Vendor)` — via `vendor_id`
- `belongsTo(GLAccount, 'gl_cost_account_id')` — `glCostAccount()`
- `belongsTo(GLAccount, 'gl_sale_account_id')` — `glSaleAccount()`
- `belongsTo(User, 'created_by')` — `creator()`
- `belongsTo(User, 'updated_by')` — `updater()`
- `hasMany(WorkOrder)` — `workOrders()`

Auto-sets `created_by` and `updated_by` via `booted()` hooks.

---

## Controller

`app/Http/Controllers/Admin/InstallerController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `index` | GET `/admin/installers` | List with search + status filter + pagination |
| `create` | GET `/admin/installers/create` | Form with vendor pre-fill dropdown |
| `store` | POST `/admin/installers` | Validate + create |
| `show` | GET `/admin/installers/{installer}` | Read-only detail view |
| `edit` | GET `/admin/installers/{installer}/edit` | Edit form |
| `update` | PUT `/admin/installers/{installer}` | Validate + update |
| `destroy` | DELETE `/admin/installers/{installer}` | Hard delete |

The `create` and `edit` methods load:
- `$subcontractors` — active Vendors with `vendor_type = 'Subcontractor'`
- `$glAccounts` — active GL accounts ordered by account_number

---

## Routes

```
admin.installers.index
admin.installers.create
admin.installers.store
admin.installers.show
admin.installers.edit
admin.installers.update
admin.installers.destroy
```

Middleware: `role_or_permission:admin|view installers`
URL prefix: `/admin/installers`

---

## Views

`resources/views/admin/installers/`

- `index.blade.php` — table with search, status filter, per-page, GL account columns
- `create.blade.php` — Alpine.js pre-fill from subcontractor vendor dropdown
- `edit.blade.php` — same layout; vendor link change does NOT overwrite fields
- `show.blade.php` — read-only detail cards + audit footer + danger zone delete

---

## Navigation

- **Sidebar** (`layouts/sidebar.blade.php`) — sub-item inside the **Vendors flyout** (hover to reveal); flyout label "Vendors" links directly to `/admin/vendors`, sub-links are Vendors + Installers
- **Top nav** (`layouts/navigation.blade.php`) — under "Manage Vendors" dropdown; dropdown highlights when on any `admin.installers.*` route

---

## Vendor Pre-fill (create page)

On the create form, selecting a subcontractor vendor from the dropdown triggers an Alpine.js `prefill()` function that copies the following fields into the form inputs:
`company_name`, `contact_name`, `phone`, `mobile`, `email`, `address`, `address2`, `city`, `province`, `postal_code`, `account_number`, `terms`

The user can then edit any of these before saving. The `vendor_id` is stored as a reference link only — it is not kept in sync automatically.

---

## User Account Linking (2026-03-24)

Installer users log in through the normal auth system but are redirected to the installer portal.

### Admin setup flow
- In Users admin (`/admin/users`), edit or create a user
- Set **User Type = Installer** (radio button)
- Select the **Installer Record** from the dropdown (already-linked installers shown as disabled)
- On save: `installer` role is auto-assigned; `installers.user_id` is set to the new user's ID
- Switching back to **Staff** type clears the `user_id` link and syncs normal roles

### UserController changes
- `create()` / `edit()` now pass `$installers` and `$linkedInstaller` to views
- `store()` / `update()` handle `user_type = installer` → sync role + set `installers.user_id`
- Edit blade detects current type from `$userRoles` to pre-select the right radio

### Post-login redirect
`AuthenticatedSessionController::store()` checks `hasRole('installer')` → redirects to `installer.dashboard`; otherwise continues to normal home.

---

## Installer Portal

See `Context/context_installer_portal.md` for full details.

---

## Open Items / Future Work

- Installer labour rates / pricing section for cost estimation
- Filter Work Orders list by installer
