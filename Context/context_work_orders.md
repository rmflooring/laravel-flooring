# Work Orders Module — Dev Context
Updated: 2026-03-16 (session 15)

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
| `wo_number`         | string unique           | Auto-generated: `{seq}-{sale_number}` (e.g. `3-8`), plain integers, no year prefix |
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
| `wo_notes`     | text nullable           | Per-item notes visible to installer (added 2026-03-15) |
| `sort_order`   | integer default 0       |                                                    |

### `work_order_item_materials` table (added 2026-03-15)

Links a WO labour item to one or more sale material items (the products being installed).

| Column              | Type                     | Notes                              |
|---------------------|--------------------------|------------------------------------|
| `id`                | bigint PK                |                                    |
| `work_order_item_id`| FK → work_order_items    | cascade delete                     |
| `sale_item_id`      | FK → sale_items          | cascade delete                     |

---

## Models

### `App\Models\WorkOrder`

- `use SoftDeletes`
- `$guarded = ['id', 'wo_number']`
- **Auto-generation** in `booted()` creating hook: `{seq}-{sale_number}` format (e.g. `3-8`). Sequence extracted via `CAST(SUBSTRING_INDEX(wo_number, '-', 1) AS UNSIGNED)`. Retries 10x.
- Auto-sets `created_by` / `updated_by` from `auth()->id()` on create/update
- **Constants:** `STATUSES`, `STATUS_LABELS`
- **Accessors:** `getStatusLabelAttribute()`, `getCalendarSyncedAttribute()`, `getGrandTotalAttribute()` (sum of `items.cost_total`)
- **Relationships:** `sale()`, `installer()` (→ Installer), `items()` (→ WorkOrderItem), `calendarEvent()` (→ CalendarEvent), `creator()`, `updater()`

### `App\Models\WorkOrderItem`

- `$guarded = ['id']`
- `saving` hook auto-calculates `cost_total = quantity * cost_price`
- **Relationships:** `workOrder()`, `saleItem()` (→ SaleItem, nullable), `relatedMaterials()` (→ WorkOrderItemMaterial, hasMany)

### `App\Models\WorkOrderItemMaterial` (added 2026-03-15)

- `$guarded = ['id']`
- **Relationships:** `workOrderItem()` (→ WorkOrderItem), `saleItem()` (→ SaleItem)

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

All inside the `pages` middleware group:

```
GET    pages/work-orders                                 pages.work-orders.index             role_or_permission:admin|view work orders
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
- Loads `$sale->rooms` with their **labour AND material** items (`item_type` IN `['labour', 'material']`)
- Calculates `scheduledQtys()` to determine remaining qty per item
- Passes `$installers` (active), `$rooms`, `$scheduledQtys` to view

### `store()`
- Validates: at least one item selected, qty > 0 and ≤ remaining; `wo_notes.*` optional text; `materials.*` optional arrays
- DB transaction: create WorkOrder + WorkOrderItem records + WorkOrderItemMaterial records
- Auto-advances to `scheduled` if installer + date both set
- Triggers `syncCalendarCreate()` best-effort

### `edit()`
- Eager loads `items.relatedMaterials`, `items.saleItem.room.items` (material only)
- Calls `maxQtys($workOrder)` to provide per-item max constraints
- Passes `$installers`, `$maxQtys` to view

### `show()`
- Eager loads `items.relatedMaterials.saleItem`, `items.saleItem.room`, `calendarEvent.externalLink`, `creator`
- Also `$sale->loadMissing('sourceEstimate')` for email template `customer_name` fallback
- Calls `resolveEmailTemplate()` → passes `$emailSubject`, `$emailBody` to view

### `update()`
- Validates item qtys vs remaining (excluding this WO); `wo_notes.*`; `wo_materials.*`
- DB transaction: update WO header + all items + resync material associations (delete + recreate)
- Triggers calendar create/update/cancel based on what changed

### `previewPdf()` / `sendEmail()`
- Eager loads `items.relatedMaterials.saleItem`, `items.saleItem.room`

### `previewPdf()`
- DomPDF inline browser response

### `sendEmail()`
- Track 1 (shared mailbox)
- PDF attached as base64 fileAttachment
- Stamps `sent_at` on the WO

### `resolveEmailTemplate(WorkOrder, Sale): array`
- Private helper — resolves `work_order` email template via `EmailTemplateService`
- Tags resolved: `customer_name`, `wo_number`, `job_name`, `job_no`, `job_address`, `pm_name`, `pm_first_name`, `sender_name`, `sender_email`, `wo_link`
- `wo_link` = `route('mobile.work-orders.show', $workOrder)` — full URL to the mobile WO view

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
- Else if `sync_calendar` unchecked and event exists → `cancelCalendarEvent()`
- Else if `sync_calendar` checked and calendar fields changed:
  - If existing `calendar_event_id` → `syncCalendarUpdate()`
  - Else → `syncCalendarCreate()`

### Calendar event title format
`{installer first word} - {sale.homeowner_name}` (falls back to `customer_name` then `job_name`)
e.g. `John - Smith`

### Opt-in/out checkbox (added 2026-03-15)
- Create + edit forms have a **"Add/Sync to RM – Installations calendar"** checkbox, default checked
- Posts `sync_calendar=1`; unchecked posts nothing (falsy)
- On create: skips `syncCalendarCreate()` if unchecked
- On edit: if unchecked and event exists → cancels/removes the event; edit form shows amber warning when unchecking an already-synced WO
- **Bug fix (2026-03-16)**: `$request->boolean('sync_calendar', true)` defaulted to `true` when checkbox was unchecked (no POST value). Fixed to `false` default in both `store()` and `update()`.

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
| Index WO   | `resources/views/pages/work-orders/index.blade.php` |
| Create WO  | `resources/views/pages/work-orders/create.blade.php` |
| Edit WO    | `resources/views/pages/work-orders/edit.blade.php` |
| Show WO    | `resources/views/pages/work-orders/show.blade.php` |
| PDF        | `resources/views/pdf/work-order.blade.php` |

All use `x-app-layout`.

### Index view (`x-app-layout`)
- Route: `GET /pages/work-orders` → `pages.work-orders.index`
- Filters: search (WO#, installer name, sale#), status dropdown, date from/to (created_at)
- Table columns: WO#, Sale (linked), Installer, Items count, Scheduled date/time, Status badge, Calendar sync badge, Created, View action
- View links to `pages.sales.work-orders.show` (requires both `{sale}` and `{workOrder}`)
- Paginated 25/page with `withQueryString()`

### Create form features
- Installer dropdown (active installers; installer-vendors excluded)
- **Room cards** — one card per sale room (house icon header); labour items inside each card
- Fully-scheduled items shown disabled with "Fully scheduled" badge
- Partially-scheduled items show remaining qty badge
- When checked: qty (pre-filled with remaining, editable) + unit cost + wo_notes (editable)
- **Material checkboxes** shown inside each labour item when checked — selects material sale items from the same room to associate (`name="materials[{labour_item_id}][]"`, value = material `sale_item_id`)
- Scheduling: date + time, calendar hint shows when installer + date are both set
- Notes textarea
- Alpine.js components: `woCreate()`, `woItem(itemId, maxQty, defaultCost)`

### Edit form features
- Installer dropdown
- Status dropdown
- Labour items table (editable qty/cost/wo_notes; max hint shown; live total in last column)
- **Related Materials checkboxes** per item row — pre-checked from `$item->relatedMaterials->pluck('sale_item_id')`; input `name="wo_materials[{wo_item_id}][]"`
- Items cannot be added/removed after creation
- Scheduling: date + time
- Notes
- Alpine.js components: `woEdit()`, `woRow(id, qty, cost)`

### Show page features
- Header: WO number, status badge, calendar badge, sent_at timestamp
- Action buttons: Back, Edit, Print PDF, Send to Installer, Delete (with confirm)
- Installer details card + Job details card
- **Labour Items grouped by room** — one card per sale room (blue house icon header); items table inside each card
- Grand Total row below all room cards
- Linked materials shown above each labour item (package icon, product name, qty, unit)
- WO notes shown below item name
- Notes section
- Send email modal (to, subject, body pre-filled)

### Sale edit page integration
- **WO card** added after PO card, gated by `@can('view work orders')`
- Shows: WO number, installer, status badge, scheduled date/time, total cost, View/Edit links
- Loaded via `SaleController::edit()` eager-loading `workOrders.installer` + `workOrders.items`

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
- **Items grouped by room**: each room gets a blue `⌂ Room Name` header row, then an items table (Item, Qty, Unit, Unit Cost, Total); materials shown above item name with `▸` prefix; WO notes shown below item name
- **Grand Total** standalone table at the bottom
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

---

## WO Staging / Pick Tickets (sessions 14–15, 2026-03-16)

Full pick ticket details in `Context/context_warehouse_pick_tickets.md`.

A Work Order can be **staged** — creating a `staged` PickTicket from the WO show page.

### Stage Pick Ticket flow
- "Stage Work Order" orange button → opens a **modal** (not a direct POST)
  - Warehouse notes textarea (optional) → saved as `staging_notes` on the PT
  - "Staged by: {current user}" info box displayed
- Route: `POST pages/sales/{sale}/work-orders/{workOrder}/stage-pick-ticket` → `WorkOrderController::stagePickTicket(Request $request, ...)`
- Stock check blocks if: active PT exists, no materials linked, or any material has insufficient `InventoryAllocation`

### Unstage flow
- "Unstage" red button → opens **unstage modal**
  - Shows PT#, staged by + date, staging notes
  - Reason textarea (optional)
- Route: `POST warehouse/pick-tickets/{pickTicket}/unstage` → `WarehousePickTicketController::unstage()`
- Sets `status = cancelled`, stamps `unstaged_by`, `unstaged_at`, `unstage_reason`
- WO can be re-staged after unstaging

### Delivery flow
- "Record Delivery" green button on WO staging section → **links to the PT show page** (full per-item delivery UX lives there, not inline on WO page)
- Supports partial delivery: each item has its own `delivered_qty`; status becomes `partially_delivered` until all items are fully delivered → then `delivered`

### Material Staging section on WO show page
- Shown below WO notes card; gated by `@can('edit work orders')`
- Orange "Staged by / notes" meta bar (creator name, date, staging notes if any)
- Job info grid: Sale#, Job, Installer, Install date
- Materials table: Material | Qty | Unit | Room

### `WorkOrderController::show()`
- `$stagingPickTicket` loaded via `PickTicket::where('work_order_id', ...)->whereNotIn('status', ['cancelled'])->with(['items.saleItem.room', 'creator'])->first()`

---

---

## Mobile WO View & QR Code (2026-03-23)

### Mobile view
- Route: `GET /m/wo/{workOrder}` → `mobile.work-orders.show` (middleware: `auth + verified + role_or_permission:admin|view work orders`)
- Controller: `app/Http/Controllers/Mobile/WorkOrderController::show()` — loads `sale`, `installer`, `items.saleItem.room`, `items.relatedMaterials.saleItem`, `creator`
- View: `resources/views/mobile/work-orders/show.blade.php`
  - WO# + status badge
  - Scheduled date/time (prominent blue box)
  - Job site card: homeowner name, job name, tappable Google Maps link for address
  - Items grouped by room (blue house header), with related materials + WO notes per item
  - Grand total, notes, Print PDF link

### QR code in WO PDF
- Footer of `resources/views/pdf/work-order.blade.php` includes a QR code pointing to `mobile.work-orders.show`
- Rendered as SVG base64 data URI in an `<img>` tag (NOT inline SVG, NOT PNG — Imagick not installed; inline SVG conflicts with global `table {}` CSS)
- Uses `display:table` div layout (not `<table>`) to avoid global table CSS interference

### `{{wo_link}}` email tag
- `EmailTemplate::TAGS['work_order']` includes `{{wo_link}}`
- `EmailTemplate::DEFAULTS['work_order']['body']` includes `View on mobile: {{wo_link}}`
- `WorkOrderController::resolveEmailTemplate()` resolves all tags including `wo_link = route('mobile.work-orders.show', $workOrder)`
- WO show email modal now pre-filled from resolved template (was previously hardcoded)
- `{{wo_link}}` appears in Settings → Email Templates → Work Order tab (auto-rendered from TAGS constant)

---

## Resume prompt

> Read CLAUDE.md and Context/context_work_orders.md. I want to continue working on the Work Orders module. One step at a time.
