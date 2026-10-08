<?php

namespace App\Services;

use App\Http\Controllers\Admin\ShopSettingsController;
use App\Models\Customer;
use App\Models\InventoryReceipt;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceRoom;
use App\Models\Opportunity;
use App\Models\OpportunitySource;
use App\Models\ProductStyle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleRoom;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Online orders from rmflooring.ca: paid by Stripe on the website, picked up at the warehouse.
 *
 * Each order becomes Customer → Opportunity (source "Website") → Sale (channel = web) with a paid
 * Invoice + "Online (Stripe)" payment, modelled on Quick Sale. Staff move it through
 * New → Confirmed → Ready for pickup → Picked up (stock deducted, sale completed), or Refunded
 * (the website issues the Stripe refund). Customers are emailed and texted at each step.
 */
class WebOrderService
{
    public const TAX_GROUP_NAME = 'GPT';   // GST + PST

    public function __construct(private GraphMailService $mail, private SmsService $sms) {}

    /** Creates the sale from the website's order payload. Idempotent on order_number. */
    public function create(array $order): Sale
    {
        if ($existing = Sale::where('web_order_number', $order['order_number'])->first()) {
            return $existing;
        }

        $sale = DB::transaction(function () use ($order) {
            $c = $order['customer'];

            // 1. Customer: match by email, then phone, else create
            $digits   = substr(preg_replace('/\D/', '', (string) ($c['phone'] ?? '')), -10);
            $customer = (! empty($c['email']) ? Customer::where('email', $c['email'])->first() : null)
                ?? ($digits ? Customer::whereRaw("RIGHT(REGEXP_REPLACE(COALESCE(phone, ''), '[^0-9]', ''), 10) = ?", [$digits])->first() : null)
                ?? Customer::create([
                    'name'          => $c['name'],
                    'company_name'  => $c['name'],
                    'email'         => $c['email'] ?? null,
                    'phone'         => $c['phone'] ?? null,
                    'customer_type' => 'individual',
                    'notes'         => 'Created from an online order on rmflooring.ca',
                ]);

            // 2. Opportunity (source "Website")
            $source = OpportunitySource::firstOrCreate(['name' => 'Website'], ['is_active' => true]);
            $opportunity = Opportunity::create([
                'parent_customer_id'    => $customer->id,
                'job_site_customer_id'  => $customer->id,
                'job_no'                => 'Web order ' . $order['order_number'],
                'status'                => 'Approved',
                'opportunity_source_id' => $source->id,
                'is_active'             => true,
            ]);

            // 3. Tax (GST + PST group)
            [$taxGroupId, $taxRate] = $this->taxGroup();
            $pretax    = round(collect($order['items'])->sum(fn ($i) => round((float) $i['quantity'] * (float) $i['unit_price'], 2)), 2);
            $taxAmount = round($pretax * $taxRate / 100, 2);
            $total     = round($pretax + $taxAmount, 2);

            $notes = 'Online order ' . $order['order_number'] . ' — paid by card on rmflooring.ca, pickup at the warehouse.';
            if (abs($total - (float) $order['total']) > 0.02) {
                $notes .= " ⚠ Website charged \${$order['total']}, FM calculates \${$total} — please check.";
            }
            if (! empty($order['notes'])) {
                $notes .= "\nCustomer note: " . $order['notes'];
            }

            // 4. Sale
            $sale = Sale::create([
                'opportunity_id'        => $opportunity->id,
                'customer_id'           => $customer->id,
                'channel'               => 'web',
                'web_order_number'      => $order['order_number'],
                'web_status'            => 'new',
                'web_status_at'         => now(),
                'web_payment_reference' => $order['payment']['reference'] ?? null,
                'web_order_data'        => $order,
                'status'                => 'open',
                'customer_name'         => $c['name'],
                'homeowner_name'        => $c['name'],
                'job_name'              => 'Web order ' . $order['order_number'],
                'job_phone'             => $c['phone'] ?? null,
                'job_email'             => $c['email'] ?? null,
                'tax_group_id'          => $taxGroupId,
                'tax_rate_percent'      => $taxRate,
                'subtotal_materials'    => $pretax,
                'pretax_total'          => $pretax,
                'tax_amount'            => $taxAmount,
                'grand_total'           => $total,
                'notes'                 => $notes,
            ]);

            $room = SaleRoom::create(['sale_id' => $sale->id, 'room_name' => 'Web order', 'sort_order' => 0]);

            $invoice = Invoice::create([
                'sale_id'     => $sale->id,
                'status'      => 'paid',
                'due_date'    => today(),
                'subtotal'    => $pretax,
                'tax_amount'  => $taxAmount,
                'grand_total' => $total,
                'amount_paid' => (float) ($order['payment']['amount'] ?? $total),
            ]);
            $invoiceRoom = InvoiceRoom::create(['invoice_id' => $invoice->id, 'sale_room_id' => $room->id, 'name' => 'Web order', 'sort_order' => 0]);

            // 5. Items
            foreach (array_values($order['items']) as $idx => $item) {
                $style     = ! empty($item['product_style_id']) ? ProductStyle::with('productLine')->find($item['product_style_id']) : null;
                $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);

                $saleItem = SaleItem::create([
                    'sale_id'           => $sale->id,
                    'sale_room_id'      => $room->id,
                    'item_type'         => 'material',
                    'quantity'          => $item['quantity'],
                    'unit'              => $item['unit'] ?? null,
                    'sell_price'        => $item['unit_price'],
                    'line_total'        => $lineTotal,
                    'sort_order'        => $idx,
                    'product_line_id'   => $style?->product_line_id ?? ($item['product_line_id'] ?? null),
                    'product_style_id'  => $style?->id,
                    'manufacturer'      => $style?->productLine?->manufacturer ?? ($item['brand'] ?? null),
                    'style'             => $style?->name ?? $item['label'],
                    'color_item_number' => $style?->color,
                    'description'       => $item['label'],
                    'customer_notes'    => ! empty($item['boxes']) ? "{$item['boxes']} boxes" : null,
                ]);

                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'invoice_room_id' => $invoiceRoom->id,
                    'sale_item_id'    => $saleItem->id,
                    'item_type'       => 'material',
                    'label'           => $item['label'],
                    'quantity'        => $item['quantity'],
                    'unit'            => $item['unit'] ?? null,
                    'sell_price'      => $item['unit_price'],
                    'line_total'      => $lineTotal,
                    'tax_rate'        => $taxRate,
                    'tax_amount'      => round($lineTotal * $taxRate / 100, 2),
                    'tax_group_id'    => $taxGroupId,
                    'sort_order'      => $idx,
                ]);
            }

            InvoicePayment::create([
                'invoice_id'       => $invoice->id,
                'amount'           => (float) ($order['payment']['amount'] ?? $total),
                'payment_date'     => today(),
                'payment_method'   => 'online',
                'reference_number' => $order['payment']['reference'] ?? null,
            ]);

            return $sale;
        });

        $this->notifyCustomer($sale, 'new');
        $this->alertStaff($sale);

        return $sale;
    }

    /** Moves an order to the next status and tells the customer. */
    public function transition(Sale $sale, string $to): void
    {
        abort_unless($sale->channel === 'web', 404);

        $allowed = [
            'new'       => ['confirmed', 'ready', 'refunded'],
            'confirmed' => ['ready', 'refunded'],
            'ready'     => ['picked_up', 'refunded'],
        ];
        if (! in_array($to, $allowed[$sale->web_status] ?? [], true)) {
            throw new RuntimeException("Can't move an order from “{$sale->web_status}” to “{$to}”.");
        }

        if ($to === 'refunded') {
            $this->refundOnWebsite($sale);   // throws if the refund didn't go through — nothing else changes
        }

        DB::transaction(function () use ($sale, $to) {
            $sale->update(array_filter([
                'web_status'    => $to,
                'web_status_at' => now(),
                'web_ready_at'  => $to === 'ready' ? now() : null,
            ]) + ($to === 'refunded' ? ['status' => 'cancelled'] : []));

            if ($to === 'picked_up') {
                $this->deductInventory($sale);
                $sale->update(['status' => 'completed', 'locked_at' => now(), 'locked_by' => auth()->id()]);
            }

            if ($to === 'refunded') {
                $sale->invoices()->update(['status' => 'voided']);
            }
        });

        $this->notifyCustomer($sale->fresh(), $to);
    }

    /** Daily: remind customers whose order has been ready longer than the hold period. */
    public function sendPickupReminders(): int
    {
        $holdDays = (int) Setting::get('web_order_hold_days', 14);
        $due = Sale::where('channel', 'web')->where('web_status', 'ready')
            ->whereNull('web_reminder_sent_at')
            ->where('web_ready_at', '<=', now()->subDays($holdDays))
            ->get();

        foreach ($due as $sale) {
            $this->notifyCustomer($sale, 'reminder');
            $sale->update(['web_reminder_sent_at' => now()]);
        }

        return $due->count();
    }

    // ── Notifications ────────────────────────────────────────────────────────

    public function messages(Sale $sale, string $event): array
    {
        $n       = $sale->web_order_number;
        $pickup  = $sale->web_order_data['pickup'] ?? [];
        $where   = $pickup['location'] ?? '120 Glacier St, Unit 4, Coquitlam';
        $hours   = $pickup['hours'] ?? 'Mon–Fri 8:30 am – 4:30 pm';
        $hold    = (int) Setting::get('web_order_hold_days', 14);
        $fee     = (int) Setting::get('web_order_storage_fee_after_days', 30);
        $feeText = Setting::get('web_order_storage_fee_text', 'a storage fee');
        $forfeit = (int) Setting::get('web_order_forfeit_after_days', 90);
        $policy  = "Orders not picked up within {$fee} days of being ready may be charged {$feeText}, and orders not picked up within {$forfeit} days are forfeited without refund.";
        $total   = '$' . number_format((float) $sale->grand_total, 2);

        return match ($event) {
            'new' => [
                "Thanks for your order {$n} — RM Flooring",
                "Thanks for your order! We've received your payment of {$total} for order {$n}. We'll confirm everything within one business day and let you know as soon as it's ready for pickup at {$where}.",
                "RM Flooring: Thanks for order {$n}! We'll confirm within 1 business day and text you when it's ready for pickup.",
            ],
            'confirmed' => [
                "Order {$n} confirmed — RM Flooring",
                "Good news — order {$n} is confirmed. We'll let you know as soon as it's ready for pickup.",
                "RM Flooring: Order {$n} is confirmed. We'll text you when it's ready for pickup.",
            ],
            'ready' => [
                "Order {$n} is ready for pickup — RM Flooring",
                "Your order {$n} is ready for pickup at {$where} ({$hours}). Please bring your order number. We'll hold it for {$hold} days. {$policy}",
                "RM Flooring: Order {$n} is ready for pickup at {$where} ({$hours}). Please bring your order number.",
            ],
            'reminder' => [
                "Reminder: order {$n} is waiting for pickup — RM Flooring",
                "Just a reminder that order {$n} is still waiting for you at {$where} ({$hours}). {$policy}",
                "RM Flooring reminder: order {$n} is still waiting for pickup at {$where}. Storage fees may apply after {$fee} days.",
            ],
            'picked_up' => [
                "Thanks for picking up order {$n} — RM Flooring",
                "Thanks for picking up order {$n}. Enjoy your new floors — and if you need installation, we're happy to help.",
                null,
            ],
            'refunded' => [
                "Order {$n} refunded — RM Flooring",
                "We're sorry — we weren't able to fulfil order {$n}. A full refund of {$total} has been issued to your card; it can take 5–10 business days to appear. Please call us if you'd like help choosing an alternative.",
                "RM Flooring: We couldn't fulfil order {$n}, so we've refunded {$total} to your card (5–10 business days).",
            ],
        };
    }

    private function notifyCustomer(Sale $sale, string $event): void
    {
        [$subject, $body, $sms] = $this->messages($sale, $event);

        try {
            if ($sale->job_email) {
                $this->mail->send($sale->job_email, $subject, $body, 'web_order', null, null, null, null, $sale->id, Sale::class);
            }
            if ($sms && $sale->job_phone) {
                $this->sms->send($sale->job_phone, $sms, 'web_order', $sale);
            }
        } catch (\Throwable $e) {
            Log::warning('[WebOrder] Customer notification failed', ['sale' => $sale->id, 'event' => $event, 'error' => $e->getMessage()]);
        }
    }

    private function alertStaff(Sale $sale): void
    {
        $items = collect($sale->web_order_data['items'] ?? [])->map(fn ($i) => '• ' . $i['label'] . (! empty($i['boxes']) ? " — {$i['boxes']} boxes" : " — {$i['quantity']} {$i['unit']}"))->implode("\n");
        $total = '$' . number_format((float) $sale->grand_total, 2);
        $url   = route('pages.web-orders.show', $sale);

        try {
            foreach (ShopSettingsController::lines(Setting::get('web_order_alert_emails', '')) as $email) {
                $this->mail->send($email, "New web order {$sale->web_order_number} — {$sale->customer_name} ({$total})",
                    "New paid online order {$sale->web_order_number} from {$sale->customer_name} ({$sale->job_phone}, {$sale->job_email}).\n\n{$items}\n\nTotal paid: {$total}\n\nPlease confirm availability within one business day: {$url}",
                    'web_order_alert', null, null, null, null, $sale->id, Sale::class);
            }
            foreach (ShopSettingsController::lines(Setting::get('web_order_alert_sms', '')) as $phone) {
                $this->sms->send($phone, "New web order {$sale->web_order_number}: {$sale->customer_name}, {$total}. Confirm in FM → Web Orders.", 'web_order_alert', $sale);
            }
        } catch (\Throwable $e) {
            Log::warning('[WebOrder] Staff alert failed', ['sale' => $sale->id, 'error' => $e->getMessage()]);
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Asks the website to refund the Stripe payment (the Stripe keys live on the website). */
    private function refundOnWebsite(Sale $sale): void
    {
        $url = rtrim((string) config('services.website.url'), '/');
        $key = (string) config('services.website.api_key');
        if (! $url || ! $key) {
            throw new RuntimeException('Website refund link isn’t configured (WEBSITE_URL / WEBSITE_API_KEY).');
        }

        $response = Http::timeout(30)->withToken($key)->acceptJson()
            ->post("{$url}/api/orders/{$sale->web_order_number}/refund");

        if (! $response->successful()) {
            throw new RuntimeException('Refund failed: ' . ($response->json('message') ?? "HTTP {$response->status()}"));
        }
    }

    /** @return array{0: int, 1: float} [tax group id, combined rate %] */
    private function taxGroup(): array
    {
        $group = DB::table('tax_rate_groups')->where('name', self::TAX_GROUP_NAME)->first()
            ?? throw new RuntimeException('Tax group “' . self::TAX_GROUP_NAME . '” not found.');

        $rateCol = \Illuminate\Support\Facades\Schema::hasColumn('tax_rates', 'tax_rate_sales') ? 'tax_rate_sales' : 'sales_rate';
        $rate    = (float) DB::table('tax_rate_group_items as tgi')
            ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
            ->where('tgi.tax_rate_group_id', $group->id)
            ->sum("tr.{$rateCol}");

        return [$group->id, $rate];
    }

    /** FIFO stock deduction on pickup — same approach as Quick Sale (warns, never blocks). */
    private function deductInventory(Sale $sale): void
    {
        foreach ($sale->rooms()->with('items')->get()->flatMap->items as $item) {
            if (! $item->product_style_id) {
                continue;
            }
            $remaining = (float) $item->quantity;
            $receipts  = InventoryReceipt::with(['allocations', 'transactions'])
                ->where('product_style_id', $item->product_style_id)
                ->orderBy('received_date')->orderBy('id')->get();

            foreach ($receipts as $receipt) {
                if ($remaining <= 0) {
                    break;
                }
                $deduct = min($receipt->available_qty, $remaining);
                if ($deduct <= 0) {
                    continue;
                }
                InventoryTransaction::create([
                    'inventory_receipt_id' => $receipt->id,
                    'type'                 => 'fulfilled',
                    'quantity'             => -$deduct,
                    'reference_type'       => Sale::class,
                    'reference_id'         => $sale->id,
                    'note'                 => "Web order {$sale->web_order_number}",
                    'created_by_user_id'   => auth()->id(),
                ]);
                $remaining -= $deduct;
            }
        }
    }
}
