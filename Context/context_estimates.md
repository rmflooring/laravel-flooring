# context_estimates.md (Floor Manager / Estimates)

Last updated: 2026-01-26 (America/Vancouver)

## Where we are
We are working on the **Create Estimate (mock)** dropdown UX in:
- `public/assets/js/estimates/estimate_mock.js`

Goal: dropdowns should open as an overlay (no internal scrolling to see options), support click/focus to open, typing to filter, and keyboard navigation.

---

## Working changes (confirmed)

### A) Freight description dropdown overlay
**Issue:** Freight dropdown required scrolling / didn’t open reliably.

**Fix (high level):**
- In `openDropdown()` anchor dropdown to the `<td>` so it overlays below the input:
  - `cell.style.position = 'relative'`
  - `cell.style.overflow = 'visible'`
  - `dropdown.style.position = 'absolute'`
  - `dropdown.style.top = '100%'`
  - High `zIndex`
- Use `pointerdown` on the freight input to open immediately (show “Loading...”, then populate when fetch resolves).
- Outside click closes the dropdown (avoid capture-mode close bugs; use a short timeout if needed so open-click doesn’t instantly close).

Key function:
- `initFreightDropdownForRoom(roomCard)`

---

### B) Labour Type dropdown stores selected ID on the row
**Issue:** Labour descriptions couldn’t load because the selected labour type id wasn’t available.

**Fix:** When selecting a labour type, store the id on the row:
- `rowEl.dataset.labourTypeId = String(selectedId)`

Key function:
- `initLabourTypeDropdownForRow(rowEl)`

Quick check:
- `document.querySelector('[data-labour-type-input]')?.closest('tr')?.dataset.labourTypeId`

---

### C) Labour Description dropdown now works (ROW-based)
**Issue:** Labour description didn’t open; DevTools showed no listeners; error came from mixed room/row scope.

**Root cause:** The description dropdown logic was accidentally written like a room-based function (referenced `roomCard`), so it never bound events.

**Fix:** Use a true row-based initializer:
- `initLabourDescriptionDropdownForRow(rowEl)`

Behavior:
- On `focus` / `click`: load items for `rowEl.dataset.labourTypeId` and open dropdown
- On `input`: filter list
- Keyboard: ArrowUp/ArrowDown/Enter/Escape
- On select: fill description + unit code (`unit_code`) and dispatch `input` events so totals recalc

Endpoint used:
- `GET /estimates/api/labour-items?labour_type_id=...`

✅ Status: Labour Description dropdown is working.

---

### D) Add Room ReferenceError fixed
**Error seen:**
- `initLabourDescriptionDropdownForRoom is not defined`

**Cause:** `addRoom()` was calling an old/non-existent room-based function name.

**Fix inside `addRoom()` after inserting `newRoomCard`:**
Initialize per labour row:
```js
newRoomCard.querySelectorAll('.labour-tbody tr').forEach((row) => {
  initLabourTypeDropdownForRow(row);
  initLabourDescriptionDropdownForRow(row);
});
```

---


### E) Tax Group (default on load + modal selection updates totals)

**Goal:** When the estimate page loads, it should use the **Default Tax Group** (from the `default_tax` table) to:
- set the hidden `tax_group_id_input`
- fetch the group rate percent
- update the **Tax (...)** label + totals

Then, when a user picks a different group in the modal, it should:
- update `tax_group_id_input`
- update the label immediately
- refetch rate percent + recalc totals

**Backend**
- In the mock-create route/controller, we now pass:
  - `defaultTaxGroupId` (latest row in `default_tax`, column name may be `tax_rate_group_id` or `tax_group_id` — we detect using `Schema::hasColumn`)
  - `taxGroups` list for the modal buttons.
- Added/used API endpoint:
  - `GET /estimates/api/tax-groups/{tax_group}/rate`
  - Returns JSON like: `{ group_id, group_name, tax_rate_percent }`
  - `tax_rate_percent` is the SUM of the group's linked `tax_rates` sales rate column.

**Frontend (estimate_mock.js)**
- We keep global/current tax state:
  - `window.FM_CURRENT_TAX_PERCENT`
  - `window.FM_CURRENT_TAX_GROUP_ID`
  - `window.FM_CURRENT_TAX_GROUP_LABEL`
- On `DOMContentLoaded`, we read `tax_group_id_input` and call `loadTaxGroupRate(defaultId)` to set percent + trigger `updateEstimateTotals()`.
- Modal buttons use `data-tax-group-id` and `data-tax-group-name` (or `data-tax-group-name` mapped to `dataset.taxGroupName`) so we can update the label immediately.
- We dispatch/listen to a single event:
  - dispatch: `document.dispatchEvent(new CustomEvent('fm:tax-group-selected', { detail: { id, name } }))`
  - listener: updates hidden input + label + calls `loadTaxGroupRate(id)`

**Important gotchas we hit**
- **`Uncaught SyntaxError: Identifier 'taxGroupInput' has already been declared`**
  - Cause: multiple `const taxGroupInput = ...` blocks / duplicate event listeners.
  - Fix: keep **ONE** default-load block and **ONE** modal-selection block (no duplicates).
- The Tax label in the summary is updated inside `updateEstimateTotals()`:
  - Example display: `Tax (GST/PST) 12%` (percent formatting trims trailing zeros).


## Debug tricks we used
- To confirm listeners exist (Chrome only): `getEventListeners(input)`
- To confirm row has selected type id: check `row.dataset.labourTypeId`

---

## Next tasks (when we resume)
- Ensure **newly added labour rows** (not just initial ones) call:
  - `initLabourTypeDropdownForRow(newRow)`
  - `initLabourDescriptionDropdownForRow(newRow)`
- Confirm other dropdowns (materials/product type/manufacturer/style/color) also initialize for new rooms and behave like labour/freight (overlay + keyboard).

## Labour row autofill (Description -> Unit, Sell, Notes)

### Goal
When a user selects a **Labour Description**, the row should autofill:
- **Unit** (unit code, e.g. `SF`)
- **Sell Price**
- **Notes**

### Backend change
File: `app/Http/Controllers/Api/EstimateLabourItemController.php`

Endpoint: `GET /estimates/api/labour-items?labour_type_id=...`

Updated query to also return:
- `labour_items.sell`
- `labour_items.notes`

So response items now include:
- `id`, `description`, `unit_code`, `sell`, `notes`

Example response:
```json
[{"id":1,"description":"Installation of hardwood T&G","unit_code":"SF","sell":"3.50","notes":"Hello here"}]
```

### Frontend change
File: `public/assets/js/estimates/estimate_mock.js`

Function: `initLabourDescriptionDropdownForRow(rowEl)`

1) Added selectors near the top:
- `priceInput` -> `input[name*="[labour]"][name$="[sell_price]"]`
- `notesInput` -> `input/textarea[name*="[labour]"][name$="[notes]"]`

2) In `render()` button HTML, added dataset attributes:
- `data-labour-sell`
- `data-labour-notes`

3) In the button click handler:
- Set `descInput.value` and `unitInput.value`
- **Call `closeDropdown()` immediately after setting unit**
- Then set `priceInput` from `sell` (toFixed(2)) and dispatch `input` (to trigger totals)
- Then set `notesInput` and dispatch `input`

Status: ✅ Working (selecting Labour Description autofills Unit, Sell Price, Notes; totals update).
