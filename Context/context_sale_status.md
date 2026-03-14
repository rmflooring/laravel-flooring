# Sale Status Page — Dev Context
Updated: 2026-03-14 (WOs wired in)

---

## Overview

The Sale Status page is a read-mostly overview page for a sale, focused on ordering progress and job readiness. Primary users are the coordinator (tracking POs and ordering) and estimator/sales (verifying job readiness before install).

---

## Route

```
GET  pages/sales/{sale}/status    pages.sales.status    role_or_permission:admin|view sale status
```

Controller: `app/Http/Controllers/Pages/SaleStatusController.php`
View: `resources/views/pages/sales/status.blade.php`

---

## Permission

- Permission name: `view sale status`
- Assigned to roles: **admin** (auto), **sales**, **estimator**, **coordinator**
- Defined in `PermissionsSeeder` + assigned in `RolesSeeder`

### Coordinator role

`coordinator` was added in the same session as this feature. Permissions:
`view dashboard`, `view customers`, `view purchase orders`, `create purchase orders`, `edit purchase orders`, `view sale status`

---

## Controller Logic (`SaleStatusController::show`)

### Eager loading
```php
$sale->load([
    'rooms.items',
    'purchaseOrders.vendor',
    'purchaseOrders.items',
    'workOrders.assignedTo',
]);
```
SoftDeletes scope on `PurchaseOrder` automatically excludes soft-deleted POs.

### Material items
```php
$materialItems = $sale->rooms
    ->flatMap(fn($room) => $room->items->where('item_type', 'material'))
    ->values();
```

### Active POs
Non-cancelled POs: `filter(fn($po) => $po->status !== 'cancelled')`

### PO items join
`purchase_order_items.sale_item_id` is the FK back to `sale_items.id`.
Map is built as `$poItemsBySaleItemId[sale_item_id][] = ['po' => $po, 'poItem' => $poItem]`

### Stats
| Variable           | Meaning                                                                  |
|--------------------|--------------------------------------------------------------------------|
| `$totalMaterialItems` | Count of material line items across all rooms                         |
| `$posCreated`      | Count of non-cancelled POs                                               |
| `$posPending`      | Count of POs with status = pending (including cancelled ones)            |
| `$itemsReceived`   | Count of po_item records on POs with status = received                   |

### WO stats
- `$totalWOs` = count of non-cancelled WOs
- `$wosScheduledOrProgress` = count of WOs with status `scheduled`, `in_progress`, or `completed`

### Progress %
```php
$denominator     = $totalMaterialItems + $totalWOs;
$numerator       = $itemsReceived + $wosScheduledOrProgress;
$progressPercent = $denominator > 0 ? round($numerator / $denominator * 100) : 0;
```
Numerator: received material items + WOs that are scheduled/in-progress/completed. Denominator: total material items + total non-cancelled WOs.

### Coverage items
For each material sale item, the controller looks up `$poItemsBySaleItemId[$item->id]`. If no matches, `dot_status = 'none'`. Otherwise it selects the best-priority match:
- `received` → priority 3
- `ordered`  → priority 2
- `pending`  → priority 1

Returns `['item' => $saleItem, 'dot_status' => string, 'po' => PurchaseOrder|null]`

### Overall status badge logic (updated — WOs now factored in)
| Badge        | Condition                                                                              |
|--------------|----------------------------------------------------------------------------------------|
| Not started  | No POs AND no WOs                                                                      |
| Needs action | Any material item has no PO, OR any WO has status `created` (unassigned/unscheduled)   |
| Ready        | All materials received AND all WOs scheduled, in-progress, or completed                |
| In progress  | Otherwise — at least one PO ordered/received OR one WO scheduled/in-progress/completed |

---

## Badge / Dot colour system (Material Coverage section)

| Dot status | Hex colour | Badge text                  |
|------------|------------|-----------------------------|
| `received` | `#16a34a`  | Received · PO-XXXX          |
| `ordered`  | `#2563eb`  | Ordered · PO-XXXX           |
| `pending`  | `#7c3aed`  | PO created · PO-XXXX        |
| `none`     | `#d97706`  | No PO yet                   |
| inventory  | `#db2777`  | From inventory *(coming soon)* — badge exists structurally, never triggered |

Inline hex colours are used (not Tailwind dynamic classes) for consistent rendering.

---

## Placeholder sections

### Work Orders
Fully wired as of 2026-03-14. See `Context/context_work_orders.md` for full details.
Stat card shows live `$totalWOs` count. WO table replaces the old empty state.
Progress bar and overall status badge now factor in WO statuses.

### From inventory (coverage dots)
The pink "From inventory" dot colour and badge label exist structurally in the blade but are never set as a `dot_status` value. When inventory tracking is built, a sale item could be marked as fulfilled from stock, and `dot_status = 'inventory'` would activate this badge.

---

## Sale show page integration

`resources/views/pages/sales/show.blade.php`:

1. **Status button** — secondary button in the header, `@can('view sale status')` gated, links to `pages.sales.status`.

2. **Slim status strip** — one-liner between the header and the summary cards:
   `X POs · X received · X work orders · View status →`
   All counts computed inline from eager-loaded `$sale->purchaseOrders` and `$sale->workOrders`.

---

## Resume prompt

> Read CLAUDE.md and Context/context_sale_status.md. I want to continue working on the Sale Status page. One step at a time.
