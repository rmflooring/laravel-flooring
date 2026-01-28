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

