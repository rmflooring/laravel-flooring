# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Floor Manager** — a custom CRM and job management system for RM Flooring (internal staff only). It replaces paper job folders, centralizing all job-related materials, communication, estimates, sales, and financials in one place per job.

## Commands

```bash
# Start all dev services (Laravel, queue, log tailing, Vite HMR) — use this instead of running them separately
composer run dev

# Run tests
composer run test
# or
php artisan test
# Run a single test file
php artisan test tests/Feature/SomeTest.php

# Build frontend assets
npm run build

# Start Vite only (HMR)
npm run dev

# First-time setup
composer run setup
```

**Live server**: `/var/www/fm.rmflooring.ca/laravel/` on `rmserveradmin@rmserver`
**Database**: MariaDB, database name `fm_laravel`

## Architecture

### Stack
- Laravel 12, PHP 8.2+
- Blade + Tailwind CSS 3 + Alpine.js + Bootstrap 5
- Vite (entry points: `resources/css/app.css`, `resources/js/app.js`)
- Spatie `laravel-permission` for roles/permissions

### Route Structure & Middleware

There are two protected route areas with different access levels:

| Prefix | Middleware | Purpose |
|--------|-----------|---------|
| `/admin` | `admin` | True system administration (users, roles, settings, accounting) |
| `/pages` | `auth + verified` + per-route `permission:` | Staff workflows (opportunities, estimates, sales) |

The `admin` middleware (`app/Http/Middleware/AdminMiddleware.php`) is for superusers only. Operational management routes use `role_or_permission:admin|view X`.

### Job Lifecycle

The same `Job` entity transitions through states — **never create separate records for each stage**:

```
Opportunity → RFM → Estimate (pending) → Sale → Invoice
```

State transitions are permission-gated (see `project_context.md` section 3 for details).

### Estimate / Sale Data Model

Both `Estimate` and `Sale` follow the same hierarchical structure:

```
Estimate / Sale
  └── Rooms (ordered by sort_order)
        └── Items (material | labour | freight rows)
```

- `app/Models/Estimate.php` → `EstimateRoom` → `EstimateItem`
- `app/Models/Sale.php` → `SaleRoom` → `SaleItem`
- **Never flatten this room structure** unless explicitly instructed
- Sales are created by converting an Estimate (`EstimateController::convertToSale`)
- `Sale.sale_number` is auto-generated on create in `YYYY-0001` format

### Controllers

- `app/Http/Controllers/Admin/` — admin-area controllers (user/role/tax/product management + EstimateController which also handles estimate profits)
- `app/Http/Controllers/Pages/` — staff-facing controllers (OpportunityController, SaleController, etc.)
- `app/Http/Controllers/Api/` — lightweight AJAX endpoints (product types, labour types, pricing)

### Key Views

- Main layout: `resources/views/layouts/app.blade.php`
- Estimate create/edit: `resources/views/admin/estimates/`
- Sale edit: `resources/views/pages/sales/edit.blade.php`
- Shared profits page (used for both Estimates and Sales): `resources/views/pages/profits/show.blade.php`

### Profits Page

The profits page (`pages/profits/show.blade.php`) is shared between estimates and sales:
- Routes: `pages.estimates.profits.show` and `pages.sales.profits.show`
- Controllers: `EstimateController::showProfits` / `SaleController::showProfits`
- Each line item has editable `cost_price`; `cost_total` is recalculated on save
- Live JS recalculation runs directly in the Blade file (not in `app.js`)
- Profit color thresholds: `< 20%` = red, `20–38%` = orange, `> 38%` = green (using inline hex colors, not Tailwind classes, for dynamic JS reliability)

### Estimate API Endpoints

AJAX endpoints for the estimate/sale UI dropdowns are prefixed under `estimates/api/`:
- `product-types`, `manufacturers`, `product-lines`, `styles`, `labour-types`, `labour-items`, `freight-items`
- Tax group rate: `estimates/api/tax-groups/{tax_group}/rate`

### Frontend Notes

- Vite HMR is configured for LAN access on `192.168.1.80:5173`
- Alpine.js and Bootstrap 5 are both in use (Bootstrap for modals/components, Tailwind for layout/utility)
- Feature-specific JavaScript is often written inline at the bottom of Blade files, not in `app.js`
- Tailwind dynamic classes are unreliable for JS-driven state — use inline styles instead

### Permissions

Defined via Spatie `laravel-permission`. Key permissions include: `create estimates`, `view estimates`, `edit estimates`, `connect microsoft calendar`. The `admin` role bypasses most permission checks via the `role_or_permission:admin|X` middleware.

## Key Files Reference

| What | Where |
|------|-------|
| Routes | `routes/web.php` |
| Models | `app/Models/` |
| Admin controllers | `app/Http/Controllers/Admin/` |
| Pages controllers | `app/Http/Controllers/Pages/` |
| Main layout | `resources/views/layouts/app.blade.php` |
| Tailwind config | `tailwind.config.js` |
| Vite config | `vite.config.js` |
| Migrations | `database/migrations/` |
| Project context | `project_context.md` (business rules & workflow) |
| Profits page context | `Context/context_profits_page.md` |
