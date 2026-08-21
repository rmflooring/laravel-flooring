<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Setting;
use App\Models\SmsConversation;
use App\Models\SmsMessage;
use Illuminate\Support\Facades\Log;

/**
 * Shared inbound-SMS handling for every channel (Twilio, VoIP.ms, ...): STOP/opt-out
 * keywords, conversation upsert (including which channel it's on), customer/opportunity
 * auto-linking, and the internal "new SMS" staff alert. Kept in one place rather than
 * duplicated per webhook controller so this logic — STOP handling especially — can't
 * drift between channels.
 */
class InboundSmsRecorder
{
    public function record(string $channel, string $rawPhone, string $body): void
    {
        $upper = strtoupper(trim($body));

        if (in_array($upper, ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'])) {
            $this->markOptedOut($rawPhone);
        }

        $this->recordInboundMessage($channel, $rawPhone, $body);
    }

    private function markOptedOut(string $rawPhone): void
    {
        $last10 = $this->last10Digits($rawPhone);
        if (! $last10) {
            return;
        }

        $customers = Customer::where(function ($q) use ($last10) {
            $q->whereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last10}"])
              ->orWhereRaw("REPLACE(REPLACE(REPLACE(mobile, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last10}"]);
        })
        ->where('sms_opted_out', false)
        ->get();

        foreach ($customers as $customer) {
            $customer->update([
                'sms_opted_out'    => true,
                'sms_opted_out_at' => now(),
            ]);
            Log::info('[Inbound SMS] Customer opted out via STOP', [
                'customer_id' => $customer->id,
                'phone'       => $rawPhone,
            ]);
        }

        if ($customers->isEmpty()) {
            Log::info('[Inbound SMS] STOP received but no matching customer found', [
                'phone' => $rawPhone,
            ]);
        }
    }

    private function recordInboundMessage(string $channel, string $rawPhone, string $body): void
    {
        if (! $body) {
            return;
        }

        $normalized = app(SmsService::class)->normalizePhone($rawPhone);

        $conversation = SmsConversation::updateOrCreate(
            ['phone' => $normalized],
            ['channel' => $channel, 'status' => 'active']
        );

        SmsMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'inbound',
            'body'            => $body,
        ]);

        $conversation->increment('unread_count');
        $conversation->update(['last_message_at' => now()]);

        if ($conversation->customer_id === null) {
            $last10 = $this->last10Digits($rawPhone);

            if ($last10) {
                $customer = Customer::where(function ($q) use ($last10) {
                    $q->whereRaw("REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last10}"])
                      ->orWhereRaw("REPLACE(REPLACE(REPLACE(mobile, '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last10}"]);
                })->first();

                if ($customer) {
                    $opportunity = Opportunity::where(function ($q) use ($customer) {
                            $q->where('parent_customer_id', $customer->id)
                              ->orWhere('job_site_customer_id', $customer->id);
                        })
                        ->where('is_active', true)
                        ->latest()
                        ->first();

                    $conversation->update([
                        'customer_id'    => $customer->id,
                        'opportunity_id' => $opportunity?->id,
                    ]);

                    Log::info('[Inbound SMS] Linked to customer', [
                        'channel'        => $channel,
                        'customer_id'    => $customer->id,
                        'opportunity_id' => $opportunity?->id,
                    ]);
                }
            }
        } elseif ($conversation->opportunity_id === null) {
            $opportunity = Opportunity::where(function ($q) use ($conversation) {
                    $q->where('parent_customer_id', $conversation->customer_id)
                      ->orWhere('job_site_customer_id', $conversation->customer_id);
                })
                ->where('is_active', true)
                ->latest()
                ->first();

            if ($opportunity) {
                $conversation->update(['opportunity_id' => $opportunity->id]);
            }
        }

        $this->sendInboundAlert($conversation, $rawPhone, $body);
    }

    private function sendInboundAlert(SmsConversation $conversation, string $rawPhone, string $body): void
    {
        if (! Setting::get('sms_inbound_alert_enabled')) {
            return;
        }

        $alertNumber = Setting::get('sms_inbound_alert_number', '');
        if (! $alertNumber) {
            return;
        }

        $conversation->load('customer');
        $from    = $conversation->customer ? $conversation->customer->name : $rawPhone;
        $preview = mb_strlen($body) > 100 ? mb_substr($body, 0, 97) . '...' : $body;

        app(SmsService::class)->send(
            $alertNumber,
            "FM SMS from {$from}: {$preview}",
            'inbound_alert'
        );
    }

    private function last10Digits(string $rawPhone): ?string
    {
        $digits = preg_replace('/\D+/', '', $rawPhone);
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        return $last10 ?: null;
    }
}
