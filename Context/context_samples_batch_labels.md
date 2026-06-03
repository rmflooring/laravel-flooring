# Sample Batch Labels + Public QR Scan Page — Context

Created: 2026-06-03

---

## Overview

Two features planned together:

1. **Avery 5163/8163 batch label printing** — select any mix of individual samples and sample sets from the index page, configure copies per item and a price toggle, generate a letter-size PDF with 10 labels per page at exact Avery 5163 grid coordinates.

2. **Public QR scan page** — all QR codes on labels point to a new public, no-auth URL (`/scan/{sampleId}`) showing product info and pricing. A "Staff — Check In / Out" button handles routing: logged-in staff with permission go straight to the checkout form; guests are redirected to mobile login and land on the checkout form after authenticating.

---

## Status

**Planned, not yet implemented.**

---

## Avery 5163/8163 Specs (in DomPDF points at 72pt/inch)

| Property | Value |
|---|---|
| Page size | `'letter'` (8.5"×11") |
| `@page` CSS margin | 36pt top/bottom, 11pt left/right |
| Label size | 288pt × 144pt (4"×2") |
| Column gutter | ~13.5pt (0.1875") |
| Grid | 2 columns × 5 rows = 10 labels per page |
| Vertical gap | 0pt |

DomPDF pattern (matches existing label methods):
```php
Pdf::loadView('pdf.batch-labels-5163', compact(...))->setPaper('letter')->stream('filename.pdf');
```

---

## Files to Create

| File | Purpose |
|---|---|
| `app/Http/Controllers/PublicSampleController.php` | Public scan page — no auth. Handles `SMP-xxxx` and `SET-xxxx` IDs |
| `resources/views/public/sample-scan.blade.php` | Public scan view (uses `x-mobile-layout`). Product info + pricing + staff button |
| `resources/views/pdf/batch-labels-5163.blade.php` | Batch PDF template — letter size, 10-up grid, table layout (no flexbox) |
| `resources/views/pages/samples/batch-label-form.blade.php` | Config form — selected items, qty inputs (1–20 per item), show-price toggle, label count |

---

## Files to Modify

| File | Change |
|---|---|
| `routes/web.php` | Add public `/scan/{sampleId}` route near line 86 (with other public routes). Add 2 batch routes (`GET/POST samples/batch`) before `samples/{sample}` wildcard (~line 1602) |
| `app/Http/Controllers/Pages/SampleController.php` | Add `batchLabelForm()` + `batchLabel()`. Update QR URL in existing `label()` to use `route('scan.sample', ...)` |
| `app/Http/Controllers/Pages/SampleSetController.php` | Update QR URL in existing `label()` to use `route('scan.sample', ...)` |
| `resources/views/pages/samples/index.blade.php` | Add checkboxes to both tables, Alpine.js selection state, sticky action bar |

---

## Route Additions

```php
// Public — near line 86 in web.php (no auth)
Route::get('/scan/{sampleId}', [\App\Http\Controllers\PublicSampleController::class, 'show'])
    ->name('scan.sample');

// Batch print — must be BEFORE samples/{sample} wildcard
Route::get('samples/batch', [SampleController::class, 'batchLabelForm'])
    ->middleware('role_or_permission:admin|view samples')
    ->name('samples.batch-label.form');

Route::post('samples/batch', [SampleController::class, 'batchLabel'])
    ->middleware('role_or_permission:admin|view samples')
    ->name('samples.batch-label');
```

---

## QR Code Change

All label QR codes (existing single-label PDFs + new batch) must point to the new public route:

```php
// Old (requires auth):
route('mobile.samples.show', $sample->sample_id)

// New (public):
route('scan.sample', $sample->sample_id)   // works for both SMP-xxxx and SET-xxxx
```

QR generation pattern (same as existing):
```php
base64_encode(QrCode::format('svg')->size(100)->generate(route('scan.sample', $id)))
```

---

## Public Scan Page — Key Design

- Uses `x-mobile-layout` (same as existing mobile show views)
- No authentication required — visible to anyone who scans the QR
- Shows: photo (samples only), product/set name, manufacturer, line, colour, SKU, price, availability, styles list (sets only), location

**Staff button logic:**
```blade
@auth
    @can('manage sample checkouts')
        <a href="{{ $checkoutUrl }}">Check Out / Return</a>   {{-- direct --}}
    @endcan
@else
    <a href="{{ $checkoutUrl }}">Staff — Check In / Out</a>   {{-- auth.mobile redirects to login then back --}}
@endauth
```

`$checkoutUrl`:
- Individual: `route('mobile.samples.checkout', $sampleId)`
- Set: `route('mobile.sample-sets.checkout', $setId)`

When a guest taps the button, Laravel's `auth.mobile` middleware stores the intended URL and redirects to login. After login, they land directly on the checkout form — **no changes needed to checkout routes**.

---

## Batch Label PDF Layout

DomPDF requires table layout (no flexbox):

```html
<table width="590" cellpadding="0" cellspacing="0">
  @foreach ($rows as $row)   {{-- $rows = array_chunk($labels, 2) --}}
  <tr>
    <td width="288" height="144" style="padding:6pt; vertical-align:top; overflow:hidden;">
      @if (isset($row[0])) {{-- label cell content --}} @endif
    </td>
    <td width="14"></td>  {{-- gutter --}}
    <td width="288" height="144" style="padding:6pt; vertical-align:top; overflow:hidden;">
      @if (isset($row[1])) {{-- label cell content (or empty for odd counts) --}} @endif
    </td>
  </tr>
  @endforeach
</table>
```

Each label cell: logo + info left (72%), QR right (28%) — mirrors existing 5371 layout. QR size ~60×60pt.

Variables passed to PDF view:
```php
compact('rows', 'logoDataUri', 'companyName', 'showPrice')
// Each $item in $rows: ['type' => 'sample'|'set', 'model' => Sample|SampleSet, 'qrSvg' => string]
```

---

## Batch Controller — `batchLabel()` Logic

```php
// Expand labels by qty (generate QR once per record, reuse per copy)
foreach ($sampleIds as $id) {
    $qrSvg = base64_encode(QrCode::format('svg')->size(100)->generate(route('scan.sample', $sample->sample_id)));
    for ($i = 0; $i < ($qtyMap["s_{$id}"] ?? 1); $i++) {
        $labels[] = ['type' => 'sample', 'model' => $sample, 'qrSvg' => $qrSvg];
    }
}
// Same pattern for sets, using qty key "set_{$id}"
$rows = array_chunk($labels, 2);
```

Form qty key convention: `qty[s_{sample->id}]` for samples, `qty[set_{set->id}]` for sets.

---

## Index Page — Alpine Selection State

```js
{
    selectedSamples: [],   // DB integer IDs
    selectedSets: [],
    showPrice: true,
    get totalSelected() { return this.selectedSamples.length + this.selectedSets.length; },
    buildUrl() {
        const p = new URLSearchParams();
        this.selectedSamples.forEach(id => p.append('samples[]', id));
        this.selectedSets.forEach(id => p.append('sets[]', id));
        if (this.showPrice) p.append('show_price', '1');
        return '{{ route("pages.samples.batch-label.form") }}?' + p;
    }
}
```

Sticky action bar: fixed bottom, slides up with `x-show="totalSelected > 0"` + `x-transition`. Contains: selected count, show-prices checkbox, "Print Labels" link (`a :href="buildUrl()"`), "Clear" button.

"Select All" per section: checkbox in each table's `<th>` header row.

---

## Reusable Patterns from Existing Code

- Branding logo loading: `SampleController::label()` lines ~236–241
- QR generation: same method, line ~232–233
- DomPDF `setPaper()`: `[0,0,width,height]` for custom sizes, or named string (`'letter'`) for standard sizes
- Mobile layout: `x-mobile-layout` component (same as `show.blade.php`, `show-set.blade.php`)
- Public route pattern: see e-signature routes at `routes/web.php` line 85–90

---

## Verification Steps

1. `composer run dev`
2. Single-label QR: print a 5371 label → scan QR → lands on `/scan/SMP-xxxx` (no auth), shows product info + price + Staff button
3. Guest staff button: logged out → tap "Staff — Check In / Out" → redirected to mobile login → after login, lands on checkout form
4. Batch flow: index → check samples + sets → action bar → "Print Labels" → form → adjust qty → "Generate PDF" → PDF opens in new tab with 5163 grid, QR on every label
5. >10 items → multi-page PDF
6. Odd count (e.g. 7 labels) → last row has one label + empty second cell, no crash
