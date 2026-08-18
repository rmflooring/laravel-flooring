# Floor Manager AI Agent System — Build Context
Updated: 2026-08-18 (attach_document's category typo made every agent-attached doc invisible in the FM UI)

---

## Overview

A hybrid AI agent system: staff trigger tasks either by forwarding email to `agent@rmflooring.ca` or (future) via an in-app chat UI, both sharing one tool library, one task queue, and one clarification flow, driven by Claude's tool-use API (not autonomous shell/DB access — Claude can only call the predefined tools below).

Full original spec (requirements, design principles, full v1 tool library, security/guardrails): `fm-agent-context.md` in repo root. This doc tracks what's actually been **built**, file-by-file, plus decisions and deviations made along the way. Rollout order per spec: photo attach → scope-of-work doc upload → find/update opportunity → create opportunity → communication logging/status auto-reply.

---

## Status

| Module | Status | Date |
|---|---|---|
| Module 1 — email intake + `attach_images` | Done | 2026-07-10 |
| Module 2 — `attach_document` | Done | 2026-07-13 |
| Module 3 — `find_opportunity` / `update_opportunity` | Done | 2026-07-16 |
| Module 4 — `create_opportunity` | Done | 2026-07-16 |
| Module 5 — `log_communication` / `check_status` | Done | 2026-08-17 |
| Task Dashboard UI (`/admin/agent/tasks`) | Done | 2026-08-17 |
| Admin Settings UI (`/admin/settings/agent`) | Done | 2026-08-17 |
| Inbound Mail Intake (`agent:check-inbound-mail`) | Done | 2026-08-17 |
| Chat UI | Not started | — |
| `undo_last_action` (dashboard-only) | Done | 2026-08-17 |

---

## Core Tables

| Table | Purpose |
|---|---|
| `agent_tasks` | One row per email/chat-triggered task. `status` has a 6th practical value, `queued`, not in the original spec's 5-value enum — set at webhook-create time, before the job classifies the task into `pending_clarification`/`pending_confirmation`/`completed`/`failed`/`ignored`. |
| `agent_messages` | The clarification/audit thread for a task (`sender` = `agent`/`user`). Every tool call and its outcome is logged here for auditability. |
| `agent_settings` | Single-row config: `admin_notification_email`, `allowed_sender_domains`/`allowed_sender_addresses` (JSON), `rate_limit_per_sender_per_hour`. Access via `AgentSetting::current()` (`firstOrCreate`). |
| `agent_notification_settings` | Per-`task_type` BCC toggle (default off). |
| `agent_notifications` | Audit log of every requester-reply / BCC email actually sent. |

Migrations: `database/migrations/2026_07_10_00000{1..5}_create_agent_*_table.php`. Models: `app/Models/Agent{Task,Message,Setting,NotificationSetting,Notification}.php`.

---

## Tool Library (built so far)

| Tool | Service | What it does |
|---|---|---|
| `attach_images` | `app/Services/Agent/AttachImagesService.php` | Attaches email image attachments to the resolved opportunity's photo gallery. `category` (before/after/moisture/damage/completion/other) is a PHP allowlist (`AttachImagesService::CATEGORIES`), stored in `OpportunityDocument.label_text` — **no new DB column**. `OpportunityDocument.category = 'media'`. |
| `attach_document` | `app/Services/Agent/AttachDocumentService.php` | Attaches a single email document (PDF/Word/scanned image) to the opportunity. `document_type` (scope_of_work/contract/insurance_certificate/permit/inspection_report/other) is a PHP allowlist (`AttachDocumentService::DOCUMENT_TYPES`), also stored in `label_text`. `OpportunityDocument.category = 'documents'` (plural — see the 2026-08-18 bug note below; this table originally said `'document'`, which was itself the bug). |
| `find_opportunity` | `app/Services/Agent/FindOpportunityService.php` | Fuzzy-matches `client_name`/`address`/`claim_number` (whichever are given) against opportunities via their `jobSiteCustomer`/`parentCustomer`. Not terminal — Claude keeps reasoning afterward. See scoring details below. |
| `update_opportunity` | `app/Services/Agent/UpdateOpportunityService.php` | Writes only `requires_rfm` (boolean) and/or `project_manager_id` (resolved from a freetext name — never accepted as a raw ID). See scope decision below. |
| `create_opportunity` | `app/Services/Agent/CreateOpportunityService.php` | Creates a new `Customer` (+ optionally links an existing parent) and `Opportunity` for a job not yet in FM. Duplicate-check gated. See notes below. |
| `log_communication` | `app/Services/Agent/LogCommunicationService.php` | Writes a categorized `OpportunityNote` summarizing an email/correspondence thread. Terminal. See Module 5 notes below. |
| `check_status` | `app/Services/Agent/CheckStatusService.php` | Read-only opportunity status summary (job status, RFM, latest estimate/sale status, PM). Terminal — its formatted summary becomes the auto-reply. See Module 5 notes below. |
| `request_clarification` | inline in `ProcessAgentTask::dispatchTool()` | Writes a question to `agent_messages`, sets `status = pending_clarification`. |
| `no_actionable_intent` | inline in `ProcessAgentTask::dispatchTool()` | Sets `status = ignored` (spam/newsletter/unrelated forward). |
| `undo_last_action` | `app/Services/Agent/UndoLastActionService.php` | Reverses a completed task via its `AgentTask.undo_data` snapshot. **Dashboard-only** — not a Claude tool, not in `AgentToolRegistry` (explicit decision, see notes below). |

Both attach tools share validation/decoding logic via `app/Services/Agent/Concerns/ValidatesAgentAttachments.php` (extracted in Module 2): `assertOpportunityMatches()`, `decodeAttachmentBytes()`, `storageFolderFor()`. Both reuse the existing document-storage stack unchanged — `App\Models\OpportunityDocument`, `App\Services\DocumentStorageService::disk()`, `Opportunity::storageFolderName()` — same conventions as the manual mobile photo/document upload flows. 20MB size limit on both.

`update_opportunity` also uses `ValidatesAgentAttachments::assertOpportunityMatches()` — the same "Claude cannot pick its own opportunity_id" invariant applies here too.

All v1 tools from `fm-agent-context.md` are now built.

### Module 3 notes: `find_opportunity` scoring

No fuzzy-matching library existed in this codebase (checked — only plain SQL `LIKE` searches elsewhere) or was added; `find_opportunity` scores with PHP's built-in `similar_text()`. Per candidate: exact case-insensitive `claim_number` match = 1.0 (weight 0.5), best `similar_text()` % across job-site/parent customer `name`/`company_name` (weight 0.3), `similar_text()` % on `address`+`city` (weight 0.2) — weights renormalize to whichever criteria were actually supplied. Candidates below 0.35 are dropped; top 5 kept. Auto-resolves `$task->opportunity_id` only when the top score is ≥ 0.85 **and** either the sole candidate or leads the runner-up by ≥ 0.2 — otherwise Claude must fall back to `request_clarification` (per spec: "zero or multiple ambiguous matches → triggers request_clarification"). Every search + its candidate scores is logged to `agent_messages` regardless of outcome, for audit.

The pre-Module-3 stand-in in `ProcessAgentTask::resolveOpportunity()` (deterministic `\b\d{2}-\d{4}\b` job-number regex, run before Claude sees the email) is **kept**, not replaced — it's a cheap fast path for the common case where the job number is right there in the email; `find_opportunity` covers everything else.

### Module 3 notes: `update_opportunity` scope decision

Deliberately narrow for v1 (confirmed with the business owner) — only `requires_rfm` and `project_manager_id`. Explicitly **excluded**, and why:
- `status` — a gated lifecycle transition with its own business rules (`OpportunityController::update` blocks setting `Lost` while active, non-cancelled sales exist). Too risky for email-triggered automation in v1; stays human-only.
- `job_no` — the job identifier itself; changing it via automation is rare and risky, and it's exactly what the pre-Module-3 regex fast path keys off of.
- `status_reason` — the controller force-nulls this unless `status` is already `Lost`/`Closed`, which this tool can't set — so it'd rarely apply and isn't worth the complexity yet.
- `sales_person_1`/`sales_person_2` — despite being documented elsewhere as "Employee ID", `OpportunityController::update` validates them as plain strings with **no** `exists:employees,id` check — they're not real FKs today. Not safe to populate from agent-inferred text without adding validation the manual form itself doesn't have.
- `parent_customer_id`/`job_site_customer_id` — structural customer linkage, human-only.

`project_manager_id` resolution requires an **exact** (case-insensitive) name match — no fuzzy guessing for an FK write — scoped to `ProjectManager::where('customer_id', ...)` against the opportunity's `parent_customer_id` first, falling back to `job_site_customer_id` (mirrors `OpportunityController::projectManagersForCustomer()`'s scoping). Zero or multiple matches → validation error, which becomes the `request_clarification` prompt rather than a silent guess.

**Incidental fix**: `agent_tasks.task_type` was declared on the table since Module 1 but never actually set anywhere in the code (true for Modules 1–2 too, not just this one). Now set in `ProcessAgentTask::handle()` from whichever tool concluded the task (`attach_images`, `attach_document`, `update_opportunity`, `no_actionable_intent`, or `other` for `request_clarification`/text-only/iteration-exhausted outcomes) — makes the column actually usable by the future task-dashboard UI.

### Module 4 notes: `create_opportunity`

The riskiest tool in v1 (per spec) — it's the only one that creates new records rather than acting on an existing opportunity. Key discovery: `OpportunityController::store()` (`app/Http/Controllers/Pages/OpportunityController.php:236-260`) always requires an *existing* `parent_customer_id` — the human flow creates a new parent customer via a separate AJAX endpoint (`storeParentCustomer`) before ever submitting the opportunity form. `create_opportunity` has to do both steps itself.

**Duplicate check** — reuses `FindOpportunityService`, refactored to expose a new public `searchCandidates(?clientName, ?address, ?claimNumber): array` (the scored/filtered/sorted/capped candidate list, now including each candidate's `created_at`) that both `execute()` and `CreateOpportunityService` call, instead of reimplementing the tokenized-LIKE-plus-`similar_text()` matching a second time. Any candidate scoring ≥ 0.6 (lower than `find_opportunity`'s 0.85 auto-resolve threshold — this is a warning gate, not an auto-resolve) whose opportunity was created within the last 60 days **blocks creation entirely** and becomes the `request_clarification` prompt. No override path in v1 — matches "never silently duplicate."

**Customer creation scope**:
- No `parent_customer_name` given → creates one new standalone `Customer` (`parent_id = null`) used as both `parent_customer_id` and `job_site_customer_id` — the common case (individual homeowner / direct insurance referral).
- `parent_customer_name` given → must resolve to an **existing** standalone customer (`parent_id IS NULL`) by exact case-insensitive name/company_name match, same invariant as `update_opportunity`'s `project_manager_name` (`UpdateOpportunityService::resolveProjectManagerId()`) — zero or multiple matches → validation error → `request_clarification`. A brand-new job-site `Customer` is created under that resolved parent. **Never** auto-creates a new parent/company record from an unmatched name, to avoid spawning duplicate company records from a misspelled or misremembered name — only the job-site/individual customer is freely created new.
- Both paths set `created_by`/`updated_by` explicitly to `$task->requester_user_id` — `Customer::booted()`'s `creating` hook sets `created_by = auth()->id()` *unconditionally* with no null-guard (unlike `Opportunity`'s hook), so in this queue context it would otherwise silently write `null`.

**Opportunity creation**: `status = 'New'` (DB default and a canonical status value), `requires_rfm` defaults to `true` server-side if the tool input omits it (a brand-new opportunity almost always needs a site measure next). Sets `$task->opportunity_id` on success. **`sales_person_1` defaults to `'2'`** (`CreateOpportunityService::DEFAULT_SALES_PERSON`) — Marco Bruni's employee id — confirmed with Richard (2026-08-18), **agent-created opportunities only**, not an app-wide default (manual opportunity creation via the FM UI is unaffected). Not exposed as a tool parameter — Claude never chooses this, it's a fixed default the service applies unconditionally, consistent with `sales_person_1`/`sales_person_2` never being agent-writable at all (see Module 3 notes above on why — no `exists:employees,id` validation, not safe to populate from agent-inferred text). `'2'` (not "Marco") matches the real existing convention in this column — checked actual dev-DB data, it's populated with numeric employee ids, not names, so this looks identical to a human-entered value rather than standing out as agent-generated.

**Guardrail in code, not just prompt**: throws immediately if `$task->opportunity_id` is already set when `create_opportunity` is called — an opportunity was already resolved (via the job-number regex fast path or `find_opportunity`), so creating a new one would be wrong; the system prompt tells Claude this too, but the check is enforced in the service regardless.

**Incomplete intake**: per the spec, "flags" rather than blocks. Missing `address`/`claim_number`/`insurance_company` (the spec also mentions "loss type," but no such field exists anywhere in this schema — confirmed via grep, dropped) doesn't stop creation; the gap is noted in the terminal summary/logged message for staff follow-up.

`dol` (date of loss) is parsed with `Carbon::parse()` and re-validated before insert — the human-facing `CustomerController`/`JobSiteCustomerController` both enforce Laravel's `date` validation rule on this field; without an equivalent check here, a malformed date from Claude would surface as a raw DB error instead of a graceful `request_clarification`.

**`job_no` bug found and fixed during live testing (2026-08-18)**: the first live `create_opportunity` test — a real forwarded restoration-company referral ("FW: Job #00705807 Wang & Ryu - 716 Roderick Ave") — correctly created the opportunity/customer but left `job_no` blank, even though the referrer's own job number was right there in the subject line. Root cause: `create_opportunity`'s tool schema never had a `job_no` parameter, so there was no way for Claude to pass one through even if it recognized one. Initially assumed this was fine ("staff assign it manually, like the UI's optional freetext field") — but checking real `opportunities.job_no` data in the dev DB proved that wrong: values like `00695330`, `00643231`, `00688278-JT`, `00682286-AB` show that **using the referring company's own reference number directly as `job_no` is already a normal, common convention here**, not something staff invent themselves. Fixed: `create_opportunity` now accepts an optional `job_no` (`CreateOpportunityService::execute()`'s new last parameter, `AgentToolRegistry`'s new `job_no` property, `ProcessAgentTask`'s dispatch case updated) — Claude is told to pass through any job/work-order/reference number mentioned by the referrer (subject line or body), in whatever format, and to omit it (leaving it for staff) if none is mentioned. No format validation — matches the column itself (`job_no` is `nullable`, explicitly commented "user-typed, NOT unique" in its migration, no enforced pattern). Verified via `Http::fake()` that a supplied `job_no` is now correctly persisted, and retroactively corrected the live-tested Opportunity #110 to `00705807`.

**Two more gaps found from the same live test, both fixed (2026-08-18):**

1. **Parent customer was never linked**, even though "First OnSite Restoration" already existed as a customer record — Claude simply never attempted `parent_customer_name` at all. Root cause: the tool description only hinted at "e.g. a property manager" as an example of a parent company, never telling Claude to consider *who sent/forwarded the referral* (sender org/domain, email signature) as a parent-customer candidate. Fixed by rewriting `parent_customer_name`'s description in `AgentToolRegistry` to explicitly call this out.

   **Follow-up, same day**: this fix alone wasn't enough — a *second* live referral (also from First OnSite) still failed to link, and it also blocked PM assignment as a side effect (PM lookup is scoped to the opportunity's customer, so if that's wrong, PM resolution has nothing to search). Claude correctly tried `parent_customer_name: "First Onsite"` both times, but `resolveExistingParent()` was still exact-match only, and "First Onsite" ≠ "First OnSite Restoration" as a literal string. Fixed properly this time: `resolveExistingParent()` now does the same two-pass exact-then-prefix pattern already proven for PM matching (`UpdateOpportunityService::findProjectManager()`) — exact match first, and only if nothing exact is found, a prefix match in either direction (input is a shortened form of the name on file, or vice versa). Still not arbitrary fuzzy-guessing — a prefix match is either unambiguous or it throws (multiple candidates), never a near-miss guess — and still never auto-creates a parent from an unmatched name. Verified against real dev-DB data: "First Onsite", "First OnSite Restoration", and "first onsite" all correctly resolve to the same customer. New test: `test_links_to_existing_parent_via_shortened_name`.

   **Second follow-up, same day**: a *third* live referral (still First OnSite) spelled it yet another way — `"FirstOnSite"`, no space at all — which the space-sensitive prefix match from the previous fix correctly didn't match (by design, not a bug), so it safely fell back to a standalone customer rather than guessing. Three real emails, three different spellings/spacings of the same company, is a real pattern, not noise — worth fixing properly rather than patching per-variant. `resolveExistingParent()`'s prefix pass now strips spaces from both sides before comparing (`REPLACE(LOWER(name), ' ', '')` in SQL, `str_replace(' ', '', ...)` on the input) — "First Onsite", "FirstOnSite", "firstonsite", and "First OnSite Restoration" all normalize to a matching prefix relationship now. Still exact-match first, still only a prefix match (never looser fuzzy matching), still throws rather than guesses on ambiguity — confirmed no false positives against an unrelated company name. New test: `test_links_to_existing_parent_regardless_of_spacing`.

   **Also confirmed working correctly in this same live test**: when `parent_customer_name` genuinely doesn't resolve, the completion email now clearly explains why to the requester (Claude's own summary text, not something hardcoded: *"the referral came from FirstOnSite but no existing customer by that name was found, so a standalone customer record was created — staff can link it to the correct parent later if needed"*) — exactly the transparency the spec wants, working as designed even in the failure case, before this particular spacing variant was fixed.

2. **`find_opportunity` couldn't search by job number at all** — surfaced by a follow-up scenario: an approved sender emailing fresh, out of the blue, "please update job # 00705807, PM as Andrew," expecting the agent to find that job and act, no prior thread needed. This is now the intended general pattern (any allowlisted email, any time, referencing a job by any identifier) rather than requiring email-reply-threading to an original task — deliberately chosen over building conversation-threading (Graph `conversationId` correlation, a completed-task resume pathway, etc.) because it reuses the existing find→act tool-calling pattern as-is. Added `job_no` as a new scored criterion on `FindOpportunityService` (exact case-insensitive match, weighted like `claim_number`, not fuzzy — job numbers are unambiguous identifiers when given) — `execute()`/`searchCandidates()`/`gatherCandidates()`/`scoreCandidates()`/`logSearch()` all take it as a new parameter (default `null`, so `CreateOpportunityService`'s existing duplicate-check call site keeps working, though it was also updated to pass `job_no` through — a matching job number on a recent opportunity is itself a strong duplicate signal). `AgentToolRegistry`'s `find_opportunity` schema and `ProcessAgentTask::SYSTEM_PROMPT` updated to match.

3. **Bonus, same live test**: `update_opportunity`'s project-manager resolution was exact-full-name-match only, which would reject a bare first name like "Andrew" typed in a correction email even though it's genuinely unambiguous ("Andrew Bou-Antoun" is the only "Andrew" under that customer). `UpdateOpportunityService::resolveProjectManagerId()` now tries exact match first (unchanged behavior), then falls back to a "starts with" match (`LOWER(name) LIKE 'andrew %'`) only if nothing exact was found anywhere — same ambiguity/not-found error handling as before, just tolerating how people actually type names in a quick email. New helper: `findProjectManager()`.

All three verified against the real dev DB: Opportunity #110 (the live-tested "Wang & Ryu" job) was retroactively corrected to link under First OnSite Restoration, then a simulated fresh "please update job # 00705807 with pm as Andrew" email was run through the *real* `ProcessAgentTask` pipeline end-to-end (find by job_no → auto-resolve → update PM by partial name) and correctly assigned "Andrew Bou-Antoun." New tests: `test_find_opportunity_resolves_by_job_no`, `test_update_opportunity_resolves_project_manager_by_partial_name` in `AgentFindUpdateOpportunityTest.php`.

**Completion email now invites follow-up corrections (2026-08-18)** — `ProcessAgentTask::notifyRequester()`'s `completed`-status body explicitly tells the requester to send a *new* email mentioning the job number/client name if anything needs fixing, rather than just stating what was done. This is what makes the job_no-search addition above actually discoverable — it's the intended "correction loop," deliberately chosen over building email-reply-threading. Two details worth knowing if this area changes again: (1) `GraphMailService::send()` hardcodes `Reply-To` to a global `Setting::get('mail_reply_to', 'noreply@rmflooring.ca')` shared by every email this app sends — not something to override per-caller lightly, so the wording explicitly says a literal Reply won't reach the agent and to address a fresh email to `agent@rmflooring.ca` instead. (2) The notification email's **From** address is now explicitly set to `AGENT_INBOUND_MAILBOX` (via `send()`'s `$fromAddress` param) rather than the app's default `mail_from_address` — verified this actually works (Graph app-only `Mail.Send` is tenant-wide, confirmed via a real `Http::fake()`-inspected payload hitting `/users/agent@rmflooring.ca/sendMail`), so the completion email visibly comes from the same address the correction should be sent to.

### Module 5 notes: `log_communication` / `check_status`

**`log_communication`** writes to `OpportunityNote` (`app/Models/OpportunityNote.php`) — previously a plain `{opportunity_id, user_id, body}` table with no category concept, always human-authored via the opportunity show page or a couple of controllers (`OpportunityNoteController`, `LeadManagementController`, `SmsPortalController`). Rather than encode `category`/`from` as a text prefix on `body` (the Module 1/2 precedent of reusing existing columns), added a small migration — `database/migrations/2026_08_17_190000_add_category_and_source_to_opportunity_notes_table.php` — adding nullable `category` and `source` columns. **Explicit product decision** (confirmed with Richard): real columns now, specifically to avoid a painful retrofit once the FM UI wants to filter the activity log by category — a text-prefix approach would mean parsing strings instead of querying a column at that point. `category` is a PHP allowlist (`LogCommunicationService::CATEGORIES` = `client_communication`, `insurance_communication`, `vendor_communication`, `internal_note`, `other` — also confirmed with Richard; `adjuster_communication` was considered and deliberately deferred as an easy future split off `insurance_communication` if it turns out to be needed). `source` is `'agent'` for agent-written notes; existing/manual notes leave it `null` (no retrofit of prior rows, no change needed to the three existing manual-note call sites).

`opportunity_notes.user_id` is a required FK — there's no system/bot user, so agent-authored notes reuse `$task->requester_user_id` (the FM staff member whose forward reached the agent inbox — usually populated, since forwarding typically puts a real FM user address in the From header). If it's null (sender's address didn't match any FM user), the tool throws `AgentToolValidationException` rather than inventing a placeholder author — surfaces as a tool error, and since nothing else concludes the task, it ends up `pending_clarification` after the iteration loop is exhausted (not a crash).

**`check_status`** is read-only — per spec it's "used for status-inquiry auto-replies," so its result is meant to become the reply. `CheckStatusService::execute()` assembles `job_no`, `status`, `status_reason`, `requires_rfm`, `projectManager->name`, the newest `rfms()` row (status + `scheduled_at` — relation already ordered newest-first), and the most recently created estimate/sale status — there was no existing "opportunity summary" service to reuse; this mirrors the same fields the opportunity show-page Blade view already surfaces, just assembled programmatically for the first time. `ProcessAgentTask::formatStatusSummary()` turns that into the plain-text auto-reply body. (Originally implemented as an immediately-terminal tool like `attach_images` — see the multi-action redesign further down for why that changed; it's since become "accumulating" like everything else, which changes nothing about its own logic.)

Both tools follow the established `ValidatesAgentAttachments::assertOpportunityMatches()` invariant (Claude can't invent/pick its own `opportunity_id`) and log to `agent_messages` / set `task_type` the same way every other write tool does.

### `undo_last_action` notes (2026-08-17)

**Dashboard-only, deliberately not a Claude tool** — confirmed with Richard. It's absent from `AgentToolRegistry` and `ProcessAgentTask::SYSTEM_PROMPT` entirely; the only entry point is the Undo button on `admin/agent/tasks/show.blade.php`, calling `AgentTaskController::undo()`. Reasoning: every other tool only ever *creates* things going forward — letting an LLM's read of an ambiguous email ("please undo that") autonomously reverse already-completed work is a different, larger risk than anything else in v1, and the spec's own Admin UI section already calls for a manual undo button anyway. Can be added as a real tool later if wanted; it's a self-contained addition (new `AgentToolRegistry` entry + a `dispatchTool()` case calling the same `UndoLastActionService`), not a rework.

**Snapshot mechanism**: `agent_tasks` gained two columns via `database/migrations/2026_08_17_200000_add_undo_data_to_agent_tasks_table.php` — nullable `undo_data` (JSON, same pattern as the existing `attachments` column) and nullable `undone_at`. Each undoable write case in `ProcessAgentTask::dispatchTool()` includes an `undo_data` key in its event (`{'type': ..., ...}` — enough to reverse that specific action). **Updated by the multi-action redesign further down**: `undo_data` is now a *list* of these (one task can complete several actions), not a single object — `UndoLastActionService` normalizes both shapes for backward compatibility with tasks created before that redesign. `UpdateOpportunityService::execute()` got a small addition to capture the opportunity's *previous* `requires_rfm`/`project_manager_id` values before applying the update (via `$opportunity->only(array_keys($changes))`), since the before-state has to be captured at write time — it can't be reconstructed after the fact.

**Per-type reversal** (`UndoLastActionService::UNDOABLE_TYPES` = `attach_images`, `attach_document`, `log_communication`, `update_opportunity`):
- `attach_images`/`attach_document` → soft-delete the `OpportunityDocument` row(s) by ID. Mirrors `OpportunityDocumentController::destroy()`'s exact existing "archive" convention (`$document->delete()`) rather than inventing a different undo semantic — the physical file is untouched, so nothing is unrecoverable.
- `log_communication` → soft-delete the `OpportunityNote` by ID (also already soft-deletable).
- `update_opportunity` → write `previous_values` back onto the opportunity.

**`create_opportunity` is explicitly excluded** (confirmed with Richard, not just an oversight) — checked the FK cascade behavior first: deleting an `Opportunity` cascades through `opportunity_notes`/`purchase_orders`/`opportunity_documents`/`flooring_sign_offs`/`opportunity_shares`, but `sales`/`estimates` are `nullOnDelete` — if either had been created on that opportunity since, undoing the create would silently orphan them (leave the row, null out the link) rather than cleanly reverse anything. Too high a blast radius for a generic snapshot-based undo. `create_opportunity` never adds an `undo_data` entry at all (not even a "not undoable" placeholder); the dashboard shows "Undo not available for this action type" when a task has *no* undoable entries in its `undo_data`, and (since the multi-action redesign) partially undoes whatever *is* undoable in a mixed task while reporting on what wasn't — see below.

**Guardrails**: `execute()` refuses a non-`completed` task, an already-undone task (`undone_at` already set — no double-undo), and a task with no undoable entries at all. All surface as a flashed error on the dashboard rather than a crash.

## `attach_document` category typo (2026-08-18) — every agent-attached document was invisible in the FM UI

After a live test that otherwise worked perfectly (correct parent, correct PM, multi-action all in one task), Richard flagged that the attached PDF didn't show up in the FM UI's Documents tab. The document itself was completely fine — active row, correct opportunity, file genuinely present on disk, right size — so this wasn't a storage/attach failure at all. Root cause: `OpportunityDocumentController::index()`'s Documents-tab query filters `whereIn('category', ['documents', 'generated_document'])` (**plural**) — human uploads via the normal UI flow write `'documents'`, but `AttachDocumentService::execute()` had been writing `'document'` (**singular**) since Module 2. Every single document the agent has ever attached (in this dev DB and presumably wherever else this ran) has been silently invisible in that tab despite being stored correctly — a plural/singular typo nobody caught, including a test that had been asserting the buggy value as correct since Module 2.

Fixed: `AttachDocumentService` now writes `'documents'`. Fixed the one existing real affected record (`OpportunityDocument#3597`, Opportunity #123). Fixed the stale test assertion in `AgentInboundEmailTest.php::test_happy_path_attaches_document_to_resolved_opportunity` (previously asserted `'document'` as the expected/correct value — it needed to change, not just the source). Checked no other code anywhere reads/depends on the singular form. `attach_images` was never affected — its `category = 'media'` has no plural/singular ambiguity and already matched the UI's filter.

## Multi-action tasks (2026-08-18) — one task can now do several things

**What prompted this**: two consecutive live tests (real forwarded restoration-company referrals — see the `job_no`/parent-customer notes above) each needed *multiple* things done from one email — create the opportunity, attach photos that were included, and assign a project manager that was mentioned. The original design only ever let a task perform exactly one write action before concluding (a deliberate v1 simplicity/auditability choice — "one task, one clear action"). Both real tests proved that doesn't match how referral emails actually work; they routinely bundle several asks together, and the old design silently dropped everything after the first action succeeded.

**What changed**: `attach_images`, `attach_document`, `update_opportunity`, `create_opportunity`, `log_communication`, and `check_status` are no longer individually terminal — each now returns a non-terminal "event" (`{terminal: false, summary, task_type, undo_data?}`) that `ProcessAgentTask::handle()` accumulates into a `$completedActions` list, and the loop *keeps going*, exactly like `find_opportunity` always did. The task only concludes when Claude stops calling tools (returns final text, `stop_reason !== 'tool_use'`) or the iteration cap (`MAX_TOOL_ITERATIONS = 5`, unchanged) is hit — at which point every accumulated action's summary is joined into one final reply. `request_clarification` and `no_actionable_intent` are unchanged — still an immediate hard stop (`{terminal: true, ...}`) the moment either is called, since those genuinely mean "stop, don't do anything else." If a hard stop happens *after* some actions already succeeded in the same task, their summaries are prepended to the hard-stop's own message rather than silently hidden — the open question still governs the task's final `status`, but nothing done gets lost from the reply.

- New helpers in `ProcessAgentTask`: `finalize()` (Claude went quiet — completed-actions-so-far plus any wrap-up text, or the old "no actionable tool call" fallback if nothing happened), `finalizeFromActions()` (join accumulated summaries into a `completed` result), `finalizeHardStop()` (fold prior actions into a `request_clarification`/`ignored` result). `task_type` on a multi-action task is the *first* action's type (the "primary" one, e.g. `create_opportunity` even if `attach_images`/`update_opportunity` also ran) — good enough for dashboard filtering; nothing currently needs a task to carry more than one type.
- `undo_data` is now a **list** of per-action entries rather than one object. `UndoLastActionService` reverses whichever entries are in `UNDOABLE_TYPES`, skips (and reports on) any that aren't — e.g. undoing a task that created an opportunity, attached photos, and set a PM reverses the photos and the PM but leaves the (non-undoable) opportunity itself alone, with a note explaining why. `UndoLastActionService::normalizeUndoData()` transparently handles both the old single-object shape (tasks created before this change) and the new list shape, so nothing needed a data migration. `AgentTaskController::show()`/the dashboard's Undo-button visibility now calls the new `UndoLastActionService::hasUndoableAction()` instead of checking `task_type` against a fixed list, since `task_type` alone no longer tells you what's actually reversible in a multi-action task.
- System prompt rewritten to explicitly tell Claude to keep addressing everything an email asked for (attachments, PM, RFM, etc.) before stopping, rather than concluding after the first action.

**Verified against the real dev DB** (reproducing the actual scenario that motivated this): one task run through the real `ProcessAgentTask` pipeline with `create_opportunity` → `attach_images` (2 real attachments) → `update_opportunity` (PM by partial name), all in the same task — correctly created the opportunity linked to the right parent, attached both photos, and assigned the PM, with one combined summary and `undo_data` containing exactly the 2 undoable entries (not `create_opportunity`). Then verified Undo on that same task: correctly reverted the photos and PM, correctly left the opportunity itself intact. Also re-verified every previously-existing single-action scenario still behaves identically (`check_status`, `attach_document`, `request_clarification`, `no_actionable_intent` alone) and confirmed backward-compat undo on the old single-object `undo_data` shape.

**Test fallout**: every existing agent test whose Claude fake ended on a tool that's no longer individually terminal needed a trailing "final text" response added, since a static/short fake sequence no longer naturally concludes the loop the way a real Claude conversation would. Fixed at the shared fake-helper level in each affected file (`fakeClaudeToolUse()`/`fakeClaudeTurns()` in `AgentInboundEmailTest.php`, `AgentLogCommunicationCheckStatusTest.php`, `AgentFindUpdateOpportunityTest.php`, `AgentCreateOpportunityTest.php`) rather than touching every individual test — the extra trailing response is harmless/unconsumed for tests that already end on a hard-stop tool. New tests: `test_one_email_creates_opportunity_attaches_photos_and_sets_pm_in_one_task` (`AgentCreateOpportunityTest.php`), `test_undo_multi_action_task_reverts_undoable_entries_and_reports_skipped` (`AgentUndoLastActionTest.php`).

---

## Architecture / Flow

```
agent:check-inbound-mail (scheduled every 2 min, polls agent@rmflooring.ca via Graph)
  → POST /api/agent/inbound-email  (multipart/form-data: from, subject, body, attachments[])
    AgentInboundEmailController::receive()
      - sender allowlist check (AgentSetting::current()->isSenderAllowed())
      - per-sender rate limit (RateLimiter, same pattern as LoginRequest)
      - reads attachments into base64, creates AgentTask (status=queued)
      - dispatches ProcessAgentTask (queued job)
  → ProcessAgentTask::handle()
      - resolveOpportunity() — exact job_no regex fast path (see Module 3 notes)
      - loop (max 5 iterations): ClaudeAgentService::sendWithTools() → dispatchTool()
        on each tool_use block, log to agent_messages, execute the matching service
        (find_opportunity is non-terminal — may set opportunity_id and loop continues;
        create_opportunity refuses to run if opportunity_id is already set)
      - sets AgentTask.status + extracted_intent + task_type from the terminal tool result
      - notifyRequester() — auto-reply via GraphMailService::send(), + BCC if
        AgentNotificationSetting::bccEnabledFor($task_type), logs to agent_notifications
```

- `ClaudeAgentService` (`app/Services/Agent/ClaudeAgentService.php`) — raw `Http::post()` to `https://api.anthropic.com/v1/messages` (no PHP Anthropic SDK exists). Model `claude-opus-4-8`, adaptive thinking, `output_config.effort = medium`. Key: `config('services.anthropic.key')` ← `ANTHROPIC_API_KEY` env.
- `AgentToolRegistry::forEmail()` (`app/Services/Agent/AgentToolRegistry.php`) — the JSON tool-schema array sent to Claude. Add new tools here as new modules land.
- Route auth: `POST /api/agent/inbound-email` behind `api.key:AGENT_INBOUND_API_KEY` — `ApiKeyMiddleware` was extended (Module 1) to accept a middleware parameter naming which env var to check, defaulting to `LEAD_API_KEY` for backward compat with the existing `leads/incoming` webhook.

---

## Task Dashboard UI notes (2026-08-17)

Built under `/admin/agent/tasks` (true-superuser `admin` middleware, same route group as `/settings`, `/settings/email-templates` — no new Spatie permission introduced; this is a superuser-only area by design).

- `app/Http/Controllers/Admin/AgentTaskController.php` — `index()` (filter by status/task_type/source, paginate 25, newest first), `show()` (task detail + `agent_messages` thread), `reply()` (only when `status === pending_clarification`; writes an `AgentMessage(sender: 'user')`, sets status back to `queued`, dispatches `ProcessAgentTask`), `undo()` (stub — `undo_last_action` isn't built yet; flashes a message, does nothing, only rendered for `status === completed`).
- Views: `resources/views/admin/agent/tasks/{index,show}.blade.php` — Tailwind/Flowbite, mirrors the `signing-requests` index (stats/filter bar, status-badge table, pagination) and `leads/show` (detail card, status pill) conventions. Thread renders as chat bubbles (agent left/gray, user right/blue).

**Found and fixed while building this (pre-existing gaps, not new scope):**
- `ProcessAgentTask::notifyRequester()` had linked every auto-reply email to `/pages/agent-tasks/{id}` since Module 1 — a route that never existed (dangling placeholder, no code/test depended on the exact path). Now points at `route('admin.agent.tasks.show', $task)`.
- `ProcessAgentTask::buildUserMessage()` only ever used `$task->raw_content` — a job dispatched to "resume" a task (e.g. this dashboard's reply flow) replayed the original email from scratch with no memory of prior `agent_messages`, so Claude would very likely re-ask the same clarifying question. Fixed via new `buildPriorThreadSummary()`: when a task already has messages, they're folded into the initial user turn as plain-text context ("here's the prior thread, don't re-ask what's already answered"). Deliberately **not** a reconstructed multi-turn API history — the raw `tool_use`/`tool_result` content blocks from the original run were never persisted (`agent_messages` only stores human-readable summaries via `logMessage()`), so a real multi-turn replay isn't reconstructable without a schema change. The plain-text fold-in is the minimal fix that makes resume functionally correct.

**Verification:** `tests/Feature/AgentTaskDashboardTest.php` written (index/filter, show+thread, reply happy-path + rejection, undo stub, non-admin 403) but — like all Feature tests here — currently can't run via `php artisan test` because `pdo_sqlite` isn't installed on this box (pre-existing, see Testing section below; confirmed unrelated to this work). Verified instead by driving the controller directly against the real dev DB (`fm_laravel_dev`) via `php artisan tinker`, per the documented workaround: created live `pending_clarification`/`completed` tasks, rendered `index`/`show`, exercised `reply()` (status transition, message ordering, `Queue::fake()` + assertPushed on `ProcessAgentTask`), exercised `undo()` and the rejected-reply path, inspected `buildUserMessage()`'s output directly via reflection to confirm the prior-thread text is correctly assembled, and confirmed `AdminMiddleware` 403s a non-admin user.

---

## Admin Settings UI notes (2026-08-17)

Built under `/admin/settings/agent` (same `admin`-middleware-only convention as the Task Dashboard — no new Spatie permission), plus a tile on the `/admin/settings` hub linking to it.

- `app/Http/Controllers/Admin/AgentSettingsController.php` — `index()` loads `AgentSetting::current()` and every `AgentNotificationSetting` row keyed by `task_type`; `update()` validates and writes both. Sender domains/addresses are edited as plain one-per-line textareas (explicit product decision — no tag-style UI for v1) and split/trimmed/filtered into the existing JSON array columns. The BCC matrix always renders all of `AgentTaskController::TASK_TYPES`, including types with no `agent_notification_settings` row yet, defaulting unchecked (per spec: "new task types default to BCC off until explicitly enabled").
- **Bug caught during manual verification, not shipped:** the first pass read `array_keys($request->input('bcc', []))` to determine which task types were enabled — but since every row renders *both* a hidden `bcc[type]=0` field and the checkbox `bcc[type]=1` under the same key (the same pattern `sms.blade.php` already uses for its single toggle), every task type's key is *always* present in the submitted array regardless of checked state; only the *value* (last one wins, per PHP's duplicate-key-in-body behavior) tells you on/off. `array_keys()` would have force-enabled BCC for every task type on every save. Fixed to check `($bcc[$taskType] ?? '0') === '1'` per type before it was verified/shipped.
- View: `resources/views/admin/settings/agent.blade.php`, mirrors `admin/settings/sms.blade.php`'s card layout and toggle-switch styling.
- **Verification note:** `AgentSetting::current()`'s `firstOrCreate([])` call during this session's first manual check *created* the `agent_settings` row in `fm_laravel_dev` for the first time (confirmed via its `created_at` timestamp landing in this same session) — there was no pre-existing dev configuration to preserve. After exercising `update()` against real data, the row and all `agent_notification_settings` rows were reset back to that true pristine state (all nulls / `rate_limit_per_sender_per_hour = 20` / zero notification-setting rows) rather than left with test values.

---

## Inbound Mail Intake notes (2026-08-17) — deviates from the original spec, deliberately

`fm-agent-context.md`'s architecture assumed a Postfix pipe script parsing raw inbound MIME email. **That doesn't fit this org's actual mail infrastructure** — `rmflooring.ca` is hosted on Microsoft 365 (confirmed via `config/services.php`'s `microsoft` block + `GraphMailService`'s existing app-token client-credentials flow), and there is no self-hosted MTA for this domain (`/etc/postfix` doesn't exist on this box). Richard does have a separate self-hosted Postfix serving *other* domains, and it was discussed as an option (see chat — 2026-08-17), but decided against: MX records route per-domain not per-mailbox, so `agent@rmflooring.ca` can't cleanly live on a different mail system without either (a) making Postfix the front door for all of `rmflooring.ca`'s live mail just to catch one address, or (b) an Exchange mail-flow rule forwarding a copy out to Postfix — both add complexity/risk for no real benefit here. Decision: **poll `agent@rmflooring.ca` via Microsoft Graph**, matching the exact pattern `app/Console/Commands/CheckRfmCalendarConfirmations.php` already uses successfully in this codebase.

- New command `app/Console/Commands/CheckAgentInboundMail.php` (`agent:check-inbound-mail`), scheduled `everyTwoMinutes()` in `routes/console.php` (`withoutOverlapping()`, matching the RFM command's convention — RFM itself runs `everyTenMinutes()`, agent mail is more time-sensitive for staff so it's tighter).
- Two new `GraphMailService` methods, added in the same style as its existing `getUnreadMessages()`/`getMessageMime()`/`markMessageRead()`: `getMessageWithAttachments()` fetches a message's `from`/`subject`/`body` plus `$expand=attachments` in one call — **no MIME parsing, no new composer dependency**. Graph normalizes everything into JSON, and critically every attachment carries an `isInline` boolean, which directly satisfies the spec's "inline body images vs. true attachments handled via separate parsing paths" guardrail for free — the command just filters `isInline === true` out (`realAttachments()`), no `cid:` reference-matching or nested-multipart logic needed.
- HTML bodies (`body.contentType === 'html'`) are stripped to plain text via `strip_tags()` + entity-decode (`extractPlainBody()`) since the webhook's `body` field and `ProcessAgentTask`'s prompt-building both expect plain text — no HTML-to-text library added, matching this codebase's general preference for hand-rolled solutions over new dependencies when the problem is this contained. **Bug found and fixed (2026-08-18)**: a bare `strip_tags()` inserts no separator at all between adjacent elements — a two-column "job information" table (`<td>PM Contact</td><td>Andrew Bou-Antoun</td>`, the exact structure a real referral partner's work-order email template uses) collapsed into run-on text like `"PM ContactAndrew Bou-Antoun"` with zero space between label and value. Confirmed live: this silently dropped a real PM assignment that was genuinely present in the email, because it became unreliable to parse rather than because it wasn't there. Fixed by inserting a space at common block/cell boundaries (`preg_replace` on `</?(?:td|tr|div|p|br|th|li|h[1-6])[^>]*>` before stripping the rest) and collapsing the resulting extra whitespace. New test: `test_html_table_cells_get_separated_not_mashed_together` in `CheckAgentInboundMailTest.php`. Complementary fix in `ProcessAgentTask::SYSTEM_PROMPT`: explicitly tells Claude that a labeled "PM Contact"/"Project Manager" field in a referrer's structured job data is itself the instruction to assign that PM via `update_opportunity` — it doesn't need narrative phrasing like "please set the PM to X" to count as a request.
- The command POSTs to the **existing, already-tested** `/api/agent/inbound-email` webhook via `Http::attach()` + `AGENT_INBOUND_API_KEY` bearer auth, exactly as a real pipe script would — it doesn't call `AgentInboundEmailController` in-process. This preserves the doc's original architecture boundary (mail source → webhook → orchestration) so any future mail source could plug in the same way, and means zero changes were needed to the allowlist/rate-limit/task-creation logic.
- **Retry-safe by construction:** a message is only `markMessageRead()`'d after the webhook responds successfully. A rejected message (blocked sender, rate limit, validation error) or a thrown exception (Graph hiccup, etc.) leaves the message unread and logs a warning/error — it's simply picked up again on the next 2-minute run. No task is silently dropped on a transient failure.
- New env var `AGENT_INBOUND_MAILBOX` (default `agent@rmflooring.ca`), added to `.env` and `.env.example` alongside the other two agent env vars.

**Live setup — DONE and proven working end-to-end (2026-08-17/18, on this dev box):**
1. `agent@rmflooring.ca` shared mailbox created in Exchange Admin Center. Shared mailboxes are free/unlicensed under standard M365 policy (up to 50GB, no license needed for app-only access) — nothing in this app depends on it having a license either way.
2. Real `MICROSOFT_CLIENT_ID`/`MICROSOFT_CLIENT_SECRET`/`MICROSOFT_TENANT_ID` added to this dev box's `.env` (previously only Anthropic/agent keys were configured here — every prior Graph interaction in this doc had been `Http::fake()`, never a real call, until this).
3. **Root cause of a multi-hour `403 ErrorAccessDenied`, fully resolved**: `Mail.ReadWrite` was initially granted on the *wrong Azure app registration* (client ID `5f14afe7-...`, not this app's `fb31ca39-5523-4c5f-a6f7-990cfa4c66e4`) — an easy mistake since Entra's app-registration search is by name, and apparently more than one app existed. Confirmed via decoding the actual issued access token's JWT `roles` claim (`base64_decode` the middle segment) — the new permission never appeared in tokens no matter how long we waited, which ruled out simple propagation delay and pointed at the grant itself being wrong. Once granted **Application**-type `Mail.ReadWrite` on the *correct* app (there was also an existing but irrelevant **Delegated**-type `Mail.ReadWrite` on it — Delegated permissions never apply to `GraphMailService::getAppToken()`'s client-credentials/app-only flow, only Application ones do), the token immediately included it and the mailbox call succeeded. **If Graph auth ever mysteriously fails again for this app, decoding the JWT `roles` claim is the fastest way to check what's actually in the token** vs. what the portal claims is granted.
4. **Confirmed with a real end-to-end test**: sent a real email to `agent@rmflooring.ca`, ran `agent:check-inbound-mail` for real (no fakes) — Graph fetch succeeded, task created, Claude correctly called `no_actionable_intent` on the test's deliberately-non-actionable content, and a real auto-reply was sent back via `GraphMailService::send()`. Full chain proven live, not just against fakes.
5. **Operational gap found in the same test**: nothing was consuming the `database` queue on this box — `ProcessAgentTask` (and an unrelated `MirrorFileToOneDrive` job) sat pending until `php artisan queue:work --stop-when-empty` was run manually. **A persistent queue worker (Supervisor-managed `php artisan queue:work`) is a hard requirement for this feature in production**, separate from and in addition to the scheduler cron — the scheduler only dispatches `ProcessAgentTask`, a worker is what actually runs it. Add this explicitly to the deploy checklist, easy to miss since `QUEUE_CONNECTION=sync` (which needs no worker) is a common local-dev default and this box uses `database` instead.

**Also fixed while diagnosing the 403**: `GraphMailService::getUnreadMessages()`, `getMessageMime()`, and `markMessageRead()` never checked `$response->successful()` — a failed/denied Graph call silently looked identical to "nothing found" (`json('value', [])` returns `[]` on any response shape, error or not), which is exactly what made the very first live check falsely report "0 unread messages" instead of the real 403. All three now throw `\RuntimeException` on a non-2xx response, matching the pattern already used in this file's `getMessageWithAttachments()`. This also benefits `rfm:check-confirmations`, which shares these methods and had the identical blind spot. New test: `tests/Feature/GraphMailServiceErrorHandlingTest.php`.

**Verification:** `tests/Feature/CheckAgentInboundMailTest.php` written (no-unread no-op, forward + inline-attachment exclusion, HTML→plain-text stripping, rejected-message-stays-unread) but blocked by the same pre-existing `pdo_sqlite` gap as every other Feature test here — verified instead via `Http::fake()`-based manual runs (see file for exact assertions), *and* additionally proven against the real Microsoft 365 tenant end-to-end as described in point 4 above — about as strong a verification as this feature can get short of production traffic.

---

## Env Vars

| Var | Purpose |
|---|---|
| `ANTHROPIC_API_KEY` | Claude Messages API key |
| `AGENT_INBOUND_API_KEY` | Bearer token `agent:check-inbound-mail` sends to `/api/agent/inbound-email` |
| `AGENT_INBOUND_MAILBOX` | Mailbox polled for inbound tasks, default `agent@rmflooring.ca` |
| `MICROSOFT_CLIENT_ID` / `MICROSOFT_CLIENT_SECRET` / `MICROSOFT_TENANT_ID` | Pre-existing Graph app-registration credentials (not new to this feature) — required for `agent:check-inbound-mail` to authenticate at all. Must be the app that actually has **Application**-type `Mail.ReadWrite` granted — see the "wrong app registration" gotcha above if this ever breaks again. |

All added to `.env.example`. **Production deploy checklist for this feature specifically** (beyond the general deploy steps): the `agent@rmflooring.ca` shared mailbox must exist with `Mail.ReadWrite` (Application) granted on the correct app registration (done, see above); the scheduler cron (`* * * * * php artisan schedule:run`) must be running so `agent:check-inbound-mail` actually fires every 2 minutes; and — easy to miss — **a persistent queue worker must be running** (`php artisan queue:work` under Supervisor or similar) since `ProcessAgentTask` is a queued job and the scheduler only dispatches it, it doesn't run it.

**Also add: verify upload size limits on the live server.** A live test with ~20MB of real referral attachments (several PDFs) got rejected `413 Payload Too Large` on this dev box — traced to PHP-FPM's `post_max_size = 20M` (php8.4-fpm's `www.conf` pool, the one `devfm.rmflooring.ca` uses here). Nginx itself allows up to 600MB; PHP-FPM is the actual bottleneck. **Deliberately left unfixed on this dev box** — that pool is shared by ~10 other live production sites on this box, and Richard confirmed the live server likely doesn't have this problem, so raising it here wasn't worth the shared-infra risk for a dev-only test. If this recurs on the live server, the safe fix is a **dedicated PHP-FPM pool for just that one site** (its own socket, its own higher `post_max_size`/`upload_max_filesize`) rather than raising the limit on whatever pool is shared with other sites there too — don't touch a shared pool without confirming the actual blast radius on that specific server first.

---

## Testing

`tests/Feature/AgentInboundEmailTest.php` covers: happy path for `attach_images`, happy path for `attach_document`, sender-not-allowed rejection, no-job-number → clarification, rate-limit rejection. `tests/Feature/AgentFindUpdateOpportunityTest.php` and `tests/Feature/AgentCreateOpportunityTest.php` cover Modules 3 and 4 respectively. All use `Http::fake()` for both the Claude and Microsoft Graph calls.

**Caveat:** `php artisan test` does not currently complete a full fresh migration (blocks on a pre-existing, unrelated MySQL-only `SHOW INDEX` migration + ~13 other unverified raw-SQL migrations — see `feedback_broken_test_bootstrap` in session memory for full detail; fixing this was explicitly deferred as out of scope for the agent-system work). Two other pre-existing bootstrap bugs (`app_settings` boot-order crash, `labour_items` migration ordering) **were** fixed along the way and are safe/committed.

Until the sqlite portability issue is resolved, verify new agent-system work against the **real dev DB** (`.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=fm_laravel_dev`) via `php artisan tinker` — create test rows, `Http::fake()` the Claude/Graph calls, call `ProcessAgentTask::handle()` directly, assert DB/storage state, clean up. This is how both modules were actually verified.

---

## Key Files Reference

| What | Where |
|---|---|
| Spec/requirements | `fm-agent-context.md` |
| Migrations | `database/migrations/2026_07_10_*_create_agent_*_table.php`, `2026_08_17_190000_add_category_and_source_to_opportunity_notes_table.php`, `2026_08_17_200000_add_undo_data_to_agent_tasks_table.php` |
| Models | `app/Models/Agent{Task,Message,Setting,NotificationSetting,Notification}.php` |
| Webhook controller | `app/Http/Controllers/Api/AgentInboundEmailController.php` |
| Route | `routes/api.php` → `POST /api/agent/inbound-email` |
| Orchestration job | `app/Jobs/ProcessAgentTask.php` |
| Claude API wrapper | `app/Services/Agent/ClaudeAgentService.php` |
| Tool schemas | `app/Services/Agent/AgentToolRegistry.php` |
| Tool services | `app/Services/Agent/{Attach{Images,Document},Find,Update,Create}OpportunityService.php`, `app/Services/Agent/{LogCommunication,CheckStatus,UndoLastAction}Service.php` |
| Shared attachment validation | `app/Services/Agent/Concerns/ValidatesAgentAttachments.php` |
| Task dashboard controller | `app/Http/Controllers/Admin/AgentTaskController.php` |
| Task dashboard views | `resources/views/admin/agent/tasks/{index,show}.blade.php` |
| Task dashboard routes | `routes/web.php` → `admin.agent.tasks.{index,show,reply,undo}` |
| Settings controller | `app/Http/Controllers/Admin/AgentSettingsController.php` |
| Settings view | `resources/views/admin/settings/agent.blade.php` |
| Settings routes | `routes/web.php` → `admin.settings.agent`, `admin.settings.agent.update` |
| Inbound mail poll command | `app/Console/Commands/CheckAgentInboundMail.php` (`agent:check-inbound-mail`) |
| Inbound mail schedule | `routes/console.php` → `everyTwoMinutes()` |
| Tests | `tests/Feature/Agent{InboundEmail,FindUpdateOpportunity,CreateOpportunity,TaskDashboard,Settings,LogCommunicationCheckStatus,UndoLastAction}Test.php`, `tests/Feature/CheckAgentInboundMailTest.php` |
