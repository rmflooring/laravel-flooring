# Floor Manager AI Agent System — Build Context
Updated: 2026-07-16

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
| Module 3 — `find_opportunity` / `update_opportunity` | Not started | — |
| Module 4 — `create_opportunity` | Not started | — |
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
| `request_clarification` | inline in `ProcessAgentTask::dispatchTool()` | Writes a question to `agent_messages`, sets `status = pending_clarification`. |
| `no_actionable_intent` | inline in `ProcessAgentTask::dispatchTool()` | Sets `status = ignored` (spam/newsletter/unrelated forward). |

Both attach tools share validation/decoding logic via `app/Services/Agent/Concerns/ValidatesAgentAttachments.php` (extracted in Module 2): `assertOpportunityMatches()`, `decodeAttachmentBytes()`, `storageFolderFor()`. Both reuse the existing document-storage stack unchanged — `App\Models\OpportunityDocument`, `App\Services\DocumentStorageService::disk()`, `Opportunity::storageFolderName()` — same conventions as the manual mobile photo/document upload flows. 20MB size limit on both.

**Not yet built:** `find_opportunity` (fuzzy name/address/claim# matching + confidence scoring), `create_opportunity`, `update_opportunity`, `log_communication`, `check_status`, `undo_last_action`.

### Deliberate scope decision: opportunity resolution

Full fuzzy `find_opportunity` has **not** been built. Instead, `ProcessAgentTask::resolveOpportunity()` does a minimal deterministic regex match: scans the email subject+body for a job-number pattern (`\b\d{2}-\d{4}\b`, e.g. `26-0001`) and matches it exactly against `Opportunity.job_no`. Anything that doesn't resolve to exactly one match stays unresolved (`opportunity_id = null`), and both attach tools **reject** any Claude-proposed `opportunity_id` that doesn't equal the one already resolved this way — Claude cannot pick its own opportunity. This keeps Module 1/2 low-risk; full fuzzy matching with confidence scoring is deferred to its own future module per the spec's rollout order.

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
      - resolveOpportunity() — exact job_no match (see above)
      - loop (max 5 iterations): ClaudeAgentService::sendWithTools() → dispatchTool()
        on each tool_use block, log to agent_messages, execute the matching service
      - sets AgentTask.status + extracted_intent from the terminal tool result
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

`tests/Feature/AgentInboundEmailTest.php` covers: happy path for `attach_images`, happy path for `attach_document`, sender-not-allowed rejection, no-job-number → clarification, rate-limit rejection. Uses `Http::fake()` for both the Claude and Microsoft Graph calls.

**Caveat:** `php artisan test` does not currently complete a full fresh migration (blocks on a pre-existing, unrelated MySQL-only `SHOW INDEX` migration + ~13 other unverified raw-SQL migrations — see `feedback_broken_test_bootstrap` in session memory for full detail; fixing this was explicitly deferred as out of scope for the agent-system work). Two other pre-existing bootstrap bugs (`app_settings` boot-order crash, `labour_items` migration ordering) **were** fixed along the way and are safe/committed.

Until the sqlite portability issue is resolved, verify new agent-system work against the **real dev DB** (`.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=laravel_local`) via `php artisan tinker` — create test rows, `Http::fake()` the Claude/Graph calls, call `ProcessAgentTask::handle()` directly, assert DB/storage state, clean up. This is how both modules were actually verified.

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
| Tool services | `app/Services/Agent/Attach{Images,Document}Service.php` |
| Shared attachment validation | `app/Services/Agent/Concerns/ValidatesAgentAttachments.php` |
| Tests | `tests/Feature/AgentInboundEmailTest.php` |
