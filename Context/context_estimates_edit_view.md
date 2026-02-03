# Estimates Edit View Context

## Current Status (Jan 2026)

### What Was Working
- Create estimate blade renders rooms with Materials, Freight, and Labour sections using templates
- Data saves correctly to:
  - estimates table
  - estimate rooms/items structure
- Foreign keys added for:
  - salesperson_1_id → employees.id
  - salesperson_2_id → employees.id

### What Was Broken
- Edit estimate blade only rendered:
  - Room header
  - Room name field
- The actual Materials / Freight / Labour tables were missing entirely

### Root Cause
In edit.blade.php, inside the rooms loop, the view only had placeholder comments:

```
{{-- Materials / Freight / Labour tables --}}
```

So even though rooms loaded correctly, none of the content rendered.

### Fix Applied
Inserted full UI structure inside each existing room:

- Materials table + row template
- Freight table + row template
- Labour table + row template

Matching the same structure used in the room template for create.

### Result
- Rooms now visually render properly in edit view
- Ready for next step:
  - Preloading existing DB rows into tbody sections
  - Wiring update saving logic

## Debug Tip Used
Added temporary counter:

```
Rooms loaded: {{ $estimate->rooms->count() }}
```

Confirmed rooms were loading — view was the issue, not controller.

---

## Next Logical Steps

1. Inject existing materials into `.materials-tbody`
2. Inject freight rows into `.freight-tbody`
3. Inject labour rows into `.labour-tbody`
4. Sync edit JS logic to behave same as create
5. Enable update saving

---

## Working Principles (important)

- No assumptions — always inspect actual data
- One step at a time
- Match edit view structure to create view first
- Then wire data

## UI Fix: Materials Table Overflow + Dropdown Clipping (Feb 2, 2026)

### Problem
- Materials table row could extend outside the room card (too wide), especially in edit view.
- After constraining the table with horizontal scrolling, autocomplete dropdowns were trapped inside the scroll container (required vertical scrolling inside the row to see options).
- Newly added rows and newly added rooms did not inherit the same fixes at first.

### Fixes Applied (in order)

1. **Fix malformed HTML in the new-room template**
   - In `#room-template` → `template.material-row-template`, the **Color / Item #** cell had:
     - a duplicated dropdown block
     - an extra stray `</td>`
   - Cleaned it so the cell contains only one dropdown container and one closing `</td>`.

2. **Constrain Materials table width with horizontal scrolling**
   - Wrapped the Materials table container with `overflow-x-auto` so wide columns no longer break out of the room card:
     - Existing rooms: `<div class="border border-gray-200 rounded-lg overflow-x-auto">`
     - New rooms (inside `#room-template`): same wrapper added so new rooms behave identically.

3. **Restore dropdown behavior outside the scroll container**
   - Added a small **dropdown pinning** helper script that:
     - detects when a dropdown becomes visible (hidden → shown)
     - temporarily positions it as `position: fixed` using the owning input’s viewport coordinates
     - prevents clipping by any overflow/scroll parents

4. **Make pinning work for dynamically-added rows**
   - Upgraded the script to attach observers to dropdowns added after page load (new material rows) using a document-level `MutationObserver`.

### Result
- Materials table no longer extends outside the room card.
- Materials section scrolls horizontally when needed (expected).
- Dropdowns extend downward normally (not clipped), for:
  - existing rows
  - newly added material rows
  - newly added rooms

### Notes / Pattern
- Keep **room-card** overflow visible for dropdowns.
- Put horizontal overflow control on the **table wrapper** only:
  - Card: dropdown-friendly
  - Table wrapper: width-safe
