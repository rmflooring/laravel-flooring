# Opportunity Documents Module — Context Snapshot (Jan 11, 2026)

Project: **laravel-flooring**  
Area: **Staff Pages → Opportunities → Documents**  
Primary view: `resources/views/pages/opportunities/documents/index.blade.php`  
Controller: `app/Http/Controllers/OpportunityDocumentController.php`  
Routes: `routes/web.php` (inside `Route::prefix('pages')->name('pages.')->middleware(['auth','verified'])`)

---

## What we built / current behavior

### Documents index page
Path (example):  
- `GET /pages/opportunities/{opportunity}/documents`

Features implemented:
- Upload panel (collapsed by default) with drag-and-drop, multi-file upload
- Label dropdown (managed labels) and optional description applied to all files in that upload batch
- Filters: type tabs (All / Documents / Media), label filter, Show Archived checkbox
- Table of documents:
  - Checkbox selection for non-archived rows
  - Inline description editing (AJAX PATCH)
  - Label selection dropdown (form submit)
  - Single-file archive (soft delete)
  - Restore (for archived)
  - Force delete (admin only)

### Bulk archive (soft delete)
- Bulk selection UI:
  - `#select-all` checkbox
  - `.doc-checkbox` per row (non-archived only)
  - Bulk action bar `#bulk-actions` shows when 1+ selected
  - `#bulk-delete-btn` triggers JS to populate hidden inputs `ids[]` and submit hidden form

Hidden bulk form:
- `<form id="bulk-archive-form" method="POST" action=".../documents/bulk">`
- includes CSRF token
- includes explicit `_method=DELETE` hidden input
- JS populates `#bulk-ids-container` with hidden `ids[]` inputs

Bulk action result:
- Archives selected docs and redirects to the documents index route with flash message:
  - Success: `"Selected files archived."`
  - Error (no selection): `"No files selected."` (normally guarded by UI since bulk bar only shows when selected)

---

## Key bug fixes we made (important)

### 1) Bulk route was returning 404 / not hitting controller
**Cause:** Route order conflict.  
`DELETE documents/{document}` was defined above `DELETE documents/bulk`, so `/documents/bulk` matched `{document}="bulk"` and failed route-model binding → 404.

**Fix:** In `routes/web.php`, move the bulk route **above** the `{document}` delete route.

Correct order inside:
`Route::prefix('pages')->name('pages.')->group(...)`
and then:
`Route::prefix('opportunities/{opportunity}')->group(...)`:

```php
Route::delete('documents/bulk', [OpportunityDocumentController::class, 'bulkDestroy'])
    ->name('opportunities.documents.bulkDestroy');

Route::delete('documents/{document}', [OpportunityDocumentController::class, 'destroy'])
    ->name('opportunities.documents.destroy');
```

Also fixed route naming confusion caused by `->name('pages.')` group:
- Inside that group, use `->name('opportunities.documents.bulkDestroy')` (NOT `pages.opportunities...`), so final is `pages.opportunities.documents.bulkDestroy`.

### 2) Duplicate flash messages
The index blade was rendering flash success twice.
**Fix:** Keep one “Flash Messages” block (success + error) and remove the duplicate success-only block.

### 3) Restore route + method spoofing issues
We observed browsers sending POST and restore route originally used PATCH + method spoofing.
Rather than rely on method spoofing, we changed restore route to POST.

**Route now:**
```php
Route::post('documents/{document}/restore', [OpportunityDocumentController::class, 'restore'])
    ->name('opportunities.documents.restore');
```

### 4) Restore was still 404 even with correct route
**Cause:** Laravel route-model-binding does NOT resolve soft-deleted models by default.
So `restore(Opportunity $opportunity, OpportunityDocument $document)` 404’d before controller ran.

**Fix:** Update controller restore signature to accept `$document` id and load with `withTrashed()`.

---

## Current routes (expected)

From `php artisan route:list | grep pages.opportunities.documents`:

- `GET    pages/opportunities/{opportunity}/documents` → `pages.opportunities.documents.index`
- `POST   pages/opportunities/{opportunity}/documents` → `pages.opportunities.documents.store`
- `PATCH  pages/opportunities/{opportunity}/documents/{document}` → `pages.opportunities.documents.update`
- `DELETE pages/opportunities/{opportunity}/documents/{document}` → `pages.opportunities.documents.destroy`
- `DELETE pages/opportunities/{opportunity}/documents/bulk` → `pages.opportunities.documents.bulkDestroy`
- `POST   pages/opportunities/{opportunity}/documents/{document}/restore` → `pages.opportunities.documents.restore`
- `DELETE pages/opportunities/{opportunity}/documents/{document}/force` → `pages.opportunities.documents.forceDestroy` (admin middleware)

Note: ordering matters for routes that include `{document}` vs literal segments like `bulk` and `restore`.

---

## Controller snapshot (important parts)

### bulkDestroy()
- Reads `ids[]` from request
- Validates array-ish + non-empty (manual check)
- Soft-deletes all matching docs for the opportunity, excluding already-deleted rows
- Redirects to `pages.opportunities.documents.index` with flash success

### restore() — updated to support soft-deleted rows
```php
public function restore(Opportunity $opportunity, $document)
{
    $doc = OpportunityDocument::withTrashed()
        ->where('opportunity_id', $opportunity->id)
        ->where('id', $document)
        ->firstOrFail();

    $doc->restore();

    return back()->with('success', 'Document restored.');
}
```

---

## Next ideas (not implemented yet)
- Bulk restore (for archived items)
- Admin-only bulk force delete
- Shift-click range selection for checkboxes
- Media thumbnails / preview cards
- Toast notifications instead of inline flash blocks
