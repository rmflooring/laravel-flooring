# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard
Updated: 2026-03-13 (session 2)

## Working style rules
- Flowbite UI required for all new pages/components.
- One step at a time.
- Do not guess routes, schema, file paths, or controller methods.
- Verify with route:list / logs / schema before changing architecture.
- Keep context files updated after meaningful progress.

---

## System overview
Internal operations platform for RM Flooring using Laravel 12.

Current core modules:
- Opportunities
- RFMs (Requests for Measure) — see `Context/context_rfm.md`
- Estimates
- Sales
- Documents / Media
- Calendar (MS365 integration)
- Email system (Track 1 + Track 2) — see `Context/context_graph_mail.md`
- Email Templates (per-user + admin system) — see `Context/context_email_templates.md`
- Users / Roles / Employees
- Admin pages (tax groups, settings, email management)

---

## Email system summary
Full details in `Context/context_graph_mail.md`.

### Track 1 — Working
- Shared mailbox `reception@rmflooring.ca` via Graph API client credentials
- Used for: RFM notifications (create + edit), test sends
- Admin portal at `/admin/settings/mail`
- Controlled by `app_settings` table keys: `mail_from_address`, `mail_from_name`, `mail_reply_to`, `mail_notifications_enabled`
- All sends logged to `mail_log` table with `track=1`

### Track 2 — Working ✓
- Per-user delegated OAuth — each staff member's personal `@rmflooring.ca` MS365 account
- Admin connects each user from `/admin/settings/mail` Track 2 table (Connect button → OAuth flow)
- Per-user **Send Test** button confirmed working on live server
- Token stored encrypted on `microsoft_accounts` (`mail_connected`, `mail_connected_at` columns)
- `GraphMailService::sendAsUser(User $user, ...)` — active and tested
- Auto token refresh built in; marks `mail_connected=false` if refresh fails
- All sends logged to `mail_log` with `track=2` and `sent_from=user_email`
- **Wired into**: estimates (edit page), sales (edit + show pages)
- **Not yet wired into**: invoices (module not built yet)

### Fallback pattern (for when wiring Track 2 into estimates/invoices)
```php
$sent = $user->microsoftAccount?->mail_connected
    ? $mailer->sendAsUser($user, $to, $subject, $body, $type)
    : false;
if (! $sent) {
    $mailer->send($to, $subject, $body, $type); // Track 1 fallback
}
```

### Azure app permissions (same app registration for both) — both confirmed ✓
- `Mail.Send` Application — Track 1 ✓
- `Mail.Send` Delegated — Track 2 ✓ (granted for RM Flooring tenant)

### Azure redirect URIs configured
- `http://localhost/admin/settings/mail/callback` — local dev
- `https://fm.rmflooring.ca/admin/settings/mail/callback` — production
- `http://localhost/pages/settings/integrations/microsoft/callback` — calendar local
- `https://fm.rmflooring.ca/pages/settings/integrations/microsoft/callback` — calendar production

---

## RFM module summary
Full details in `Context/context_rfm.md`.

- RFMs (Requests for Measure) — scheduled site visits before producing an estimate
- Belong to an Opportunity; one opportunity can have many RFMs
- Routes: 6 routes nested under `pages/opportunities/{opportunity}/rfms/`
- MS365 calendar event created on RFM store (best-effort, non-blocking)
- Show page: clickable calendar event modal (estimator name + scheduled time)
- Job Transactions card on opportunity show: RFMs listed as clickable links
- Email notifications on create: estimator (default ON) + PM (default OFF), checkbox-driven
- Email notifications on edit: estimator auto-checked when key fields change, PM always OFF by default

### RFM open items
1. Sync MS365 calendar event when RFM is edited
2. Delete RFM route + cancel/delete calendar event on cancel/delete
3. RFM → Estimate creation shortcut from the show page

---

## Sales module summary
Sales are contractual job records created from approved estimates.

Confirmed sales routes:
- `pages.sales.index` → `/pages/sales`
- `pages.sales.show` → `/pages/sales/{sale}`
- `pages.sales.edit` → `/pages/sales/{sale}/edit`
- `pages.sales.profits.save-costs` → `POST /pages/sales/{sale}/profits/save-costs`

Financial display logic:
- Revised Contract Total = `revised_contract_total` → `locked_grand_total` → `grand_total`
- Locked if `locked_at` is not null
- Fully invoiced if `is_fully_invoiced = true`
- Partially invoiced if `invoiced_total > 0` and not fully invoiced

---

## Profits / cost tracking
Costs must flow: Catalog → Estimate → Sale

Required source fields:
- Products: `product_styles.cost_price`
- Labour: `labour_items.cost`
- Freight: `freight_items.cost_price`

Required persisted fields on line items: `cost_price`, `cost_total`

Shared profits modal: `resources/views/components/modals/profits-modal.blade.php`
- Opens from Estimate page or Sale page
- AJAX save wired separately per context (`pages.estimates.profits.save-costs` / `pages.sales.profits.save-costs`)

### Profits open items
- Recalculate sale-level rollups after modal save (total material/labour/freight cost, profit, margin)
- Improve modal UX (Profit $, Margin %, grouped subtotals, better save-state UI)
- Estimate-side cost flow: Blade row templates must include cost fields; JS autofill should populate from catalog; estimate → sale conversion must copy costs reliably

---

## Key file locations

| What | Where |
|------|-------|
| Routes | `routes/web.php` |
| Models | `app/Models/` |
| Admin controllers | `app/Http/Controllers/Admin/` |
| Pages controllers | `app/Http/Controllers/Pages/` |
| Mail classes | `app/Mail/` |
| Mail service | `app/Services/GraphMailService.php` |
| Email template service | `app/Services/EmailTemplateService.php` |
| Email template model | `app/Models/EmailTemplate.php` |
| User email templates page | `resources/views/pages/settings/email-templates.blade.php` |
| Admin email templates page | `resources/views/admin/settings/email-templates.blade.php` |
| Calendar service | `app/Services/GraphCalendarService.php` |
| Main layout | `resources/views/layouts/app.blade.php` |
| Email portal | `resources/views/admin/settings/mail.blade.php` |
| Profits page | `resources/views/pages/profits/show.blade.php` |
| Estimate builder JS | `public/assets/js/estimates/estimate.js` |

---

## Resume prompts for next chat

**To continue email work (RFM templates, HTML bodies, invoice send flow):**
> Read CLAUDE.md and Context/context_email_templates.md and Context/context_graph_mail.md. I want to continue the email system. One step at a time.

**To continue RFM module work:**
> Read CLAUDE.md and Context/context_rfm.md. Next priority: sync MS365 calendar event when an RFM is edited.

**To continue profits / cost tracking:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md. I want to improve the profits modal and sale-level cost rollups. One step at a time.

**To start a fresh feature:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md, then tell me the current state of the system before we begin.
