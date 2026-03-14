# Sale Status Page — Dev Context
Updated: 2026-03-14

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

### Progress %
```php
$progressPercent = ($totalMaterialItems > 0 && $posCreated > 0)
    ? (int) round($itemsReceived / $totalMaterialItems * 100)
    : 0;
```
Numerator: count of po_item records on received POs. Denominator: total material sale items.
Shows 0% if no POs exist.

### Coverage items
For each material sale item, the controller looks up `$poItemsBySaleItemId[$item->id]`. If no matches, `dot_status = 'none'`. Otherwise it selects the best-priority match:
- `received` → priority 3
- `ordered`  → priority 2
- `pending`  → priority 1

Returns `['item' => $saleItem, 'dot_status' => string, 'po' => PurchaseOrder|null]`

### Overall status badge logic
| Badge         | Condition                                                           |
|---------------|---------------------------------------------------------------------|
| Not started   | No POs exist (`$posCreated === 0`)                                  |
| Ready         | All material items have `dot_status = 'received'`                   |
| Needs action  | At least one material item has `dot_status = 'none'`                |
| In progress   | At least one PO has status `ordered` or `received`                  |

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

## Placeholder sections (not yet wired to data)

### Work Orders
Section heading and empty-state message only. No model, no data, no routes.
When the Work Orders module is built: wire stat card count, add a real table here, and factor WO completion into the overall status badge and progress bar.

### From inventory (coverage dots)
The pink "From inventory" dot colour and badge label exist structurally in the blade but are never set as a `dot_status` value. When inventory tracking is built, a sale item could be marked as fulfilled from stock, and `dot_status = 'inventory'` would activate this badge.

---

## Sale show page integration

`resources/views/pages/sales/show.blade.php`:

1. **Status button** — secondary button in the header, `@can('view sale status')` gated, links to `pages.sales.status`.

2. **Slim status strip** — one-liner between the header and the summary cards:
   `X POs · X received · 0 work orders · View status →`
   Computed inline from already-eager-loaded `$sale->purchaseOrders`. Work order count is hardcoded 0 until that module is built.

---

## Resume prompt

> Read CLAUDE.md and Context/context_sale_status.md. I want to continue working on the Sale Status page. One step at a time.
