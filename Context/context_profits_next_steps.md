# context_profits_next_steps.md

Project: Floor Manager — Estimate Profits Modal
Owner: Richard / RM Flooring

## What was completed in this chat

We finished the Estimate-side Profits modal so it now works end-to-end.

### File worked on
- `resources/views/components/modals/profits-modal.blade.php`

### What was added/updated in the modal
1. Added data hooks to each profit row:
   - `data-profit-row`
   - `data-item-type`
   - `data-sell-total`
   - `data-cost-total`
   - `data-profit`
   - `data-margin`

2. Added grouped totals calculation in the modal:
   - Materials
   - Labour
   - Freight
   - Grand Total Profit

3. Added live recalculation when Cost $ is edited:
   - Cost Total updates live
   - Profit updates live
   - Margin updates live
   - Grouped Totals update live

4. Added Save Costs button JS handler:
   - Reads all rows in the modal
   - Collects:
     - `item id`
     - `cost_price`
   - Sends POST request with CSRF token

### Route added
In `routes/web.php`:

```php
Route::post('estimates/{estimate}/profits/save-costs', [EstimateController::class, 'saveProfitCosts'])
    ->name('pages.estimates.profits.save-costs');
```

Note:
- `php artisan route:list | grep save-costs` showed the actual route name is:
  - `pages.estimates.profits.save-costs`

### Controller method added
In `EstimateController.php`:
- Added `saveProfitCosts(Request $request, Estimate $estimate)`

What it does:
- validates `items`
- loops through posted rows
- finds each `EstimateItem`
- updates:
  - `cost_price`
  - `cost_total`

### Confirmed working
Richard tested:
- Labour cost save
- Material cost save
- Freight cost save

All of these worked:
- values update live in modal
- clicking Save Costs persists values
- page refresh still shows saved values
- phpMyAdmin confirms DB updates

### DB columns confirmed updating
Table:
- `estimate_items`

Columns:
- `cost_price`
- `cost_total`

---

## Important current state

Estimate Profits modal is now functional.

This means the following all work on the Estimate edit page:
- grouped totals display
- live profit calculations
- live margin calculations
- save to DB

---

## Suggested next improvements

### 1) Improve Save Costs button UX
Replace browser `alert()` messages with better UI:
- disable button while saving
- change button text to something like:
  - `Saving...`
  - `Saved ✓`
- optionally turn button green on success
- prevent double-click saves

### 2) Add Flowbite success/error feedback
Instead of `alert()`:
- show inline message in modal footer
- or show Flowbite toast/alert

### 3) Add Estimate-level profit summary outside modal
Potential future UI:
- Material Profit
- Labour Profit
- Freight Profit
- Total Profit
- Overall Margin

Could be shown in estimate summary panel for quick decision-making.

### 4) Add same functionality to Sales profits modal
Important future step:
- mirror this cost-saving and live calculation flow on the Sales edit page
- save to `sales_items`
- compare estimated vs actual profit later

### 5) Add locked profits workflow later
The modal already has placeholders for:
- Lock Status
- Locked at
- Locked by
- Locked snapshot tab

Future work could:
- snapshot current profits
- make locked version read-only
- preserve verified profit state

---

## Recommended next chat starting point

Use this exact prompt:

"We finished the Estimate profits modal. Please continue with the next step: improve the Save Costs button UX by removing alert() and making the button show Saving... and Saved checkmark states. One step at a time and tell me which file we are working in."

---

## Reminder of user workflow preference

Richard wants:
- one step at a time
- no guessing
- exact file being worked on each step
- clear placement instructions before code changes
