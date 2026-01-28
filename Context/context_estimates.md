# context_estimates.md (Floor Manager / Estimates)

Last updated: 2026-01-26 (America/Vancouver)

## Recent Fix --- Create Estimate Not Saving

Problem: Create Estimate kept redirecting back to the create page after
clicking Save.

Root cause: Controller validation requires status but create blade never
submitted it.

Fix now required in mock-create.blade.php:

`<input type="hidden" name="status" value="draft">`{=html}

Silent redirect almost always means validation failure.
