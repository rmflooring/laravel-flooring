# QuickBooks Online Integration Context
Updated: 2026-04-20 (session 52)

## Overview
Two-way sync between Floor Manager and QuickBooks Online (QBO). FM is the operational source of truth; QBO is the accounting source of truth.

- **FM → QBO**: Vendors, Customers, Bills (AP), Invoices (AR), Payments
- **QBO → FM**: Payment status updates (Phase 5, webhooks)
- One QBO company (RM Flooring), sandbox during development, production when ready
- Manual "Push to QBO" button per bill/invoice to start; auto-push later

---

## Credentials & Environment

### .env keys (both local and live server)
```
QBO_CLIENT_ID=
QBO_CLIENT_SECRET=
QBO_REDIRECT_URI=https://fm.rmflooring.ca/admin/settings/quickbooks/callback
QBO_ENVIRONMENT=sandbox
QBO_WEBHOOK_VERIFIER_TOKEN=      ← Phase 5, leave blank for now
```

### Intuit Developer Portal
- App name: RMFM
- Scope: `com.intuit.quickbooks.accounting` only
- Redirect URIs registered:
  - `https://developer.intuit.com/v2/OAuth2Playground/RedirectUrl` (default, keep)
  - `https://fm.rmflooring.ca/admin/settings/quickbooks/callback`
- Sandbox company realm ID: `9341456914584979`
- OAuth connect flow must be done from the live server (fm.rmflooring.ca) — Intuit does not accept localhost with ports or IP addresses as redirect URIs

### config/services.php
```php
'quickbooks' => [
    'client_id'     => env('QBO_CLIENT_ID'),
    'client_secret' => env('QBO_CLIENT_SECRET'),
    'redirect_uri'  => env('QBO_REDIRECT_URI'),
    'environment'   => env('QBO_ENVIRONMENT', 'sandbox'),
],
```

---

## Database

### `qbo_connections`
Stores the single OAuth connection record (one row only — updated on reconnect).
- `realm_id` — QBO Company ID
- `environment` — sandbox | production
- `access_token` — encrypted via `Crypt::encryptString()`
- `refresh_token` — encrypted via `Crypt::encryptString()`
- `token_expires_at` — auto-refresh when past
- `connected_at`, `connected_by` (FK → users)

### `qbo_sync_log`
Audit trail of every push/pull. No `updated_at` (insert-only).
- `entity_type` — bill | invoice | vendor | customer | payment
- `entity_id` — local FM record ID
- `direction` — push | pull
- `qbo_id` — QBO entity ID
- `status` — success | error | skipped
- `message`, `payload` (json), `response` (json)

### Columns to add to existing tables (Phases 2–4)
| Table | Columns |
|-------|---------|
| `vendors` | `qbo_id`, `qbo_sync_token` |
| `customers` | `qbo_id`, `qbo_sync_token` |
| `bills` | `qbo_id`, `qbo_sync_token`, `qbo_synced_at`, `qbo_paid_at` |
| `invoices` | `qbo_id`, `qbo_sync_token`, `qbo_synced_at` |
| `invoice_payments` | `qbo_id` |
| `tax_groups` | `qbo_tax_code_id` |

---

## Key Files

| What | Where |
|------|-------|
| Service | `app/Services/QuickBooksService.php` |
| Controller | `app/Http/Controllers/Admin/QuickBooksController.php` |
| Connection model | `app/Models/QboConnection.php` |
| Sync log model | `app/Models/QboSyncLog.php` |
| Admin settings view | `resources/views/admin/settings/quickbooks.blade.php` |
| Migrations | `database/migrations/2026_04_20_151840_create_qbo_connections_table.php` |
| | `database/migrations/2026_04_20_151840_create_qbo_sync_log_table.php` |

---

## Routes
All under `admin` middleware group (admin only):
```
GET  admin/settings/quickbooks          → admin.settings.quickbooks         (index)
GET  admin/settings/quickbooks/connect  → admin.settings.quickbooks.connect (redirect to Intuit)
GET  admin/settings/quickbooks/callback → admin.settings.quickbooks.callback (OAuth callback)
POST admin/settings/quickbooks/disconnect → admin.settings.quickbooks.disconnect
```

---

## QuickBooksService

### OAuth
- `getAuthorizationUrl(string $state)` — builds Intuit OAuth URL
- `handleCallback(string $code, string $realmId, int $userId)` — exchanges code for tokens, saves to `qbo_connections`
- `disconnect()` — revokes refresh token with Intuit, clears token fields

### Token management
- `getAccessToken()` — returns valid access token; calls `refreshToken()` if expired
- `refreshToken(QboConnection)` — private; clears tokens + throws if refresh fails (forces reconnect)

### API
- `get(string $endpoint, array $query)` — GET request to QBO API
- `post(string $endpoint, array $payload)` — POST request (create/update)
- `query(string $sql)` — QBO query language (e.g. `SELECT * FROM Vendor WHERE ...`)
- `apiUrl()` — returns base URL: `{sandbox|production}-quickbooks.api.intuit.com/v3/company/{realm_id}`

### Helpers
- `isConnected()` — returns bool (realm_id + refresh_token present)
- `log(...)` — writes to `qbo_sync_log`

---

## SyncToken (important)
QBO uses optimistic locking. Every entity has a `SyncToken` (version number). Updates MUST include the current `SyncToken` or QBO returns a conflict error. Always store `qbo_sync_token` alongside `qbo_id` and refresh it after every successful push.

---

## Phase Roadmap

| Phase | Status | What |
|-------|--------|------|
| 1 | ✅ Done | OAuth connect, QuickBooksService, admin settings page, DB tables |
| 2 | Next | Push Vendor to QBO; Push Customer to QBO |
| 3 | | Push Bill (AP) to QBO — manual "Push to QBO" button on bill show page |
| 4 | | Push Invoice (AR) + InvoicePayments to QBO |
| 5 | | Webhook receiver — QBO → FM payment status sync |
| 6 | | Tax code mapping UI + account mapping per entity type |

---

## Canadian Tax Notes
- QBO Canada uses `TaxCodeRef` on line items (not a flat rate)
- Bills already store `gst_amount` + `pst_amount` separately — aligns with QBO
- Tax groups need `qbo_tax_code_id` column to map local tax → QBO tax code
- Account mapping (income/expense accounts): Richard to provide QBO account IDs per entity type

---

## Known Issues / Gotchas
- Intuit does NOT accept localhost with a port or IP addresses as redirect URIs — OAuth must be done from the live server (fm.rmflooring.ca)
- `.env` values must not accidentally include the key name (e.g. `QBO_REDIRECT_URI=QBO_REDIRECT_URI=...`) — happened twice during setup
- `QBO_ENVIRONMENT=sandbox` must be set correctly — affects which API base URL is used
- Token refresh failure clears tokens silently — admin will need to reconnect from the settings page
