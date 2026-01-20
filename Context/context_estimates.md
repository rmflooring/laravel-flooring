# Estimate Autocomplete Context (Product Type)

## Goal
Implement a real autocomplete UX for the **Product Type** field in the Create Estimate flow.

## What is working

### API
- Endpoint exists and works:
  - `GET /admin/estimates/api/product-types`
  - Returns JSON list of product types

### JavaScript
File: `estimate_mock.js`

We implemented a custom dropdown-based autocomplete for Product Type with:

- Opens on mouse click (not only Tab)
- Opens on focus
- Filters on typing
- Closes on selection
- Keyboard navigation:
  - ArrowDown / ArrowUp
  - Enter to select
  - Escape to close

### UX Improvements
- Dropdown positioning fixed (no clipping)
- Dropdown width matches input
- Styled like a real menu
- Hover states added
- Focus states added

### Unit Autofill Logic
We split **label vs code** behavior:

- Dropdown shows: `unit.label` (e.g., "Square Feet")
- Unit input field receives: `unit.code` (e.g., "SF")

This is done by:
- Storing both label and code
- Displaying label
- Writing code into the input

### Structure Used
Each option button contains:
- `data-pt-name`
- `data-pt-unit` (code)

Unit input is populated from:
```js
btn.dataset.ptUnit
```

---

## Known Next Steps (Not Done Yet)

Potential future improvements:

- Click outside to close dropdown
- Apply same autocomplete logic to:
  - Manufacturer
  - Style
  - Color / Item #
  - Labor
  - Freight
- Autofill price
- Prevent duplicate listeners on dynamic rows
- Add loading indicators
- Add debounce
- Add "No results" state

---

## Important UX Decisions

- Dropdown opens on mouse click (not just focus)
- Keyboard-first UX supported
- Human-readable labels shown
- Machine-friendly codes stored

---

## Files Involved

- `estimate_mock.js`
- Create Estimate Blade
- `/admin/estimates/api/product-types`

---

## Status
Product Type autocomplete is complete and working as intended.

---

When resuming, next logical step:
➡ Extend this system to Manufacturer.
