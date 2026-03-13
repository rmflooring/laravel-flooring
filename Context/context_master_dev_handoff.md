# Master Dev Handoff Context — RM Flooring / Floor Manager

Owner: Richard
Updated: 2026-03-13

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

### Track 2 — Infrastructure built, wiring pending
- Per-user delegated OAuth — each staff member's personal `@rmflooring.ca` MS365 account
- Admin connects each user from `/admin/settings/mail` Track 2 table (Connect button → OAuth flow)
- Token stored encrypted on `microsoft_accounts` (`mail_connected`, `mail_connected_at` columns added)
- `GraphMailService::sendAsUser(User $user, ...)` is ready to call
- Auto token refresh built in; marks `mail_connected=false` if refresh fails
- All sends logged to `mail_log` with `track=2` and `sent_from=user_email`
- **Not yet wired into**: estimates, invoices

### Fallback pattern (for when wiring Track 2 into estimates/invoices)
```php
$sent = $user->microsoftAccount?->mail_connected
    ? $mailer->sendAsUser($user, $to, $subject, $body, $type)
    : false;
if (! $sent) {
    $mailer->send($to, $subject, $body, $type); // Track 1 fallback
}
```

### Azure app permissions needed (same app registration for both)
- `Mail.Send` Application — Track 1 (already granted ✓)
- `Mail.Send` Delegated — Track 2 (must be added in Azure portal + admin consent before Track 2 sends will work)

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
| Calendar service | `app/Services/GraphCalendarService.php` |
| Main layout | `resources/views/layouts/app.blade.php` |
| Email portal | `resources/views/admin/settings/mail.blade.php` |
| Profits page | `resources/views/pages/profits/show.blade.php` |
| Estimate builder JS | `public/assets/js/estimates/estimate.js` |

---

## Resume prompts for next chat

**To continue Track 2 email wiring (estimates/invoices):**
> Read CLAUDE.md and Context/context_graph_mail.md. I want to wire Track 2 per-user email into the estimate sending flow. The sendAsUser() method is built and ready. One step at a time.

**To continue RFM module work:**
> Read CLAUDE.md and Context/context_rfm.md. Next priority: sync MS365 calendar event when an RFM is edited.

**To continue profits / cost tracking:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md. I want to improve the profits modal and sale-level cost rollups. One step at a time.

**To start a fresh feature:**
> Read CLAUDE.md and Context/context_master_dev_handoff.md, then tell me the current state of the system before we begin.
