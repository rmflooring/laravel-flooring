<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\InboundSmsRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoipMsSmsWebhookController extends Controller
{
    public function handle(Request $request, InboundSmsRecorder $recorder, string $secret)
    {
        $expected = Setting::get('voipms_webhook_secret', '');

        if (! $expected || ! hash_equals($expected, $secret)) {
            Log::warning('[VoIP.ms Webhook] Invalid secret — request rejected', [
                'ip' => $request->ip(),
            ]);
            abort(403);
        }

        $from = $request->input('from', '');
        $body = trim($request->input('message', ''));

        Log::info('[VoIP.ms Webhook] Inbound SMS', ['from' => $from, 'body' => $body]);

        $recorder->record('voipms', $from, $body);

        // VoIP.ms expects the literal plain-text string "ok" back.
        return response('ok', 200)->header('Content-Type', 'text/plain');
    }
}
