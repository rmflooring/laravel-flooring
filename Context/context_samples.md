# Sample Tracking — Context

Updated: 2026-04-09 (session 50)

---

## Overview

Showroom sample tracking system. Tracks individual samples and sample sets that can be checked out to customers or staff, returned, and reminded when overdue. Includes mobile scan-to-checkout flow and printable labels.

---

## Data Model

### Individual Samples

**`samples`** — `sample_id` (unique human ID, e.g. `SMP-0001`), `product_style_id` FK, `status` enum (`active`/`checked_out`/`discontinued`/`retired`/`lost`), `quantity`, `location`, `display_price` (nullable override), `notes`, `received_at`, `discontinued_at`; SoftDeletes

**`sample_checkouts`** — shared table for both individual samples and sets:
- `sample_id` nullable FK → individual sample
- `sample_set_id` nullable FK → sample set
- `checkout_type` enum (`customer`/`staff`)
- `customer_id` nullable FK, `customer_name`, `customer_phone`, `customer_email`
- `user_id` nullable FK (staff borrower)
- `destination`, `qty_checked_out` (individual samples only), `checked_out_by` FK
- `checked_out_at`, `due_back_at`, `returned_at`, `return_notes`
- `reminders_sent`, `last_reminder_at`

**`product_style_photos`** — `product_style_id` FK, `file_path`, `is_primary` (bool), `sort_order`, `uploaded_by` FK
- Up to 3 photos per product style
- Used on sample show page and mobile scan page (NOT on labels)

### Sample Sets

**`sample_sets`** — `set_id` (unique human ID, e.g. `SET-0001`), `product_line_id` FK, `name` (optional override), `status` enum (same as samples), `location`, `notes`; SoftDeletes

**`sample_set_items`** — `sample_set_id` FK, `product_style_id` FK, `display_price` nullable
- Each item is one style in the set; sets contain multiple styles from the same product line

---

## Models

### `app/Models/Sample.php`
- Auto-generates `SMP-0001` IDs in `booted()`
- `STATUSES` / `STATUS_COLORS` constants
- `available_qty` accessor: `qty − active checkout sum`
- `effective_price` accessor: `display_price ?? productStyle->sell_price`
- `overdue` scope
- SoftDeletes

### `app/Models/SampleSet.php`
- Auto-generates `SET-0001` IDs in `booted()`
- `STATUSES` / `STATUS_COLORS` constants (same as Sample)
- `is_available` accessor: `status === 'active'`
- `status_label` accessor
- `overdue` scope (via checkouts)
- Relationships: `productLine()`, `items()`, `checkouts()`, `activeCheckout()`, `creator()`, `updater()`
- SoftDeletes

### `app/Models/SampleSetItem.php`
- Belongs to `SampleSet` and `ProductStyle`

### `app/Models/SampleCheckout.php`
- Shared for both individual samples and sets
- Defaults `checked_out_at = now()` and `due_back_at = today + sample_checkout_days` in `booted()`
- Accessors: `is_returned`, `is_overdue`, `days_overdue`, `subject_label` (returns sample or set ID), `borrower_name`
- Relationships: `sample()`, `sampleSet()`, `customer()`, `user()`, `checkedOutBy()`

### `app/Models/ProductStylePhoto.php`
- `url` accessor via `Storage::disk('public')->url()`
- Belongs to `ProductStyle`

### `ProductStyle` model additions
- `photos()` hasMany `ProductStylePhoto`
- `primaryPhoto()` hasMany with `limit(1)`

---

## Observer

**`app/Observers/ProductStyleObserver.php`**
- Watches `updated`; when `status` changes to `discontinued`, bulk-updates all linked samples (skips those already retired/lost/discontinued)
- Registered in `AppServiceProvider::boot()` via `ProductStyle::observe()`

---

## Controllers

### `app/Http/Controllers/Pages/SampleController.php`
- `index()` — search, status, location, overdue filters; paginated 30; shows both samples AND sets sections
- `create()` / `store()` — Alpine.js typeahead to `searchStyles` AJAX; style preview card; qty/price/location/notes
- `show()` — product details, primary photo, active checkouts + return form, checkout history, label print dropdown
- `edit()` / `update()` — status, qty, price override, location, received_at, notes
- `destroy()` — blocked if active checkouts exist
- `label()` — DomPDF PDF; format via `?format=5371|5388`
- `returnCheckout()` — marks checkout returned, flips sample back to `active` if qty available
- `searchStyles()` — AJAX typeahead for create form

### `app/Http/Controllers/Pages/SampleSetController.php`
- `create()` / `store()` — product line dropdown, then styles-by-line AJAX to populate style checkboxes with display_price fields; pre-fills display_price from `sell_price`
- `show()` — set details, styles table, active checkout + return form, history, label print dropdown, mobile/QR card
- `edit()` / `update()` — status, location, notes, display prices per item
- `destroy()` — blocked if active checkout
- `label()` — DomPDF PDF; format via `?format=5371|5388`
- `checkout()` — desktop checkout (redirects to mobile page for now)
- `returnCheckout()` — marks checkout returned, flips set back to `active`
- `stylesByLine()` — AJAX endpoint; returns product styles for a given product line

### `app/Http/Controllers/Admin/ProductStylePhotoController.php`
- `store()` — validates image (jpg/png/webp, max 5MB), enforces 3-photo limit, stores to `product-style-photos/{style_id}/` on public disk (NAS); auto-sets first upload as primary
- `destroy()` — deletes file; auto-promotes next photo to primary if deleted was primary
- `setPrimary()` — clears all `is_primary`, sets new primary

### `app/Http/Controllers/Mobile/SampleController.php`
- `show(string $sampleId)` — supports both `SMP-xxxx` (individual) and `SET-xxxx` (set); routes to `show` or `show-set` view accordingly
- `checkout(string $sampleId)` — individual checkout form
- `storeCheckout()` — creates `SampleCheckout`, flips sample status to `checked_out` if all qty out
- `checkoutSet(string $setId)` — set checkout form
- `storeCheckoutSet()` — creates `SampleCheckout` (with `sample_set_id`), flips set status to `checked_out`

---

## Routes

### Desktop (pages middleware, gated by permission)

```
pages.samples.index              GET    pages/samples
pages.samples.styles.search      GET    pages/samples/styles/search
pages.samples.create             GET    pages/samples/create
pages.samples.store              POST   pages/samples
pages.samples.show               GET    pages/samples/{sample}
pages.samples.label              GET    pages/samples/{sample}/label
pages.samples.edit               GET    pages/samples/{sample}/edit
pages.samples.update             PUT    pages/samples/{sample}
pages.samples.destroy            DELETE pages/samples/{sample}
pages.samples.checkouts.return   POST   pages/samples/{sample}/checkouts/{checkout}/return

pages.sample-sets.create         GET    pages/sample-sets/create
pages.sample-sets.styles-by-line GET    pages/sample-sets/styles-by-line/{productLine}
pages.sample-sets.store          POST   pages/sample-sets
pages.sample-sets.show           GET    pages/sample-sets/{sampleSet}
pages.sample-sets.label          GET    pages/sample-sets/{sampleSet}/label
pages.sample-sets.edit           GET    pages/sample-sets/{sampleSet}/edit
pages.sample-sets.update         PUT    pages/sample-sets/{sampleSet}
pages.sample-sets.destroy        DELETE pages/sample-sets/{sampleSet}
pages.sample-sets.checkout       POST   pages/sample-sets/{sampleSet}/checkout
pages.sample-sets.checkouts.return POST pages/sample-sets/{sampleSet}/checkouts/{checkout}/return
```

### Mobile (auth + verified, no installer role)

```
mobile.samples.show              GET   m/sample/{sampleId}       — handles both SMP-xxxx and SET-xxxx
mobile.samples.checkout          GET   m/sample/{sampleId}/checkout
mobile.samples.checkout.store    POST  m/sample/{sampleId}/checkout
mobile.sample-sets.checkout      GET   m/sample/{setId}/checkout-set
mobile.sample-sets.checkout.store POST m/sample/{setId}/checkout-set
```

### Admin (product style photos, gated `edit product styles`)

```
admin.product_styles.photos.store   POST   admin/product-lines/{line}/product-styles/{style}/photos
admin.product_styles.photos.destroy DELETE admin/product-lines/{line}/product-styles/{style}/photos/{photo}
admin.product_styles.photos.primary POST   admin/product-lines/{line}/product-styles/{style}/photos/{photo}/primary
```

---

## Permissions

| Permission | Roles |
|---|---|
| `view samples` | admin, coordinator, sales, reception, estimator |
| `create samples`, `edit samples` | admin, coordinator |
| `delete samples` | admin |
| `manage sample checkouts` | admin, coordinator, sales, reception |

---

## Views

### Desktop pages (`resources/views/pages/samples/`)
- `index.blade.php` — filter bar (search, status, location, overdue toggle); two sections: individual samples table + sample sets table
- `create.blade.php` — Alpine.js typeahead to `searchStyles`; style preview card; qty/price/location/notes
- `show.blade.php` — primary photo, product details, active checkouts + return form, checkout history, label print dropdown (5371/5388), QR/mobile link
- `edit.blade.php` — status dropdown, qty, display_price override, location, received_at, notes

### Desktop pages (`resources/views/pages/sample-sets/`)
- `create.blade.php` — product line dropdown → AJAX loads styles; display_price per style pre-filled from sell_price
- `show.blade.php` — set details card, styles table (name/SKU/colour/price), active checkout + return, checkout history, label print dropdown (5371/5388), mobile/QR card, record info, delete button
- `edit.blade.php` — status, location, notes, display prices per item

### Mobile (`resources/views/mobile/samples/`)
- `show.blade.php` — individual: primary photo, identity/pricing/availability chips, Check Out button
- `show-set.blade.php` — set: product line info, styles list, Check Out button (or "Currently Checked Out" if not available)
- `checkout.blade.php` — individual: Customer/Staff toggle, customer dropdown with auto-fill, staff dropdown, qty + due date
- `checkout-set.blade.php` — set: same Customer/Staff toggle pattern, no qty field (sets are all-or-nothing)

### PDFs (`resources/views/pdf/`)
- `sample-label.blade.php` — individual sample label; two layouts in one file:
  - **5371** (3.5"×2"): horizontal — logo + product name + price left, QR right
  - **5388** (3"×5"): vertical — logo + name + QR top row, divider, meta details, price, sample ID footer
- `sample-set-label.blade.php` — set label; same two layouts:
  - **5371**: table layout (no flexbox) — content left 72%, QR right 28%; price range "From $X.XX"
  - **5388**: top row logo+name+QR, divider, meta, divider, styles table with per-item prices, set ID footer

### Emails
- `resources/views/emails/samples/overdue-reminder.blade.php`

---

## Label Printing

- DomPDF custom paper sizes: `5371 = [0,0,252,144]` pts, `5388 = [0,0,216,360]` pts
- QR code generated by `simplesoftwareio/simple-qrcode` as base64 SVG; points to `mobile.samples.show` URL using the human ID (`SMP-xxxx` or `SET-xxxx`) — stable even if DB record changes
- Format selected at print time via dropdown on show page
- **Important**: DomPDF does not support CSS flexbox reliably — use `<table>` layout for multi-column arrangements in label PDFs
- Logo loaded as base64 data URI from NAS storage

### Label format considerations
- **5371** works well for individual samples (business card size); set labels show "From $X.XX" price range
- **5388** works well for both — enough height to list all styles in a set
- **Avery 5168** (3.5"×5", 4-up on letter sheet) — discussed but not yet implemented. Correct approach would be a full letter-size (8.5"×11") PDF with up to 4 labels positioned at exact Avery grid coordinates, allowing batch printing of 1–4 samples/sets per sheet. Requires multi-select UI on the index page.

---

## Overdue Reminders

**Command:** `app/Console/Commands/SendSampleReminders.php` (`samples:send-reminders`)
- Queries overdue **customer** checkouts only (`due_back_at < today`, `returned_at null`)
- First reminder: `reminders_sent = 0`; follow-up: `last_reminder_at <= now() − sample_reminder_days`
- Handles both individual samples and sets (checks `sampleSet` relationship)
- Sends email via `GraphMailService::send()` (Track 1 shared mailbox)
- Sends SMS via `SmsService::send()`
- Increments `reminders_sent`, stamps `last_reminder_at`
- **Scheduler:** daily at 09:00 Vancouver (`routes/console.php`)

---

## Admin Settings (`app_settings` keys)

| Key | Default | Purpose |
|---|---|---|
| `sample_email_reminders_enabled` | `1` | Toggle overdue email reminders |
| `sample_sms_reminders_enabled` | `1` | Toggle overdue SMS reminders |
| `sample_reminder_days` | `3` | Days between follow-up reminders |
| `sample_checkout_days` | `5` | Default due-back period when checking out |

- Mail settings page (`/admin/settings/mail`): "Sample Overdue Reminders" toggle
- SMS settings page (`/admin/settings/sms`): SMS reminder toggle + re-remind interval input

---

## Sidebar

- "Samples" link (tag icon) between Sales and Customers in sidebar, gated `@can('view samples')`
- Links to `pages.samples.index` which shows both individual samples and sets

---

## Open Items

- End-to-end test: create → label → mobile scan → checkout → return → reminder command
- **Avery 5168 batch label printing** — full letter-size PDF with 1–4 labels per sheet; needs multi-select UI on index and a dedicated controller method/route; decision pending on printer type in use
- Sample index page: consider adding column sort, or separate tabs for samples vs sets
