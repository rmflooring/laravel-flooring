<?php

namespace App\Services;

use App\Models\MailLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphMailService
{
    /**
     * Obtain an app-level access token via the client credentials grant.
     * No user is involved — uses the Azure app registration credentials only.
     */
    public function getAppToken(): string
    {
        $tenantId     = config('services.microsoft.tenant_id');
        $clientId     = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'scope'         => 'https://graph.microsoft.com/.default',
            ]
        );

        if (! $response->successful()) {
            Log::error('[GraphMail] Failed to obtain app token', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('GraphMailService: could not obtain app access token.');
        }

        return $response->json('access_token');
    }

    /**
     * Send an email via Microsoft Graph using the shared mailbox.
     *
     * @param  string|array  $to           Single address or array of addresses
     * @param  string        $subject
     * @param  string        $body         Plain text body
     * @param  string        $type         Log type label (rfm_notification, test, etc.)
     * @param  string|null   $fromAddress  Overrides the configured shared mailbox address
     * @return bool
     */
    public function send(
        string|array $to,
        string $subject,
        string $body,
        string $type = 'system',
        ?string $fromAddress = null,
    ): bool {
        // Respect the global notifications toggle
        if (! Setting::get('mail_notifications_enabled', '1')) {
            Log::info('[GraphMail] Notifications disabled — skipping send', [
                'to'      => $to,
                'subject' => $subject,
            ]);
            return false;
        }

        $from     = $fromAddress
            ?? Setting::get('mail_from_address')
            ?? config('services.microsoft.mail_from_address', 'reception@rmflooring.ca');

        $fromName = Setting::get('mail_from_name', 'RM Flooring Notifications');
        $replyTo  = Setting::get('mail_reply_to', 'noreply@rmflooring.ca');

        $recipients = collect((array) $to)->map(fn ($address) => [
            'emailAddress' => ['address' => $address],
        ])->values()->all();

        try {
            $token = $this->getAppToken();

            $message = [
                'subject' => $subject,
                'body'    => [
                    'contentType' => 'Text',
                    'content'     => $body,
                ],
                'toRecipients' => $recipients,
                'from'         => [
                    'emailAddress' => array_filter([
                        'address' => $from,
                        'name'    => $fromName,
                    ]),
                ],
                'replyTo' => [
                    ['emailAddress' => ['address' => $replyTo]],
                ],
            ];

            $payload = [
                'message'         => $message,
                'saveToSentItems' => true,
            ];

            $response = Http::withToken($token)
                ->acceptJson()
                ->post("https://graph.microsoft.com/v1.0/users/{$from}/sendMail", $payload);

            if ($response->successful()) {
                Log::info('[GraphMail] Email sent', [
                    'from'    => $from,
                    'to'      => $to,
                    'subject' => $subject,
                ]);

                foreach ((array) $to as $address) {
                    MailLog::create([
                        'to'      => $address,
                        'subject' => $subject,
                        'status'  => 'sent',
                        'type'    => $type,
                    ]);
                }

                return true;
            }

            $errorBody = $response->body();

            Log::error('[GraphMail] Send failed', [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $errorBody,
            ]);

            foreach ((array) $to as $address) {
                MailLog::create([
                    'to'      => $address,
                    'subject' => $subject,
                    'status'  => 'failed',
                    'type'    => $type,
                    'error'   => $errorBody,
                ]);
            }

            return false;

        } catch (\Throwable $e) {
            Log::error('[GraphMail] Exception during send', [
                'from'    => $from ?? '?',
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);

            foreach ((array) $to as $address) {
                MailLog::create([
                    'to'      => $address,
                    'subject' => $subject,
                    'status'  => 'failed',
                    'type'    => $type,
                    'error'   => $e->getMessage(),
                ]);
            }

            return false;
        }
    }
}
