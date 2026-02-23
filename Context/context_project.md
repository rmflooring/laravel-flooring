# Project Context --- RM Flooring Internal Platform

## System Overview

Internal operations platform for RM Flooring using **Laravel 12**.

Core modules (current): - Opportunities (CRUD + job site customer) -
Estimates (admin UI + room totals + opportunity link) - Sales (index +
view + edit + financial tracking + lock/invoice state) - Documents &
Media (opportunity docs + labels + archive/restore + bulk actions) -
Calendar (FullCalendar + Outlook-style modal UI) - Microsoft 365
Integration (OAuth2 + group calendars + sync) - Users & Roles (Spatie
Permission) - Employees (roles + departments) - Admin (tax groups + tax
index pages)

------------------------------------------------------------------------

## Rules (working style)

1.  **Flowbite UI required** for all pages/components
2.  **One step at a time**
3.  **No guessing --- ask and verify**
4.  Everything documented
5.  Context files used for resuming

------------------------------------------------------------------------

# Sales Module --- Jan 2026 (Newly Added)

The Sales module represents the **contractual job record** created from
an approved Estimate.

## Routes

### Index

-   URL: /pages/sales
-   Route Name: pages.sales.index

### Show (Read-Only View)

-   URL: /pages/sales/{sale}
-   Route Name: pages.sales.show

### Edit

-   URL: /pages/sales/{sale}/edit
-   Route Name: pages.sales.edit

------------------------------------------------------------------------

## Sales Index Features

Sales index mirrors the Estimates index design and includes:

-   Flowbite-based admin layout
-   Search (q)
-   Status filter
-   Date From / Date To filters
-   Reset button
-   Pagination with summary count
-   Status badges
-   Locked badge (based on locked_at)
-   Revised Contract Total display
-   Approved CO display beneath total
-   Invoiced Total display
-   Partial / Fully invoiced indicator
-   View button (read-only page)
-   Edit button

------------------------------------------------------------------------

## Financial Logic

Revised Contract Total = revised_contract_total fallback →
locked_grand_total fallback → grand_total

Locked state: - Locked if locked_at is not null

Invoicing state: - Fully invoiced if is_fully_invoiced = true -
Partially invoiced if invoiced_total \> 0 and not fully invoiced

------------------------------------------------------------------------

## Sales Philosophy

-   Estimates = Proposal versions
-   Sales = Locked contractual job records
-   Change Orders adjust contract total
-   Locked snapshot protects financial integrity
-   Sales index designed for operational + financial oversight

------------------------------------------------------------------------

## When Resuming Work

Say: **"Resume from project_context.md"**

------------------------------------------------------------------------

## Golden Rules (do not break)

-   Always Flowbite UI
-   One step at a time
-   Never assume schema, routes, or file paths
-   Ask how to verify when unsure
-   Keep context updated

------------------------------------------------------------------------

End of Updated Project Context

---

## Update Log (2026-02-21)

### Opportunities → Show page: display linked Sales
- Confirmed `sales` table includes `opportunity_id` and fields like `sale_number`, `status`, `revised_contract_total`, `locked_grand_total`, `invoiced_total`, `locked_at`, etc.
- Implementation pattern:
  - In `OpportunityController@show($id)`, load sales for the opportunity:
    - `Sale::where('opportunity_id', $opportunity->id)->latest('updated_at')->get();`
  - Pass `$sales` to `resources/views/pages/opportunities/show.blade.php`.
  - Render Sales in the “Job Transactions” table (Sales column) as a list of linked sales (link to `pages.sales.edit` for now).
- Styling requirement: keep using Flowbite/Tailwind components to match the Opportunities show layout.

