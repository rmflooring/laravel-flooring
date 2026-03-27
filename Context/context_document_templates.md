# Document Templates Module Context — RM Flooring / Floor Manager

Updated: 2026-03-27 (session 36)

---

## Overview

Admin-managed printable document templates with merge tags. Staff generate PDFs from an opportunity's Documents tab. Generated PDFs are saved to the opportunity's document library.

Use cases: front file labels, flooring selection sign-offs, work authorization forms, estimator checklists, and any other printable job document.

---

## Data Model

### `document_templates` table
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `name` | string | Template name shown to staff |
| `description` | text (nullable) | Short hint shown in generate modal |
| `body` | text | HTML body with `{{merge_tags}}` |
| `needs_sale` | boolean | When true, staff must select a Sale at generate time (enables `{{flooring_items_table}}`) |
| `is_active` | boolean | Inactive templates hidden from staff |
| `sort_order` | integer | Ordering in dropdowns and admin list |
| `created_by` / `updated_by` | nullable FK → users | Audit |
| `timestamps` | | |

### `opportunity_documents.template_id` (nullable FK → document_templates)
Added to link generated documents back to their source template. `nullOnDelete()` — record kept even if template deleted.

---

## Models & Services

### `app/Models/DocumentTemplate.php`
- `fillable`: name, description, body, needs_sale, is_active, sort_order, created_by, updated_by
- Booted hooks: set `created_by` / `updated_by` on create/update
- Relationships: `creator()`, `updater()` (BelongsTo User), `generatedDocuments()` (HasMany OpportunityDocument)
- **`OPPORTUNITY_TAGS`** constant — 18 tags always available:
  `{{customer_name}}`, `{{job_name}}`, `{{job_no}}`, `{{job_site_name}}`, `{{job_site_address}}`, `{{job_site_phone}}`, `{{job_site_email}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{pm_phone}}`, `{{pm_email}}`, `{{insurance_company}}`, `{{adjuster}}`, `{{policy_number}}`, `{{claim_number}}`, `{{dol}}`, `{{date}}`, `{{generated_by}}`
- Insurance tags resolve from `jobSiteCustomer` columns (`insurance_company`, `adjuster`, `policy_number`, `claim_number`, `dol`). `{{dol}}` formatted as `M j, Y` (e.g. "Jan 15, 2024"); empty string if not set.
- **`SALE_TAGS`** constant — 2 tags available when `needs_sale = true`:
  `{{sale_number}}`, `{{flooring_items_table}}`

### `app/Services/DocumentTemplateService.php`
- `render(DocumentTemplate $template, Opportunity $opportunity, ?Sale $sale = null): string`
  - Loads `parentCustomer`, `jobSiteCustomer`, `projectManager` on opportunity
  - Builds vars array for all opportunity tags
  - If `needs_sale` + sale provided: adds `sale_number` and `flooring_items_table`
  - `str_replace` loop replaces all `{{tags}}`
- `buildFlooringTable(Sale $sale): string` (private)
  - Loads `sale->rooms->items`, filters to `type === 'material'`
  - HTML table: blue header row (#1d4ed8), columns: Room (with rowspan), Product (type — manufacturer — style — color/item#), Qty, Unit

---

## Admin CRUD

### Controller
`app/Http/Controllers/Admin/DocumentTemplateController.php`
- `index()` — ordered by `sort_order` then `name`
- `store()` / `update()` — validates: name (required), description (nullable), body (required), needs_sale (boolean), is_active (boolean), sort_order (integer)
- `destroy()` — blocks deletion if template has been used to generate documents (`usageCount > 0`); shows error redirect

### Routes
Registered under the `admin` middleware group:
```
GET    admin/document-templates               admin.document-templates.index
GET    admin/document-templates/create        admin.document-templates.create
POST   admin/document-templates              admin.document-templates.store
GET    admin/document-templates/{id}/edit     admin.document-templates.edit
PUT    admin/document-templates/{id}          admin.document-templates.update
DELETE admin/document-templates/{id}          admin.document-templates.destroy
```
All gated by `role_or_permission:admin`.

### Views
`resources/views/admin/document-templates/`
- `index.blade.php` — table with sort_order, name, description, "Sale required" badge, Active/Inactive badge, usage count, Edit / Delete (blocked when in use → "In use")
- `create.blade.php` — wraps `_form` partial
- `edit.blade.php` — wraps `_form` partial; amber warning if usageCount > 0; **Preview button** (client-side, see below)
- `_form.blade.php` — fields: name, description, sort_order, needs_sale checkbox, is_active checkbox, body textarea (font-mono, rows=16); Tag reference panel with click-to-copy tags

### Preview button (edit page only)
- Client-side JavaScript; no server round-trip
- Reads current textarea content, replaces all known merge tags with sample placeholder values
- Renders in a simulated PDF layout (blue header, body, footer)
- Unknown `{{tags}}` highlighted in amber
- Script uses `@verbatim`/`@endverbatim` to prevent Blade parsing `{{tags}}` in JS; date passed via a small pre-verbatim `<script>` block

### Admin Settings page
Link to "Document Templates" added to `resources/views/admin/settings.blade.php` (after SMS Templates).

### Admin Sidebar
"Document Templates" link added to `resources/views/layouts/sidebar.blade.php` (above Document Labels).

---

## Staff — Generate Document

### `OpportunityDocumentController` changes
- `index()`: loads `$activeTemplates` and `$opportunitySales`; type filter updated to `whereIn('category', ['documents', 'generated_document'])`
- `generate(Request $request, Opportunity $opportunity)`:
  - Validates `template_id` (required), `sale_id` (nullable; required when `needs_sale = true`)
  - Calls `DocumentTemplateService::render()`
  - DomPDF renders `pdf.document-template` on letter/portrait
  - Stored at `opportunities/{id}/doc_{slug}_{timestamp}.pdf` on `public` disk
  - Creates `OpportunityDocument` with `category='generated_document'`, `template_id` set
  - Redirects with `success` flash + `generated_doc_id` session
- `reprint(Opportunity $opportunity, OpportunityDocument $document)`:
  - Asserts document belongs to opportunity
  - Aborts 404 if `category !== 'generated_document'`
  - Streams stored PDF inline

### Routes (nested under `pages/opportunities/{opportunity}/`)
```
POST   documents/generate              pages.opportunities.documents.generate
GET    documents/{document}/reprint    pages.opportunities.documents.reprint
```

### Generate Modal (documents index blade)
Alpine.js `x-data` component in `resources/views/pages/opportunities/documents/index.blade.php`:
- "Create Document" emerald button in action bar (only shown when `$activeTemplates->isNotEmpty()`)
- Template dropdown — populates `needsSale` flag from `$activeTemplates` JSON
- Sale dropdown appears (`x-show="needsSale"`) when selected template requires a sale
- If no sales exist for the opportunity, an amber warning message shows instead of the dropdown
- POSTs to `pages.opportunities.documents.generate`

### Documents table
- Category badge: "Generated" (green) for `generated_document` category
- Actions column: generated docs show "Print" link (→ `reprint` route, opens in new tab) instead of View

---

## PDF Template

`resources/views/pdf/document-template.blade.php`
- Font: DejaVu Sans 11px, 32px body padding
- **Header**: branding logo (base64 data URI from `public` disk) or text fallback; right side shows template name + generated date + job_no + sale_number
- **Body**: `{!! $body !!}` (raw HTML — merge tags already resolved by service)
- **Footer**: fixed bottom; left: brand name / phone / email; right: template name
- Uses `Setting::get('branding_*')` for all branding values
- ⚠️ Do NOT put `{{tags}}` anywhere in this file — Blade parses them even inside CSS comments

---

## Seeded Starter Templates

Three templates seeded via migration `2026_03_27_113248_create_document_templates_table.php`:

### 1. Front File Label (sort_order: 1, needs_sale: false)
Layout updated via migration `2026_03_27_115223_update_front_file_label_template_body.php` and then refined via tinker to match the RFM show page layout:
- Blue header (Job # large, customer name below)
- Indigo job name strip
- Two columns: **left** = Parent Customer name + PM box (name, phone, email); **right** = Job Site box (name, address, phone, email)
- Measure Details section: 4 blank fill-in underline fields (Estimator, Flooring Type, Scheduled Date & Time, Completed Date)
- Special Instructions box (amber, empty — for handwriting)
- Notes box (larger, empty — for handwriting)
- Generated date + generated_by footer line

### 2. Flooring Selection Sign-Off (sort_order: 2, needs_sale: true)
- Requires Sale selection
- Shows `{{flooring_items_table}}` — renders all material items by room
- Signature line at bottom

### 3. Work Authorization Form (sort_order: 3, needs_sale: false)
- Customer/job detail table
- Authorization paragraph text
- Two signature lines (customer + RM Flooring rep)

---

## Known Blade Gotchas

Blade parses `{{ }}` everywhere in `.blade.php` files — including inside:
- CSS comments: `/* {{flooring_items_table}} */` → **error**. Fixed: removed tag from CSS comment.
- JavaScript string literals: `'{{customer_name}}'` → **error**. Fixed: wrap script block in `@verbatim`/`@endverbatim`.
- Template hint text: `{{flooring_items_table}}` in form labels → **error**. Fixed: use `@{{flooring_items_table}}`.

Pattern for JS blocks with merge tag keys: declare a small `<script>` block before `@verbatim` for any PHP values needed (e.g. date), then reference the JS variable from within verbatim.

---

## Open Items

- Preview button on the **create** page (currently edit only)
- Highlight newly generated document in the documents list on redirect (session data `generated_doc_id` available but not yet used)
- Additional starter templates as needed (e.g. Estimator Checklist)
