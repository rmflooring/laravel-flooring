# Web Orders (rmflooring.ca online shop) — Dev Context
Created: 2026-10-08

## What it is
Customers buy products with a visible price on rmflooring.ca, pay by card (Stripe, on the website), and pick up at the
warehouse. The website POSTs each paid order to FM, which records it and handles fulfilment + customer notifications.
Stripe keys live only on the website; FM asks the website to refund.

## Flow
```
rmflooring.ca (Stripe paid) ── POST /api/web-orders (Bearer WEB_ORDERS_API_KEY) ──▶ WebOrderService::create()
   Customer (match email → phone → create) → Opportunity (status Approved, source "Website", auto-created)
   → Sale (channel=web, web_status=new, status=open, tax group "GPT") → SaleRoom "Web order" → SaleItems
   → Invoice (paid) + InvoiceRoom/Items → InvoicePayment (method 'online', reference = Stripe payment intent)
   → email + SMS customer ("received") → alert staff (Shop Settings → alert emails / SMS, one per line)
```
Idempotent on `sales.web_order_number`.

## Statuses (`sales.web_status`, `Sale::WEB_STATUSES`)
new → confirmed → ready → picked_up, or (from new/confirmed/ready) → refunded.
- **ready**: sets `web_ready_at`; customer told where/when to pick up + abandoned-order policy.
- **picked_up**: FIFO stock deduction (same as Quick Sale, warns never blocks); sale `completed` + locked.
- **refunded**: FM calls `POST {WEBSITE_URL}/api/orders/{web_order_number}/refund` (Bearer WEBSITE_API_KEY);
  only if that succeeds: sale `cancelled`, invoices `voided`, customer notified.
- `web-orders:remind` (daily 10:00 Vancouver): one reminder when an order has been ready ≥ `web_order_hold_days`.

## UI
- Sales → **Web Orders** (`pages.web-orders.*`, view: `view sales`, actions: `edit sales`); sidebar badge = open orders.
- Admin → Settings → **Shop**: alert emails/SMS, pickup hold days, storage-fee start + wording, forfeit day.
- Vendors → edit → **Return policy (online shop)** (`vendors.returns_accepted` null = not set up, `return_days`,
  `return_condition`, `restocking_fee_percent`, `special_orders_final_sale`, `return_policy_notes`). `Vendor::returnPolicy()`.

## Shop API additions
- `product-lines/{id}`: each style has `return_policy` (style vendor → line vendor; vendor name never exposed).
- `GET api/shop/return-policies`: policies grouped by brand (manufacturer) for the website's Returns page.
- `GET api/shop/store-policy`: hold / storage-fee / forfeit settings.

## Notifications
Text lives in `WebOrderService::messages()` (email subject/body + SMS). Sent via GraphMailService (type `web_order`)
and SmsService (respects `sms_enabled`, `mail_notifications_enabled`).

## .env
`WEB_ORDERS_API_KEY` (website → FM), `WEBSITE_URL`, `WEBSITE_API_KEY` (FM → website refunds).
