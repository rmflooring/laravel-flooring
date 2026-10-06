# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Changed
- `SHOP_URL` can list several shop sites (comma-separated) so product changes refresh both shop.rmflooring.ca and the new rmflooring.ca shop during the changeover.

### Added
- Confirmation prompt before moving events between calendars.

### Fixed
- Shop API no longer sends `sell_price` for styles whose price is hidden (line and style "show price" both off) — hidden prices were readable in the public API and shop page source.
- Calendar move logic not triggering when the original calendar ID was missing.
- UI dropdown state becoming out of sync when cancelling a move.
- Event relocation flow reliability across controller/routes/JS.

