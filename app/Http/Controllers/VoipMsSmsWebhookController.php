<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\InboundSmsRecorder;
use App\Services\VoipMsSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoipMsSmsWebhookController extends Controller
{
    public function handle(Request $request, InboundSmsRecorder $recorder, VoipMsSmsService $voipMs, string $secret)
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
        $id   = $request->input('id') ?: null;
        // {MEDIA} in the callback URL is a comma-separated list of picture links
        $mediaUrls = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('media', '')))));

        Log::info('[VoIP.ms Webhook] Inbound SMS', ['from' => $from, 'body' => $body, 'id' => $id, 'media' => count($mediaUrls)]);

        if (! $mediaUrls && $body !== '') {
            $recorder->record('voipms', $from, $body, voipmsId: $id);
        } else {
            // A picture (or a blank-bodied MMS, when the callback URL has no {MEDIA} and the
            // links have to be looked up by id). Downloading — and the ~10s VoIP.ms lookup —
            // happens after "ok" is sent, so VoIP.ms isn't left waiting on it.
            defer(function () use ($recorder, $voipMs, $from, $body, $id, $mediaUrls) {
                if (! $mediaUrls && $id) {
                    $mediaUrls = $voipMs->mmsMediaUrls($id);
                }

                $recorder->record('voipms', $from, $body, $mediaUrls, $id);
            });
        }

        // VoIP.ms expects the literal plain-text string "ok" back.
        return response('ok', 200)->header('Content-Type', 'text/plain');
    }
}
