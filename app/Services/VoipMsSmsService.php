<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    // sendMMS takes up to three attachments (media1..media3)
    public const MAX_MEDIA = 3;

    /** Error from the most recent failed send(), for showing to the user. */
    public ?string $lastError = null;

    /**
     * True when the most recent send() timed out waiting for VoIP.ms. VoIP.ms often
     * still delivers these (seen 2026-10-29: two "failed" sends both arrived), so
     * send() returns true and callers should flag the message rather than resend.
     */
    public bool $unconfirmed = false;

    /** The sms_log row written by the most recent send(), for VerifyVoipMsSend. */
    public ?int $lastLogId = null;

    // How long to wait for sendSMS. Must stay under nginx's 60s fastcgi_read_timeout
    // for fm.rmflooring.ca, or the portal page itself errors out (504).
    private const SEND_TIMEOUT = 45;

    /**
     * Send an SMS, or an MMS when it's long or has pictures attached ($media: items
     * from SmsMediaStore). Returns true on success, false on failure.
     * Never throws — logs the result either way, same contract as SmsService::send().
     */
    public function send(string $to, string $body, string $type = 'general', ?Model $related = null, array $media = []): bool
    {
        $this->lastError   = null;
        $this->unconfirmed = false;
        $this->lastLogId   = null;

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
        $media      = array_slice($media, 0, self::MAX_MEDIA);
        $method     = ($media || mb_strlen($body) > self::SMS_MAX_LENGTH) ? 'sendMMS' : 'sendSMS';
        $logBody    = $media ? trim($body . ' [+' . count($media) . ' picture' . (count($media) > 1 ? 's' : '') . ']') : $body;

        $params = [
            'api_username' => $username,
            'api_password' => $password,
            'method'       => $method,
            'did'          => $did,
            'dst'          => $normalized,
            'message'      => $body,
        ];

        // Attachments go as base64 data URIs (VoIP.ms also accepts a public URL, but the
        // files are private). That's far too big for a query string, so MMS-with-media
        // is POSTed instead of the usual GET — as multipart: a urlencoded POST to
        // rest.php gets treated as a SOAP call and rejected "Bad Request" (2026-11-04).
        $store = app(SmsMediaStore::class);
        foreach (array_values($media) as $i => $item) {
            $params['media' . ($i + 1)] = 'data:' . $item['mime'] . ';base64,' . base64_encode((string) $store->contents($item));
        }

        try {
            // VoIP.ms's REST API is inherently slow — confirmed real sendSMS/getDIDsInfo
            // calls routinely take 10-11s to respond (2026-08-21), and on bad days 20-30s+
            // (2026-11-27: a bare request took 22s and a send hit the old 30s limit, yet
            // was delivered). Timeouts are now checked afterwards by VerifyVoipMsSend.
            $response = $media
                ? Http::timeout(60)->asMultipart()->post(self::API_URL, $params)
                : Http::timeout(self::SEND_TIMEOUT)->get(self::API_URL, $params);

            $data = $response->json();

            if (($data['status'] ?? null) !== 'success') {
                throw new \RuntimeException('VoIP.ms API error: ' . ($data['status'] ?? $response->body()));
            }

            $this->lastLogId = SmsLog::create([
                'to'           => $normalized,
                'from'         => $did,
                'body'         => $logBody,
                'type'         => $type,
                'status'       => 'sent',
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => now(),
            ])->id;

            return true;
        } catch (\Throwable $e) {
            $timedOut = $e instanceof ConnectionException && str_contains($e->getMessage(), 'cURL error 28');
            $error    = $timedOut
                ? "VoIP.ms didn't respond in time — the message may still have been delivered. Checking with VoIP.ms automatically."
                : $this->cleanError($e->getMessage(), $password);

            $this->lastError   = $error;
            $this->unconfirmed = $timedOut;
            $this->lastLogId   = SmsLog::create([
                'to'           => $normalized,
                'from'         => $did,
                'body'         => $logBody,
                'type'         => $type,
                'status'       => $timedOut ? 'unconfirmed' : 'failed',
                'error'        => $error,
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => null,
            ])->id;

            return $timedOut;
        }
    }

    /**
     * cURL errors end with "(see https://curl.se/...) for <full request URL>", and that
     * URL carries the API username, password and message — keep only the useful part.
     */
    private function cleanError(string $message, string $password): string
    {
        $message = preg_replace('~\s*\(see https?://\S+\)~', '', $message);
        $message = preg_replace('~\s+for https?://\S+~', '', $message);

        return str_replace([$password, urlencode($password)], '***', $message);
    }

    /**
     * Did VoIP.ms actually send this logged message? Looks it up in the line's sent
     * history (SMS, then MMS — long or picture messages go out as MMS).
     * Returns true/false, or null when VoIP.ms couldn't be asked (try again later).
     */
    public function wasSent(SmsLog $log): ?bool
    {
        $since = $log->created_at->copy()->utc()->subMinute();
        $body  = $this->comparable(preg_replace('/\s*\[\+\d+ pictures?\]$/', '', (string) $log->body));

        foreach (['getSMS', 'getMMS'] as $method) {
            $rows = $this->sentHistory($method, $log->to, $since);

            if ($rows === null) {
                return null;
            }

            foreach ($rows as $row) {
                $sentAt = \Carbon\Carbon::parse($row['date'] ?? '1970-01-01', 'UTC');

                if ($sentAt->gte($since) && ($body === '' || $this->comparable($row['message'] ?? '') === $body)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Sent messages to one number since a time, or null if VoIP.ms couldn't be reached. */
    private function sentHistory(string $method, string $contact, \Carbon\Carbon $since): ?array
    {
        $username = Setting::get('voipms_api_username');
        $password = Setting::get('voipms_api_password');
        $did      = Setting::get('voipms_did');

        if (! $username || ! $password || ! $did) {
            return null;
        }

        try {
            $response = Http::timeout(60)->get(self::API_URL, [
                'api_username' => $username,
                'api_password' => $password,
                'method'       => $method,
                'did'          => $did,
                'type'         => 0, // sent
                'contact'      => $this->normalizePhone($contact),
                'from'         => $since->toDateString(),
                'to'           => now('UTC')->toDateString(),
                'timezone'     => 0, // dates in UTC
                'limit'        => 100,
            ]);
            $data = $response->json();
        } catch (\Throwable $e) {
            Log::warning('[VoIP.ms] ' . $method . ' lookup failed', ['error' => $this->cleanError($e->getMessage(), $password)]);
            return null;
        }

        $status = $data['status'] ?? null;

        if ($status === 'success') {
            return $data['sms'] ?? [];
        }

        if ($status === 'no_sms') {
            return [];
        }

        Log::warning('[VoIP.ms] ' . $method . ' lookup returned an unexpected status', [
            'status' => $status,
            'http'   => $response->status(),
            'body'   => mb_substr($response->body(), 0, 200),
        ]);
        return null;
    }

    private function comparable(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Media links on one received MMS, looked up by the {ID} VoIP.ms put in the callback.
     * Used when the callback URL doesn't carry {MEDIA} itself.
     *
     * @return string[]
     */
    public function mmsMediaUrls(string $mmsId): array
    {
        $row = $this->getMms(['mms' => $mmsId, 'media' => 1])[0] ?? null;

        return array_values(array_filter($row['media'] ?? []));
    }

    /**
     * Received MMS on the shop line between two dates (Y-m-d, VoIP.ms account time),
     * newest first — for importing pictures that came in before media was supported.
     */
    public function receivedMms(string $from, string $to): array
    {
        return $this->getMms([
            'type'  => 1,
            'from'  => $from,
            'to'    => $to,
            'limit' => 1000,
            'media' => 1,
        ]);
    }

    private function getMms(array $params): array
    {
        $username = Setting::get('voipms_api_username');
        $password = Setting::get('voipms_api_password');
        $did      = Setting::get('voipms_did');

        if (! $username || ! $password || ! $did) {
            return [];
        }

        try {
            $data = Http::timeout(30)->get(self::API_URL, array_merge([
                'api_username' => $username,
                'api_password' => $password,
                'method'       => 'getMMS',
                'did'          => $did,
            ], $params))->json();
        } catch (\Throwable $e) {
            Log::warning('[VoIP.ms] getMMS failed', [
                'error' => str_replace([$password, urlencode($password)], '***', $e->getMessage()),
            ]);
            return [];
        }

        // "no_sms" is VoIP.ms's status for an empty result
        return ($data['status'] ?? null) === 'success' ? ($data['sms'] ?? []) : [];
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
