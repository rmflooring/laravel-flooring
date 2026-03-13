# Graph Mail Context — RM Flooring / Floor Manager

Updated: 2026-03-13

---

## Overview

System email notifications sent via Microsoft Graph API using the RM Flooring Azure app registration (same app used for calendar). No separate mail server or SMTP credentials needed.

---

## Architecture

### Two-track email system

| Track | Purpose | Sender | Status |
|-------|---------|--------|--------|
| Track 1 | Internal system notifications (RFM alerts, test sends, etc.) | Shared mailbox via app credentials | **Working** |
| Track 2 | Customer-facing emails (estimates, invoices) | Per-user MS365 accounts | Planned |

---

## How Track 1 Works

Uses the **client credentials grant** (application permission, no user OAuth required):

1. `GraphMailService::getAppToken()` calls `POST /oauth2/v2.0/token` with `grant_type=client_credentials`
2. Uses `MICROSOFT_CLIENT_ID` + `MICROSOFT_CLIENT_SECRET` + `MICROSOFT_TENANT_ID` from `.env`
3. Scope: `https://graph.microsoft.com/.default`
4. Returns a short-lived access token (fetched fresh per send)
5. `GraphMailService::send()` posts to `POST /v1.0/users/{from}/sendMail`

---

## Azure App Permissions Required

The RM Flooring Calendar App (`fb31ca39-5523-4c5f-a6f7-990cfa4c66e4`) needs:

| Permission | Type | Purpose |
|-----------|------|---------|
| `Mail.Send` | **Application** | Send mail as any user — required for client credentials flow |

> **Important:** Must be **Application** type, not Delegated. Delegated requires a signed-in user and will return `403 ErrorAccessDenied` with client credentials flow.

Admin consent must be granted (green checkmark in Azure portal).

---

## Sending Configuration

All Track 1 settings are stored in the `app_settings` table and editable at `/admin/settings/mail`.

| Setting Key | Default | Purpose |
|-------------|---------|---------|
| `mail_from_address` | `reception@rmflooring.ca` | Shared mailbox to send from |
| `mail_from_name` | `RM Flooring Notifications` | Display name shown in email clients |
| `mail_reply_to` | `noreply@rmflooring.ca` | Reply-to address (doesn't need to exist) |
| `mail_notifications_enabled` | `1` | Global kill-switch for all notifications |

---

## Files

| File | Purpose |
|------|---------|
| `app/Services/GraphMailService.php` | Core mail service — token fetch + Graph sendMail + MailLog write |
| `app/Mail/RfmCreatedMail.php` | RFM creation notification — builds recipients + body |
| `app/Models/MailLog.php` | Eloquent model for `mail_log` table |
| `app/Models/Setting.php` | Key/value settings store (`get`/`set` static helpers) |
| `app/Http/Controllers/Admin/MailSettingsController.php` | Admin controller for Email Management portal |
| `resources/views/admin/settings/mail.blade.php` | Full Email Management portal view |
| `database/migrations/2026_03_12_234901_create_app_settings_table.php` | app_settings table |
| `database/migrations/2026_03_13_002601_create_mail_log_table.php` | mail_log table |

---

## app_settings Table

Simple key/value store. Current keys used by the mail system:

| Key | Default Value |
|-----|---------------|
| `mail_from_address` | `reception@rmflooring.ca` |
| `mail_from_name` | `RM Flooring Notifications` |
| `mail_reply_to` | `noreply@rmflooring.ca` |
| `mail_notifications_enabled` | `1` |

---

## mail_log Table

Every send attempt (success or failure) is written to `mail_log`.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `to` | string | Recipient address |
| `subject` | string | Email subject |
| `status` | enum `sent\|failed` | Result |
| `type` | string | `rfm_notification`, `test`, `system`, etc. |
| `error` | text (nullable) | Graph error body on failure |
| `created_at` / `updated_at` | timestamps | |

---

## GraphMailService API

```php
$mailer->send(
    to:          'someone@example.com',   // string or array of strings
    subject:     'Subject line',
    body:        'Plain text body',
    type:        'rfm_notification',      // used for mail_log grouping
    fromAddress: null,                    // overrides setting — usually leave null
);
```

- Returns `true` on success, `false` on failure
- Never throws — all failures are logged to `[GraphMail]` channel
- `fromAddress` falls back to `Setting::get('mail_from_address')` then `config('services.microsoft.mail_from_address')`
- Reads `mail_from_name` and `mail_reply_to` from `Setting` — not passed as params
- Checks `mail_notifications_enabled` before sending — returns `false` immediately if disabled
- Writes a `MailLog` row for every recipient on every send attempt (sent or failed)

---

## RFM Notification — RfmCreatedMail

**Trigger:** `RfmController::store` — fires after calendar event block, best-effort (try/catch)

**Recipients:**
- Assigned estimator (`rfm->estimator->email`) — if email is set on the employee record
- Project Manager (`opportunity->projectManager->email`) — if a PM is assigned and has an email

**Subject:** `RFM Scheduled: #JOB-NO Customer Name`

**Body (plain text):**
```
A new Request for Measure has been scheduled.

----------------------------------------
Job:          #Jan-29-01 — Customer Name
Job Site:     Site Name
Estimator:    John Smith
Scheduled:    Thursday, March 13, 2026 at 9:00 AM
Address:      123 Main St, Vancouver, V5K 1A1
Flooring:     Carpet, Vinyl / LVP
[Special Instructions if set]
----------------------------------------
View RFM: https://...
```

---

## Email Management Portal — `/admin/settings/mail`

Full portal built at `resources/views/admin/settings/mail.blade.php`. Four sections:

### 1. Track 1 Settings Form
- Global enabled/disabled toggle (`mail_notifications_enabled`)
- From Address (`mail_from_address`)
- From Display Name (`mail_from_name`)
- Reply-To Address (`mail_reply_to`)
- Save Settings button → `POST /admin/settings/mail` (`admin.settings.mail.update`)

### 2. Send Test Email
- Single email field (pre-filled with logged-in user's email)
- Send Test button → `POST /admin/settings/mail/test` (`admin.settings.mail.test`)
- Returns success/error flash on the same page

### 3. Email Log Table
- Shows last 50 entries from `mail_log`
- Columns: Date, To, Subject, Type, Status (Sent/Failed badges)
- Failed rows show Graph error body as a tooltip on the badge

### 4. Track 2 — Per-User MS365 (Placeholder)
- Lists all users with their MS365 connection status and connected date
- "Disconnect" buttons rendered but disabled with "Coming soon" tooltip
- Full Track 2 implementation is a future item

---

## Routes

```
GET   /admin/settings/mail          admin.settings.mail           index()
POST  /admin/settings/mail          admin.settings.mail.update    update()
POST  /admin/settings/mail/test     admin.settings.mail.test      testSend()
```

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `ErrorInvalidUser` (404) | From address doesn't exist as a mailbox in the tenant | Change `mail_from_address` to a valid Exchange Online mailbox |
| `ErrorAccessDenied` (403) | Using Delegated `Mail.Send` instead of Application | Add `Mail.Send` as **Application** permission and grant admin consent |
| `could not obtain app access token` | Wrong client credentials or tenant ID | Check `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_TENANT_ID` in `.env` |
| Emails not sending despite valid config | Global toggle off | Check `mail_notifications_enabled` in app_settings or the admin portal |

---

## Open Items

1. **RFM updated notification** — currently only fires on create (`RfmController::store`), not on edit
2. **Track 2** — per-user MS365 OAuth email for customer-facing sends (estimates, invoices)
