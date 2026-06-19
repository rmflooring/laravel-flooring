# FM Lead Integration — Context for Shop Projects

This document describes how an external shop/website submits incoming leads to
Floor Manager (FM) at `fm.rmflooring.ca`.

---

## Database Table

Table name: `incoming_leads`

FM stores the lead as a pending record, notifies the admin, and allows staff to
approve (creating an Opportunity) or deny from the FM Leads UI.

---

## API Endpoint

```
POST https://fm.rmflooring.ca/api/leads/incoming
```

**Headers:**
```
Authorization: Bearer {FM_LEAD_API_KEY}
Content-Type: application/json
```

**Success response** (HTTP 200):
```json
{ "success": true, "lead_id": 42 }
```

**Auth failure** (HTTP 401):
```json
{ "error": "Unauthorized." }
```

**Validation failure** (HTTP 422):
```json
{ "message": "...", "errors": { "field": ["..."] } }
```

---

## Authentication

The endpoint uses a static Bearer token. FM reads it from its own `.env` as
`LEAD_API_KEY`. The shop must send the same value in the `Authorization` header.

On the shop side, store the key in `.env` as `FM_LEAD_API_KEY` (or any name you
prefer) and pass it as the Bearer token on every request. This is **separate**
from any other FM API authentication — the existing `/api/shop/*` routes use no
auth, but this endpoint requires the Bearer token on every call.

---

## Fields

Send as a JSON body. Field names must match exactly.

| Field | Type | Required | Max | Notes |
|---|---|---|---|---|
| `source` | string | **yes** | 100 | Identifier for the originating site, e.g. `"shop.rmflooring.ca"` |
| `name` | string | **yes** | 255 | Full name — combine first + last if your form collects them separately |
| `phone` | string | **yes** | 20 | Any format; FM normalises to E.164 for SMS |
| `email` | string | no | 255 | Must be a valid email if provided |
| `sms_consent` | boolean | no | — | `true` if the user opted in to receive a confirmation SMS. Defaults to `false` if omitted |
| `service_type` | string | no | 100 | Type of flooring, e.g. `"Hardwood Flooring"`, `"Vinyl Plank (LVP)"` |
| `project_type` | string | no | 100 | e.g. `"New Installation"`, `"Replacement"`, `"Repair"` |
| `area` | string | no | 100 | Square footage range, e.g. `"500–1,000 sq ft"` |
| `timeline` | string | no | 100 | e.g. `"Within 1–3 months"`, `"As soon as possible"` |
| `message` | string | no | 2000 | Free-text message from the customer |
| `referral_source` | string | no | 100 | e.g. `"Google Search"`, `"Friend or Family Referral"` |

Only `source`, `name`, and `phone` are required. All other fields are optional
but recommended — they appear in the FM lead detail view and the admin
notification email/SMS.

---

## Example Request

```bash
curl -X POST https://fm.rmflooring.ca/api/leads/incoming \
  -H "Authorization: Bearer YOUR_FM_LEAD_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "source":          "shop.rmflooring.ca",
    "name":            "Jane Smith",
    "phone":           "6041234567",
    "email":           "jane@example.com",
    "sms_consent":     true,
    "service_type":    "Hardwood Flooring",
    "project_type":    "New Installation",
    "area":            "500–1,000 sq ft",
    "timeline":        "Within 1–3 months",
    "message":         "Looking to redo main floor hardwood.",
    "referral_source": "Google Ad"
  }'
```

---

## What FM Does on Receipt

1. Saves the record with `status = pending`
2. Dispatches a queued job that:
   - Emails the admin (`ADMIN_NOTIFICATION_EMAIL`) with full lead details and a
     "Review" link to FM
   - SMS the admin (`ADMIN_SMS_NUMBER`) with a short summary
   - If `sms_consent` is `true`: sends an acknowledgment SMS to the lead's phone
     number
3. The lead appears in FM under the **Leads** sidebar item (pending count badge)

---

## Notes for Implementation

- **Combine first/last name** before sending — FM expects a single `name` field,
  not `first_name`/`last_name`
- **`sms_consent` must be a real boolean** (`true`/`false`), not a string. PHP
  forms submit checkboxes as `"1"` or absent — make sure to cast it: use
  `$request->boolean('sms_consent', false)` in Laravel, or cast explicitly in
  other frameworks
- **Don't send on every page load** — only submit on form POST; the endpoint is
  not idempotent
- **Handle failures gracefully** — if the request returns non-200, show the user
  a generic error and let them retry or call the store directly. Do not expose
  API error details to the customer
- **Timeout** — set a reasonable HTTP timeout (15s). If FM is unreachable, fall
  back gracefully

---

## Env Vars Needed on the Shop

```env
FM_API_BASE_URL=https://fm.rmflooring.ca
FM_LEAD_API_KEY=<get this from FM .env LEAD_API_KEY>
```
