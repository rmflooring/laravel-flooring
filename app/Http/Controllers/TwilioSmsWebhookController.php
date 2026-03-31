<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Setting;
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

        $from = $request->input('From', '');
        $body = strtoupper(trim($request->input('Body', '')));

        Log::info('[Twilio Webhook] Inbound SMS', ['from' => $from, 'body' => $body]);

        // Only act on STOP (and variants Twilio may send)
        if (in_array($body, ['STOP', 'STOPALL', 'UNSUBSCRIBE', 'CANCEL', 'END', 'QUIT'])) {
            $this->markOptedOut($from);
        }

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
