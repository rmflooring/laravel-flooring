# Purchase Orders Module — Dev Context
Updated: 2026-03-15 (session 12)

---

## Overview

Purchase Orders (POs) are raised against a Sale to order material items from vendors.
Multiple POs can exist per sale (e.g. different vendors). Each PO tracks which material
line items are being ordered, at what quantity and cost.

**Key constraints:**
- POs are created from within a sale (nested route)
- Only `material` type sale items can be included (not labour or freight)
- The system enforces that total ordered qty across all non-cancelled POs never exceeds
  the sale item's original quantity
- A sale item that is fully ordered cannot be added to a new PO

---

## Data Model

### `purchase_orders` table

| Column                  | Type         | Notes                                              |
|-------------------------|--------------|----------------------------------------------------|
| `id`                    | bigint PK    |                                                    |
| `po_number`             | string unique| Auto-generated: `{seq}-{sale_number}` if tied to a sale (e.g. `3-8`), or just `{seq}` for stock POs. Plain integers, no year prefix. |
| `sale_id`               | FK → sales   |                                                    |
| `opportunity_id`        | FK → opportunities (nullable) | Copied from sale at creation      |
| `vendor_id`             | FK → vendors |                                                    |
| `status`                | enum         | `pending`, `ordered`, `received`, `cancelled`      |
| `vendor_order_number`   | string nullable | Required when status = `ordered`               |
| `expected_delivery_date`| date nullable|                                                    |
| `fulfillment_method`    | enum         | `delivery_site`, `delivery_warehouse`, `delivery_custom`, `pickup` |
| `delivery_address`      | text nullable| Resolved from fulfillment method at save           |
| `special_instructions`  | text nullable|                                                    |
| `ordered_by`            | FK → users   | Auto-set to auth user on create                    |
| `pickup_at`             | datetime nullable | Assembled from pickup_date + pickup_time; set when fulfillment = `pickup` |
| `calendar_event_id`     | nullable FK → calendar_events | Local CalendarEvent for pickup scheduling |
| `sent_at`               | datetime nullable | Stamped when emailed to vendor                |
| `created_by` / `updated_by` | FK → users |                                                |
| `deleted_at`            | datetime nullable | Soft deletes                                  |
| `created_at` / `updated_at` | timestamps |                                                |

**Indexes:** `[sale_id, status]`, `[opportunity_id, status]`

### `purchase_order_items` table

| Column              | Type       | Notes                                              |
|---------------------|------------|----------------------------------------------------|
| `id`                | bigint PK  |                                                    |
| `purchase_order_id` | FK → purchase_orders (cascade delete) |               |
| `sale_item_id`      | FK → sale_items | Link back to originating sale item          |
| `item_name`         | string     | Snapshot: `product_type — manufacturer — style — color_item_number` |
| `quantity`          | decimal    | May be overridden from sale item qty at creation   |
| `unit`              | string     | Copied from sale item                              |
| `cost_price`        | decimal    | May be overridden from sale item cost at creation  |
| `cost_total`        | decimal    | Auto-calculated by model: `quantity * cost_price`  |
| `po_notes`          | text nullable | Per-item notes; pre-filled from sale item's `po_notes`, editable on create and edit |
| `sort_order`        | integer    |                                                    |

`cost_total` is recalculated automatically via `PurchaseOrderItem::booted()` `saving` hook.

---

## Models

### `App\Models\PurchaseOrder`
- `use SoftDeletes`
- `$guarded = ['id', 'po_number']`
- **Auto-generation** in `booted()` creating hook: `{seq}-{sale_number}` (or just `{seq}` for stock POs). Sequence extracted via `CAST(SUBSTRING_INDEX(po_number, '-', 1) AS UNSIGNED)`. Retries 10x.
- Auto-sets `ordered_by`, `created_by`, `updated_by` from `auth()->id()` on create
- Auto-sets `updated_by` on update
- **Casts:** `pickup_at` → `datetime`
- **Relationships:** `sale()`, `opportunity()`, `vendor()`, `items()`, `orderedBy()`, `creator()`, `updater()`, `calendarEvent()` (→ CalendarEvent)
- **Accessors:** `getFulfillmentLabelAttribute()`, `getStatusLabelAttribute()`, `getGrandTotalAttribute()`

### `App\Models\PurchaseOrderItem`
- `$guarded = ['id']`
- `cost_total` auto-calculated on `saving` hook
- **Relationships:** `purchaseOrder()`, `saleItem()`

### `App\Models\Sale` (modified)
- Added: `purchaseOrders()` → `HasMany(PurchaseOrder::class)->orderByDesc('created_at')`

### `App\Models\Opportunity` (modified)
- Added: `purchaseOrders()` → `HasMany(PurchaseOrder::class)->orderByDesc('created_at')`

---

## Routes

All inside the `pages` middleware group (`auth + verified`):

```
GET  pages/sales/{sale}/purchase-orders/create     pages.sales.purchase-orders.create    role_or_permission:admin|create purchase orders
POST pages/sales/{sale}/purchase-orders            pages.sales.purchase-orders.store     role_or_permission:admin|create purchase orders
GET  pages/purchase-orders/{purchaseOrder}         pages.purchase-orders.show            role_or_permission:admin|view purchase orders
GET  pages/purchase-orders/{purchaseOrder}/edit    pages.purchase-orders.edit            role_or_permission:admin|edit purchase orders
PUT  pages/purchase-orders/{purchaseOrder}         pages.purchase-orders.update          role_or_permission:admin|edit purchase orders
GET  pages/purchase-orders/{purchaseOrder}/pdf     pages.purchase-orders.pdf             role_or_permission:admin|view purchase orders
POST pages/purchase-orders/{purchaseOrder}/send-email  pages.purchase-orders.send-email  role_or_permission:admin|edit purchase orders
DELETE pages/purchase-orders/{purchaseOrder}       pages.purchase-orders.destroy         role_or_permission:admin|delete purchase orders
DELETE pages/purchase-orders/{purchaseOrder}/force pages.purchase-orders.force-destroy   role:admin   (uses ->withTrashed())
```

---

## Controller — `App\Http\Controllers\Pages\PurchaseOrderController`

### Key methods

**`create(Sale $sale)`**
- Loads sale rooms + material items (non-removed, ordered by sort_order)
- Loads active vendors, **excluding any vendor linked to an Installer** (`Installer::whereNotNull('vendor_id')->pluck('vendor_id')`)
- Computes `$remainingQtys` — remaining available qty per sale item across all non-cancelled POs
- Passes `$remainingQtys` to view

**`store(Request $request, Sale $sale)`**
- Validates fields + `qty[]`, `cost[]`, `po_notes[]` override arrays; `pickup_date` / `pickup_time` when fulfillment = `pickup`
- Validates each submitted item's qty doesn't exceed remaining (via `orderedQtys()`)
- Creates PO + PO items inside `DB::transaction()`; saves `po_notes` per item
- Assembles `pickup_at` datetime from `pickup_date` + `pickup_time` when fulfillment = `pickup`
- Triggers `syncCalendarCreate()` best-effort for pickup scheduling
- Redirects to PO show page

**`edit(PurchaseOrder $purchaseOrder)`**
- Loads `items.saleItem` (needed for max qty calculation)
- Computes `$maxQtys` per PO item: `sale_item.quantity - ordered_by_other_pos`
  (excludes current PO from the calculation)
- Passes `$maxQtys` to view
- Vendor dropdown also excludes installer-linked vendors

**`update(Request $request, PurchaseOrder $purchaseOrder)`**
- Gate: status `ordered` requires `vendor_order_number`
- Validates `po_items[id][quantity]`, `po_items[id][cost_price]`, `po_items[id][po_notes]` overrides; `pickup_date` / `pickup_time`
- Validates each item's new qty against max (excluding current PO)
- Updates PO header fields + PO items
- Calendar sync/update/cancel based on fulfillment change

**`destroy(PurchaseOrder $purchaseOrder)`** — soft delete, redirects to sale

**`forceDestroy(PurchaseOrder $purchaseOrder)`** — permanent delete, admin only, uses `->withTrashed()` route

**`previewPdf()`** — DomPDF, inline browser preview

**`sendEmail()`** — Track 1 (shared mailbox) only for vendor emails, attaches PDF, stamps `sent_at`

### Private helpers

**`orderedQtys(int $saleId, ?int $excludePurchaseOrderId = null): array`**
- Returns `[sale_item_id => total_ordered_qty]`
- Excludes cancelled POs, soft-deleted POs (automatic via SoftDeletes scope)
- Pass `$excludePurchaseOrderId` when editing, to exclude the current PO's own quantities

**`buildItemName(SaleItem $item): string`**
- Returns `product_type — manufacturer — style — color_item_number` (or `'Material Item'`)

**`resolveDeliveryAddress(string $method, ?string $custom, Sale $sale): ?string`**
- `delivery_site` → `sale->job_address`
- `delivery_warehouse` → branding settings street/city/province/postal
- `delivery_custom` → custom textarea value
- `pickup` → null

**`warehouseAddress(): string`** — reads `branding_street/city/province/postal` from `app_settings`

**`buildPickupEventData(PurchaseOrder $po): array`** — builds Graph API event payload for a pickup

**`syncCalendarCreate(PurchaseOrder $po)`** — creates event on RM Warehouse group calendar (`group_id = 4bfd495c-4df2-4eaa-9d8c-987c4ef23b02`); best-effort

**`syncCalendarUpdate(PurchaseOrder $po)`** — updates existing event via Graph API; best-effort

**`cancelCalendarEvent(PurchaseOrder $po)`** — deletes event from Graph API, soft-deletes local CalendarEvent, clears `calendar_event_id`

---

## Permissions

Defined in `PermissionsSeeder`:
- `view purchase orders`
- `create purchase orders`
- `edit purchase orders`
- `delete purchase orders`

Role assignments (`RolesSeeder`):
- **admin**: all (auto via `Permission::all()`)
- **sales**: view, create, edit
- **estimator**: view
- **accounting**: view
- **reception**: view

Force-delete route uses `role:admin` middleware (not permission-based).

---

## Views

| View      | Path |
|-----------|------|
| Index PO  | `resources/views/pages/purchase-orders/index.blade.php` |
| Create PO | `resources/views/pages/purchase-orders/create.blade.php` |
| Edit PO   | `resources/views/pages/purchase-orders/edit.blade.php` |
| Show PO   | `resources/views/pages/purchase-orders/show.blade.php` |
| PDF       | `resources/views/pdf/purchase-order.blade.php` |

### Index view (`x-app-layout`)
- Route: `GET /pages/purchase-orders` → `pages.purchase-orders.index`
- Filters: search (PO#, vendor name, sale#), status dropdown, date from/to (created_at)
- Table columns: PO#, Sale (linked), Vendor, Status badge, Fulfillment, Expected ETA, Total, Created, View/Edit actions
- Edit action is permission-gated (`can('edit purchase orders')`)
- Paginated 25/page with `withQueryString()`

### Create view (`x-app-layout`)
- Alpine component: `poCreate()`
- **Vendor dropdown** with email shown in option text; installer-linked vendors excluded
- **Material items** grouped by room with checkboxes
  - Items with 0 remaining: greyed out, disabled checkbox, "Fully ordered" badge
  - Items with partial remaining: "X remaining" blue badge, qty pre-filled with remaining
  - On check: qty, unit cost, and **po_notes** textarea slide in (Alpine `x-show`)
  - Qty capped at remaining (`max=""` attribute + Alpine validation)
  - `validateQty(itemId, value, maxQty)` tracks `qtyErrors` object; blocks submit if errors exist
  - Toggle All only selects items with remaining > 0
- **Fulfillment Method** dropdown (not radios); Alpine `x-show` hints + custom address textarea
- **Pickup scheduling** — blue box shown when fulfillment = `pickup` (Alpine `x-show`): pickup date + time inputs; syncs to RM Warehouse group calendar
- **Expected ETA** date input
- **Special Instructions** textarea
- Submit disabled until vendor + items + fulfillment all set

### Edit view (`x-app-layout`)
- Alpine components: `poEdit()` (form-level) + `poItems()` (items section)
- **Vendor dropdown** (installer-linked vendors excluded)
- **Status dropdown** — vendor order number field shown/required when status = `ordered` or `received`
- **Items table** — qty, cost_price, and **po_notes** are editable inputs
  - "max N" hint shown below each qty input
  - `recalcRow(id, event, maxQty)` in `poItems()` live-recalculates row total and grand total
  - Inline error shown if qty exceeds max
  - Items cannot be added or removed post-creation (fixed at create time)
- **Fulfillment Method** dropdown with same Alpine hints as create
- **Pickup scheduling** fields pre-filled from `$purchaseOrder->pickup_at`; "Synced" green badge if `calendar_event_id` set
- **Expected ETA**, **Special Instructions**

### Show view (`x-app-layout`)
- Header: PO number, status badge, sale link, sent date
- Action buttons: Back to Sale, Edit, Print PDF, Send to Vendor, Delete, Permanently Delete
  - Delete (soft): visible to users with `delete purchase orders` permission
  - Permanently Delete (hard): visible to admin role only — has destructive confirmation dialog
- Details card: vendor info, PO number, expected ETA, fulfillment, delivery address, ordered by
- Pickup datetime shown under Fulfillment with "Synced" badge when `calendar_event_id` set
- Items table with **po_notes** shown below item name; grand total footer
- Special instructions
- Send Email modal: pre-filled vendor email, default subject/body, PDF attachment preview link

### PDF template (`resources/views/pdf/purchase-order.blade.php`)
- Matches sale PDF style (DejaVu Sans, blue `#1d4ed8` accents)
- Header: branding logo + company info (left), "PURCHASE ORDER" + PO number + dates (right)
- Info grid: vendor details (left) + PO details (right)
- Delivery address box (blue-tinted) or "Pickup" notice
- Items table with alternating rows; **po_notes** shown below item name in small muted text; grand total footer
- Special instructions section
- Ordered by, footer

---

## Qty Tracking — Business Rules

1. `orderedQtys()` sums `purchase_order_items.quantity` grouped by `sale_item_id` for all
   non-cancelled, non-soft-deleted POs on a given sale.

2. **On create**: remaining = `sale_item.quantity - orderedQtys[sale_item_id]`
   - `remaining <= 0` → item disabled (fully ordered)
   - `remaining > 0` → item selectable, qty pre-filled with remaining, hard cap enforced

3. **On edit**: max = `sale_item.quantity - orderedQtys_excluding_current_PO[sale_item_id]`
   - This allows the current PO to be re-allocated up to the full available headroom
   - Validated both client-side (Alpine) and server-side

4. Cancelling a PO frees up its quantities (excluded from `orderedQtys` query).
   Soft-deleting a PO also frees its quantities (excluded via SoftDeletes scope).

---

## PO Summary on Other Pages

### Sale show page (`resources/views/pages/sales/show.blade.php`)
- `+ Create PO` button in header (permission-gated)
- PO summary table at bottom: PO number, vendor, status badge, fulfillment, expected ETA,
  total cost, View/Edit links
- Loaded via `SaleController::show()` eager loading `purchaseOrders.vendor` + `purchaseOrders.items`

### Sale edit page (`resources/views/pages/sales/edit.blade.php`)
- Same PO card rendered outside the `<form>` tag (after `</form>`, before scripts)
- Same `+ Create PO` button
- Loaded via `SaleController::edit()` eager loading `purchaseOrders.vendor` + `purchaseOrders.items`

### Opportunity show page (`resources/views/pages/opportunities/show.blade.php`)
- Job Transactions table: PO column shows clickable PO numbers with status badges
- Full "Purchase Orders" section at bottom: PO number, vendor, sale link, status, fulfillment,
  expected ETA, View/Edit actions
- Loaded via `OpportunityController::show()` with `->with(['vendor', 'sale'])`

---

## Email — Vendor Send

- **Track 1 only** (shared mailbox `reception@rmflooring.ca`) — vendor emails always use shared mailbox
- PDF auto-attached (base64 via Graph API fileAttachment)
- `sent_at` stamped on PO record on successful send
- Send modal on show page: editable To/Subject/Body, pre-filled from vendor email

---

## Resume Prompt

> Read CLAUDE.md and Context/context_purchase_orders.md. I want to continue working on the Purchase Orders module. One step at a time.
