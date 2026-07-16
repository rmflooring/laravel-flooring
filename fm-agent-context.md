# Floor Manager AI Agent System — Context & Spec

## Overview
A hybrid AI agent system for Floor Manager (fm.rmflooring.ca) that lets staff trigger
tasks two ways — forwarding an email to `agent@rmflooring.ca`, or typing into an
in-app chat UI — both of which share one tool library, one task queue, and one
clarification flow. The agent uses Claude's tool-use API (not Claude Code / autonomous
shell access) to keep actions constrained to a well-defined, auditable set of functions.

Environment: this is a module within the existing Laravel 12 Floor Manager app.
Follow existing FM conventions: Blade templates, Alpine.js, Flowbite, Spatie
permissions, inline JS for JS-driven states.

---

## Design Principles
- Claude never gets raw DB/shell access. It only calls predefined tools mapped to
  Laravel services/actions.
- Read-only actions auto-execute. Any write with ambiguity, missing critical fields,
  or generally risky/destructive potential goes to a clarification/approval step.
- Every decision Claude makes that involves fuzzy matching (e.g. matching a client
  name to an opportunity) is logged with a confidence score for auditability.
- Email is a pure capture/notification surface — all clarification and confirmation
  happens in the FM dashboard UI, not via email reply threads.
- BCC admin notifications are silent (true BCC) and logged separately for audit
  purposes, since silent copies leave no trace in the email thread itself.

---

## Core Tables

### `agent_tasks`
- `id`
- `source` (enum: email, chat)
- `requester_email` (from header on email tasks, or logged-in user's email on chat tasks)
- `requester_user_id` (nullable, if matched to an FM user)
- `raw_content` (original email body / chat message)
- `attachments` (JSON: paths to stored files)
- `extracted_intent` (Claude's parsed summary of what was requested)
- `task_type` (enum: create_opportunity, update_opportunity, attach_images,
  attach_document, log_communication, check_status, no_actionable_intent, other)
- `status` (enum: pending_clarification, pending_confirmation, completed, failed, ignored)
- `confidence_score` (nullable float, for match-related tasks)
- `opportunity_id` (nullable FK, once resolved)
- `created_at`, `updated_at`

### `agent_messages`
- `id`
- `task_id` (FK)
- `sender` (enum: agent, user)
- `body`
- `created_at`
(Powers both the clarification thread and the general chat UI)

### `agent_notification_settings`
- `id`
- `task_type`
- `admin_bcc_enabled` (boolean, default false — opt-in per type)

### `agent_notifications`
- `id`
- `task_id` (FK)
- `sent_to` (email)
- `type` (enum: bcc_admin, requester_reply)
- `created_at`
(Audit log — required since BCC is silent and otherwise unverifiable)

### `agent_settings`
- `admin_notification_email` (nullable string)
- `allowed_sender_domains` (JSON array)
- `allowed_sender_addresses` (JSON array)
- `rate_limit_per_sender_per_hour` (int, default e.g. 20)

---

## Tool Library (v1)

1. **`create_opportunity(fields...)`**
   All fields nullable except client name. Checks for a possible duplicate
   (same address/claim number within a recent window) before creating — flags
   instead of silently duplicating. Flags "incomplete intake" if key fields
   (address, claim #, insurance co, loss type) are missing.

2. **`find_opportunity(client_name, address, claim_number)`**
   Returns match(es) with a confidence score. Always logged. Zero or multiple
   ambiguous matches → triggers `request_clarification`.

3. **`update_opportunity(id, fields...)`**
   Requires a resolved opportunity_id (via find_opportunity first).

4. **`attach_images(opportunity_id, images[], label, category)`**
   Category should map to a defined enum in FM (before/after/moisture/damage/
   completion, etc.) rather than freetext.

5. **`attach_document(opportunity_id, file, document_type)`**
   e.g. document_type = 'scope_of_work'. Scanned/image-based PDFs may need OCR
   before Claude can extract meaningful content — reuse existing PDF skill pattern.

6. **`log_communication(opportunity_id, summary, from, category)`**
   Summarizes an email/correspondence thread onto the opportunity's activity log.

7. **`check_status(opportunity_id)`**
   Read-only. Auto-executes, no approval needed. Used for status-inquiry auto-replies.

8. **`request_clarification(question)`**
   Writes a message to `agent_messages`, sets task status to
   `pending_clarification`, triggers notification flow.

9. **`no_actionable_intent()`**
   Used when Claude can't determine any actionable request (e.g. spam,
   newsletter, unclear forward). Replies "couldn't determine what you'd like
   me to do," sets status to `ignored`, logs and closes.

10. **`undo_last_action(task_id)`**
    Reverses the effect of a completed task where feasible (e.g. detach
    uploaded images, revert updated fields to prior values — store a
    before-state snapshot on write actions to make this possible).

---

## Flow Logic

**Email-initiated task:**
1. Email to `agent@rmflooring.ca` parsed (sender, subject, body, attachments)
2. Sender checked against `allowed_sender_domains` / `allowed_sender_addresses`
3. Rate limit checked per sender
4. New `agent_tasks` row created, source = email
5. Claude (tool-use API) processes intent, attempts relevant tool calls
6. If read-only or high-confidence unambiguous write → auto-execute, log, mark completed
7. If ambiguous / missing critical info / no actionable intent → status set accordingly,
   question written to `agent_messages`
8. Auto-reply email fires immediately to `requester_email`:
   - If clarification needed: "Got your request — check the Agent dashboard: [task link]"
   - If completed: confirmation summary
   - If no actionable intent: polite "couldn't determine what you'd like me to do"
9. If `admin_bcc_enabled` for that task_type: silent BCC to `admin_notification_email`,
   logged in `agent_notifications`

**Chat-initiated task:**
Same tool library and task table, source = chat, requester = logged-in FM user.
Clarification happens synchronously in the same panel rather than round-tripping
through email. Non-trivial writes show a "here's what I'm about to do" confirmation
step (Confirm/Cancel) before executing; read-only lookups auto-run.

**Resolution (either source):**
User replies/clarifies in the FM dashboard task view (shared `agent_messages` thread
regardless of original source). Claude executes once resolved, sets status to
completed, sends final confirmation email if source was email.

---

## Security / Guardrails
- Sender allowlist (domains + explicit addresses) for the email inbox
- Chat UI inherits existing FM auth / Spatie permissions — no separate auth layer needed
- Rate limit: max tasks/hour per sender (configurable in `agent_settings`)
- No shell/DB access for Claude — tool calls only
- File size/type limits on attachments (block or reject oversized/unsupported files
  rather than silently failing)
- Inline body images vs. true attachments handled via separate parsing paths

## Admin UI
- Settings page: `admin_notification_email` field, and a per-task-type matrix/table
  of BCC on/off toggles (not a single global switch)
- New task types default to BCC "off" until explicitly enabled
- Task dashboard: queue view of all `agent_tasks`, filterable by status/type/source,
  with the clarification thread inline
- Manual "undo" button on completed tasks where `undo_last_action` is supported

## Cost / Rate Controls
- Track Claude API spend per task (tokens in/out, especially attachment-heavy tasks)
- Simple dashboard metric for API cost so it's not invisible
- Rate limit per sender as above

---

## Rollout Order
1. **Photo attach** (lowest risk) — build and dogfood first
2. **Scope of work document upload**
3. **Find / update opportunity**
4. **Create opportunity** (highest impact — most validation needed, duplicate detection critical)
5. **Communication logging + status auto-reply** (status auto-reply is read-only, safe to
   automate early if convenient)

## Deployment Notes
- Build and test on dev server against a copy of the FM database first
- Promote module by module (per rollout order above) rather than shipping all at once,
  consistent with existing FM development pattern (one module at a time, plan approval
  before coding, commit to GitHub after each feature)
