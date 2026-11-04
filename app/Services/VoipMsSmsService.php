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

    /**
     * Send an SMS, or an MMS when it's long or has pictures attached ($media: items
     * from SmsMediaStore). Returns true on success, false on failure.
     * Never throws — logs the result either way, same contract as SmsService::send().
     */
    public function send(string $to, string $body, string $type = 'general', ?Model $related = null, array $media = []): bool
    {
        $this->lastError   = null;
        $this->unconfirmed = false;

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
            // calls routinely take 10-11s to respond (2026-08-21), so 15s left almost no
            // margin and caused intermittent cURL error 28 timeouts that looked like a
            // credentials problem but weren't. 30s gives real headroom; uploads get more.
            $response = $media
                ? Http::timeout(60)->asMultipart()->post(self::API_URL, $params)
                : Http::timeout(30)->get(self::API_URL, $params);

            $data = $response->json();

            if (($data['status'] ?? null) !== 'success') {
                throw new \RuntimeException('VoIP.ms API error: ' . ($data['status'] ?? $response->body()));
            }

            SmsLog::create([
                'to'           => $normalized,
                'from'         => $did,
                'body'         => $logBody,
                'type'         => $type,
                'status'       => 'sent',
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => now(),
            ]);

            return true;
        } catch (\Throwable $e) {
            // cURL errors include the full request URL, which carries the API password.
            $error    = str_replace([$password, urlencode($password)], '***', $e->getMessage());
            $timedOut = $e instanceof ConnectionException && str_contains($e->getMessage(), 'cURL error 28');

            $this->lastError   = $error;
            $this->unconfirmed = $timedOut;
            SmsLog::create([
                'to'           => $normalized,
                'from'         => $did,
                'body'         => $logBody,
                'type'         => $type,
                'status'       => $timedOut ? 'unconfirmed' : 'failed',
                'error'        => $error,
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => null,
            ]);

            return $timedOut;
        }
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
