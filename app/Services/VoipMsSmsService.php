<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

/**
 * Sends SMS via VoIP.ms's REST API, as the shop's main line (604-299-4447) — used only
 * for the SMS portal's two-way conversations. Reminders (RFM/samples/work orders) stay
 * on SmsService/Twilio; this is a deliberately separate, narrower send path.
 */
class VoipMsSmsService
{
    private const API_URL = 'https://voip.ms/api/v1/rest.php';

    // VoIP.ms rejects sendSMS bodies over 160 chars (sms_toolong); longer messages go
    // out as a single MMS instead, which allows up to 2048 chars.
    private const SMS_MAX_LENGTH = 160;

    /** Error from the most recent failed send(), for showing to the user. */
    public ?string $lastError = null;

    /**
     * Send an SMS. Returns true on success, false on failure.
     * Never throws — logs the result either way, same contract as SmsService::send().
     */
    public function send(string $to, string $body, string $type = 'general', ?Model $related = null): bool
    {
        $this->lastError = null;

        if (! Setting::get('voipms_enabled')) {
            $this->lastError = 'VoIP.ms SMS is disabled.';
            return false;
        }

        $username = Setting::get('voipms_api_username');
        $password = Setting::get('voipms_api_password');
        $did      = Setting::get('voipms_did');

        if (! $username || ! $password || ! $did) {
            $this->lastError = 'VoIP.ms SMS not configured — missing API credentials or DID.';
            SmsLog::create([
                'to'           => $to,
                'from'         => $did,
                'body'         => $body,
                'type'         => $type,
                'status'       => 'failed',
                'error'        => 'VoIP.ms SMS not configured — missing API credentials or DID.',
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => null,
            ]);
            return false;
        }

        $normalized = $this->normalizePhone($to);
        $method     = mb_strlen($body) > self::SMS_MAX_LENGTH ? 'sendMMS' : 'sendSMS';

        try {
            // VoIP.ms's REST API is inherently slow — confirmed real sendSMS/getDIDsInfo
            // calls routinely take 10-11s to respond (2026-08-21), so 15s left almost no
            // margin and caused intermittent cURL error 28 timeouts that looked like a
            // credentials problem but weren't. 30s gives real headroom.
            $response = Http::timeout(30)->get(self::API_URL, [
                'api_username' => $username,
                'api_password' => $password,
                'method'       => $method,
                'did'          => $did,
                'dst'          => $normalized,
                'message'      => $body,
            ]);

            $data = $response->json();

            if (($data['status'] ?? null) !== 'success') {
                throw new \RuntimeException('VoIP.ms API error: ' . ($data['status'] ?? $response->body()));
            }

            SmsLog::create([
                'to'           => $normalized,
                'from'         => $did,
                'body'         => $body,
                'type'         => $type,
                'status'       => 'sent',
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            SmsLog::create([
                'to'           => $normalized,
                'from'         => $did,
                'body'         => $body,
                'type'         => $type,
                'status'       => 'failed',
                'error'        => $e->getMessage(),
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => null,
            ]);

            return false;
        }
    }

    /**
     * VoIP.ms expects bare NANP digits (no +1) for did/dst — distinct from
     * SmsService::normalizePhone()'s E.164 output, which stays the canonical format
     * stored on sms_conversations.phone.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}
