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
scheduled
in_progress
on_hold
completed
partially_invoiced
invoiced
cancelled
```

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
Handle scope additions or credits after Sale creation.

## CO Header (`sale_change_orders`)
- Linked to `sale_id`
- Status: draft, sent, approved, rejected, cancelled
- CO totals
- Locked snapshot on approval

## CO Items
- Same structure as Sale Items
- Supports credits via:
  - Negative quantity (recommended approach)

## Tax Handling
- CO always uses the Sale’s tax group and rate
- No separate tax selector for CO

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

