# Context Profits Dev Handoff — Updated After Sales Modal Fix

Project: Floor Manager (RM Flooring)
Owner: Richard
Date: 2026-03-08

## Summary of latest completed work

The Sales-side profits modal save flow is now working.

Before this fix:
- Sales profits modal opened correctly.
- Editing costs in the modal updated the UI only.
- Database did not persist changes until the user clicked the main **Save Sale** button.

After this fix:
- The modal saves directly to DB through AJAX.
- No second Sale save is required.

## Files involved

### 1) Sale edit Blade
Existing labour rows were missing hidden cost inputs.

Added hidden inputs for labour rows on the Sale edit page:
- `id`
- `cost_price`
- `cost_total`

Purpose:
- ensure normal Sale form saves preserve labour costs just like materials and freight.

### 2) Profits modal component
File:
- `resources/views/components/modals/profits-modal.blade.php`

Updated save URL logic to switch by context:

```blade
const saveUrl = @json(
    $context === 'sale'
        ? route('pages.sales.profits.save-costs', $recordId)
        : route('pages.estimates.profits.save-costs', $recordId)
);
```

### 3) Sales route
A route was added for Sales modal saves.

Effective registered route from `php artisan route:list --path=sales`:
- `POST pages/sales/{sale}/profits/save-costs`
- name: `pages.sales.profits.save-costs`
- action: `Pages\SaleController@saveProfitCosts`

Route definition used inside grouped routes:

```php
Route::post('sales/{sale}/profits/save-costs', [\App\Http\Controllers\Pages\SaleController::class, 'saveProfitCosts'])
    ->name('sales.profits.save-costs');
```

Note:
The surrounding route-name group prefixes it with `pages.` automatically.

### 4) SaleController method
File:
- `app/Http/Controllers/Pages/SaleController.php`

New method added:

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

## Verified behavior

### Main Sale save
- material costs save
- freight costs save
- labour costs save

### Sales profits modal save
- direct AJAX save works
- database updates immediately
- does not require Save Sale afterward

## Important troubleshooting history

These wrong paths were tested and should not be repeated:

1. Using a hardcoded estimate save route for sales modal
   - caused modal save to hit wrong endpoint

2. Adding a route to `SaleProfitController`
   - failed because controller did not exist

3. Adding route to `SaleController::saveProfitCosts` before method existed
   - route resolved but action failed

4. Using route names without recognizing the existing `pages.` group prefix
   - caused route lookup confusion

## Best next steps

Recommended next development items:

### A) Sales profit rollups
Add automatic recalculation of grouped costs/profit after modal save:
- materials total cost
- labour total cost
- freight total cost
- gross profit
- gross margin

### B) Shared service / reuse
Refactor Estimate + Sale modal save logic into a shared service or trait if both flows now do similar cost persistence.

### C) Improve modal UX
Add:
- Profit $
- Margin %
- grouped subtotal display
- maybe save-state indicator instead of browser alert

## Resume prompt
"Please continue from the updated profits dev handoff. The Sales profits modal direct-save is now working. Next I want to improve profit totals and modal UX, one step at a time."
