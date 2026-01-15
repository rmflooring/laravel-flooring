# Opportunity Documents Module — Context (Jan 11, 2026)

Project: **laravel-flooring** (RM Flooring / Floor Manager)  
Area: **Staff Pages → Opportunities → Documents**  
Primary view: `resources/views/pages/opportunities/documents/index.blade.php`  
Controller: `app/Http/Controllers/OpportunityDocumentController.php`  
Routes: `routes/web.php` under `Route::prefix('pages')->...`

---

## What we built / current behavior

### 1) Documents index page (UI)
File: `resources/views/pages/opportunities/documents/index.blade.php`

Key features now working:
- **Upload panel** (collapsed by default) with drag/drop + selected file list.
- **Filters**:
  - Type tabs: Show All / Documents / Media
  - Label filter dropdown
  - **Show Archived** checkbox
- **Row UI**:
  - Each row has a checkbox (`.doc-checkbox`) with `data-trashed="1|0"`.
  - Archived rows are highlighted (`bg-yellow-50`) and show “Archived” in description/label columns.
  - Active rows allow inline description editing via AJAX (`PATCH`) on blur.
  - Active rows allow label change via `<select>` that submits `PATCH`.
- **Bulk actions bar** (hidden until any selection):
  - Shows selected count
  - Buttons shown depend on selection type:
    - Active selected → **Archive Selected**
    - Archived selected → **Restore Selected** and **Delete Selected (permanent)**

### 2) Bulk selection rules (Option B)
**Select All** follows the current filter state:
- If **Show Archived is checked** → Select All applies to **archived rows only**
- If **Show Archived is unchecked** → Select All applies to **active rows only**

This is implemented by using `data-trashed` on each checkbox.

### 3) Routes currently in place (pages/opportunities/{opportunity}/documents/*)
In `routes/web.php` under the `pages` prefix, we have:

- `DELETE pages/opportunities/{opportunity}/documents/bulk`
  - name: `pages.opportunities.documents.bulkDestroy`
  - action: `OpportunityDocumentController@bulkDestroy`
- `POST pages/opportunities/{opportunity}/documents/bulk-restore`
  - name: `pages.opportunities.documents.bulkRestore`
  - action: `OpportunityDocumentController@bulkRestore`
- `DELETE pages/opportunities/{opportunity}/documents/bulk-force`
  - name: `pages.opportunities.documents.bulkForceDestroy`
  - action: `OpportunityDocumentController@bulkForceDestroy`
- Single item:
  - `POST .../documents/{document}/restore` → restore archived doc
  - `DELETE .../documents/{document}` → archive (soft delete)
  - `PATCH .../documents/{document}` → update (description/label)
  - `DELETE .../documents/{document}/force` → permanent delete (admin only)

Confirmations observed:
- Bulk force delete request example shows method override: `_method=DELETE` with `POST` resulting in `302` redirect (expected).

---

## Controller logic (current)
File: `app/Http/Controllers/OpportunityDocumentController.php`

Important method confirmed working:
### `bulkForceDestroy(Opportunity $opportunity, Request $request)`
- Reads `ids[]` from request
- Filters to **this opportunity** and **archived only** (`whereNotNull('deleted_at')`)
- Deletes physical file (`Storage::disk($doc->disk)->delete($doc->path)`)
- Calls `$doc->forceDelete()`
- Redirects back with success message: “X archived file(s) permanently deleted.”

Also present:
- `restore()` for single archived document
- `forceDestroy()` for single permanent delete (admin only)
- `assertBelongsToOpportunity()` helper

Note: A prior error occurred (`Cannot redeclare ... bulkForceDestroy`) and was fixed by removing/renaming the duplicate method.

---

## The latest issue we were fixing
### “Clear Selection” label not showing when Show Archived is checked + Select All is used

Cause:
- Label logic compared `count === checkboxes.length` (all checkboxes on the page),
  but Option B selects only **eligible** checkboxes (archived-only or active-only).

Fix (planned / last instruction):
- Update the label logic to compare against **eligible checkboxes only**:
  - If show_archived checked → eligible = archived checkboxes
  - else → eligible = active checkboxes
- Then show **Clear Selection** when `eligibleSelected.length === eligible.length`.

(We provided a replacement snippet for the label block only.)

---

## Files involved (quick list)
- `resources/views/pages/opportunities/documents/index.blade.php`
- `app/Http/Controllers/OpportunityDocumentController.php`
- `routes/web.php`

---

## Next step (1 step at a time)
1) Apply the “eligible-only” label logic for **Select All / Clear Selection** inside `updateBulkUI()` so the label flips correctly under Option B.

After that:
- If desired, also flip the label text to “Clear All” instead of “Clear Selection”.
- Optionally ensure select-all checkbox checked/indeterminate state is also based on eligible-only (not required if current behavior is acceptable).

---

# Context History Updates (append)
## 2026-01-11 — Opportunity Documents bulk actions
- Added bulk archive (**soft delete**) and bulk restore flows with hidden forms submitted by JS.
- Added bulk permanent delete (bulk force destroy) for archived docs (`/documents/bulk-force`) and confirmed it works.
- Implemented Option B selection logic: Select All respects Show Archived state (archived-only vs active-only).
- UI now toggles bulk buttons depending on selected row types (active vs archived).
- Remaining UX issue: Select All label doesn’t switch to “Clear Selection” in archived-only mode due to eligible-vs-total mismatch; fix snippet provided.
