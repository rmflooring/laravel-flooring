# Document Templates Module Context — RM Flooring / Floor Manager

Updated: 2026-04-14 (session 53)

---

## Overview

Admin-managed printable document templates with merge tags. Staff generate documents from an opportunity's Documents tab. The document opens as an editable form page (pre-filled from opportunity/sale data), staff review/edit the fields, save it, then print to PDF from the saved document's show page.

Generated documents are saved to the opportunity's document library (category: `generated_document`).

Use cases: front file labels, flooring selection sign-offs, work authorization forms, estimator checklists, and any other printable job document.

---

## Flow (session 52 — new)

Old flow: modal → instant PDF → stored on disk → "Print" link.

**New flow:**
1. Staff click "Create Document" on the Opportunity Documents tab
2. A dropdown picker appears inline (no modal) — select template, select sale if required
3. Click "Continue →" → navigates to a dedicated **create page**
4. Create page shows all merge tag fields used in that template as labeled inputs, pre-filled from opportunity/sale data
5. Staff edit any fields as needed, then click **Save Document**
6. Redirects to the **document show page** — displays rendered HTML document + Edit Fields + Print/PDF buttons
7. Staff can re-open the edit page at any time and re-save
8. **Print / PDF** button generates PDF on-demand from the saved `rendered_body`

---

## Data Model

### `document_templates` table
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | PK |
| `name` | string | Template name shown to staff |
| `description` | text (nullable) | Short hint |
| `body` | text | HTML body with `{{merge_tags}}` |
| `needs_sale` | boolean | When true, staff must select a Sale |
| `special_flow` | string (nullable) | e.g. `flooring_sign_off` — bypasses normal flow |
| `is_active` | boolean | Inactive templates hidden from staff |
| `sort_order` | integer | Ordering |
| `created_by` / `updated_by` | nullable FK → users | Audit |

### `opportunity_documents` table — added columns (session 52)
| Column | Type | Notes |
|--------|------|-------|
| `sale_id` | nullable FK → sales | Which sale was selected at generation time |
| `document_fields` | JSON (nullable) | Field values saved at last save/update |
| `rendered_body` | longtext (nullable) | Final rendered HTML (merge tags resolved). Present on new-flow docs; null on legacy stored-PDF docs |

### `opportunity_documents.template_id` (nullable FK → document_templates)
Links generated documents back to their source template. `nullOnDelete()` — record kept if template deleted.

---

## Models & Services

### `app/Models/DocumentTemplate.php`
- `fillable`: name, description, body, needs_sale, special_flow, is_active, sort_order, created_by, updated_by
- **`OPPORTUNITY_TAGS`** constant — tags always available (opportunity context):
  `{{customer_name}}`, `{{job_name}}`, `{{job_no}}`, `{{job_site_name}}`, `{{job_site_address}}`, `{{job_site_phone}}`, `{{job_site_email}}`, `{{pm_name}}`, `{{pm_first_name}}`, `{{pm_phone}}`, `{{pm_email}}`, `{{insurance_company}}`, `{{adjuster}}`, `{{policy_number}}`, `{{claim_number}}`, `{{dol}}`, `{{date}}`, `{{generated_by}}`, `{{special_instructions}}`, `{{notes}}`, `{{opportunity_photos_qr}}`, `{{opportunity_qr}}`
- **`SALE_TAGS`** constant — 2 tags when `needs_sale = true`:
  `{{sale_number}}`, `{{flooring_items_table}}`

### `app/Models/OpportunityDocument.php`
- Added `sale_id`, `document_fields`, `rendered_body` to `$fillable`
- `document_fields` cast to `array`

### `app/Services/DocumentTemplateService.php`
- **`TAG_LABELS`** constant — human-readable labels for each tag key (used on create/edit form)
- `render(template, opportunity, ?sale)` — delegates to `getDefaultFields()` + `renderFromFields()`
- **`getDefaultFields(template, opportunity, ?sale)`** — resolves all tag values from opportunity/sale data; returns only tags whose `{{tag}}` appears in the template body. **QR code tags are intentionally excluded** — they are auto-generated at render time and must never appear as editable form fields.
- **`renderFromFields(template, fields, ?sale, ?opportunity)`** — renders body from caller-supplied field values. Three tags are always re-built fresh from live data regardless of stored fields:
  - `{{flooring_items_table}}` — rebuilt from sale rooms/items
  - `{{opportunity_photos_qr}}` — SVG QR code generated from `mobile.opportunity.photos` route
  - `{{opportunity_qr}}` — SVG QR code generated from `mobile.opportunity.show` route
- `buildFlooringTable(Sale)` — private; builds HTML table of material items grouped by room

---

## Controller

`app/Http/Controllers/OpportunityDocumentController.php`

### New methods (session 52)
| Method | Purpose |
|--------|---------|
| `createGenerated(Request, Opportunity, DocumentTemplate)` | Shows editable form pre-filled from opportunity/sale. Handles `special_flow` redirect. |
| `storeGenerated(Request, Opportunity)` | Saves field values + renders body → creates `OpportunityDocument` with `rendered_body` |
| `showGenerated(Opportunity, OpportunityDocument)` | Shows saved document (rendered HTML) with Edit + Print/PDF buttons |
| `editGenerated(Opportunity, OpportunityDocument)` | Re-opens editable form pre-filled from saved `document_fields` |
| `updateGenerated(Request, Opportunity, OpportunityDocument)` | Re-renders body from updated fields, saves |
| `downloadPdf(Opportunity, OpportunityDocument)` | Generates PDF on-demand from `rendered_body` using `pdf.document-template` |

### Updated methods
- `reprint()` — if document has `rendered_body`, redirects to `show-generated`; otherwise streams legacy stored PDF

### Private helper
- `sanitizeFields(array)` — strips any field keys not in the allowed tag lists before saving

---

## Routes

All nested under `pages/opportunities/{opportunity}/`:

```
GET  documents/create/{template}           pages.opportunities.documents.create-generated
POST documents/generated                   pages.opportunities.documents.store-generated
GET  documents/{document}/view             pages.opportunities.documents.show-generated
GET  documents/{document}/edit-fields      pages.opportunities.documents.edit-generated
PUT  documents/{document}/generated        pages.opportunities.documents.update-generated
GET  documents/{document}/pdf              pages.opportunities.documents.pdf
GET  documents/{document}/reprint          pages.opportunities.documents.reprint  (legacy)
```

---

## Views

### `resources/views/pages/opportunities/documents/create-generated.blade.php`
- Shared for create and edit (edit passes `$document`, create passes `null`)
- Document Name field (defaults to template name, editable — stored as `original_name`)
- Sale selector shown when `needs_sale = true`
- Labeled inputs for each tag that appears in the template body (from `getDefaultFields()`)
- `job_site_address`, `special_instructions`, and `notes` render as textareas; all others as text inputs
- `{{flooring_items_table}}`, `{{opportunity_qr}}`, and `{{opportunity_photos_qr}}` are never shown as editable fields — always regenerated at render time
- On **edit**, stored `document_fields` are merged over fresh `getDefaultFields()` defaults so newly-added tags (e.g. fields added to a template after a document was first generated) still appear in the form

### `resources/views/pages/opportunities/documents/show-generated.blade.php`
- Breadcrumb: Opportunity → Documents → document name
- Toolbar: Edit Fields button, Print/PDF button (opens new tab), ← Documents link
- Rendered HTML displayed in a styled document preview div
- Template name + created/updated timestamps shown

### `resources/views/pages/opportunities/documents/index.blade.php` (updated)
- **Removed**: generate modal
- **Added**: inline dropdown picker (Alpine.js) — "Create Document" button reveals template + sale selectors; "Continue →" navigates to `create-generated` route
- Generated docs with `rendered_body`: show **"Open"** link → `show-generated`
- Generated docs without `rendered_body` (legacy): show **"Print"** link → `reprint`

---

## PDF Template

`resources/views/pdf/document-template.blade.php` — unchanged
- Receives pre-rendered `$body` (merge tags already resolved)
- Font: DejaVu Sans 11px, letter/portrait
- Header: branding logo + template name + generated date + job_no
- Footer: brand name / phone / email

---

## Legacy Compatibility

Old generated docs (stored as PDF files on disk) still work via `reprint`. The `reprint` method detects whether `rendered_body` is set:
- Has `rendered_body` → redirect to `show-generated` page
- No `rendered_body` → stream stored PDF file (old behaviour)

---

## Seeded Starter Templates

1. **Front File Label** (sort_order: 1, needs_sale: false) — blue header, job name strip, two-column layout, fill-in measure detail lines, editable **Special Instructions** (`{{special_instructions}}`) and **Notes** (`{{notes}}`) sections
2. **Flooring Selection Sign-Off** (sort_order: 2, special_flow: `flooring_sign_off`) — redirects to dedicated sign-off wizard
3. **Work Authorization Form** (sort_order: 3, needs_sale: false) — customer table, authorization paragraph, signature lines

---

## Admin CRUD

`app/Http/Controllers/Admin/DocumentTemplateController.php`
- Routes: `admin.document-templates.*` (index, create, store, edit, update, destroy)
- Gated: `role_or_permission:admin`
- Destroy blocked if template has generated documents (`usageCount > 0`)
- Edit page has client-side **Preview button** (replaces tags with sample values, no server round-trip)
- Views: `resources/views/admin/document-templates/` (index, create, edit, _form partials)

---

## Known Blade Gotchas

- Blade parses `{{ }}` everywhere — use `@{{tag}}` in text, `@verbatim` in `<script>` blocks
- Do NOT put `{{tags}}` in CSS comments in Blade files
- `flooring_items_table`, `opportunity_qr`, and `opportunity_photos_qr` are always re-rendered from live data — never stored as editable fields. QR codes are generated as `<img src="data:image/svg+xml;base64,...">` inline.
- `opportunity_documents.path` is nullable — generated documents have no physical file path

---

## Open Items

- Preview button on the **create** page for admin template editor (currently edit only)
- Highlight newly saved document in the documents list on redirect
- Additional starter templates (e.g. Estimator Checklist)
