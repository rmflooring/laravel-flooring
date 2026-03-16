# Warehouse / Inventory / Pick Tickets — Dev Context
Updated: 2026-03-16 (session 16)

---

## Overview

The warehouse module covers three related concerns:

1. **Inventory Receipts** — physical goods received at the warehouse (from a PO or manually)
2. **Inventory Allocations** — reserving received stock against a specific sale item
3. **Pick Tickets (PTs)** — warehouse fulfilment records that track physical movement of materials to a job site

Pick Tickets are created two ways:
- **From Allocation** — when warehouse staff allocate inventory to a sale item; auto-creates a `pending` PT
- **From WO Staging** — when a coordinator stages a Work Order; creates a `staged` PT (no inventory allocation required)

---

## Data Model

### `inventory_receipts` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `purchase_order_id` | nullable FK → purchase_orders | nullOnDelete |
| `purchase_order_item_id` | nullable FK → purchase_order_items | nullOnDelete |
| `product_style_id` | nullable FK → product_styles | nullOnDelete |
| `item_name` | string | Snapshot |
| `unit` | string | |
| `quantity_received` | decimal(10,2) | |
| `received_date` | date | |
| `notes` | text nullable | |
| `created_by` / `updated_by` | nullable FK → users | |

**Accessor:** `getAvailableQtyAttribute()` — `quantity_received` minus sum of all allocation quantities.

### `inventory_allocations` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `inventory_receipt_id` | FK → inventory_receipts | |
| `sale_item_id` | FK → sale_items | |
| `sale_id` | FK → sales | |
| `quantity` | decimal(10,2) | |
| `released_at` | datetime nullable | Stamped when PT is fully delivered; cleared on return |
| `notes` | text nullable | |
| `allocated_by` | nullable FK → users | Auto-set on create |

### `pick_tickets` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `pt_number` | string unique | Auto-generated: `{seq}-{sale_number}` or `{seq}` if no sale |
| `sale_id` | nullable FK → sales | nullOnDelete |
| `work_order_id` | nullable FK → work_orders | nullOnDelete |
| `status` | string | See statuses below |
| `notes` | text nullable | Populated with delivery info on "mark delivered" |
| `staging_notes` | text nullable | Instructions entered at staging time |
| `unstaged_by` | nullable FK → users | nullOnDelete |
| `unstaged_at` | datetime nullable | |
| `unstage_reason` | text nullable | |
| `delivered_at` | datetime nullable | Stamped on first (partial) delivery |
| `returned_at` | datetime nullable | |
| `ready_at` / `picked_at` | datetime nullable | For pending → ready → picked flow |
| `created_by` / `updated_by` | nullable FK → users | |

**Indexes:** `[sale_id, status]`, `[work_order_id]`

### `pick_ticket_items` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `pick_ticket_id` | FK → pick_tickets (cascade delete) | |
| `inventory_allocation_id` | **nullable** FK → inventory_allocations | Null for WO-staged PTs |
| `sale_item_id` | FK → sale_items | |
| `item_name` | string | Snapshot |
| `unit` | string | |
| `quantity` | decimal(10,2) | Total quantity to be delivered |
| `delivered_qty` | decimal(10,2) default 0 | Running total of what has been delivered so far |
| `returned_qty` | decimal(10,2) default 0 | Running total of what has been returned from site |
| `sort_order` | integer default 0 | |

---

## Status Lifecycle

```
pending → ready → picked → delivered ⇌ partially_delivered
                                ↓
                            returned

staged → partially_delivered → delivered
       ↓
     cancelled (via Unstage)

any → cancelled
```

**Partial return:** "Return Items" on a `delivered` or `partially_delivered` PT subtracts returned_qty per item.
- If any item's net-at-site (delivered − returned) > 0 → status = `partially_delivered`
- If all items fully returned → status = `returned`, allocations `released_at` cleared

### Status Constants (`PickTicket::STATUSES` / `STATUS_LABELS`)

| Status | Label | Badge color |
|--------|-------|-------------|
| `pending` | Pending | Gray `#6b7280` |
| `ready` | Ready | Blue `#2563eb` |
| `picked` | Picked | Purple `#7c3aed` |
| `staged` | Staged | Orange `#ea580c` |
| `partially_delivered` | Partial | Yellow-600 `#ca8a04` |
| `delivered` | Delivered | Green `#16a34a` |
| `returned` | Returned | Amber `#d97706` |
| `cancelled` | Cancelled | Light gray `#9ca3af` |

---

## Models

### `App\Models\PickTicket`
- `$guarded = ['id', 'pt_number']`
- **Casts:** `ready_at`, `picked_at`, `delivered_at`, `returned_at`, `unstaged_at` → datetime
- **Auto-generation** (`booted()` creating hook): `{seq}-{sale_number}` or `{seq}`. Retries 10x.
- Auto-sets `created_by`, `updated_by` on create; `updated_by` on update
- **Relationships:** `sale()`, `workOrder()`, `items()`, `creator()`, `updater()`, `unstagedBy()`
- **Accessor:** `getStatusLabelAttribute()`

### `App\Models\PickTicketItem`
- `$guarded = ['id']`
- **Casts:** `quantity`, `delivered_qty`, `returned_qty` → decimal:2
- **Relationships:** `pickTicket()`, `inventoryAllocation()`, `saleItem()`

### `App\Models\InventoryReceipt`
- `$guarded = ['id']`
- Auto-sets `created_by`, `updated_by` on create/update
- **Relationships:** `purchaseOrder()`, `purchaseOrderItem()`, `productStyle()`, `allocations()`, `creator()`, `updater()`
- **Accessor:** `getAvailableQtyAttribute()` = `quantity_received` − sum of `allocations.quantity`

### `App\Models\InventoryAllocation`
- `$guarded = ['id']`
- Auto-sets `allocated_by` on create
- **Casts:** `quantity`, `released_at`
- **Relationships:** `inventoryReceipt()`, `saleItem()`, `sale()`, `allocatedBy()`, `pickTicketItems()`

---

## Services

### `App\Services\PickTicketService`

**`createFromAllocation(InventoryAllocation $allocation, ?WorkOrder $workOrder = null): PickTicket`**
- Creates a `pending` PT with a single item (from the allocation)
- Optionally links to a WO

**`createFromWorkOrder(WorkOrder $workOrder, ?string $stagingNotes = null): PickTicket`**
- Creates a `staged` PT from all material sale items linked to the WO via `WorkOrderItemMaterial`
- `inventory_allocation_id = null` on all items — no allocation required for staging
- Saves `staging_notes` to the PT record

**`deliver(PickTicket $pt, array $itemQtys, ?string $receivedBy, ?string $deliveryNotes): void`**
- `$itemQtys` = `[pick_ticket_item_id => qty_delivered_this_trip]`
- Adds submitted qty to each item's `delivered_qty` (capped at `quantity`)
- If ALL items reach full qty → status = `delivered`; releases inventory allocations (`released_at = now()`)
- If any item still has remaining → status = `partially_delivered`; keeps `delivered_at` from first delivery
- Saves received_by + delivery_notes composite into `notes` field

**`unstage(PickTicket $pt, ?string $reason = null): void`**
- Only valid for `staged` status
- Sets `status = cancelled`, `unstaged_by = auth()->id()`, `unstaged_at = now()`, `unstage_reason`
- WO can be re-staged after unstaging (new PT can be created)

**`markReady(PickTicket $pt)`** → `status = ready`, stamps `ready_at`

**`markPicked(PickTicket $pt)`** → `status = picked`, stamps `picked_at`

**`cancel(PickTicket $pt)`** → `status = cancelled` (inventory allocations left intact)

**`returnTicket(PickTicket $pt, array $itemQtys = [], ?string $returnNotes = null): void`**
- `$itemQtys` = `[pick_ticket_item_id => qty_returned_this_trip]`
- Adds submitted qty to each item's `returned_qty` (capped at `delivered_qty`)
- If net-at-site (delivered − returned) > 0 for any item → status = `partially_delivered`; stamps `returned_at` on first return
- If all items fully returned → status = `returned`, clears `released_at` on allocations
- Saves `return_notes` into `notes` field

### `App\Services\InventoryService`

**`receiveFromPOItem(PurchaseOrderItem, float $qty, string $date, ?string $notes): InventoryReceipt`**

**`receiveManual(array $data): InventoryReceipt`**

**`allocate(InventoryReceipt, SaleItem, float $qty, ?string $notes): InventoryAllocation`**
- Validates qty ≤ `available_qty` on the receipt
- Creates the allocation inside a transaction

**`allocatedQtyForSaleItem(SaleItem): float`**

**`coverageForSaleItem(SaleItem): array`** — `['covered' => bool, 'quantity' => float, 'allocations' => Collection]`

---

## Controllers

### `App\Http\Controllers\Pages\WarehousePickTicketController`

| Method | Route | Notes |
|--------|-------|-------|
| `index()` | `GET warehouse/pick-tickets` | Filter by status/search; ordered by status priority |
| `show()` | `GET warehouse/pick-tickets/{pickTicket}` | Loads sale, workOrder.installer, items.saleItem.room, creator, updater, unstagedBy |
| `pdf()` | `GET warehouse/pick-tickets/{pickTicket}/pdf` | DomPDF inline stream |
| `updateStatus()` | `PATCH warehouse/pick-tickets/{pickTicket}/status` | Actions: `mark_ready`, `mark_picked`, `deliver`, `return`, `cancel` |
| `unstage()` | `POST warehouse/pick-tickets/{pickTicket}/unstage` | Only for `staged` status; accepts `unstage_reason` |

**`updateStatus()` deliver action** accepts:
- `items[{pick_ticket_item_id}]` = qty to deliver this trip
- `received_by` = name of person who received materials (saved to `notes`)
- `delivery_notes` = free-text notes (saved to `notes`)

**`updateStatus()` return action** accepts:
- `items[{pick_ticket_item_id}]` = qty being returned this trip
- `return_notes` = free-text notes (saved to `notes`)

### `App\Http\Controllers\Pages\InventoryController`

| Method | Route | Notes |
|--------|-------|-------|
| `index()` | `GET inventory` | Filter by name, date range, show_depleted toggle; summary stats |
| `show()` | `GET inventory/{inventoryReceipt}` | Full receipt detail with allocations + pick ticket items |

### `App\Http\Controllers\Pages\InventoryAllocationController`

| Method | Route | Notes |
|--------|-------|-------|
| `store()` | `POST pages/sales/{sale}/sale-items/{saleItem}/inventory-allocations` | Allocates receipt qty to a sale item; auto-creates a pending PT; links to WO if one covers the material |

---

## Routes

All under `pages` middleware group:

```
GET    pages/warehouse/pick-tickets                              pages.warehouse.pick-tickets.index       role_or_permission:admin|view pick tickets
GET    pages/warehouse/pick-tickets/{pickTicket}                 pages.warehouse.pick-tickets.show        role_or_permission:admin|view pick tickets
GET    pages/warehouse/pick-tickets/{pickTicket}/pdf             pages.warehouse.pick-tickets.pdf         role_or_permission:admin|view pick tickets
PATCH  pages/warehouse/pick-tickets/{pickTicket}/status          pages.warehouse.pick-tickets.update-status  role_or_permission:admin|view pick tickets
POST   pages/warehouse/pick-tickets/{pickTicket}/unstage         pages.warehouse.pick-tickets.unstage     role_or_permission:admin|view pick tickets

GET    pages/inventory                                           pages.inventory.index                    role_or_permission:admin|view pick tickets
GET    pages/inventory/{inventoryReceipt}                        pages.inventory.show                     role_or_permission:admin|view pick tickets

POST   pages/sales/{sale}/sale-items/{saleItem}/inventory-allocations  pages.sales.inventory-allocations.store  role_or_permission:admin|view pick tickets
POST   pages/sales/{sale}/work-orders/{workOrder}/stage-pick-ticket    pages.sales.work-orders.stage-pick-ticket  role_or_permission:admin|edit work orders
```

---

## Views

| View | Path |
|------|------|
| PT Index | `resources/views/pages/warehouse/pick-tickets/index.blade.php` |
| PT Show | `resources/views/pages/warehouse/pick-tickets/show.blade.php` |
| PT Status Badge | `resources/views/pages/warehouse/pick-tickets/_status-badge.blade.php` |
| PT PDF | `resources/views/pdf/pick-ticket.blade.php` |
| Inventory Index | `resources/views/pages/inventory/index.blade.php` |
| Inventory Receipt Show | `resources/views/pages/inventory/show.blade.php` |
| PO Receive | `resources/views/pages/purchase-orders/receive.blade.php` |

---

## PT Show Page — Key UI Sections

### Header action buttons (by status)

| Status | Buttons shown |
|--------|--------------|
| `staged` | Record Delivery (→ modal), Unstage (→ modal) |
| `partially_delivered` | Record Delivery (→ modal), Return Items (→ modal) |
| `pending` | Mark Ready, Cancel |
| `ready` | Mark Picked |
| `picked` | Record Delivery (→ modal) |
| `delivered` | Return Items (→ modal) |

### Items table columns
- **Item** (with unit), **Room**, **Ordered**, **Delivered**, **Remaining**
- When any `returned_qty > 0`: adds **Returned** and **At Site** columns; Remaining reflects net at-site
- Fully fulfilled rows are dimmed; Remaining shows ✓ in green when done

### Record Delivery modal
- Per-item table: Item | Room | Ordered | Delivered | Remaining | **Delivering Now** (input)
- "Delivering Now" pre-fills with remaining qty; user adjusts down for partial delivery
- Fully delivered items show "Done" (hidden input submits 0)
- Fields: Received by (text), Delivery notes (text)
- Submits `items[{id}]` map + received_by + delivery_notes to `updateStatus` PATCH

### Return Items modal
- Per-item table: Item | Room | Delivered | Prev. Returned | At Site | **Returning Now** (input)
- "Returning Now" pre-fills with 0; user enters qty coming back (max = At Site)
- Items with nothing at site show "All returned" (hidden input submits 0)
- Field: Return notes (text)
- Submits `items[{id}]` map + return_notes to `updateStatus` PATCH with `action=return`
- Status result: partial return → `partially_delivered`; full return → `returned`

### Staging info (orange bar, shown when PT exists)
- "Staged by: {name} · {date}" + staging notes if present

### Unstage modal
- Shows PT#, staged by + date, staging notes
- Reason textarea (optional)
- "Unstaged by: {current user} · {today}"

### Timeline sidebar
- Created (by {name}), Ready, Picked, Delivered, Returned, Unstaged (by {name})
- Staging notes section below timeline (if present)
- Unstage reason section in red (if present)

---

## WO Show Page — Staging Section

Located below the WO notes card. Gated by `@can('edit work orders')`.

**When no active PT:**
- "Stage Work Order" orange button → opens **Stage modal**
  - Warehouse notes textarea (optional) — saved as `staging_notes`
  - "Staged by: {you} · {today}" info box
  - Submits POST to `stage-pick-ticket` route

**When PT exists (staged or partially_delivered):**
- Orange "Staged by / notes" meta bar
- Job info grid: Sale#, Job, Installer, Install date
- Materials table: Material | Qty | Unit | Room
- "Record Delivery" green button → links to PT show page (delivery UX lives there)
- "Unstage" red button (staged only) → opens **Unstage modal**

**When PT is delivered/cancelled:**
- PT number + status badge shown; no action buttons

---

## Permissions

- `view pick tickets` — admin, coordinator, estimator, sales, reception (view only)
- Staging controlled by `edit work orders` permission

---

## PDF Template (`resources/views/pdf/pick-ticket.blade.php`)

Matches WO/PO PDF style (DejaVu Sans, blue `#1d4ed8`):
- **Header**: branding logo/name (left), "PICK TICKET" + PT#, date, status (right)
- **Info grid**: Job Details left (Sale#, customer, homeowner, job, address) | WO/Installer right (WO#, installer, install date) — graceful null fallbacks
- **Items grouped by room** — blue `⌂ Room Name` headers
- Items table: Item | Qty | Unit
- **Notes box** if present
- **Footer**: company name, phone, email, website

---

## Resume Prompt

> Read CLAUDE.md and Context/context_warehouse_pick_tickets.md. I want to continue working on the warehouse/pick ticket module. One step at a time.
