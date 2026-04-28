<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessQboWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuickBooksWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $rawBody  = $request->getContent();
        $signature = $request->header('intuit-signature', '');

        if (! $this->verifySignature($rawBody, $signature)) {
            Log::warning('[QBO Webhook] Invalid signature — request rejected', [
                'ip' => $request->ip(),
            ]);
            abort(403);
        }

        $payload = json_decode($rawBody, true);

        if (empty($payload['eventNotifications'])) {
            return response()->noContent();
        }

        // Dispatch a job so we can respond 200 immediately — QBO requires a fast response
        ProcessQboWebhook::dispatch($payload);

        return response()->noContent();
    }

    private function verifySignature(string $rawBody, string $signature): bool
    {
        $verifierToken = config('services.quickbooks.webhook_verifier_token');

        if (! $verifierToken) {
            // Token not configured — allow through in dev/sandbox but log a warning
            Log::warning('[QBO Webhook] QBO_WEBHOOK_VERIFIER_TOKEN is not set — skipping signature check.');
            return true;
        }

        if (! $signature) {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $rawBody, $verifierToken, true));
        return hash_equals($expected, $signature);
    }
}
