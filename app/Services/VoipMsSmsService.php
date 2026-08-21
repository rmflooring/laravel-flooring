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

    /**
     * Send an SMS. Returns true on success, false on failure.
     * Never throws — logs the result either way, same contract as SmsService::send().
     */
    public function send(string $to, string $body, string $type = 'general', ?Model $related = null): bool
    {
        if (! Setting::get('voipms_enabled')) {
            return false;
        }

        $username = Setting::get('voipms_api_username');
        $password = Setting::get('voipms_api_password');
        $did      = Setting::get('voipms_did');

        if (! $username || ! $password || ! $did) {
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

        try {
            $response = Http::timeout(15)->get(self::API_URL, [
                'api_username' => $username,
                'api_password' => $password,
                'method'       => 'sendSMS',
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
