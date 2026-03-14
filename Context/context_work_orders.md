# Work Orders Module — Dev Context
Updated: 2026-03-14 (session 6 — full redesign)

---

## Overview

Work Orders (WOs) represent scheduled installation tasks assigned to an **Installer** for a given sale. Multiple WOs can exist per sale. They are created from within a sale, include **labour-type line items** with qty/cost tracking, and optionally sync to the "RM – Installations" MS365 group calendar.

---

## Data Model

### `work_orders` table

| Column              | Type                    | Notes                                                         |
|---------------------|-------------------------|---------------------------------------------------------------|
| `id`                | bigint PK               |                                                               |
| `sale_id`           | FK → sales              | cascade delete                                                |
| `installer_id`      | nullable FK → installers | nullOnDelete                                                 |
| `wo_number`         | string unique           | Auto-generated: `WO-YYYY-NNNN`, sequential per year           |
| `status`            | string                  | default `created`; see status lifecycle below                 |
| `scheduled_date`    | nullable date           |                                                               |
| `scheduled_time`    | nullable string (HH:MM) | 5-char time string                                            |
| `calendar_event_id` | nullable FK → calendar_events | Local CalendarEvent record                              |
| `sent_at`           | nullable timestamp      | Stamped when WO email is sent to installer                    |
| `notes`             | nullable text           |                                                               |
| `created_by`        | nullable FK → users     |                                                               |
| `updated_by`        | nullable FK → users     |                                                               |
| `created_at` / `updated_at` | timestamps      |                                                               |
| `deleted_at`        | nullable (SoftDeletes)  |                                                               |

**Index:** `[sale_id, status]`

---

### `work_order_items` table

| Column         | Type                    | Notes                                              |
|----------------|-------------------------|----------------------------------------------------|
| `id`           | bigint PK               |                                                    |
| `work_order_id`| FK → work_orders        | cascade delete                                     |
| `sale_item_id` | nullable FK → sale_items| nullOnDelete — snapshot preserved if item deleted  |
| `item_name`    | string                  | Snapshot: `labour_type — description`              |
| `quantity`     | decimal(10,2)           |                                                    |
| `unit`         | string                  | e.g. `sqft`, `lnft`                                |
| `cost_price`   | decimal(10,2)           | Per-unit installer cost                            |
| `cost_total`   | decimal(10,2)           | Auto-calculated: `quantity × cost_price` (saving hook) |
| `sort_order`   | integer default 0       |                                                    |

---

## Models

### `App\Models\WorkOrder`

- `use SoftDeletes`
- `$guarded = ['id', 'wo_number']`
- **Auto-generation** in `booted()` creating hook: `WO-YYYY-NNNN`, sequential per year, retries 10x
- Auto-sets `created_by` / `updated_by` from `auth()->id()` on create/update
- **Constants:** `STATUSES`, `STATUS_LABELS`
- **Accessors:** `getStatusLabelAttribute()`, `getCalendarSyncedAttribute()`, `getGrandTotalAttribute()` (sum of `items.cost_total`)
- **Relationships:** `sale()`, `installer()` (→ Installer), `items()` (→ WorkOrderItem), `calendarEvent()` (→ CalendarEvent), `creator()`, `updater()`

### `App\Models\WorkOrderItem`

- `$guarded = ['id']`
- `saving` hook auto-calculates `cost_total = quantity * cost_price`
- **Relationships:** `workOrder()`, `saleItem()` (→ SaleItem, nullable)

### `App\Models\Sale` (modified)
- `workOrders()` → `HasMany(WorkOrder::class)->orderByDesc('created_at')`

---

## Status Lifecycle

```
created → scheduled:      requires installer_id + scheduled_date
scheduled → in_progress:  manual
in_progress → completed:  manual
any → cancelled:          with confirmation dialog (calendar event deleted)
```

Constants: `WorkOrder::STATUSES = ['created', 'scheduled', 'in_progress', 'completed', 'cancelled']`

Status auto-advance in `store()`: if `installer_id` + `scheduled_date` are both set, status is set to `scheduled` automatically.

---

## Qty Tracking

WOs track how much of each labour sale item has been scheduled across all non-cancelled WOs — mirrors the PO `orderedQtys()` pattern.

**`scheduledQtys(int $saleId, ?int $excludeWorkOrderId): array`** — returns `[sale_item_id => total_qty_scheduled]` for all non-cancelled WOs on a sale. The `excludeWorkOrderId` param excludes the current WO when calculating max qtys on the edit page.

**`maxQtys(WorkOrder $workOrder): array`** — returns `[wo_item_id => max_allowed_qty]` for the edit form.

Rules enforced on create:
- Cannot select a labour item that is already fully scheduled
- Qty entered cannot exceed `sale_item.quantity - already_scheduled_qty`

Rules enforced on update:
- Each item's qty cannot exceed `(sale_item.quantity - already_scheduled_qty_excluding_this_wo)`

---

## Routes

All inside the `pages` middleware group, nested under `sales/{sale}`:

```
GET    pages/sales/{sale}/work-orders/create             pages.sales.work-orders.create      role_or_permission:admin|create work orders
POST   pages/sales/{sale}/work-orders                    pages.sales.work-orders.store       role_or_permission:admin|create work orders
GET    pages/sales/{sale}/work-orders/{workOrder}        pages.sales.work-orders.show        role_or_permission:admin|view work orders
GET    pages/sales/{sale}/work-orders/{workOrder}/edit   pages.sales.work-orders.edit        role_or_permission:admin|edit work orders
PUT    pages/sales/{sale}/work-orders/{workOrder}        pages.sales.work-orders.update      role_or_permission:admin|edit work orders
DELETE pages/sales/{sale}/work-orders/{workOrder}        pages.sales.work-orders.destroy     role_or_permission:admin|delete work orders
GET    pages/sales/{sale}/work-orders/{workOrder}/pdf    pages.sales.work-orders.pdf         role_or_permission:admin|view work orders
POST   pages/sales/{sale}/work-orders/{workOrder}/send-email  pages.sales.work-orders.send-email  role_or_permission:admin|edit work orders
```

**Scope check:** every method accepting `{workOrder}` calls `abort_if($workOrder->sale_id !== $sale->id, 404)`.

---

## Controller — `App\Http\Controllers\Pages\WorkOrderController`

### Key constants
```php
const INSTALLATIONS_GROUP_ID = 'a6890136-56b9-42fc-ac2b-8e05c98c0e8c';
```

### `create()`
- Loads `$sale->rooms` with their labour items
- Calculates `scheduledQtys()` to determine remaining qty per item
- Passes `$installers` (active), `$rooms`, `$scheduledQtys` to view

### `store()`
- Validates: at least one item selected, qty > 0 and ≤ remaining
- DB transaction: create WorkOrder + WorkOrderItem records
- Auto-advances to `scheduled` if installer + date both set
- Triggers `syncCalendarCreate()` best-effort

### `edit()`
- Eager loads `items.saleItem`
- Calls `maxQtys($workOrder)` to provide per-item max constraints
- Passes `$installers`, `$maxQtys` to view

### `update()`
- Validates item qtys vs remaining (excluding this WO)
- DB transaction: update WO header + all items
- Triggers calendar create/update/cancel based on what changed

### `previewPdf()`
- DomPDF inline browser response

### `sendEmail()`
- Track 1 (shared mailbox)
- PDF attached as base64 fileAttachment
- Stamps `sent_at` on the WO

---

## Calendar Sync — "RM – Installations" Group Calendar

WO calendar events go to the **RM – Installations group calendar** (not a user's personal calendar).

```php
const INSTALLATIONS_GROUP_ID = 'a6890136-56b9-42fc-ac2b-8e05c98c0e8c';
```

**`syncCalendarCreate(WorkOrder $workOrder)`**
- Finds the logged-in user's connected `MicrosoftAccount` (`is_connected = true`)
- Finds the calendar with `group_id = INSTALLATIONS_GROUP_ID` from `microsoft_calendars`
- Calls `GraphCalendarService::createEvent()` + `persistLocalEvent()`
- Saves `calendar_event_id` on the WO

**`syncCalendarUpdate(WorkOrder $workOrder)`**
- Loads `calendarEvent.externalLink`
- Calls `GraphCalendarService::updateEvent()` via the group endpoint
- Updates the local `CalendarEvent` record

**`cancelCalendarEvent(WorkOrder $workOrder)`**
- Loads `calendarEvent.externalLink`
- Calls `GraphCalendarService::deleteEvent()`
- Soft-deletes the local `CalendarEvent`
- Sets `calendar_event_id = null` on WO

All calendar operations are **best-effort** — wrapped in `try/catch`, logged to `[WO]` channel, never block the save.

### Calendar trigger logic in `update()`
- If being cancelled → `cancelCalendarEvent()`
- Else if `installer_id`, `scheduled_date`, or `scheduled_time` changed:
  - If existing `calendar_event_id` → `syncCalendarUpdate()`
  - Else → `syncCalendarCreate()`

---

## Permissions

Defined in `PermissionsSeeder`:
- `view work orders`
- `create work orders`
- `edit work orders`
- `delete work orders`

Role assignments (`RolesSeeder`):
| Role        | view | create | edit | delete |
|-------------|:----:|:------:|:----:|:------:|
| admin       | ✓    | ✓      | ✓    | ✓      |
| coordinator | ✓    | ✓      | ✓    | —      |
| estimator   | ✓    | ✓      | ✓    | —      |
| sales       | ✓    | ✓      | ✓    | —      |
| reception   | ✓    | —      | —    | —      |

---

## Views

| View       | Path |
|------------|------|
| Create WO  | `resources/views/pages/work-orders/create.blade.php` |
| Edit WO    | `resources/views/pages/work-orders/edit.blade.php` |
| Show WO    | `resources/views/pages/work-orders/show.blade.php` |
| PDF        | `resources/views/pdf/work-order.blade.php` |

All use `x-app-layout`.

### Create form features
- Installer dropdown (active installers)
- Labour items grouped by room, each with checkbox to include
- Fully-scheduled items shown disabled with "Fully scheduled" badge
- Partially-scheduled items show remaining qty badge
- When checked: qty (pre-filled with remaining, editable) + unit cost (editable)
- Scheduling: date + time, calendar hint shows when installer + date are both set
- Notes textarea
- Alpine.js components: `woCreate()`, `woItem(itemId, maxQty, defaultCost)`

### Edit form features
- Installer dropdown
- Status dropdown
- Labour items table (editable qty/cost; max hint shown; live total in last column)
- Items cannot be added/removed after creation
- Scheduling: date + time
- Notes
- Alpine.js components: `woEdit()`, `woRow(id, qty, cost)`

### Show page features
- Header: WO number, status badge, calendar badge, sent_at timestamp
- Action buttons: Back, Edit, Print PDF, Send to Installer, Delete (with confirm)
- Installer details card + Job details card
- Items table with grand total
- Notes section
- Send email modal (to, subject, body pre-filled)

### Status badge colours
| Status      | Tailwind class              |
|-------------|-----------------------------|
| created     | `bg-gray-100 text-gray-700` |
| scheduled   | `bg-blue-100 text-blue-800` |
| in_progress | `bg-amber-100 text-amber-800` |
| completed   | `bg-green-100 text-green-800` |
| cancelled   | `bg-red-100 text-red-800`   |

---

## PDF Template — `resources/views/pdf/work-order.blade.php`

Matches PO style (DejaVu Sans, `#1d4ed8` blue):
- **Header**: branding logo/name (left) + "WORK ORDER" title, WO number, date, status (right)
- **Info grid**: Installer details (left column), Job details (right column)
- **Schedule box**: blue-tinted, shows date/time + job address
- **Items table**: Item, Qty, Unit, Unit Cost, Total; Grand Total footer row
- **Notes box**
- **Footer**: company name + phone + email + website

---

## Sale page integration

`SaleController::show()` eager loads `workOrders.installer`.

`resources/views/pages/sales/show.blade.php`:
- **Work Orders section** (below POs, `@can('view work orders')` gated): table with WO number, installer, item count, scheduled date/time, status badge, calendar badge, View button
- **Status strip**: WO count live from `$sale->workOrders->count()`

---

## Sale Status page integration

`SaleStatusController::show()` eager loads `workOrders.installer`.

`resources/views/pages/sales/status.blade.php`:
- WO table columns: WO Number, Installer, Items, Scheduled, Status, Calendar, link
- Progress bar + overall status badge factor in WO statuses (see `context_sale_status.md`)

---

## Resume prompt

> Read CLAUDE.md and Context/context_work_orders.md. I want to continue working on the Work Orders module. One step at a time.
