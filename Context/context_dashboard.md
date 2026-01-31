# context_dashboard.md

Date: 2026-01-30 (America/Vancouver)

## Goal
Update the Dashboard Blade design to feel **more modern** (less pastel) while keeping Flowbite/Tailwind styling.
Dashboard layout: top nav + main content with a grid of clickable cards linking to key pages.

## Working Rules
- One step at a time (ask before making changes)
- Avoid assumptions
- Flowbite/Tailwind only

## Current Dashboard Structure (high level)
- Uses `<x-app-layout>` with header slot “Dashboard”
- Welcome card at top
- Card grid (4 cards row 1, 4 cards row 2, 2 cards row 3, 1 card row 4)
- Cards are rendered via a reusable component:
  - `resources/views/components/dashboard-card.blade.php`
  - Cards are included with `@include('components.dashboard-card', ['c' => $c])`
  - Each card config array has: `title`, `subtitle`, `href` (or null), `accent`, `icon`

## Routes / Links (as implemented)
- Customers: `route('admin.customers.index')`
- Opportunities: `route('pages.opportunities.index')`
- Estimates: `route('admin.estimates.index')` (pages.estimates.index also exists)
- Labour Catalog: `route('admin.labour_items.index')`
- Product Catalog: `route('admin.product_lines.index')`
- Vendors: `route('admin.vendors.index')`
- Calendar: `route('pages.calendar.index')`
- Invoices / Inventory / Work Orders / Purchase Orders: currently **Coming soon** (href null)

## Card UX Enhancements already done
1) Modern hover (Option A)
- `transition-all duration-200`
- `hover:-translate-y-1`
- `hover:shadow-xl`
- `hover:ring-2 hover:ring-white/60 dark:hover:ring-gray-700`

2) Whole-card click
- Outer wrapper changed to `<a ...>` so clicking anywhere navigates
- Disabled cards omit href and show “Coming soon”
- Important: do NOT nest `<a>` tags inside another `<a>`

3) Nested anchor fix
- The inner “Open” button was originally an `<a>`, which became invalid when card wrapper became `<a>`
- Fix: changed inner “Open” element to a `<span>` styled like a button (visual only)

## New Design Direction: “Pastel” -> “Harder / Modern”
Current pastel accents were like:
- `bg-blue-50 ... border-blue-200 ...` etc.

Wanted: stronger, more modern look using either:
A) White cards + strong accent stripe (clean SaaS)
B) Solid color cards with white text (bold tiles)

## A/B Test Plan (to compare styles)
Only change two cards initially to compare:

### Customers (Style A: stripe)
Change Customers accent to:
- `bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 border-l-blue-600`

### Opportunities (Style B: solid)
Change Opportunities accent to:
- `bg-emerald-600 text-white border border-emerald-700 dark:bg-emerald-700 dark:border-emerald-800`

### Component tweaks suggested for solid-color card readability
In `dashboard-card.blade.php`, we planned to adjust the title/subtitle text classes so solid cards render readable text.
(This was queued next, after you preview A vs B.)

## Next Steps (when resuming)
1) Apply the A/B test changes to Customers + Opportunities and refresh dashboard to compare.
2) Decide preferred style:
- Mostly A, mostly B, or hybrid (A for most cards + 1–2 hero B cards)
3) Roll the chosen accent approach across all cards.
4) Optional: unify routes to pages.* as Employee UI grows.
