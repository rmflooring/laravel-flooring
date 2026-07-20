# Floor Manager AI Agent System — Build Context
Updated: 2026-07-16 (Module 4)

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
| Module 5 — `log_communication` / `check_status` | Not started | — |
| Chat UI, admin settings UI, task dashboard UI | Not started | — |

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
| `attach_document` | `app/Services/Agent/AttachDocumentService.php` | Attaches a single email document (PDF/Word/scanned image) to the opportunity. `document_type` (scope_of_work/contract/insurance_certificate/permit/inspection_report/other) is a PHP allowlist (`AttachDocumentService::DOCUMENT_TYPES`), also stored in `label_text`. `OpportunityDocument.category = 'document'`. |
| `find_opportunity` | `app/Services/Agent/FindOpportunityService.php` | Fuzzy-matches `client_name`/`address`/`claim_number` (whichever are given) against opportunities via their `jobSiteCustomer`/`parentCustomer`. Not terminal — Claude keeps reasoning afterward. See scoring details below. |
| `update_opportunity` | `app/Services/Agent/UpdateOpportunityService.php` | Writes only `requires_rfm` (boolean) and/or `project_manager_id` (resolved from a freetext name — never accepted as a raw ID). See scope decision below. |
| `create_opportunity` | `app/Services/Agent/CreateOpportunityService.php` | Creates a new `Customer` (+ optionally links an existing parent) and `Opportunity` for a job not yet in FM. Duplicate-check gated. See notes below. |
| `request_clarification` | inline in `ProcessAgentTask::dispatchTool()` | Writes a question to `agent_messages`, sets `status = pending_clarification`. |
| `no_actionable_intent` | inline in `ProcessAgentTask::dispatchTool()` | Sets `status = ignored` (spam/newsletter/unrelated forward). |

Both attach tools share validation/decoding logic via `app/Services/Agent/Concerns/ValidatesAgentAttachments.php` (extracted in Module 2): `assertOpportunityMatches()`, `decodeAttachmentBytes()`, `storageFolderFor()`. Both reuse the existing document-storage stack unchanged — `App\Models\OpportunityDocument`, `App\Services\DocumentStorageService::disk()`, `Opportunity::storageFolderName()` — same conventions as the manual mobile photo/document upload flows. 20MB size limit on both.

`update_opportunity` also uses `ValidatesAgentAttachments::assertOpportunityMatches()` — the same "Claude cannot pick its own opportunity_id" invariant applies here too.

**Not yet built:** `log_communication`, `check_status`, `undo_last_action`.

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

**Opportunity creation**: `status = 'New'` (DB default and a canonical status value), `requires_rfm` defaults to `true` server-side if the tool input omits it (a brand-new opportunity almost always needs a site measure next). Sets `$task->opportunity_id` on success.

**Guardrail in code, not just prompt**: throws immediately if `$task->opportunity_id` is already set when `create_opportunity` is called — an opportunity was already resolved (via the job-number regex fast path or `find_opportunity`), so creating a new one would be wrong; the system prompt tells Claude this too, but the check is enforced in the service regardless.

**Incomplete intake**: per the spec, "flags" rather than blocks. Missing `address`/`claim_number`/`insurance_company` (the spec also mentions "loss type," but no such field exists anywhere in this schema — confirmed via grep, dropped) doesn't stop creation; the gap is noted in the terminal summary/logged message for staff follow-up.

`dol` (date of loss) is parsed with `Carbon::parse()` and re-validated before insert — the human-facing `CustomerController`/`JobSiteCustomerController` both enforce Laravel's `date` validation rule on this field; without an equivalent check here, a malformed date from Claude would surface as a raw DB error instead of a graceful `request_clarification`.

---

## Architecture / Flow

```
Postfix pipe script (parses email)
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

## Env Vars

| Var | Purpose |
|---|---|
| `ANTHROPIC_API_KEY` | Claude Messages API key |
| `AGENT_INBOUND_API_KEY` | Bearer token the Postfix pipe script must send to `/api/agent/inbound-email` |

Both added to `.env.example`. **Not yet set up:** the actual Postfix pipe script that parses inbound mail to `agent@rmflooring.ca` and POSTs it — that's an infra piece outside the Laravel app, not yet built/documented.

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
| Migrations | `database/migrations/2026_07_10_*_create_agent_*_table.php` |
| Models | `app/Models/Agent{Task,Message,Setting,NotificationSetting,Notification}.php` |
| Webhook controller | `app/Http/Controllers/Api/AgentInboundEmailController.php` |
| Route | `routes/api.php` → `POST /api/agent/inbound-email` |
| Orchestration job | `app/Jobs/ProcessAgentTask.php` |
| Claude API wrapper | `app/Services/Agent/ClaudeAgentService.php` |
| Tool schemas | `app/Services/Agent/AgentToolRegistry.php` |
| Tool services | `app/Services/Agent/{Attach{Images,Document},Find,Update,Create}OpportunityService.php` |
| Shared attachment validation | `app/Services/Agent/Concerns/ValidatesAgentAttachments.php` |
| Tests | `tests/Feature/Agent{InboundEmail,FindUpdateOpportunity,CreateOpportunity}Test.php` |
