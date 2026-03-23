# Context: Sales & Change Order Module (FM Project)

## Overview
This document captures the architecture and design decisions for the **Sales section** of the Floor Manager (FM) system.

The Sales module represents the **job-running version of an approved estimate**, including support for:
- Locking contract values
- Progress invoicing
- Change Orders (COs)
- Audit/change tracking

This file allows us to resume development later without rethinking architecture decisions.

---

# 1. Core Workflow Rules

## Create Sale
- A Sale can only be created when:
  - `estimate.status = approved`
  - No existing Sale is linked to that estimate (1:1 enforced)

- When created:
  - Sale is created with `status = open`
  - It is editable
  - It copies all header data, rooms, and items from the approved Estimate
  - Stores:
    - `source_estimate_id`
    - `source_estimate_number`

- Sale is NOT locked automatically.

---

# 2. Sale Status Enum

```
open
sent
approved
change_in_progress
scheduled
in_progress
on_hold
completed
partially_invoiced
invoiced
cancelled
```

- `sent` — set automatically when sale email is sent to homeowner
- `change_in_progress` — set when a draft/sent Change Order exists; blocks PO/WO creation

## Invoicing Status Logic
- `partially_invoiced` when:
  - Sale is locked
  - invoiced_total > 0
  - invoiced_total < revised contract total

- `invoiced` when:
  - Sale is locked
  - invoiced_total >= revised contract total

---

# 3. Sale Locking Behavior

When a Sale is locked:
- Totals are copied into `locked_*` fields
- Sale becomes non-editable (unless unlocked later)

Locked snapshot fields:
- locked_subtotal_materials
- locked_subtotal_labour
- locked_subtotal_freight
- locked_pretax_total
- locked_tax_group_id
- locked_tax_rate_percent
- locked_tax_amount
- locked_grand_total

---

# 4. Progress Invoicing Design

- Multiple invoices per Sale
- Future `invoices` table will include:
  - `sale_id` FK

Sales table includes summary fields:
- invoiced_total
- invoiced_at
- is_fully_invoiced
- approved_co_total
- revised_contract_total

Revised contract total =
```
locked_grand_total + sum(approved CO locked totals)
```

---

# 5. Change Orders (COs)

## Purpose
Handle scope additions or credits after a Sale is approved, before any POs are ordered.

## CO Header (`sale_change_orders`)
- Linked to `sale_id`
- Status: draft, sent, approved, rejected, cancelled
- `co_number` auto-generated: `CO-{seq}-{sale_number}`
- Snapshots `original_grand_total` at creation for delta tracking
- `locked_grand_total` frozen on approval

## CO Rooms / Items
- `sale_change_order_rooms` — room snapshots; `sale_room_id` FK back to live room
- `sale_change_order_items` — item snapshots; `sale_item_id` is plain nullable integer (no FK constraint)
- **No FK on sale_item_id** — sale update deletes+recreates all items on every save; FK caused ON DELETE SET NULL to wipe all snapshot links. Delta uses positional sort_order matching instead.

## Delta Calculation
- Snapshot items matched to current items positionally by sort_order index within each room
- Statuses: added / removed / changed / unchanged
- `ChangeOrderService::calculateDelta()` returns structured rooms[] array with grand totals

## Revert
- Deletes all current items in each snapshot room, rebuilds directly from snapshot data
- Does not rely on sale_item_id; safe regardless of FK state

## Sales Status
- `change_in_progress` added to `sales.status` enum — set when a draft/sent CO exists
- PO and WO creation blocked while `change_in_progress`

## Tax Handling
- CO always uses the Sale’s tax group and rate
- No separate tax selector for CO

## Full Implementation Details
See `Context/context_master_dev_handoff.md` → "Change Orders (session 26)"

---

# 6. 1:1 Relationship Rule

- One Sale per approved Estimate
- Enforced via unique constraint on `sales.source_estimate_id`

---

# 7. Estimate Revisions After Sale Exists

Allowed behavior:
- Estimate may be revised (new estimate_number)
- Sale does NOT auto-update
- Scope changes must be handled via Change Orders

---

# 8. Tables Created

## Sales Core
- sales
- sale_rooms
- sale_items

## Change Orders
- sale_change_orders
- sale_change_order_rooms
- sale_change_order_items

---

# 9. Pending Future Work

- Create Sale from approved Estimate (controller logic)
- Lock Sale action
- Invoice module
- Automatic status updates
- Change tracking (full audit trail option B)
- UI screens for Sales + COs

---

# 10. Design Philosophy

- Sales represent the contractual job record
- Estimates represent proposal versions
- Change Orders formalize scope adjustments
- Locking protects financial integrity
- Invoicing reconciles against locked contract totals

---

End of Context File

