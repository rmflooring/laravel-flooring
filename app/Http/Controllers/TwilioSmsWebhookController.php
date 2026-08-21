<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\InboundSmsRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwilioSmsWebhookController extends Controller
{
    public function handle(Request $request, InboundSmsRecorder $recorder)
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

        Log::info('[Twilio Webhook] Inbound SMS', ['from' => $from, 'body' => $rawBody]);

        $recorder->record('twilio', $from, $rawBody);

        // Return empty TwiML — do not send a reply
        return response('<Response/>', 200)->header('Content-Type', 'text/xml');
    }

    private function validateSignature(Request $request): bool
    {
        $authToken = Setting::get('sms_auth_token', '');
        if (! $authToken) {
            return true;
        }

        try {
            $secret = decrypt($authToken);
        } catch (\Throwable) {
            $secret = $authToken;
        }

        $validator = new \Twilio\Security\RequestValidator($secret);
        $signature = $request->header('X-Twilio-Signature', '');
        $url       = rtrim(config('app.url'), '/') . $request->getRequestUri();

        // Attempt 1: middleware-transformed params (TrimStrings + ConvertEmptyStringsToNull may alter values)
        $valid = $validator->validate($signature, $url, $request->post());

        // Attempt 2: raw POST body parsed directly — bypasses TrimStrings/ConvertEmptyStringsToNull
        // which can change values and break Twilio's HMAC computation
        if (! $valid) {
            $rawParams = [];
            parse_str($request->getContent(), $rawParams);
            $valid = $validator->validate($signature, $url, $rawParams);
        }

        if (! $valid) {
            Log::warning('[Twilio Webhook] Signature validation failed', [
                'url'       => $url,
                'signature' => $signature,
                'ip'        => $request->ip(),
            ]);
        }

        return $valid;
    }
}
