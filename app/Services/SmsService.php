<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Model;
use Twilio\Rest\Client;

class SmsService
{
    protected ?Client $client = null;

    protected function getClient(): ?Client
    {
        $sid   = Setting::get('sms_account_sid');
        $token = Setting::get('sms_auth_token');

        if (! $sid || ! $token) {
            return null;
        }

        if (! $this->client) {
            $this->client = new Client($sid, $token);
        }

        return $this->client;
    }

    /**
     * Send an SMS. Returns true on success, false on failure.
     * Never throws — logs the result either way.
     */
    public function send(string $to, string $body, string $type = 'general', ?Model $related = null): bool
    {
        if (! Setting::get('sms_enabled')) {
            return false;
        }

        $from   = Setting::get('sms_from_number');
        $client = $this->getClient();

        if (! $client || ! $from) {
            SmsLog::create([
                'to'           => $to,
                'from'         => $from,
                'body'         => $body,
                'type'         => $type,
                'status'       => 'failed',
                'error'        => 'SMS not configured — missing credentials or from number.',
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => null,
            ]);
            return false;
        }

        $normalized = $this->normalizePhone($to);

        try {
            $client->messages->create($normalized, [
                'from' => $from,
                'body' => $body,
            ]);

            SmsLog::create([
                'to'           => $normalized,
                'from'         => $from,
                'body'         => $body,
                'type'         => $type,
                'status'       => 'sent',
                'related_type' => $related ? get_class($related) : null,
                'related_id'   => $related?->id,
                'sent_at'      => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            SmsLog::create([
                'to'           => $normalized,
                'from'         => $from,
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
     * Normalize a phone number to E.164 format for Twilio.
     * Assumes Canadian/US numbers if no country code present.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }

        // Already has country code or non-standard — prepend + if missing
        return str_starts_with($phone, '+') ? $phone : '+' . $digits;
    }
}
