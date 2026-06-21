<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Setting;
use App\Models\SmsConversation;
use App\Models\SmsMessage;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioSmsWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Validate Twilio signature
        if (! $this->validateSignature($request)) {
            Log::warning('[Twilio Webhook] Invalid signature — request rejected', [
                'ip' => $request->ip(),
            ]);
            abort(403);
        }

        $from    = $request->input('From', '');
        $rawBody = trim($request->input('Body', ''));
        $upper   = strtoupper($rawBody);

        Log::info('[Twilio Webhook] Inbound SMS', ['from' => $from, 'body' => $rawBody]);

        // Handle opt-out keywords first
        if (in_array($upper, ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'])) {
            $this->markOptedOut($from);
        }

        // Store in portal conversation
        $this->recordInboundMessage($from, $rawBody);

        // Return empty TwiML — do not send a reply
        return response('<Response/>', 200)->header('Content-Type', 'text/xml');
    }

    private function markOptedOut(string $rawPhone): void
    {
        // Normalize to last 10 digits for matching
        $digits = preg_replace('/\D+/', '', $rawPhone);
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        if (! $last10) {
            return;
        }

        // Find customers whose phone or mobile ends in these 10 digits
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
            Log::info('[Twilio Webhook] Customer opted out via STOP', [
                'customer_id' => $customer->id,
                'phone'       => $rawPhone,
            ]);
        }

        if ($customers->isEmpty()) {
            Log::info('[Twilio Webhook] STOP received but no matching customer found', [
                'phone' => $rawPhone,
            ]);
        }
    }

    private function recordInboundMessage(string $rawPhone, string $body): void
    {
        if (! $body) {
            return;
        }

        $normalized = app(SmsService::class)->normalizePhone($rawPhone);

        $conversation = SmsConversation::firstOrCreate(
            ['phone' => $normalized],
            ['status' => 'active']
        );

        SmsMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'inbound',
            'body'            => $body,
        ]);

        $conversation->increment('unread_count');
        $conversation->update(['last_message_at' => now()]);

        // Auto-link to a customer if not already linked
        if ($conversation->customer_id === null) {
            $digits = preg_replace('/\D+/', '', $rawPhone);
            $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

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

                    Log::info('[Twilio Webhook] Inbound SMS linked to customer', [
                        'customer_id'    => $customer->id,
                        'opportunity_id' => $opportunity?->id,
                    ]);
                }
            }
        } elseif ($conversation->opportunity_id === null) {
            // Customer already linked but no opportunity — try to find one now
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
    }

    private function validateSignature(Request $request): bool
    {
        $authToken = Setting::get('sms_auth_token', '');
        if (! $authToken) {
            // No token configured — skip validation (dev/test environments)
            return true;
        }

        try {
            $secret    = decrypt($authToken);
        } catch (\Throwable) {
            $secret = $authToken;
        }

        $validator = new \Twilio\Security\RequestValidator($secret);
        $signature = $request->header('X-Twilio-Signature', '');
        $url       = $request->fullUrl();
        $params    = $request->post();

        return $validator->validate($signature, $url, $params);
    }
}
