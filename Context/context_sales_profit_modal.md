# Sales Profit Modal Context Update

Project: Floor Manager / RM Flooring
Date: 2026-03-08

## What was fixed in this chat

We fixed the Sales edit page so that cost values for Materials, Freight, and Labour can persist correctly.

### 1) Labour costs on Edit Sale form
The labour rows in the Sale edit Blade were missing the hidden fields needed to carry cost data during normal Sale saves.

For existing labour rows, we added:
- `rooms[ROOM_INDEX][labour][ITEM_INDEX][id]`
- `rooms[ROOM_INDEX][labour][ITEM_INDEX][cost_price]`
- `rooms[ROOM_INDEX][labour][ITEM_INDEX][cost_total]`

This made labour cost values save correctly when the main **Save Sale** button is used.

### 2) Profit modal save for Sales
Originally, the profits modal on Sales showed “Costs saved successfully” but did not actually update the database unless the user also clicked **Save Sale** afterward.

Root causes found:
- The modal script was hardcoded to the Estimate save-costs route.
- A Sale save-costs route did not exist yet.
- The first attempted controller target did not exist.
- Then the Sale route existed, but `SaleController::saveProfitCosts()` did not exist yet.

### 3) New Sales save-costs route
A new Sales route was added and confirmed in route:list:

- URI: `pages/sales/{sale}/profits/save-costs`
- Route name: `pages.sales.profits.save-costs`
- Controller: `App\Http\Controllers\Pages\SaleController@saveProfitCosts`

Route definition used:

```php
Route::post('sales/{sale}/profits/save-costs', [\App\Http\Controllers\Pages\SaleController::class, 'saveProfitCosts'])
    ->name('sales.profits.save-costs');
```

Because this lives inside the existing `pages` route-name group, Laravel registers it as:

```php
pages.sales.profits.save-costs
```

### 4) Profits modal Blade save URL logic
In `resources/views/components/modals/profits-modal.blade.php`, the JS `saveUrl` logic must switch by context:

```blade
const saveUrl = @json(
    $context === 'sale'
        ? route('pages.sales.profits.save-costs', $recordId)
        : route('pages.estimates.profits.save-costs', $recordId)
);
```

### 5) SaleController method added
In:

`app/Http/Controllers/Pages/SaleController.php`

A new method was added:

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

### 6) Result
Now, on the Edit Sale page:
- opening the profits modal
- changing material / freight / labour cost prices
- clicking **Save** in the modal

updates the database immediately, without needing to click **Save Sale**.

## Current known-good behavior

### Edit Sale main form
- Materials cost fields save
- Freight cost fields save
- Labour cost fields save

### Profits modal on Sales
- AJAX save works
- DB updates immediately
- success message appears correctly

## Suggested next improvements

These were identified as good follow-up tasks:

1. Recalculate and persist higher-level sales profit totals automatically
   - total material cost
   - total labour cost
   - total freight cost
   - overall profit / margin

2. Add stronger recalculation after modal save
   - derive totals from `sale_items`
   - avoid mismatches if users edit rows and modal separately

3. Expand profits modal display
   - per-row Profit $
   - per-row Margin %
   - grouped totals by section
   - overall profit summary

## Resume prompt for next chat
Use this prompt:

"We fixed the Sales profits modal so it now saves directly to the DB through `SaleController::saveProfitCosts()`. Please continue from the context file and help me implement the next improvement, one step at a time."
