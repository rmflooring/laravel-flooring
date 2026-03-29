# SMS Notification System — Dev Context
Updated: 2026-03-27 (session 36)

---

## Overview

SMS notifications are sent via **Twilio** for scheduling and reminder events. The system is separate from the email system — SMS is a distinct action, not bundled with email sends. All configuration is admin-managed. No customer consent tracking is handled in-app (managed externally).

Provider: **Twilio** (`twilio/sdk ^8.11`)
Phone numbers are normalized to E.164 format before sending.

---

## Data Model

### `sms_log` table

| Column         | Type                | Notes                                          |
|----------------|---------------------|------------------------------------------------|
| `id`           | bigint PK           |                                                |
| `to`           | string              | E.164 normalized recipient number             |
| `from`         | nullable string     | Twilio from number                             |
| `body`         | text                | Message body sent                              |
| `type`         | nullable string     | e.g. `wo_scheduled`, `rfm_booked`, `test`     |
| `status`       | string              | `sent` or `failed`                             |
| `error`        | nullable text       | Error message on failure                       |
| `related_type` | nullable string     | Morph class e.g. `App\Models\WorkOrder`        |
| `related_id`   | nullable bigint     | Related record ID                              |
| `sent_at`      | nullable timestamp  | Set on successful send                         |
| `created_at`   | timestamp           |                                                |

### `sms_templates` table

| Column      | Type       | Notes                                     |
|-------------|------------|-------------------------------------------|
| `id`        | bigint PK  |                                           |
| `type`      | string     | unique; matches `SmsTemplate::TYPES` keys |
| `body`      | text       | Template body with `{{tag}}` placeholders |
| `created_at`| timestamp  |                                           |
| `updated_at`| timestamp  |                                           |

### Columns added to existing tables

| Table         | Column                 | Type               | Notes                                      |
|---------------|------------------------|--------------------|--------------------------------------------|
| `users`       | `phone`                | nullable string    | Office phone for staff/PM users            |
| `users`       | `mobile`               | nullable string    | Mobile — used for SMS sends to PMs         |
| `work_orders` | `sms_reminder_sent_at` | nullable timestamp | Stamped after day-before reminder is sent  |
| `rfms`        | `sms_reminder_sent_at` | nullable timestamp | Stamped after day-before reminder is sent  |

---

## App Settings Keys

All stored in `app_settings` table via `Setting::get()` / `Setting::set()`.

| Key                      | Type    | Notes                                           |
|--------------------------|---------|-------------------------------------------------|
| `sms_enabled`            | `1`/`0` | Global on/off switch                            |
| `sms_account_sid`        | string  | Twilio Account SID                              |
| `sms_auth_token`         | string  | Twilio Auth Token                               |
| `sms_from_number`        | string  | Twilio from number in E.164 format              |
| `sms_reminder_time`      | string  | HH:MM — time of day to run daily reminder cron |
| `sms_notify_wo_scheduled`| `1`/`0` | WO scheduled notification on/off               |
| `sms_notify_wo_reminder` | `1`/`0` | WO day-before reminder on/off                  |
| `sms_notify_rfm_booked`  | `1`/`0` | RFM booked notification on/off                 |
| `sms_notify_rfm_updated` | `1`/`0` | RFM updated notification on/off                |
| `sms_notify_rfm_reminder`| `1`/`0` | RFM day-before reminder on/off                 |
| `sms_wo_scheduled_to`    | string  | Comma-separated: `pm`, `installer`, `homeowner`|
| `sms_wo_reminder_to`     | string  | Comma-separated: `pm`, `installer`, `homeowner`|
| `sms_rfm_booked_to`      | string  | Comma-separated: `estimator`, `pm`, `customer` |
| `sms_rfm_updated_to`     | string  | Comma-separated: `estimator`, `pm`, `customer` |
| `sms_rfm_reminder_to`    | string  | Comma-separated: `estimator`, `pm`, `customer` |

---

## Notification Types

| Type           | Label                         | Trigger                                          | Recipients                      |
|----------------|-------------------------------|--------------------------------------------------|---------------------------------|
| `wo_scheduled` | Work Order Scheduled          | WO status → `scheduled` (store or update)        | PM (`projectManager.mobile`), Installer (`installer.mobile`), Homeowner (`sale.job_phone` → `sourceEstimate.homeowner_phone`) |
| `wo_reminder`  | WO Day-Before Reminder        | `sms:send-reminders` command, day before WO date | PM, Installer, Homeowner (`job_phone`) |
| `rfm_booked`   | RFM Booked                    | `RfmController::store()`                         | Estimator (`employee.phone`), PM (`projectManager.mobile`), Customer (`parentCustomer.mobile` → `.phone`) |
| `rfm_updated`  | RFM Updated                   | `RfmController::update()` — fires on every save  | Estimator (`employee.phone`), PM (`projectManager.mobile`), Customer (`parentCustomer.mobile` → `.phone`) |
| `rfm_reminder` | RFM Day-Before Reminder       | `sms:send-reminders` command, day before RFM     | Estimator, PM, Customer (`customer.mobile` or `.phone`) |

---

## Phone Number Sources

| Recipient   | Source                                              |
|-------------|-----------------------------------------------------|
| PM          | `opportunity.projectManager.mobile` (`ProjectManager` model) |
| Installer   | `installer.mobile` (`Installer` model)              |
| Estimator   | `employee.phone` (`Employee` model — no `mobile` field) |
| Homeowner   | `sale.job_phone` → fallback `sale.sourceEstimate.homeowner_phone` |
| Customer    | `customer.mobile` → fallback `customer.phone`       |
| Staff users | `users.mobile` (added this session)                 |

---

## Key Files

| What                        | Where                                                              |
|-----------------------------|--------------------------------------------------------------------|
| Twilio wrapper service      | `app/Services/SmsService.php`                                      |
| Template render service     | `app/Services/SmsTemplateService.php`                              |
| SMS log model               | `app/Models/SmsLog.php`                                            |
| SMS template model          | `app/Models/SmsTemplate.php` — TYPES, TAGS, DEFAULTS constants     |
| Admin settings controller   | `app/Http/Controllers/Admin/SmsSettingsController.php`             |
| Admin template controller   | `app/Http/Controllers/Admin/AdminSmsTemplateController.php`        |
| Admin settings view         | `resources/views/admin/settings/sms.blade.php`                     |
| Admin templates view        | `resources/views/admin/settings/sms-templates.blade.php`           |
| Reminder command            | `app/Console/Commands/SendSmsReminders.php`                        |
| Scheduler registration      | `routes/console.php`                                               |

---

## Routes

| Method   | URI                                     | Name                               |
|----------|-----------------------------------------|------------------------------------|
| GET      | `admin/settings/sms`                    | `admin.settings.sms`               |
| POST     | `admin/settings/sms`                    | `admin.settings.sms.update`        |
| POST     | `admin/settings/sms/test`               | `admin.settings.sms.test`          |
| GET      | `admin/settings/sms-templates`          | `admin.settings.sms-templates.index` |
| POST     | `admin/settings/sms-templates/{type}`   | `admin.settings.sms-templates.save` |
| DELETE   | `admin/settings/sms-templates/{type}`   | `admin.settings.sms-templates.reset` |

---

## SmsService

`app/Services/SmsService.php`

- `send(string $to, string $body, string $type, ?Model $related): bool`
  - Checks `sms_enabled` setting first — returns `false` if disabled
  - Normalizes phone to E.164 via `normalizePhone()` (handles 10-digit and 11-digit CA/US numbers)
  - Sends via Twilio REST API
  - Logs every attempt (success or failure) to `sms_log`
  - Never throws — catches all exceptions and logs them
- `normalizePhone(string $phone): string`
  - 10 digits → `+1XXXXXXXXXX`
  - 11 digits starting with `1` → `+1XXXXXXXXXX`
  - Already E.164 → unchanged

---

## SmsTemplateService

`app/Services/SmsTemplateService.php`

- `getBody(string $type): string` — returns saved template body or built-in default
- `render(string $template, array $vars): string` — replaces `{{tag}}` placeholders
- `renderTemplate(string $type, array $vars): string` — get + render in one call

---

## Artisan Command

```
php artisan sms:send-reminders
```

- Runs daily at `sms_reminder_time` setting (default `16:00`) via Laravel scheduler
- Queries WOs where `scheduled_date = tomorrow`, status in `[scheduled, in_progress]`, `sms_reminder_sent_at IS NULL`
- Queries RFMs where `scheduled_at date = tomorrow`, status in `[pending, confirmed]`, `sms_reminder_sent_at IS NULL`
- Stamps `sms_reminder_sent_at = now()` after a successful send to prevent duplicate reminders
- Gated by `sms_notify_wo_reminder` and `sms_notify_rfm_reminder` toggles independently
- Output: info lines per sent reminder, error lines per failure

---

## Wiring Points

### WO Scheduled SMS
- `WorkOrderController::store()` — fires after transaction when `$status === 'scheduled'`
- `WorkOrderController::update()` — fires when `$data['status'] === 'scheduled'` AND `$previousStatus !== 'scheduled'`
  - `$previousStatus` is captured BEFORE the DB update (not via `getOriginal()` which is unreliable after `refresh()`)
- Helper: `WorkOrderController::sendScheduledSms(WorkOrder $workOrder)` — private method
- Homeowner phone: `sale.job_phone` → fallback `sale.sourceEstimate.homeowner_phone`; requires `loadMissing(['sale.sourceEstimate'])`

### RFM Booked SMS
- `RfmController::store()` — fires after email block, gated by `sms_notify_rfm_booked` setting
- Customer phone: `opportunity.parentCustomer.mobile` → fallback `.phone`
- Create form shows SMS Notifications section (gated by `smsRfmBookedEnabled` view var) — estimator (default ON) + PM (default ON if mobile set); individual checkboxes `sms_notify_estimator` / `sms_notify_pm` gate each send

### RFM Updated SMS
- `RfmController::update()` — fires after email block, gated by `sms_notify_rfm_updated` setting
- Edit form shows SMS Notifications section (gated by `smsRfmUpdatedEnabled` view var) — both default OFF; phone hint updates on estimator dropdown change
- `sms_notify_estimator` / `sms_notify_pm` checkboxes gate each send; customer send fires unconditionally when `customer` in `sms_rfm_updated_to`

---

## Admin UI

### SMS Settings page (`/admin/settings/sms`)
Two tabs:
1. **Configuration** — global toggle, Twilio credentials, reminder time, per-notification toggles with recipient checkboxes, test send form
2. **Send Log** — table of last 100 SMS sends with date, to, type, status (green/red badge), message body or error

### SMS Templates page (`/admin/settings/sms-templates`)
- Tab per notification type (4 tabs)
- Live character counter — warns amber when over 160 chars, shows segment count
- Copy-to-clipboard tag pills
- Custom / Default badge
- Reset to default button (DELETE route, confirm dialog)

---

## Bugs Fixed

- **Toggle save bug**: `SmsSettingsController::update()` used `$request->has()` to check toggle state — always returned `true` because hidden `<input value="0">` fields are always submitted. Fixed to `$request->input(...) === '1'`. Affects all 5 toggles: `sms_enabled` + all 4 notification toggles.

## Open Items

- ~~Wire RFM updated SMS~~ — Done (2026-03-27): `rfm_updated` type wired in `RfmController::update()`, fires on every save when enabled
- Wire homeowner SMS for CO (change order sent notification)
- Add SMS send button to WO show page (manual on-demand send to installer/PM)
- Consider adding `mobile` field to `Employee` model for estimator mobile SMS (currently uses `employee.phone`)
