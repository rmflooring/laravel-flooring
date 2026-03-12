# Context: Profits Page

Project: Floor Manager (RM Flooring)  
Owner: Richard  
Updated: 2026-03-11

## Purpose

Continue building the shared **Profits page** used by both:

- Estimates
- Sales

This page has now replaced the old profits modal workflow and is the main profit analysis screen.

---

## Current completed status

The shared profits page is now working for both **Estimate** and **Sale** records and is being used as the primary workflow.

### Working features

- Shared Blade page exists at:
  - `resources/views/pages/profits/show.blade.php`
- Shared page works for both:
  - estimate profits
  - sale profits
- Header now shows:
  - `Estimate Profit Analysis` or `Sale Profit Analysis`
  - estimate number or sale number
- A **Back** button now exists in the header:
  - `Back to Estimate`
  - `Back to Sale`
- `Save Costs` button remains in the header
- Profit Summary section exists below the page header
- Rooms are displayed in separate sections/cards
- Each room shows all line items
- Each line item shows:
  - Type
  - Description
  - Qty
  - Unit
  - Sell
  - Line Total
  - editable Cost
  - Cost Total
  - Profit
  - Margin
- Cost inputs are editable directly on the page
- Save works for both estimates and sales
- After save, the page redirects back to the same profits page
- Green success flash message shows after save
- Room footer totals include:
  - Room Sell Total
  - Room Cost Total
  - Room Profit
  - Room Margin
- Live JavaScript recalculation is working for:
  - line Cost Total
  - line Profit
  - line Margin
  - room Sell Total
  - room Cost Total
  - room Profit
  - room Margin
  - top header Profit Summary totals
- Profit and Margin colors update live:
  - under 20% = red
  - 20% to 38% = orange
  - above 38% = green
- Top Profit Summary values now also update live when cost inputs change
- Profit Summary values currently use the working format without additional comma-formatting changes

---

## Important workflow decision completed

The old profits modal is no longer the primary workflow.

### Decision made
Use **Option A**:
- Clicking `Profits` from Edit Estimate opens the full profits page
- Clicking `Profits` from Edit Sale opens the full profits page

### Current navigation flow
- `Edit Estimate -> Profits -> Back to Estimate`
- `Edit Sale -> Profits -> Back to Sale`

### Old modal cleanup completed
The old profits modal component was removed from both pages:

- estimate edit page old modal removed:
  - `<x-modals.profits-modal context="estimate" :record-id="$estimate->id" />`
- sale edit page old modal removed:
  - `<x-modals.profits-modal context="sale" :record-id="$sale->id" />`

---

## Final route / controller structure currently in use

### Routes currently used
These are inside the `pages.` route group.

- `pages.estimates.profits.show`
- `pages.estimates.profits.save-costs`
- `pages.sales.profits.show`
- `pages.sales.profits.save-costs`

### Current URL patterns
- `/pages/estimates/{estimate}/profits`
- `/pages/estimates/{estimate}/profits/save-costs`
- `/pages/sales/{sale}/profits`
- `/pages/sales/{sale}/profits/save-costs`

### Controllers currently used
- Estimate profits controller method is in:
  - `app/Http/Controllers/Admin/EstimateController.php`
- Sale profits controller method is in:
  - `app/Http/Controllers/Pages/SaleController.php`

### Current working controller methods
#### Estimate
- `showProfits(Estimate $estimate)`
  - loads `rooms.items`
  - returns `pages.profits.show`
- `saveProfitCosts(Request $request, Estimate $estimate)`
  - saves updated cost prices and recalculates `cost_total`
  - redirects back to `pages.estimates.profits.show`
  - flashes success message

#### Sale
- `showProfits(Sale $sale)`
  - loads `rooms.items`
  - returns `pages.profits.show`
- `saveProfitCosts(Request $request, Sale $sale)`
  - saves updated cost prices and recalculates `cost_total`
  - redirects back to `pages.sales.profits.show`
  - flashes success message

---

## Edit page profits button behavior now in use

### Estimate edit page
The old modal-trigger button was replaced with a normal link:

```blade
<a href="{{ route('pages.estimates.profits.show', $estimate->id) }}"
  class="relative z-10 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">
  ...
  Profits
</a>
```

### Sale edit page
The old modal-trigger button was replaced with a normal link:

```blade
<a href="{{ route('pages.sales.profits.show', $sale->id) }}"
  class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 border border-gray-300 hover:bg-gray-50">
  ...
  Profits
</a>
```

---

## Current Blade file structure

### Main file
- `resources/views/pages/profits/show.blade.php`

### Current page order
1. Page header
2. Record number (estimate # / sale #)
3. Header right-side actions:
   - Back to Estimate / Back to Sale
   - Save Costs
4. Profit Summary section
5. Room sections
6. Line-item tables
7. Room footer summaries
8. Bottom script for live recalculation

### Current form behavior
The entire page is wrapped in a form using:
- `id="profit-costs-form"`
- dynamic `$saveUrl` based on record type

Save button submits the form normally.

---

## Current Profit Summary markup direction

The current summary section is:

```blade
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Profit Summary</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        ...
    </div>
</div>
```

### Current summary values use data attributes
- `data-summary-sell`
- `data-summary-cost`
- `data-summary-profit`
- `data-summary-margin`

### Small styling improvement already started
The summary grid was updated to:
- `grid-cols-1 md:grid-cols-4`

This makes the summary responsive.

### Note
A more "card-like" KPI styling enhancement was discussed and partially started conceptually, but the key working priority was the live calculation and navigation flow.

---

## Current line-item table columns

The current table has **10 columns**:

1. Type
2. Description
3. Qty
4. Unit
5. Sell
6. Line Total
7. Cost
8. Cost Total
9. Profit
10. Margin

### Important alignment note
Because the table has 10 columns, room summary footer rows must stay aligned to this exact structure.

Current room footer structure has already been corrected.

---

## Current live calculation behavior

JavaScript is added directly at the bottom of `show.blade.php`.

### Live recalculation currently updates
#### Per row
- Cost Total
- Profit
- Margin
- Profit color
- Margin color

#### Per room
- Room Sell Total
- Room Cost Total
- Room Profit
- Room Margin

#### Overall top summary
- Total Sell
- Total Cost
- Total Profit
- Profit Margin

### Current color thresholds
Use inline colors, not Tailwind utility classes, because Tailwind dynamic classes were unreliable for these live states.

#### Thresholds
- `< 20` = red (`#dc2626`)
- `20 - 38` = orange (`#d97706`)
- `> 38` = green (`#16a34a`)

### Important note
Only the **Profit** and **Margin** text change color.
The full row does **not** change color.
That was intentional for readability.

---

## Important implementation details learned during this session

### 1. Scope issue with helper functions
The helper functions like `formatMoney`, `setProfitColor`, and `setMarginColor` were defined inside the main `DOMContentLoaded` block.

Because `updateProfitSummaryHeader()` was outside that block, it could not safely rely on those helper functions.

### 2. Working fix used
Instead of moving everything around, the top summary function now uses inline color logic directly inside `updateProfitSummaryHeader()`.

That restored live updating correctly.

### 3. Duplicate summary function issue was fixed
At one stage, `updateProfitSummaryHeader()` existed twice in the script.

The duplicate function was removed so there is now only one summary update function.

### 4. Room summary data attributes must be updated live
Inside `recalculateRoom(roomCard)`, the room total cells now update both:
- visible text
- `data-value`

This is required so the top summary can correctly read updated room totals.

### 5. Top summary formatting experiment
There was an attempt to switch the top summary to use `formatMoney()` for comma formatting.
That broke because of function scope.

Decision for now:
- keep the currently working version
- revisit formatting improvements later only if needed

---

## Data attributes currently in use for JS

### Row-level
- `data-profit-row`
- `data-item-id`
- `data-qty`
- `data-line-total`
- `data-cost-total`
- `data-profit`
- `data-margin`
- `.profit-cost-input`

### Room-level
- `data-room-card`
- `data-room-sell-total`
- `data-room-cost-total`
- `data-room-profit`
- `data-room-margin`

### Summary-level
- `data-summary-sell`
- `data-summary-cost`
- `data-summary-profit`
- `data-summary-margin`

---

## Confirmed working behavior from testing

### Estimate side
Tested and confirmed:
- Edit Estimate `Profits` button opens full profits page
- profits page loads
- rooms and items load correctly
- cost input saves to DB
- redirect returns to profits page
- saved cost remains visible after reload
- green success message appears
- Back to Estimate works

### Sale side
Tested and confirmed:
- Edit Sale `Profits` button opens full profits page
- profits page loads
- cost input saves to DB
- redirect returns to profits page
- saved cost remains visible after reload
- green success message appears
- Back to Sale works

---

## Recommended next upgrades for a future chat

These were discussed as the most valuable next improvements.

### 1. Profit breakdown by type
Add top-level summary breakdowns for:
- Material Profit
- Labour Profit
- Freight Profit

This is one of the most valuable management features because it shows where margin is being made or lost.

### 2. Highlight negative profit items
If a line item profit goes negative:
- highlight the row or visually flag it
- make problem items obvious immediately

### 3. Target margin indicator
Add something like:
- `Target Margin: 38%`
- `Current Margin: 34%`
- warning state if below target

This would be very useful for estimators.

### 4. Room profitability ranking
Add a simple ranking or summary showing room margins, for example:
- Kitchen 48%
- Bedroom 41%
- Bathroom 22%

Useful for quickly spotting weak rooms.

### 5. Material / Labour / Freight dashboard cards
Expand the Profit Summary into more of a true profit dashboard with category cards.

### 6. Unsaved changes warning
If costs are edited and user navigates away before saving, warn them.

### 7. Permission / lock workflow
Eventually consider:
- some users can view but not edit costs
- approved/finalized estimates or sales may lock profit editing

### 8. Better summary card styling
Possible future UI enhancement:
- stronger KPI-card styling
- smaller labels
- larger values
- maybe icons later

But this is lower priority than the functional upgrades above.

---

## Suggested next coding target when resuming

Resume in:
- `resources/views/pages/profits/show.blade.php`

Recommended next implementation target:
### Add a category-level profit breakdown section
Start with:
- Materials
- Labour
- Freight

This adds real business value without changing the current workflow.

---

## Summary snapshot

The profits page is now fully functioning as a shared tool for estimates and sales.

Current state is best described as:

- shared profits page implemented
- old modal workflow removed
- edit page buttons now route to full profits pages
- back navigation implemented
- cost editing implemented
- saving implemented
- room summaries implemented
- top summary live recalculation implemented
- live color thresholds implemented
- estimate and sale flows tested and working

