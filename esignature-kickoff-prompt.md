# Claude Code Kickoff Prompt — E-Signature Module

---

We are adding an **E-Signature Module** to Floor Manager (FM), a Laravel 12 internal operations platform.

Load the context file first:

```
~/esignature-module-context.md
```

Read it fully before writing any code. Then implement the module in this order, pausing for approval before starting each phase:

---

## Phase 1 — Foundation

1. Install `setasign/fpdi` via composer
2. Create and run the migration for `document_signing_requests`
3. Create the `DocumentSigningRequest` model with fillable fields, casts (audit_log as array, expires_at as datetime), and helper methods:
   - `isPending()`, `isSigned()`, `isExpired()`, `isCancelled()`
   - `isViewable()` — pending and not past expires_at
4. Create the `DocumentSigningRequestService` class with methods stubbed (to be filled in later phases)

**Stop and show me the migration and model for approval before continuing.**

---

## Phase 2 — PDF Persistence & Request Creation

1. Add a `storePendingPdf(DocumentSigningRequest $request)` method to the service that:
   - Re-generates the appropriate PDF using the existing DomPDF Blade template and document data
   - Saves to `storage/app/signed-documents/pending/{uuid}.pdf`
2. Add a `createSigningRequest(string $documentType, int $documentId, string $clientName, string $clientEmail)` method that:
   - Generates the UUID
   - Calls storePendingPdf
   - Creates the DB record
   - Sends `SignatureRequestMail`
3. Create all four Mailables with basic Blade templates (content can be refined later)
4. Add the "Request Signature" button and confirmation modal to the Flooring Selection and Work Auth detail views

**Stop and show me the service, mailables, and view changes for approval before continuing.**

---

## Phase 3 — Public Signing Flow

1. Create public routes in a separate route file or group (no auth middleware)
2. Create `SigningController` with:
   - `show(string $uuid)` — validates, stamps viewed_at, renders signing page
   - `document(string $uuid)` — streams the pending PDF inline
   - `sign(string $uuid, Request $request)` — handles signature submission
3. Create the public signing Blade layout (no FM nav, RM Flooring branding, clean)
4. Create the signing page view with:
   - Inline PDF viewer (iframe pointing to `/sign/{uuid}/document`)
   - Tabs: Draw (signature_pad.js via CDN) / Type (cursive font input)
   - Agreement checkbox
   - Submit button
5. Implement the FPDI signature stamping logic in the service
6. Create thank-you, already-signed, and expired error page views
7. Add rate limiting to the POST route (5/min per IP)

**Stop and show me the controller, service stamping logic, and signing page view for approval before continuing.**

---

## Phase 4 — Scheduled Jobs

1. Create `ExpireSigningRequests` artisan command — marks overdue pending requests as expired
2. Create `SendSigningReminders` artisan command — sends reminders at 3, 7, and 9 days per the spec
3. Register both in the scheduler (daily frequency)

**Stop and show me both commands for approval before continuing.**

---

## Phase 5 — Admin UI

1. Create `Admin\SigningRequestController` with index, cancel, resend, and download actions
2. Create the admin list view following FM's Flowbite conventions:
   - Stats strip (Pending / Signed / Expired / Cancelled counts)
   - Filterable table with all columns and action buttons per row
   - Search by client name or email
3. Add "Documents > Signing Requests" to the FM nav
4. Gate all admin actions behind `manage signing requests` Spatie permission — seed this permission if a seeder exists

**Stop and show me the controller and view for approval before continuing.**

---

## Notes

- Follow all FM conventions: Flowbite UI, Blade + inline JS, Spatie permissions, additive-only
- Use `php8.4 artisan` for all artisan commands
- Timezone for all display timestamps: `America/Vancouver`
- Admin notification email: `richard@rmflooring.ca`
- Do not modify any existing PDF generation logic — only add the persistence layer on top of it
