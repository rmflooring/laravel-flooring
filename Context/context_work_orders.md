# Work Orders Module — Dev Context
Created: 2026-03-14

---

## Overview

Work Orders (WOs) represent scheduled installation or prep tasks assigned to a user for a given sale. Multiple WOs can exist per sale (e.g. "Tile installation", "Subfloor prep"). They are created from within a sale and optionally sync to the assigned user's MS365 calendar.

---

## Data Model

### `work_orders` table

| Column                 | Type           | Notes                                              |
|------------------------|----------------|----------------------------------------------------|
| `id`                   | bigint PK      |                                                    |
| `sale_id`              | FK → sales     | cascade delete                                     |
| `wo_number`            | string unique  | Auto-generated: `WO-YYYY-NNNN`, sequential per year|
| `work_type`            | string         | Free text e.g. "Tile installation", "Subfloor prep"|
| `assigned_to_user_id`  | nullable FK → users | The user assigned to perform the work         |
| `scheduled_date`       | nullable date  |                                                    |
| `scheduled_time`       | nullable string (HH:MM) | 5-char time string                        |
| `status`               | string         | default `created`; see status lifecycle below      |
| `calendar_event_id`    | nullable FK → calendar_events | Local CalendarEvent record (same as RFM pattern) |
| `notes`                | nullable text  |                                                    |
| `created_by`           | nullable FK → users |                                               |
| `updated_by`           | nullable FK → users |                                               |
| `created_at` / `updated_at` | timestamps |                                                |
| `deleted_at`           | nullable (SoftDeletes) |                                           |

**Index:** `[sale_id, status]`

---

## Model — `App\Models\WorkOrder`

- `use SoftDeletes`
- `$guarded = ['id', 'wo_number']`
- **Auto-generation** in `booted()` creating hook: `WO-YYYY-NNNN`, sequential, retries 10x — same pattern as PO and Sale numbers
- Auto-sets `created_by` / `updated_by` from `auth()->id()` on create/update
- **Constants:** `STATUSES`, `STATUS_LABELS`
- **Accessors:** `getStatusLabelAttribute()`, `getCalendarSyncedAttribute()` (true if `calendar_event_id` is set)
- **Relationships:** `sale()`, `assignedTo()` (→ User), `calendarEvent()` (→ CalendarEvent), `creator()`, `updater()`

### `App\Models\Sale` (modified)
Added: `workOrders()` → `HasMany(WorkOrder::class)->orderByDesc('created_at')`

---

## Status Lifecycle

```
created → scheduled:   requires assigned_to_user_id + scheduled_date
scheduled → in_progress: manual
in_progress → completed: manual
any → cancelled:        with confirmation dialog (calendar event deleted)
```

Constants: `WorkOrder::STATUSES = ['created', 'scheduled', 'in_progress', 'completed', 'cancelled']`

Status auto-advance in `store()`: if `assigned_to_user_id` + `scheduled_date` are both set at create time, status is set to `scheduled` automatically.

Transition rule enforcement in `update()`: controller validates that moving to `scheduled` requires an assignee and scheduled date.

---

## Routes

All inside the `pages` middleware group:

```
GET    pages/sales/{sale}/work-orders/create           pages.sales.work-orders.create   role_or_permission:admin|create work orders
POST   pages/sales/{sale}/work-orders                  pages.sales.work-orders.store    role_or_permission:admin|create work orders
GET    pages/sales/{sale}/work-orders/{workOrder}      pages.sales.work-orders.show     role_or_permission:admin|view work orders
GET    pages/sales/{sale}/work-orders/{workOrder}/edit pages.sales.work-orders.edit     role_or_permission:admin|edit work orders
PUT    pages/sales/{sale}/work-orders/{workOrder}      pages.sales.work-orders.update   role_or_permission:admin|edit work orders
DELETE pages/sales/{sale}/work-orders/{workOrder}      pages.sales.work-orders.destroy  role_or_permission:admin|delete work orders
```

**Scope check:** every method accepting `{workOrder}` calls `abort_if($workOrder->sale_id !== $sale->id, 404)`.

---

## Controller — `App\Http\Controllers\Pages\WorkOrderController`

### Calendar sync helpers

All calendar operations are **best-effort** — wrapped in `try/catch`, logged to `[WO]` channel, never block the save.

**`syncCalendarCreate(WorkOrder $workOrder)`**
- Only fires when `assigned_to_user_id` + `scheduled_date` are both set
- Finds the assigned user's connected `MicrosoftAccount` (`is_connected = true`)
- Finds their primary enabled `MicrosoftCalendar`
- Calls `GraphCalendarService::createEvent()` + `persistLocalEvent()`
- Saves `calendar_event_id` on the WO

**`syncCalendarUpdate(WorkOrder $workOrder)`**
- Loads `calendarEvent.externalLink`
- Calls `GraphCalendarService::updateEvent()` (new method added to service)
- Also updates the local `CalendarEvent` record with new title/times

**`cancelCalendarEvent(WorkOrder $workOrder)`**
- Loads `calendarEvent.externalLink`
- Calls `GraphCalendarService::deleteEvent()` (new method added to service)
- Soft-deletes the local `CalendarEvent`
- Sets `calendar_event_id = null` on WO

**`buildEventData(WorkOrder $workOrder): array`**
- Title: `WO-XXXX · [job_name] — [work_type]` (or `WO-XXXX: [work_type]` if no job_name)
- Start: `scheduled_date + scheduled_time` (defaults to 08:00 if no time)
- End: start + 2 hours
- Location: `sale->job_address`
- Notes: "Sale: [number]\nAssigned to: [name]\nNotes: [notes]"

### Calendar trigger logic in `update()`
- If being cancelled → `cancelCalendarEvent()`
- Else if `assigned_to_user_id`, `scheduled_date`, or `scheduled_time` changed:
  - If existing `calendar_event_id` → `syncCalendarUpdate()`
  - Else → `syncCalendarCreate()`

---

## GraphCalendarService additions

Two new methods were added to `App\Services\GraphCalendarService`:

**`updateEvent(MicrosoftAccount $account, ExternalEventLink $link, array $eventData): void`**
- Looks up the calendar via `$link->external_calendar_id`
- Uses group or personal endpoint based on `calendar->group_id`
- PATCH via Graph API
- Updates `ExternalEventLink::last_synced_at`

**`deleteEvent(MicrosoftAccount $account, ExternalEventLink $link): void`**
- Uses group or personal endpoint
- DELETE via Graph API (expects 204)
- Deletes the `ExternalEventLink` record

These methods also close the known gap documented in `context_rfm.md` (RFM edit sync + cancel).

---

## Calendar for WOs — which calendar?

Unlike RFMs (which use a hardcoded group calendar `b8483c56-fc4b-4734-8011-335b88c7e4ad`), WO events go on the **assigned user's personal primary calendar**:
- `MicrosoftCalendar::where('microsoft_account_id', $account->id)->where('is_enabled', true)->orderByDesc('is_primary')->first()`
- If the assigned user has no connected MS account, sync is skipped silently

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

| View | Path |
|------|------|
| Create WO | `resources/views/pages/work-orders/create.blade.php` |
| Edit WO   | `resources/views/pages/work-orders/edit.blade.php` |
| Show WO   | `resources/views/pages/work-orders/show.blade.php` |

All use `x-admin-layout`. Calendar sync hint shown inline on create/edit with Alpine.js.

### Status badge colours (inline hex for JS-driven states)
| Status      | Tailwind class                   |
|-------------|----------------------------------|
| created     | `bg-gray-100 text-gray-700`      |
| scheduled   | `bg-blue-100 text-blue-800`      |
| in_progress | `bg-amber-100 text-amber-800`    |
| completed   | `bg-green-100 text-green-800`    |
| cancelled   | `bg-red-100 text-red-800`        |

### Calendar sync badges
- On calendar: green pill with green dot
- Not synced: gray pill with gray dot

---

## Sale page integration

`SaleController::show()` eager loads `workOrders.assignedTo`.

`resources/views/pages/sales/show.blade.php`:
1. **Work Orders section** (below POs, `@can('view work orders')` gated): table with WO number, work type, assigned to, scheduled date/time, status badge, calendar badge, View button
2. **Status strip**: WO count is live from `$sale->workOrders->count()`

---

## Sale Status page integration

`SaleStatusController::show()` eager loads `workOrders.assignedTo`.

### Updated progress bar formula
```
numerator   = items_received + wos_scheduled_or_progress (scheduled | in_progress | completed)
denominator = total_material_items + total_non_cancelled_wos
progressPercent = denominator > 0 ? round(numerator / denominator * 100) : 0
```

### Updated overall status badge logic
| Badge        | Condition                                                                              |
|--------------|----------------------------------------------------------------------------------------|
| Not started  | No POs AND no WOs                                                                      |
| Needs action | Any material item has no PO, OR any WO has status `created` (unassigned/unscheduled)   |
| Ready        | All materials received AND all WOs scheduled, in-progress, or completed                |
| In progress  | Otherwise — at least one PO ordered/received OR one WO scheduled/in-progress/completed |

---

## Resume prompt

> Read CLAUDE.md and Context/context_work_orders.md. I want to continue working on the Work Orders module. One step at a time.
