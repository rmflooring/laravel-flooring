# dashboard_blade_context.md

Date: 2026-01-29 (America/Vancouver)

## Goal
Redesign the Laravel dashboard Blade to show a **top nav** (existing layout) and a **main area** with a grid of modern, Flowbite-style cards that link to key pages:
- Row 1: Customers, Opportunities, Estimates, Invoices
- Row 2: Labour Catalog, Product Catalog, Inventory, Vendors
- Row 3: Work Orders, Purchase Orders
- Row 4: Calendar

## Working Style Rules (important)
- Work **one step at a time**
- **Do not assume** missing info; ask for it
- Use **Flowbite/Tailwind** styling moving forward

## Routing Decision
- Use **named routes** (recommended Laravel best practice).
- Dashboard target: **Employee UI (pages.* style)**.
  - However, not all needed modules have `pages.*` routes yet, so we agreed to keep a **mixed approach for now**:
    - Use existing `pages.*` routes where available (Opportunities, Estimates, Calendar)
    - Use existing `admin.*` routes for catalogs/setup pages where `pages.*` routes don't exist yet (e.g., Customers, Labour Catalog, Product Catalog, Vendors)
    - Show missing modules as **Coming soon** to avoid broken links (Invoices, Inventory, Work Orders, Purchase Orders — routes not present in pasted list)

## Route Evidence Provided (from `php artisan route:list | grep ...`)
Key routes confirmed:
- Customers: `admin.customers.index`
- Opportunities: `pages.opportunities.index`
- Estimates: `admin.estimates.index` and `pages.estimates.index` (both exist; Employee UI prefers `pages.estimates.index`)
- Labour Catalog: `admin.labour_items.index`
- Product Catalog: `admin.product_lines.index` (also product types exist: `admin.product_types.index`)
- Vendors: `admin.vendors.index`
- Calendar: `pages.calendar.index`
- Dashboard: `dashboard`

## Dashboard Card Layout Implemented (concept)
- 4 cards (row 1), 4 cards (row 2), 2 cards (row 3), 1 card (row 4)
- Each card has:
  - Title, subtitle
  - Icon (SVG path string in config array)
  - Accent background/border color per card
  - “Open” pill / “Coming soon” badge

## Componentization
We created a reusable Blade component:
- `resources/views/components/dashboard-card.blade.php`

And the dashboard view uses it like:
- `@include('components.dashboard-card', ['c' => $c])`
with `$c` containing:
- `title`, `subtitle`, `href` (or null), `accent`, `icon`

## UX Enhancements
### 1) Hover style (Option A chosen)
Modern SaaS hover effect:
- Slight lift: `hover:-translate-y-1`
- Shadow: `hover:shadow-xl`
- Glow ring: `hover:ring-2 hover:ring-white/60 dark:hover:ring-gray-700`
- Transition: `transition-all duration-200`

Applied to the outer wrapper class.

### 2) Make the whole card clickable
Changed the card wrapper from a `<div>` to an outer `<a>` so clicking anywhere navigates.

Important HTML rule discovered:
- **Do NOT nest `<a>` tags inside another `<a>`** (invalid HTML).
- When the wrapper became `<a>`, the inner “Open” link had to be removed or converted to a non-anchor element.

Final approach:
- Outer wrapper: `<a ...>` when enabled; no `href` when disabled.
- Inner “Open” element changed from `<a>` to `<span>` (visual only), to avoid nested anchors.

## Key Fix When It Broke
Problem:
- Nested anchor tags existed after making the wrapper clickable:
  - Outer `<a>` wrapper + inner `<a href="{{ $c['href'] }}">Open</a>`

Fix:
- Replace the inner `<a>` with a `<span>` styled like a button.

## Current Expected Behavior
- Clicking anywhere on an enabled card navigates to its route.
- Disabled cards show “Coming soon” and do not navigate.
- Cards have modern hover lift + glow.

## Next Possible Steps (not done yet)
- Add real module counts (e.g., open opportunities) on cards.
- Add keyboard focus styling for accessibility.
- Convert remaining admin-linked cards to `pages.*` once those modules exist for Employee UI.
