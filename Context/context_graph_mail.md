# Graph Mail Context — RM Flooring / Floor Manager

Updated: 2026-03-13

---

## Overview

Two-track email system using Microsoft Graph API. No SMTP or separate mail server. All sends go through the RM Flooring Azure app registration (same app used for calendar).

---

## Two-Track Architecture

| Track | Purpose | Auth method | Sender | Status |
|-------|---------|-------------|--------|--------|
| Track 1 | Internal system notifications (RFM alerts, test sends) | Client credentials (app-level) | Shared mailbox `reception@rmflooring.ca` | **Working** |
| Track 2 | Customer-facing emails (estimates, invoices) | Delegated OAuth (per-user token) | Staff member's personal `@rmflooring.ca` address | **Infrastructure built — wiring to estimates/invoices pending** |

---

## Track 1 — Shared Mailbox

### How it works
1. `GraphMailService::getAppToken()` — calls `/oauth2/v2.0/token` with `grant_type=client_credentials`
2. Uses `MICROSOFT_CLIENT_ID` + `MICROSOFT_CLIENT_SECRET` + `MICROSOFT_TENANT_ID` from `.env`
3. Scope: `https://graph.microsoft.com/.default`
4. `GraphMailService::send()` — posts to `/v1.0/users/{from}/sendMail`

### Azure permission required
| Permission | Type | Purpose |
|-----------|------|---------|
| `Mail.Send` | **Application** | Send mail as shared mailbox — required for client credentials flow |

> Must be **Application** type, not Delegated. Admin consent required (green checkmark in Azure portal).

### Sending configuration (stored in `app_settings` table, editable at `/admin/settings/mail`)

| Setting key | Default | Purpose |
|-------------|---------|---------|
| `mail_from_address` | `reception@rmflooring.ca` | Shared mailbox to send from |
| `mail_from_name` | `RM Flooring Notifications` | Display name in email clients |
| `mail_reply_to` | `noreply@rmflooring.ca` | Reply-to address (doesn't need to be a real inbox) |
| `mail_notifications_enabled` | `1` | Global kill-switch — if `0`, no emails are sent |

### API

```php
$mailer->send(
    to:          'someone@example.com',   // string or array of strings
    subject:     'Subject line',
    body:        'Plain text body',
    type:        'rfm_notification',      // used for mail_log grouping
    fromAddress: null,                    // usually leave null — reads from app_settings
);
```

- Returns `true` on success, `false` on failure
- Never throws — all failures logged to `[GraphMail]` channel
- Writes `MailLog` row with `track=1` for every send attempt

---

## Track 2 — Per-User Delegated OAuth

### How it works
1. Admin visits `/admin/settings/mail` → clicks **Connect** next to a user
2. `AdminMicrosoftMailConnectController::redirect()` starts OAuth flow with scopes `Mail.Send offline_access User.Read`, forcing Microsoft account picker so admin selects the target user's account
3. Target `user_id` stored in session alongside the CSRF state token
4. Microsoft redirects to `admin.settings.mail.callback`
5. `callback()` validates state, exchanges code for token, upserts `MicrosoftAccount` with `mail_connected=true`
6. Token stored encrypted against the user's `MicrosoftAccount` record (same record used for calendar)
7. `GraphMailService::getUserToken()` — on-demand refresh before each send
8. `GraphMailService::sendAsUser()` — posts to `/v1.0/me/sendMail` using the user's delegated token — email arrives from their personal address and appears in their Sent folder

### Azure permission required
| Permission | Type | Purpose |
|-----------|------|---------|
| `Mail.Send` | **Delegated** | Send mail as the signed-in user |

> Must be **Delegated** type. Admin consent must be granted. This is separate from the Application-type `Mail.Send` used by Track 1 — both can coexist on the same app registration.

### Token storage
Stored on the existing `microsoft_accounts` table (one row per user). New columns added:

| Column | Type | Purpose |
|--------|------|---------|
| `mail_connected` | boolean (default false) | Whether mail scope has been granted for this user |
| `mail_connected_at` | datetime nullable | When mail was last connected |

The `access_token`, `refresh_token`, and `token_expires_at` columns are shared with the calendar connection. When admin connects mail for a user who already has calendar connected, the token is updated to the new broader-scope token (which still covers calendar).

### Token refresh
`GraphMailService::getUserToken(User $user)`:
- If token is still valid → returns it immediately
- If expired → calls `/oauth2/v2.0/token` with `grant_type=refresh_token`
- If refresh fails → sets `mail_connected=false` on the account and returns `null`
- Caller should fall back to Track 1 when `null` is returned

### API

```php
$mailer->sendAsUser(
    user:    $user,                   // App\Models\User
    to:      'customer@example.com',  // string or array
    subject: 'Your Estimate',
    body:    'Plain text body',
    type:    'estimate',              // used for mail_log grouping
);
```

- Returns `true` on success, `false` on failure (token invalid, Graph error, etc.)
- Never throws
- Writes `MailLog` row with `track=2`, `sent_from` = user's MS365 email

### Fallback pattern (to implement at call sites)

```php
$mailer = app(GraphMailService::class);

$sent = $user->microsoftAccount?->mail_connected
    ? $mailer->sendAsUser($user, $to, $subject, $body, $type)
    : false;

if (! $sent) {
    // Fall back to Track 1 shared mailbox
    $mailer->send($to, $subject, $body, $type);
}
```

---

## Files

| File | Purpose |
|------|---------|
| `app/Services/GraphMailService.php` | Core service — Track 1 `send()`, Track 2 `getUserToken()` + `sendAsUser()` |
| `app/Http/Controllers/Admin/AdminMicrosoftMailConnectController.php` | Track 2 OAuth — `redirect()`, `callback()`, `disconnect()` |
| `app/Http/Controllers/Admin/MailSettingsController.php` | Admin portal controller — settings, test send, mail log, user list |
| `app/Mail/RfmCreatedMail.php` | RFM create notification (Track 1) — estimator + PM, checkbox-driven |
| `app/Mail/RfmUpdatedMail.php` | RFM update notification (Track 1) — estimator (diff) + PM (summary) |
| `app/Models/MailLog.php` | Eloquent model for `mail_log` table |
| `app/Models/MicrosoftAccount.php` | Per-user MS365 token storage (calendar + mail share one record) |
| `app/Models/Setting.php` | Key-value settings store (`get`/`set` static helpers) |
| `resources/views/admin/settings/mail.blade.php` | Full Email Management portal (4 sections) |

---

## Database

### `app_settings` table
Key-value store for Track 1 configuration. Editable at `/admin/settings/mail`.

| Key | Default |
|-----|---------|
| `mail_from_address` | `reception@rmflooring.ca` |
| `mail_from_name` | `RM Flooring Notifications` |
| `mail_reply_to` | `noreply@rmflooring.ca` |
| `mail_notifications_enabled` | `1` |

### `mail_log` table

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `to` | string | Recipient address |
| `subject` | string | |
| `status` | enum `sent\|failed` | |
| `type` | string | `rfm_notification`, `test`, `estimate`, `system`, etc. |
| `track` | tinyint (default 1) | `1` = shared mailbox, `2` = per-user delegated |
| `sent_from` | string nullable | Actual sender address used |
| `error` | text nullable | Graph error body on failure |
| `created_at` / `updated_at` | timestamps | |

### `microsoft_accounts` table (relevant columns)

| Column | Notes |
|--------|-------|
| `user_id` | FK → users (unique — one per user) |
| `access_token` | Encrypted at rest — shared by calendar + mail |
| `refresh_token` | Encrypted at rest |
| `token_expires_at` | Refreshed on demand before use |
| `is_connected` | Calendar connection flag |
| `connected_at` | Calendar connected timestamp |
| `mail_connected` | Mail OAuth granted flag (new) |
| `mail_connected_at` | Mail connected timestamp (new) |

---

## Routes

```
GET   /admin/settings/mail                    admin.settings.mail           MailSettingsController::index()
POST  /admin/settings/mail                    admin.settings.mail.update    MailSettingsController::update()
POST  /admin/settings/mail/test               admin.settings.mail.test      MailSettingsController::testSend()
GET   /admin/settings/mail/connect/{user}     admin.settings.mail.connect   AdminMicrosoftMailConnectController::redirect()
GET   /admin/settings/mail/callback           admin.settings.mail.callback  AdminMicrosoftMailConnectController::callback()
POST  /admin/settings/mail/disconnect/{user}  admin.settings.mail.disconnect AdminMicrosoftMailConnectController::disconnect()
```

---

## Email Management Portal — `/admin/settings/mail`

Four sections:

### 1. Track 1 Settings Form
Global enabled toggle, from address, from name, reply-to. Saves to `app_settings`.

### 2. Send Test Email
Sends a test via `GraphMailService::send()` (Track 1). Pre-fills with logged-in user's email.

### 3. Email Log
Last 50 entries. Columns: Date, To, Subject, Type, Track (blue Track 1 / purple Track 2 with sender address), Status badge.

### 4. Track 2 — Per-User MS365
Table of all users with:
- **Connected** (green) — `mail_connected = true`
- **Calendar only** (yellow) — `is_connected = true` but `mail_connected = false`
- **Not Connected** (grey) — no MS365 account at all
- **Connect / Reconnect** button → triggers OAuth for that user
- **Disconnect** button (shown only when connected) → `POST disconnect/{user}` with confirmation prompt

---

## RFM Email Notifications (Track 1)

Both fire from `RfmController` after save, best-effort (try/catch), never block the save.

### `RfmCreatedMail`
- Checkbox-driven: `notify_estimator` (default ON) + `notify_pm` (default OFF)
- Estimator: detailed internal email — all fields, special instructions, link
- PM: clean customer-facing — date/time, location, estimator name, access reminder

### `RfmUpdatedMail`
- Checkbox-driven: estimator auto-checked by JS when key fields change, PM always OFF by default
- Estimator: "CHANGES" block (old → new for each field) + full current details
- PM: clean updated summary, no diff, no internal jargon

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `ErrorInvalidUser` (404) | From address not a valid Exchange Online mailbox | Change `mail_from_address` to a real mailbox |
| `ErrorAccessDenied` (403) on Track 1 | `Mail.Send` is Delegated, not Application | Add Application-type `Mail.Send` + admin consent |
| `ErrorAccessDenied` (403) on Track 2 | `Mail.Send` Delegated not granted or no admin consent | Add Delegated `Mail.Send` + admin consent in Azure |
| `could not obtain app access token` | Wrong client credentials | Check `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_TENANT_ID` |
| Track 2 token refresh fails | Refresh token expired or revoked | Admin must reconnect the user from `/admin/settings/mail` |
| Emails not sending despite valid config | Global toggle off | Check `mail_notifications_enabled` in app_settings |

---

## Open Items

1. ~~**Wire Track 2 into estimates**~~ — **Done ✓** `EstimateController::sendEmail()` — modal on edit page, Track 2 / Track 1 fallback, status → `sent` on success
2. ~~**Wire Track 2 into sales**~~ — **Done ✓** `SaleController::sendEmail()` — modal on both edit and show pages
3. **Wire Track 2 into invoices** — invoice module not built yet
4. **RFM updated notification** — `updateStatus` route still sends no notification
5. **HTML email bodies** — all current emails are plain text; Track 2 customer-facing emails would benefit from branded HTML templates
6. **Wire RFM admin templates** — `rfm_created` / `rfm_updated` template types exist in `email_templates` but RFM still uses hardcoded `RfmCreatedMail` / `RfmUpdatedMail` classes
