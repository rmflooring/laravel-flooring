# context_sidebar.md
Date: 2026-01-30 (America/Vancouver)

## Goal
Update the **admin sidebar** so that:
- **Products** is no longer a direct link.
- **Products** shows a **right-arrow (→)** and on **hover** opens a **flyout menu to the right** with:
  - Product Types → `/admin/product-types` (route: `admin.product_types.index`)
  - Product Lines → `/admin/product-lines` (route: `admin.product_lines.index`)
- **Product Styles** are **not** included in the sidebar because they are tied to a Product Line (nested routes).

Then apply the **same UI pattern** to **Labour**:
- **Labour** becomes a hover flyout with:
  - Labour Types → `/admin/labour-types` (route: `admin.labour_types.index`)
  - Labour Items → `/admin/labour-items` (route: `admin.labour_items.index`)

## Key UX Decisions
- Use **hover-to-open flyout (desktop-style app menu)**.
- Use a **right arrow icon** (not a down arrow).
- Flyout should appear **beside the menu item** (to the right), not below it.
- Keep implementation **simple and CSS-only** using Tailwind classes (no Flowbite dropdown JS needed for these flyouts).

## Confirmed Routes
User confirmed these product routes exist:
- `admin.product_types.index` → `GET /admin/product-types`
- `admin.product_lines.index` → `GET /admin/product-lines`

User confirmed these labour routes exist:
- `admin.labour_types.index` → `GET /admin/labour-types`
- `admin.labour_items.index` → `GET /admin/labour-items`

## Working Implementation Pattern (Flyout)
Working structure used in the sidebar:

- Parent `<li class="relative group">`
- Parent clickable row is an `<a href="#" onclick="return false;">` containing:
  - left icon + label
  - right-arrow icon
- Flyout menu is an absolutely-positioned `<div>`:
  - `class="absolute left-full top-0 z-50 hidden w-56 rounded-lg bg-white shadow group-hover:block dark:bg-gray-700"`
  - Contains an `<ul>` with links

### Products Flyout (Working Block)
```blade
{{-- Products flyout (hover) --}}
<li class="relative group">
  <a
    href="#"
    class="sidebar-link flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
    onclick="return false;"
  >
    <div class="flex items-center gap-3">
      <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 0 0 5l10 5 10-5L10 0Z"/>
        <path d="M0 7l10 5 10-5v8l-10 5L0 15V7Z"/>
      </svg>
      <span class="sidebar-label">Products</span>
    </div>

    <!-- right arrow -->
    <svg class="h-4 w-4 text-gray-500 dark:text-gray-400 sidebar-label" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
  </a>

  <!-- Flyout menu -->
  <div class="absolute left-full top-0 z-50 hidden w-56 rounded-lg bg-white shadow group-hover:block dark:bg-gray-700">
    <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
      <li>
        <a href="{{ route('admin.product_types.index') }}"
           class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
          Product Types
        </a>
      </li>
      <li>
        <a href="{{ route('admin.product_lines.index') }}"
           class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
          Product Lines
        </a>
      </li>
    </ul>
  </div>
</li>
```

### Labour Flyout (Applied Same Pattern)
Routes used:
- `route('admin.labour_types.index')`
- `route('admin.labour_items.index')`

## Sidebar Layout Notes / Pitfalls Encountered
- A later attempt to add scrolling wrappers (`overflow-y-auto`) and additional nested `<div>` wrappers caused the flyout hover behavior to stop working.
- Returning to the simpler structure (no extra wrapper between the `<li class="relative group">` and its flyout siblings) restored correct hover behavior.
- The “working version” is the one with:
  - `<div class="relative h-full px-3 py-4">` (no extra scroll wrapper inserted in the middle)
  - `<ul class="space-y-1 font-medium">` directly containing the list items

## Current Status
- Products flyout working (hover opens right-side menu).
- Labour flyout added and working.
- User confirmed: “it looks great”.

## Next Possible Enhancements (Not Implemented)
- Active state highlighting when on a Products/Labour child route.
- Add small hover delay or “safe triangle” behavior to prevent accidental close.
- Add subtle animation for flyout appearance.
- Support collapsed sidebar: show flyout when only icons are visible.

