# Invoice Module — Context

## Overview
Invoices are created from an approved sale by selecting a subset of sale items. Supports deposit invoicing, progress billing, and multiple invoices per sale. Payments are tracked per invoice with full status lifecycle.

---

## Invoice Numbering
Format: `YYYY-NNN` — sequential per calendar year, zero-padded to 3 digits (e.g. `2026-001`, `2026-002`). Resets each year. Generated in `Invoice::booted()` using a retry loop like Sale numbering.

---

## Data Model

### `payment_terms`
| Column | Type | Notes |
|--------|------|-------|
| name | string | e.g. "Net 30", "Due on Receipt" |
| days | smallint nullable | e.g. 30 for Net 30 |
| description | text nullable | Optional |
| is_active | boolean | default true |

### `invoices`
| Column | Type | Notes |
|--------|------|-------|
| invoice_number | string unique | e.g. 2026-001 |
| sale_id | FK → sales | cascade delete |
| payment_term_id | FK → payment_terms nullable | null on delete |
| status | enum | draft, sent, paid, overdue, partially_paid, voided |
| due_date | date nullable | |
| customer_po_number | string nullable | |
| notes | text nullable | Printed on invoice |
| subtotal | decimal 10,2 | Sum of line totals |
| tax_amount | decimal 10,2 | Sum of item tax amounts |
| grand_total | decimal 10,2 | subtotal + tax_amount |
| amount_paid | decimal 10,2 | Sum of payments |
| sent_at | timestamp nullable | Set when emailed |
| voided_at | timestamp nullable | |
| void_reason | string nullable | |
| deleted_at | timestamp nullable | Soft deletes |

### `invoice_rooms`
| Column | Type | Notes |
|--------|------|-------|
| invoice_id | FK → invoices | cascade delete |
| sale_room_id | bigint nullable | Reference only — no FK constraint |
| name | string | Copied from sale room at creation |
| sort_order | smallint | |

### `invoice_items`
| Column | Type | Notes |
|--------|------|-------|
| invoice_id | FK → invoices | cascade delete |
| invoice_room_id | FK → invoice_rooms | cascade delete |
| sale_item_id | bigint nullable | Reference only — no FK constraint |
| item_type | enum | material, labour, freight |
| label | string | Human-readable — built from sale item fields at creation |
| quantity | decimal 10,2 | Invoiced qty (may be < sale item qty) |
| unit | string nullable | |
| sell_price | decimal 10,2 | Snapshotted from sale item |
| line_total | decimal 10,2 | quantity × sell_price |
| tax_rate | decimal 8,4 | Snapshotted from sale.tax_rate_percent (as percentage, e.g. 2.456) |
| tax_amount | decimal 10,2 | line_total × (tax_rate / 100) |
| tax_group_id | bigint nullable | Snapshot reference |
| sort_order | smallint | |

### `invoice_payments`
| Column | Type | Notes |
|--------|------|-------|
| invoice_id | FK → invoices | cascade delete |
| amount | decimal 10,2 | |
| payment_date | date | |
| payment_method | enum | cash, cheque, e-transfer, visa, mastercard, other, credit_card (legacy) |
| reference_number | string nullable | Cheque #, transaction ID, etc. |
| notes | text nullable | |
| recorded_by | FK → users nullable | null on delete |

---

## Models
- `App\Models\Invoice` — `booted()` generates invoice_number; `$guarded = ['id', 'invoice_number']`; accessors `balance_due`, `is_overpaid`
- `App\Models\InvoiceRoom` — accessors `subtotal`, `tax_total`
- `App\Models\InvoiceItem`
- `App\Models\InvoicePayment` — `PAYMENT_METHODS` constant (cash, cheque, e-transfer, visa, mastercard, other); accessor `method_label` (fallback uses `ucwords(str_replace('_',' ',...))` so legacy `credit_card` records display as "Credit Card")
- `App\Models\PaymentTerm`
- `App\Models\Sale` — added `invoices()` hasMany + `activeInvoices()` hasMany (excludes voided)

---

## InvoiceService (`app/Services/InvoiceService.php`)

### `createFromSale(Sale $sale, array $invoiceData, array $selectedItems): Invoice`
- `$selectedItems` = `[sale_item_id => qty_to_invoice, ...]` (only items with qty > 0)
- Iterates sale rooms in sort_order; skips rooms with no selected items
- Copies room name (`sale_room.room_name`), creates `InvoiceRoom`
- Builds label from sale item fields (product_type/manufacturer/style/color for material; labour_type; freight_description)
- Tax = `sale.tax_rate_percent / 100` applied per item
- After saving items, updates invoice subtotal/tax_amount/grand_total
- Calls `syncSaleInvoiceStatus()` after creation

### `recalculateTotals(Invoice $invoice)`
Recalculates invoice totals from items and amount_paid from payments. Derives payment status (paid/partially_paid/overdue/sent). Does NOT override voided/draft.

### `recalculateAfterPayment(Invoice $invoice)`
Called after adding or removing a payment. Updates `amount_paid`, derives new status, syncs sale.

### `syncSaleInvoiceStatus(Sale $sale)`
- Sums `grand_total` of all non-voided invoices → `invoiced_total`
- Compares against `locked_grand_total` → `revised_contract_total` → `grand_total`
- Sets `is_fully_invoiced = true` when `invoiced_total >= sale_total`
- Updates sale status: `partially_invoiced` / `invoiced` / reverts to `approved` if no active invoices
- Only updates status when sale is in an invoiceable state

### `getInvoicedQtyBySaleItem(Sale $sale): array`
Returns `[sale_item_id => float_qty]` of already-invoiced quantities across all non-voided invoices for the sale. Used on the create form to show remaining available qty.

---

## InvoiceController (`app/Http/Controllers/Pages/InvoiceController.php`)

| Method | Route | Notes |
|--------|-------|-------|
| `create` | GET sales/{sale}/invoices/create | Blocks if sale not in invoiceable status |
| `store` | POST sales/{sale}/invoices | Validates qty ≤ remaining; calls service |
| `show` | GET sales/{sale}/invoices/{invoice} | Loads rooms.items, payments, paymentTerm |
| `edit` | GET sales/{sale}/invoices/{invoice}/edit | Header fields only; blocks if voided |
| `update` | PUT sales/{sale}/invoices/{invoice} | Header fields + status; handles void transition |
| `void` | POST sales/{sale}/invoices/{invoice}/void | Sets voided_at, void_reason, syncs sale |
| `pdf` | GET sales/{sale}/invoices/{invoice}/pdf | DomPDF stream |
| `sendEmail` | POST sales/{sale}/invoices/{invoice}/send-email | Track 2 → Track 1 fallback; PDF attached; marks sent |
| `storePayment` | POST sales/{sale}/invoices/{invoice}/payments | Creates InvoicePayment, recalculates |
| `destroyPayment` | DELETE …/payments/{payment} | Removes payment, recalculates |

---

## Tax Calculation
Tax is stored at the sale level as `tax_rate_percent` (float percentage, e.g. `2.456`). At invoice creation, this is snapshotted onto each `invoice_item.tax_rate`. To apply: `tax_amount = line_total × (tax_rate / 100)`.

---

## Partial Invoicing Logic
- Each sale item can be partially invoiced across multiple invoices
- `getInvoicedQtyBySaleItem()` sums qty from all non-voided invoices
- Create form shows: Total Qty | Already Invoiced | Invoice Qty input (max = remaining)
- Fully-invoiced items show a green checkmark and are greyed out
- Partially-invoiced items show "Partially invoiced" label and remaining available qty
- Validation in `store()` rejects qty > remaining with item-specific error

---

## Sale Status Integration
Sale statuses relevant to invoicing:
- `approved` / `scheduled` / `in_progress` / `completed` — invoiceable
- `partially_invoiced` — some items invoiced, not all
- `invoiced` — fully invoiced (`invoiced_total >= sale_total`)

`Sale.invoiced_total` and `Sale.is_fully_invoiced` are updated by `syncSaleInvoiceStatus()` after every invoice event.

---

## Permissions
| Permission | Admin | Coordinator |
|-----------|-------|-------------|
| view invoices | ✓ | ✓ |
| create invoices | ✓ | ✓ |
| edit invoices | ✓ | ✓ |
| delete invoices | ✓ | ✓ |

Manageable via Admin → Roles.

---

## Payment Terms Admin
- CRUD at `/admin/payment-terms` → `admin.payment-terms.*`
- Controller: `Admin\PaymentTermController`
- Views: `resources/views/admin/payment-terms/`
- Linked from: Admin sidebar + Admin Settings page
- Designed to be reused on Vendors and Subcontractors later
- Delete blocked if term has associated invoices

---

## Views

### Create (`pages/invoices/create.blade.php`)
- Two-column layout: Invoice Details panel (left) + Room/item selector (right)
- Checkboxes toggle qty between 0 and max remaining
- Qty inputs enforce `max="{{ $remaining }}"` and show "of X avail."
- Live JS recalculates subtotal/tax/total as quantities change
- "Select all available" / "Deselect all" convenience buttons
- Tax rate displayed as label on summary panel

### Show (`pages/invoices/show.blade.php`)
- Status badge with colour coding
- Financial summary (total / paid / balance due) top-right
- Line items table grouped by room with room subtotals
- Payments table with Remove button per payment
- Add Payment modal — pre-fills balance due, defaults to today's date + e-transfer
- Void modal with optional reason field
- Send Email modal — pre-fills homeowner email, subject, body; PDF auto-attached
- Print/PDF button (new tab)
- Overdue date shown in red when past due and not paid/voided

### Edit (`pages/invoices/edit.blade.php`)
- Edits header fields only: status, payment terms, due date, customer PO#, notes
- Does NOT allow re-selecting line items (by design — create a new invoice for additional items)
- Blocked if invoice is voided

### PDF (`resources/views/pdf/invoice.blade.php`)
- Loads branding from `app_settings` directly (same pattern as change-order PDF)
- Sections: branding header, Bill To, Invoice Details, line items by room, totals, payment history (if any), notes, remittance stub (if balance > 0)
- Status label printed in matching colour
- Remittance stub only shown when balance due > 0 and not voided

---

## Sale Show Page Integration
Invoices section added below Change Orders:
- Shows all invoices with status badge, due date (red if overdue), total, paid, balance
- "Fully Invoiced" or "Partially Invoiced" badge in section header
- "New Invoice" button gated by `can('create invoices')` and sale status
- Tfoot row shows total invoiced and total paid across non-voided invoices
- Eager-loaded via `SaleController::show()` → `$sale->load(['invoices'])`

---

## Open Items
- Invoices index page (list all invoices across all sales, with filters)
- Sidebar nav link for invoices
- Scheduled job / artisan command to auto-flip `sent` → `overdue` when `due_date` passes
- Edit page: allow re-selecting/adjusting line items
- Invoice index page link in sale status summary card
