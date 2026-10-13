# Shop filter details (product attributes)

Product details used as **filters on the rmflooring.ca shop** (and shown in the product page's spec list).

## Data
- `product_lines.shop_attributes` (JSON, nullable) — keys/options defined in `App\Support\ShopAttributes::FIELDS`.
  Which fields apply depends on the product type (`ShopAttributes::family()` maps type names → hardwood / laminate / vinyl / carpet / tile; other types get none).
  Saved through `ShopAttributes::clean()` — only fields valid for the type, blanks dropped.
- `product_styles.color_tone` (string, nullable) — `ShopAttributes::TONES` (light, grey, beige, brown, dark, multi).

| Family | Fields |
|---|---|
| hardwood | construction, species, wear_layer, finish, edge, installation, water |
| laminate | ac_rating, finish, edge, installation, water, pad_attached |
| vinyl (LVP/LVT, Vinyl, VCT) | wear_layer, core, finish, edge, installation, water, pad_attached |
| carpet (Carpets, Carpet Tiles) | fibre, pile |
| tile | material |

**Option keys are a contract with the website** (`App\Support\ProductFacets::FM_LABELS` in laravel-rmflooring.ca). Labels can be reworded freely; don't rename keys without updating the website.

## UI
- Product line create/edit: "Product details (shop filters)" section (`admin/product_lines/_shop_attributes.blade.php`), fields shown/hidden by the selected product type (inline JS).
- Styles: "Colour tone" in the add/edit style modal, bulk-edit bar, and style show page.
- Duplicating a line/style copies the details.

## API (`ShopApiController`)
- `product-lines` list: `attributes`, and `color_tone` in `styles_preview`.
- `product-lines/{id}`: `attributes`, `attribute_labels` (readable "Label" => "Value" for the spec list), `color_tone` per style.
- Saving a line/style already busts the shop caches (`ShopCacheService`).

## Pre-fill
`php artisan shop:prefill-attributes` — guesses EMPTY fields from names, collections, descriptions and colour codes (GRY/BGE/BRN…). Never overwrites staff values. Dry run prints a table; `--apply` saves and busts shop caches. Product name wins over description text. Run once after deploying, then staff review/fill gaps (Kentwood lines don't name species; many colour names have no tone hint).
