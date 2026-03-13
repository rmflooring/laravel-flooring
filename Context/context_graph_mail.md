# Graph Mail (Track 1) Context — RM Flooring / Floor Manager

Updated: 2026-03-13

---

## Overview

System email notifications sent via Microsoft Graph API using the RM Flooring Azure app registration (same app used for calendar). No separate mail server or SMTP credentials needed.

---

## Architecture

### Two-track email system (Track 1 complete, Track 2 planned)

| Track | Purpose | Sender | Status |
|-------|---------|--------|--------|
| Track 1 | Internal system notifications (RFM alerts, etc.) | Shared mailbox via app credentials | **Working** |
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

**From mailbox:** `reception@rmflooring.ca`
**Display name:** `RM Flooring Notifications`
**Reply-To:** `noreply@rmflooring.ca` (doesn't need to exist — replies bounce silently)

The from address is stored in `app_settings` table under key `mail_from_address`.
Editable by admin at `/admin/settings/mail`.

---

## Files

| File | Purpose |
|------|---------|
| `app/Services/GraphMailService.php` | Core mail service — token fetch + Graph sendMail |
| `app/Mail/RfmCreatedMail.php` | RFM creation notification — builds recipients + body |
| `app/Models/Setting.php` | Key/value settings store (`get`/`set` static helpers) |
| `app/Http/Controllers/Admin/MailSettingsController.php` | Admin UI for mail settings |
| `resources/views/admin/settings/mail.blade.php` | Mail settings form |
| `database/migrations/2026_03_12_234901_create_app_settings_table.php` | app_settings table |

---

## app_settings Table

Simple key/value store. Current keys:

| Key | Value | Set via |
|-----|-------|---------|
| `mail_from_address` | `reception@rmflooring.ca` | Admin UI or `Setting::set()` |

---

## GraphMailService API

```php
$mailer->send(
    to:          'someone@example.com',   // string or array of strings
    subject:     'Subject line',
    body:        'Plain text body',
    fromAddress: null,                    // overrides setting — usually leave null
    fromName:    'RM Flooring Notifications',
    replyTo:     'noreply@rmflooring.ca',
);
```

- Returns `true` on success, `false` on failure
- Never throws — all failures are logged to `[GraphMail]` channel
- `fromAddress` falls back to `Setting::get('mail_from_address')` then `config('services.microsoft.mail_from_address')`

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

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `ErrorInvalidUser` (404) | From address doesn't exist as a mailbox in the tenant | Change `mail_from_address` to a valid Exchange Online mailbox |
| `ErrorAccessDenied` (403) | Using Delegated `Mail.Send` instead of Application | Add `Mail.Send` as **Application** permission and grant admin consent |
| `could not obtain app access token` | Wrong client credentials or tenant ID | Check `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_TENANT_ID` in `.env` |

---

## What Still Needs to Be Done

1. **Track 2** — per-user MS365 OAuth email for customer-facing sends (estimates, invoices)
2. **Email log** — store sent notifications in a DB table for audit trail
3. **RFM updated notification** — currently only fires on create, not on edit
4. **Test send button** — add to admin mail settings page to verify config without creating an RFM
