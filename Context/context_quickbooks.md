# QuickBooks Online Integration Context
Updated: 2026-05-04 (session 56)

## Overview
Two-way sync between Floor Manager and QuickBooks Online (QBO). FM is the operational source of truth; QBO is the accounting source of truth.

- **FM → QBO**: Vendors, Customers, Bills (AP), Invoices (AR), Payments
- **QBO → FM**: Payment status updates (Phase 5, webhooks)
- One QBO company (RM Flooring), sandbox during development, production when ready
- Manual "Push to QBO" button per vendor/customer/bill/invoice

---

## Credentials & Environment

### .env keys (both local and live server)
```
QBO_CLIENT_ID=
QBO_CLIENT_SECRET=
QBO_REDIRECT_URI=https://fm.rmflooring.ca/admin/settings/quickbooks/callback
QBO_ENVIRONMENT=sandbox        ← change to "production" when going live
QBO_WEBHOOK_VERIFIER_TOKEN=    ← Phase 5, leave blank for now
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

### QBO columns on existing tables (all migrated)
| Table | Columns |
|-------|---------|
| `vendors` | `qbo_id`, `qbo_sync_token`, `qbo_synced_at` |
| `customers` | `qbo_id`, `qbo_sync_token`, `qbo_synced_at` |
| `bills` | `qbo_id`, `qbo_sync_token`, `qbo_synced_at`, `qbo_paid_at` |
| `invoices` | `qbo_id`, `qbo_sync_token`, `qbo_synced_at` |

---

## Key Files

| What | Where |
|------|-------|
| Sync service | `app/Services/QboSyncService.php` |
| QBO API service | `app/Services/QuickBooksService.php` |
| Admin controller | `app/Http/Controllers/Admin/QuickBooksController.php` |
| Vendor push | `app/Http/Controllers/Admin/VendorController::pushToQbo()` |
| Customer push | `app/Http/Controllers/Admin/CustomerController::pushToQbo()` |
| Bill push | `app/Http/Controllers/Admin/BillController::pushToQbo()` |
| Invoice push | `app/Http/Controllers/Pages/InvoiceController::pushToQbo()` |
| Webhook controller | `app/Http/Controllers/QuickBooksWebhookController.php` |
| Webhook job | `app/Jobs/ProcessQboWebhook.php` |
| Connection model | `app/Models/QboConnection.php` |
| Sync log model | `app/Models/QboSyncLog.php` |
| Admin settings view | `resources/views/admin/settings/quickbooks.blade.php` |
| Migrations | `database/migrations/2026_04_20_151840_create_qbo_connections_table.php` |
| | `database/migrations/2026_04_20_151840_create_qbo_sync_log_table.php` |
| | `database/migrations/2026_04_21_123103_add_qbo_fields_to_customers_table.php` |
| | `database/migrations/2026_04_21_123103_add_qbo_fields_to_vendors_table.php` |
| | `database/migrations/2026_04_21_124333_add_qbo_fields_to_bills_table.php` |
| | `database/migrations/2026_04_21_133008_add_qbo_fields_to_invoices_table.php` |

---

## Routes
```
GET  admin/settings/quickbooks                    → admin.settings.quickbooks
GET  admin/settings/quickbooks/connect            → admin.settings.quickbooks.connect
GET  admin/settings/quickbooks/callback           → admin.settings.quickbooks.callback
POST admin/settings/quickbooks/disconnect         → admin.settings.quickbooks.disconnect
POST admin/settings/quickbooks/settings           → admin.settings.quickbooks.save-settings

POST admin/vendors/{vendor}/push-to-qbo           → admin.vendors.push-to-qbo
POST admin/customers/{customer}/push-to-qbo       → admin.customers.push-to-qbo
POST admin/bills/{bill}/push-to-qbo               → admin.bills.push-to-qbo
POST pages/sales/{sale}/invoices/{invoice}/push-to-qbo → pages.sales.invoices.push-to-qbo

POST /webhook/quickbooks                          → webhook.quickbooks (no auth, intuit-signature verified)
```

---

## QboSyncService — what each method does

### `pushVendor(Vendor $vendor)`
- Builds QBO Vendor payload (DisplayName, contact, email, phone, address, account number)
- If `qbo_id` null: checks QBO for existing vendor by DisplayName first (links if found, creates if not)
- If `qbo_id` set: updates using Id + SyncToken
- Saves `qbo_id`, `qbo_sync_token`, `qbo_synced_at` back to vendor

### `pushCustomer(Customer $customer)`
- If customer has `parent_id`, auto-pushes parent first to get its QBO ID
- Builds QBO Customer payload; sets `Job=true` + `ParentRef` for job-site sub-customers
- Same create/link/update logic as vendor
- `customerDisplayName()` makes names unique — appends " (Site)" if job site name matches parent

### `pushBill(Bill $bill, array $accountIds)`
- Only vendor bills supported (not installer bills yet)
- Auto-pushes vendor if not yet in QBO
- Maps bill items to QBO `AccountBasedExpenseLineDetail` using per-type account IDs
- GST and PST added as separate expense lines
- `$accountIds = ['product' => id, 'freight' => id, 'labour' => id]`

### `pushInvoice(Invoice $invoice, array $itemIds)`
- Auto-pushes parent customer then job site customer if not yet in QBO
- Maps invoice items to QBO `SalesItemLineDetail` using per-type item IDs
- Tax added as an additional line using the material item ID
- `$itemIds = ['material' => id, 'freight' => id, 'labour' => id]`

### `handleBillUpdate(string $qboId, string $operation)` — webhook handler
- Delete operation: clears FM sync link
- Update: fetches bill from QBO, checks `Balance`; if 0 → stamps `qbo_paid_at`, sets status `approved`

### `handleInvoiceUpdate(string $qboId, string $operation)` — webhook handler
- Delete operation: clears FM sync link
- Update: fetches invoice from QBO, calculates `amount_paid = TotalAmt - Balance`, calls `InvoiceService::derivePaymentStatus()`

---

## SyncToken (important)
QBO uses optimistic locking. Every entity has a `SyncToken` (version number). Updates MUST include the current `SyncToken` or QBO returns a conflict error. Always store `qbo_sync_token` alongside `qbo_id` and refresh it after every successful push.

---

## Account & Item Mapping Settings
Stored in `app_settings` table. Configured at `/admin/settings/quickbooks`.

| Setting key | Used for |
|-------------|---------|
| `qbo_ap_product_account_id` | Bill lines — material items |
| `qbo_ap_freight_account_id` | Bill lines — freight items |
| `qbo_ap_labour_account_id` | Bill lines — labour / installer items |
| `qbo_income_material_item_id` | Invoice lines — material items |
| `qbo_income_freight_item_id` | Invoice lines — freight items |
| `qbo_income_labour_item_id` | Invoice lines — labour items |

### How to find IDs in QBO
**Expense account IDs** (Chart of Accounts):
- QBO → Accounting → Chart of Accounts → Edit an account → grab `accountId=` from the URL bar

**Income item IDs** (Products & Services):
- QBO → Sales → Products & Services → Edit an item → grab `id=` from the URL bar

**Or use tinker on the live server:**
```php
$qbo = app(\App\Services\QuickBooksService::class);
// Accounts:
collect($qbo->query("SELECT Id, Name, AccountType FROM Account MAXRESULTS 50")['QueryResponse']['Account'] ?? [])
    ->each(fn($a) => dump($a['Id'] . ' — ' . $a['Name'] . ' (' . $a['AccountType'] . ')'));
// Items:
collect($qbo->query("SELECT Id, Name, Type FROM Item MAXRESULTS 50")['QueryResponse']['Item'] ?? [])
    ->each(fn($i) => dump($i['Id'] . ' — ' . $i['Name'] . ' (' . $i['Type'] . ')'));
```

---

## Phase Roadmap

| Phase | Status | What |
|-------|--------|------|
| 1 | ✅ Done | OAuth connect, QuickBooksService, admin settings page, DB tables |
| 2 | ✅ Done | Push Vendor + Customer to QBO — tested working in sandbox |
| 3 | ✅ Done | Push Bill (AP) to QBO — tested working in sandbox |
| 4 | ✅ Done | Push Invoice (AR) to QBO — tested working in sandbox |
| 5 | Next | Webhook receiver — QBO → FM payment status sync (code written, needs `QBO_WEBHOOK_VERIFIER_TOKEN` + Intuit portal registration) |
| 6 | Planned | Installer bill push (currently blocked — only vendor bills supported) |

---

## Production Go-Live Checklist
When ready to switch from sandbox to production:

1. In real QBO company, note expense account IDs (Chart of Accounts → Edit → URL)
2. In real QBO company, note income item IDs (Sales → Products & Services → Edit → URL)
3. On live server: change `.env` → `QBO_ENVIRONMENT=production`
4. Go to `/admin/settings/quickbooks` → **Disconnect** → **Reconnect** (OAuth will now auth against real company)
5. Update all 6 account/item IDs in the settings page with production values
6. Test push on one vendor, one bill, one invoice before using in anger

---

## Webhook Setup (Phase 5 — when ready)
1. In Intuit Developer Portal → your app → **Webhooks**
2. Add endpoint: `https://fm.rmflooring.ca/webhook/quickbooks`
3. Select entities: `Bill`, `Invoice`
4. Copy the **Verifier Token** Intuit generates
5. On live server: set `.env` → `QBO_WEBHOOK_VERIFIER_TOKEN=<token>`
6. The `QuickBooksWebhookController` + `ProcessQboWebhook` job are already fully wired

---

## Known Issues / Gotchas
- Intuit does NOT accept localhost with a port or IP addresses as redirect URIs — OAuth must be done from the live server
- `.env` values must not accidentally include the key name (e.g. `QBO_REDIRECT_URI=QBO_REDIRECT_URI=...`) — happened twice during setup
- `QBO_ENVIRONMENT=sandbox` must be set correctly — affects which API base URL is used
- Token refresh failure clears tokens silently — admin will need to reconnect from the settings page
- Installer bills (`bill_type = 'installer'`) cannot be pushed yet — `pushBill()` rejects them with a clear message
- Sandbox item/account IDs differ from production — always re-enter IDs after switching environments
