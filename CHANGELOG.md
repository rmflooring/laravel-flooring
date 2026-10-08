# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- **Web Orders** (Sales → Web Orders): paid online orders from rmflooring.ca become Customer → Opportunity (source "Website") → Sale + paid invoice; New → Confirmed → Ready for pickup → Picked up / Refunded, with customer email + SMS at each step, staff alerts, and a daily pickup reminder. See `Context/context_web_orders.md`.
- Vendor **return policy** for the online shop (Vendors → edit), exposed per product in the shop API, plus `api/shop/return-policies` and `api/shop/store-policy`.
- Shop Settings: web-order alert emails/SMS (multiple), pickup hold days, storage-fee and forfeit terms.
- "Online (Stripe)" payment method.

### Changed
- `SHOP_URL` can list several shop sites (comma-separated) so product changes refresh both shop.rmflooring.ca and the new rmflooring.ca shop during the changeover.

### Added
- Confirmation prompt before moving events between calendars.

### Fixed
- Shop API no longer sends `sell_price` for styles whose price is hidden (line and style "show price" both off) — hidden prices were readable in the public API and shop page source.
- Calendar move logic not triggering when the original calendar ID was missing.
- UI dropdown state becoming out of sync when cancelling a move.
- Event relocation flow reliability across controller/routes/JS.

