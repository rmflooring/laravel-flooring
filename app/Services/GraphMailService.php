<?php

namespace App\Services;

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
     * @param  string|null   $fromAddress  Overrides the configured shared mailbox address
     * @return bool
     */
    public function send(string|array $to, string $subject, string $body, ?string $fromAddress = null): bool
    {
        $from = $fromAddress
            ?? Setting::get('mail_from_address')
            ?? config('services.microsoft.mail_from_address', 'team@rmflooring.ca');

        $recipients = collect((array) $to)->map(fn ($address) => [
            'emailAddress' => ['address' => $address],
        ])->values()->all();

        try {
            $token = $this->getAppToken();

            $payload = [
                'message' => [
                    'subject' => $subject,
                    'body'    => [
                        'contentType' => 'Text',
                        'content'     => $body,
                    ],
                    'toRecipients' => $recipients,
                ],
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
                return true;
            }

            Log::error('[GraphMail] Send failed', [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('[GraphMail] Exception during send', [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }
}
