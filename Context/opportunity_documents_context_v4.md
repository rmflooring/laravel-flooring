# Opportunity Documents Module — Context (March 28, 2026)

Project: **laravel-flooring** (RM Flooring / Floor Manager)
Area: **Staff Pages → Opportunities → Documents + Media Gallery**
Primary views:
- `resources/views/pages/opportunities/documents/index.blade.php`
- `resources/views/pages/opportunities/media/index.blade.php`

Controller: `app/Http/Controllers/OpportunityDocumentController.php`
Model: `app/Models/OpportunityDocument.php`
Routes: `routes/web.php` under `Route::prefix('pages')->...`

---

## Changes Made This Session

### Fix 1 — `$doc->url` accessor on OpportunityDocument model

Added `getUrlAttribute()` to `app/Models/OpportunityDocument.php`:

```php
use Illuminate\Support\Facades\Storage;

public function getUrlAttribute(): string
{
    return Storage::disk($this->disk)->url($this->path);
}
```

This was an active bug: the media gallery view used `$doc->url` but the model had no such accessor.

---

### Fix 2 — Per-file label + description + progress bar uploads (Documents page)

Replaced the old single-batch `<form>` upload with a JS-driven queue-based upload panel.

**Upload flow:**
1. User drags/drops or picks files → per-file cards appear in `#file-queue`
2. Each card has its own label dropdown + description input
3. When 2+ files are queued, an "Apply to all" bar appears (`#queue-defaults`)
4. On submit: a single `FormData` XHR is sent with all files + `label_ids[]` + `descriptions[]` arrays
5. Progress bar (`#upload-progress-wrap`) updates via `xhr.upload.onprogress`
6. On success: panel resets, page reloads after 1200ms

**Key XHR details:**
- Header: `X-Requested-With: XMLHttpRequest` (ensures `$request->ajax()` = true)
- Header: `Accept: application/json`
- Header: `X-CSRF-TOKEN: <meta csrf token>`
- Success check: `data?.success === true` (not just HTTP 2xx)
- Error display: shows `data.errors` if present

**Controller `store()` changes:**
- Accepts `label_ids[]` and `descriptions[]` per-file arrays
- Falls back to single `label_id` / `description` if per-file not set
- Validation: `label_ids.*` uses `nullable|integer` (NOT `exists:...`) — the exists check caused silent 422 failures with empty strings
- Label ID normalization: `($rawLabelId && is_numeric($rawLabelId)) ? (int) $rawLabelId : null`
- Category detection uses MIME + extension fallback:
  ```php
  $mediaExtensions = ['jpg','jpeg','png','gif','webp','bmp','tiff','tif','heic','heif','avif','svg','mp4','mov','avi','mkv','webm','wmv','m4v','3gp'];
  $isMedia = str_starts_with($mime, 'image/')
      || str_starts_with($mime, 'video/')
      || in_array($ext, $mediaExtensions);
  $category = $isMedia ? 'media' : 'documents';
  ```
- Auto-assigns "Photos" label for media (uses `$isMedia` not MIME check alone)
- Returns `response()->json(['success' => true, 'count' => $count])` when `$request->expectsJson()`

---

### Fix 3 — Image thumbnails + viewer in Documents table

Each row in the documents table now shows:
- **Image files**: actual `<img>` thumbnail (40×40, object-cover, rounded) with a "View" button
- **Video files**: ▶ badge
- **Other files**: file extension badge (e.g. "PDF", "DOC")

Detection logic in Blade `@php` block:
```php
$docMime = $document->mime_type ?? '';
$docExt  = strtolower($document->extension ?? '');
$isImage = str_starts_with($docMime, 'image/') || in_array($docExt, ['jpg','jpeg','png','gif','webp','bmp','tiff','heic','heif','avif']);
$isVideo = str_starts_with($docMime, 'video/') || in_array($docExt, ['mp4','mov','avi','mkv','webm','wmv','m4v','3gp']);
```

Image rows get `data-doc-type="image"` on the `<tr>`.

**Image Viewer Modal (`#docImageViewer`):**
- Full screen overlay, dark backdrop
- Opens via `openDocImageViewer(url, name)` called from the View button
- Closes via `closeDocImageViewer()`, Escape key, or backdrop click
- Functions are global (outside `DOMContentLoaded`) so inline `onclick=` works

---

### Fix 4 — Upload panel in Media Gallery

`resources/views/pages/opportunities/media/index.blade.php` now has a full upload panel identical in behavior to the documents page, but:
- Tailored for images/video (label dropdown pre-suggests Photos label)
- Uses `gQueue` JS array (`g` prefix to avoid naming collision with documents page)
- Uploaded files go to the same `opportunities/{id}/` path on `public` disk and are stored as `media` category
- Flash messages section added (was previously missing)
- "Upload Photos" button in the controls bar toggles the panel

---

### Fix 5 — Search + tab counts (Documents page)

**Controller `index()` changes:**
- Accepts `?search=` query param, searches `original_name` and `description` (LIKE both sides)
- Shared base constraint closure using `tap()` — applies to both the count query and the main query
- Per-category counts via a single GROUP BY query:
  ```php
  $catCounts = $opportunity->documents()
      ->withTrashed()
      ->tap($base)
      ->selectRaw('category, count(*) as cnt')
      ->groupBy('category')
      ->pluck('cnt', 'category');

  $counts = [
      'all'       => $catCounts->sum(),
      'documents' => ($catCounts['documents'] ?? 0) + ($catCounts['generated_document'] ?? 0),
      'media'     => $catCounts['media'] ?? 0,
  ];
  ```
- Passes `$search` and `$counts` to view

**View changes:**
- Tab buttons now show counts: `All (N)` / `Documents (N)` / `Media (N)`
- Counts respect active label/search/archived filters but ignore the type tab, so all tabs always show their true totals
- Added `Search` text input to the filter form, pre-filled with `$search`

---

## Current State of Documents Page

### Filter bar
- Type tabs: All (N) / Documents (N) / Media (N)
- Search input (file name or description)
- Label dropdown filter
- Show Archived checkbox
- Apply button

### Table columns
- Checkbox | File (thumbnail + name) | Type | Size | Label | Description | Uploaded | Actions

### Row behavior
- Archived rows highlighted yellow, show "Archived" badge
- Active rows: inline description edit (AJAX PATCH on blur), inline label change (PATCH on change)
- Image rows: thumbnail + "View" button opens full-screen viewer modal

### Bulk actions
- Archive Selected (active rows)
- Restore Selected (archived rows)
- Delete Selected (Permanent) — archived rows only

### Upload panel
- Drag/drop or click-to-select
- Per-file label + description cards
- Apply-to-all defaults for 2+ files
- Real progress bar via XHR upload events

---

## File Reference

| File | Purpose |
|------|---------|
| `app/Models/OpportunityDocument.php` | Model with `getUrlAttribute()`, `SoftDeletes` |
| `app/Http/Controllers/OpportunityDocumentController.php` | All CRUD, bulk ops, generate, reprint |
| `resources/views/pages/opportunities/documents/index.blade.php` | Documents page |
| `resources/views/pages/opportunities/media/index.blade.php` | Photo/media gallery |

## Routes (relevant)

```
GET    pages/opportunities/{opportunity}/documents           → documents.index
POST   pages/opportunities/{opportunity}/documents           → documents.store
PATCH  pages/opportunities/{opportunity}/documents/{doc}     → documents.update
DELETE pages/opportunities/{opportunity}/documents/{doc}     → documents.destroy
POST   pages/opportunities/{opportunity}/documents/bulk-destroy       → documents.bulkDestroy
POST   pages/opportunities/{opportunity}/documents/bulk-restore       → documents.bulkRestore
POST   pages/opportunities/{opportunity}/documents/bulk-force-destroy → documents.bulkForceDestroy
POST   pages/opportunities/{opportunity}/documents/generate  → documents.generate
GET    pages/opportunities/{opportunity}/documents/{doc}/reprint → documents.reprint
GET    pages/opportunities/{opportunity}/media               → media.index
```
