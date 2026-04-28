# QuickBooks Online Integration Context
Updated: 2026-04-27 (session 55)

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
| 6 | Planned | Per-type account/item mapping UI (product/freight/labour split for both expense accounts and income items) — see "Planned: Per-Type Account & Item Mapping" section above |

---

## Planned: Per-Type Account & Item Mapping

Currently the settings page has a single **Expense Account ID** (`qbo_ap_account_id`) and a single **Income Item ID** (`qbo_income_item_id`), both applied to every line of every bill/invoice. The planned change is to split these into per-type mappings.

### New settings keys (replace the two existing ones)

| Old key | New keys |
|---------|----------|
| `qbo_ap_account_id` | `qbo_ap_product_account_id`, `qbo_ap_freight_account_id`, `qbo_ap_labour_account_id` |
| `qbo_income_item_id` | `qbo_income_material_item_id`, `qbo_income_freight_item_id`, `qbo_income_labour_item_id` |

All stored in `app_settings` via `Setting::get/set`, same as current.

### Income (Invoice) mapping — clean

`invoice_items` already has `item_type` enum (`material`, `labour`, `freight`). `buildInvoicePayload()` in `QboSyncService` loops through each item and sets `SalesItemLineDetail.ItemRef.value` — just select the correct item ID by `item_type`.

### Expense (Bill) mapping — mostly clean

`bill_items` does **not** have an `item_type` column, but:
- **Labour bills**: `bill.bill_type = 'installer'` → all lines use `qbo_ap_labour_account_id`
- **Vendor bills**: `bill.bill_type = 'vendor'` → lines come from PO items, which link to `sale_item_id` on `sale_items` (which has `type`: `material`/`freight`). Two options:
  - **Option A (preferred)**: Add nullable `item_type` enum column to `bill_items` via migration, populated when the bill is created from a PO/WO.
  - **Option B (no migration)**: In `buildBillPayload()`, if `bill_type = 'vendor'`, default all lines to `qbo_ap_product_account_id` (freight is rare on vendor bills and is already a separate PO).

Option A is cleaner long-term. Option B is quicker to ship. Decision deferred to implementation session.

### Files to change

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/QuickBooksController.php` | `index()` loads 6 settings; `saveSettings()` validates + saves 6 keys |
| `resources/views/admin/settings/quickbooks.blade.php` | Replace 2-field grid with two labelled sections (Expense Accounts / Income Items), 3 fields each |
| `app/Http/Controllers/Admin/BillController.php` | `pushToQbo()` loads 3 expense account IDs, passes to sync service |
| `app/Http/Controllers/Pages/InvoiceController.php` | `pushToQbo()` loads 3 income item IDs, passes to sync service |
| `app/Services/QboSyncService.php` | `pushBill()` + `buildBillPayload()` accept array of 3 account IDs; select by type. `pushInvoice()` + `buildInvoicePayload()` accept array of 3 item IDs; select by `item_type`. |
| *(optional)* new migration | Add `item_type` enum to `bill_items` if Option A chosen |

### Validation note

All 6 settings should be `required` on save (same as current). If any are missing, the push should fail with a clear error message naming which type is unconfigured.

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
