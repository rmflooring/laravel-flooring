# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard  
Updated: 2026-03-08

## Working style rules
- Flowbite UI required for all new pages/components.
- One step at a time.
- Do not guess routes, schema, file paths, or controller methods.
- Verify with route:list / logs / schema before changing architecture.
- Keep context files updated after meaningful progress.

---

## System overview
Internal operations platform for RM Flooring using Laravel 12.

Current core modules include:
- Opportunities
- Estimates
- Sales
- Documents / Media
- Calendar
- Microsoft 365 integration
- Users / Roles / Employees
- Admin pages including tax groups

---

## Sales module summary
Sales are the contractual job records created from approved estimates.

Confirmed sales routes:
- `pages.sales.index` → `/pages/sales`
- `pages.sales.show` → `/pages/sales/{sale}`
- `pages.sales.edit` → `/pages/sales/{sale}/edit`
- `pages.sales.profits.save-costs` → `POST /pages/sales/{sale}/profits/save-costs`

Sales philosophy:
- Estimates = proposal versions
- Sales = contractual / operational record
- Change orders adjust contract totals
- Locked snapshots protect financial integrity

Financial display logic already documented:
- Revised Contract Total = `revised_contract_total` fallback → `locked_grand_total` fallback → `grand_total`
- Locked if `locked_at` is not null
- Fully invoiced if `is_fully_invoiced = true`
- Partially invoiced if `invoiced_total > 0` and not fully invoiced

---

## Profits / cost tracking overall goal
Costs must flow reliably through the system:

Catalog → Estimate → Sale

Required source fields:
- Products: `product_styles.cost_price`
- Labour: `labour_items.cost`
- Freight: `freight_items.cost_price`

Required persisted fields on line items:
- `cost_price`
- `cost_total` (or `total_cost` depending on table naming in that module)

Primary objective:
- ensure costs are stored and editable for both Estimates and Sales
- enable reliable profit reporting later

---

## Existing profits modal
Shared modal component:
- `resources/views/components/modals/profits-modal.blade.php`

Purpose:
- show line items
- allow editing cost fields
- calculate grouped totals
- support future profit analysis / lock snapshot workflows

Modal can open from:
- Estimate page
- Sale page

---

## Important frontend file history
Estimate builder JS:
- `public/assets/js/estimates/estimate.js`

This file handles:
- dynamic rooms
- row creation
- dropdowns/autofill for materials / labour / freight
- line totals
- room totals
- overall totals

Historical missing piece:
- cost fields were not consistently included in row posts
- cost values needed to be added alongside sell-side values

---

## What was fixed in this chat

### 1) Edit Sale labour cost persistence on normal save
On the Edit Sale Blade, existing labour rows were missing hidden fields needed to preserve cost values when the main **Save Sale** button was used.

Added hidden fields for existing labour rows:
- `id`
- `cost_price`
- `cost_total`

Result:
- labour costs now save correctly on normal Sale save, matching material and freight behavior.

### 2) Sales profits modal direct-save
The Sales profits modal originally displayed “Costs saved successfully” but did not actually persist to DB unless the user also clicked **Save Sale** afterward.

Root causes discovered:
- modal script was hardcoded to estimate save route
- no sale-specific profits save route existed initially
- first attempted controller target did not exist
- then the controller action method did not exist

### 3) Correct modal save URL logic
In `resources/views/components/modals/profits-modal.blade.php`, the JS save URL now switches by context:

```blade
const saveUrl = @json(
    $context === 'sale'
        ? route('pages.sales.profits.save-costs', $recordId)
        : route('pages.estimates.profits.save-costs', $recordId)
);
```

### 4) Sales profits save route
Working route added inside grouped routes:

```php
Route::post('sales/{sale}/profits/save-costs', [\App\Http\Controllers\Pages\SaleController::class, 'saveProfitCosts'])
    ->name('sales.profits.save-costs');
```

Because it lives inside the existing route-name group, Laravel registers it as:
- `pages.sales.profits.save-costs`

Verified via `php artisan route:list --path=sales`.

### 5) SaleController method added
File:
- `app/Http/Controllers/Pages/SaleController.php`

Working method:

```php
public function saveProfitCosts(\Illuminate\Http\Request $request, \App\Models\Sale $sale)
{
    $data = $request->validate([
        'items' => ['required', 'array'],
        'items.*.id' => ['required', 'integer'],
        'items.*.cost_price' => ['nullable', 'numeric'],
    ]);

    \DB::transaction(function () use ($sale, $data) {
        foreach ($data['items'] as $row) {
            $item = \App\Models\SaleItem::where('sale_id', $sale->id)
                ->where('id', (int) $row['id'])
                ->first();

            if (!$item) {
                continue;
            }

            $costPrice = (float) ($row['cost_price'] ?? 0);
            $qty = (float) ($item->quantity ?? 0);

            $item->cost_price = $costPrice;
            $item->cost_total = round($qty * $costPrice, 2);
            $item->save();
        }
    });

    return response()->json([
        'success' => true,
    ]);
}
```

### 6) Verified result
On Edit Sale:
- main Sale save preserves material / freight / labour costs
- profits modal AJAX save now updates DB immediately
- no second Save Sale is required

---

## Troubleshooting history to avoid repeating
These paths were tested and caused problems:

1. Hardcoding the sales modal to the estimate save route  
   Result: modal save hit wrong endpoint.

2. Creating a route to `SaleProfitController`  
   Result: failed because controller did not exist.

3. Adding a route to `SaleController::saveProfitCosts` before method existed  
   Result: route resolved but action failed.

4. Confusion around route names without respecting the existing `pages.` route-name prefix  
   Result: route lookup failures.

5. Checking logs with only the stack tail  
   Better approach: grep the actual exception line first.

---

## Current known-good state
### Edit Sale form
- material costs save
- freight costs save
- labour costs save

### Sales profits modal
- opens correctly
- saves through AJAX
- updates DB immediately
- returns success properly

---

## Best next steps
Recommended next development items:

### A) Recalculate sale-level profit rollups
After modal save, automatically recalculate and persist:
- total material cost
- total labour cost
- total freight cost
- overall profit
- margin

### B) Improve modal UX
Add:
- Profit $
- Margin %
- grouped subtotals by section
- better save-state UI instead of alerts

### C) Shared logic refactor
If estimate and sale profits save flows remain similar, move shared cost-persistence logic into a service or trait.

### D) Continue estimate-side cost flow cleanup
Still keep in mind the broader cost-tracking roadmap:
- Blade row templates must include cost fields
- JS autofill should populate cost values from catalog endpoints
- estimate save must persist those values
- estimate → sale conversion must copy them reliably

---

## Resume prompts for a future chat
Use one of these:

1. `Resume from the master dev handoff. We fixed the Sales profits modal direct-save. Continue one step at a time.`

2. `Use this master context file. Next I want to improve sale profit totals and modal UX, one step at a time.`

3. `Resume from the master handoff and help me continue cost tracking from estimate through sale.`
