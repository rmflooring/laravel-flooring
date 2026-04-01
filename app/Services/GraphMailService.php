<?php

namespace App\Services;

use App\Models\MailLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphMailService
{
    // =========================================================================
    // Track 1 — Shared Mailbox (app-level client credentials)
    // =========================================================================

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
     * Send an email via Microsoft Graph using the shared mailbox (Track 1).
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
        ?array $attachment = null,
        ?array $cc = null,
        ?string $icsContent = null,
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

            if (! empty($cc)) {
                $message['ccRecipients'] = collect($cc)->map(fn ($address) => [
                    'emailAddress' => ['address' => $address],
                ])->values()->all();
            }

            if ($icsContent) {
                // Send as raw MIME so the ICS is an inline text/calendar part,
                // which Outlook/Exchange will render with Accept/Decline buttons.
                $mime = $this->buildMimeWithCalendar(
                    from:       $from,
                    fromName:   $fromName,
                    to:         (array) $to,
                    replyTo:    $replyTo,
                    subject:    $subject,
                    body:       $body,
                    icsContent: $icsContent,
                    attachment: $attachment,
                    cc:         $cc ?? [],
                );

                $response = Http::withToken($token)
                    ->withHeaders(['Content-Type' => 'text/plain'])
                    ->withBody($mime, 'text/plain')
                    ->post("https://graph.microsoft.com/v1.0/users/{$from}/sendMail");
            } else {
                $attachments = [];
                if ($attachment) {
                    $attachments[] = [
                        '@odata.type'  => '#microsoft.graph.fileAttachment',
                        'name'         => $attachment['filename'],
                        'contentType'  => 'application/pdf',
                        'contentBytes' => $attachment['content'],
                    ];
                }
                if (! empty($attachments)) {
                    $message['attachments'] = $attachments;
                }

                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post("https://graph.microsoft.com/v1.0/users/{$from}/sendMail", [
                        'message'         => $message,
                        'saveToSentItems' => true,
                    ]);
            }

            if ($response->successful()) {
                Log::info('[GraphMail] Track 1 email sent', [
                    'from'    => $from,
                    'to'      => $to,
                    'subject' => $subject,
                ]);

                foreach ((array) $to as $address) {
                    MailLog::create([
                        'to'        => $address,
                        'subject'   => $subject,
                        'status'    => 'sent',
                        'type'      => $type,
                        'track'     => 1,
                        'sent_from' => $from,
                    ]);
                }

                return true;
            }

            $errorBody = $response->body();

            Log::error('[GraphMail] Track 1 send failed', [
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $errorBody,
            ]);

            foreach ((array) $to as $address) {
                MailLog::create([
                    'to'        => $address,
                    'subject'   => $subject,
                    'status'    => 'failed',
                    'type'      => $type,
                    'track'     => 1,
                    'sent_from' => $from,
                    'error'     => $errorBody,
                ]);
            }

            return false;

        } catch (\Throwable $e) {
            Log::error('[GraphMail] Track 1 exception during send', [
                'from'    => $from ?? '?',
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);

            foreach ((array) $to as $address) {
                MailLog::create([
                    'to'        => $address,
                    'subject'   => $subject,
                    'status'    => 'failed',
                    'type'      => $type,
                    'track'     => 1,
                    'sent_from' => $from ?? null,
                    'error'     => $e->getMessage(),
                ]);
            }

            return false;
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a raw RFC 2822 MIME message with the ICS as an inline text/calendar
     * part so that Exchange/Outlook renders Accept / Decline buttons.
     */
    private function buildMimeWithCalendar(
        string $from,
        string $fromName,
        array  $to,
        string $replyTo,
        string $subject,
        string $body,
        string $icsContent,
        ?array $attachment = null,
        array  $cc = [],
    ): string {
        $boundary = 'RMF_' . bin2hex(random_bytes(8));

        $toHeader = implode(', ', $to);
        $ccHeader = ! empty($cc) ? 'Cc: ' . implode(', ', $cc) . "\r\n" : '';

        $mime  = "MIME-Version: 1.0\r\n";
        $mime .= "From: {$fromName} <{$from}>\r\n";
        $mime .= "To: {$toHeader}\r\n";
        $mime .= $ccHeader;
        $mime .= "Reply-To: {$replyTo}\r\n";
        $mime .= "Subject: {$subject}\r\n";
        $mime .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $mime .= "\r\n";

        // Plain text body
        $mime .= "--{$boundary}\r\n";
        $mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mime .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $mime .= "\r\n";
        $mime .= quoted_printable_encode($body) . "\r\n";

        // Inline calendar part — triggers Accept/Decline in Outlook/Exchange
        $mime .= "--{$boundary}\r\n";
        $mime .= "Content-Type: text/calendar; method=REQUEST; name=\"invite.ics\"\r\n";
        $mime .= "Content-Transfer-Encoding: base64\r\n";
        $mime .= "Content-Disposition: attachment; filename=\"invite.ics\"\r\n";
        $mime .= "\r\n";
        $mime .= chunk_split(base64_encode($icsContent), 76, "\r\n");

        // Optional PDF attachment
        if ($attachment) {
            $mime .= "--{$boundary}\r\n";
            $mime .= "Content-Type: application/pdf; name=\"{$attachment['filename']}\"\r\n";
            $mime .= "Content-Transfer-Encoding: base64\r\n";
            $mime .= "Content-Disposition: attachment; filename=\"{$attachment['filename']}\"\r\n";
            $mime .= "\r\n";
            $mime .= chunk_split($attachment['content'], 76, "\r\n");
        }

        $mime .= "--{$boundary}--\r\n";

        return $mime;
    }

    // =========================================================================
    // Track 2 — Per-User Delegated Token
    // =========================================================================

    /**
     * Get a valid access token for the user's personal MS365 account.
     * Refreshes automatically if expired.
     * Returns null (and marks mail_connected=false) if the token cannot be refreshed.
     */
    public function getUserToken(User $user): ?string
    {
        $account = $user->microsoftAccount;

        if (! $account || ! $account->mail_connected || ! $account->refresh_token) {
            return null;
        }

        // Token still valid
        if ($account->token_expires_at && now()->lt($account->token_expires_at)) {
            return $account->access_token;
        }

        // Token expired — attempt refresh
        $tenantId = config('services.microsoft.tenant_id');

        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $account->refresh_token,
                'scope'         => 'offline_access User.Read Mail.Send',
            ]
        );

        if (! $response->successful()) {
            Log::warning('[GraphMail] Track 2 token refresh failed — marking mail disconnected', [
                'user_id'    => $user->id,
                'account_id' => $account->id,
                'status'     => $response->status(),
            ]);

            $account->update(['mail_connected' => false]);
            return null;
        }

        $token = $response->json();

        $account->update([
            'access_token'      => $token['access_token'],
            'refresh_token'     => $token['refresh_token'] ?? $account->refresh_token,
            'token_expires_at'  => now()->addSeconds((int) ($token['expires_in'] ?? 3600) - 60),
        ]);

        Log::info('[GraphMail] Track 2 token refreshed', ['user_id' => $user->id]);

        return $token['access_token'];
    }

    /**
     * Send an email using the user's personal MS365 delegated token (Track 2).
     * Email appears in their personal Sent folder and arrives from their @rmflooring.ca address.
     *
     * @param  User          $user     The sending user
     * @param  string|array  $to       Single address or array of addresses
     * @param  string        $subject
     * @param  string        $body     Plain text body
     * @param  string        $type     Log type label
     * @return bool
     */
    public function sendAsUser(
        User $user,
        string|array $to,
        string $subject,
        string $body,
        string $type = 'system',
        ?array $attachment = null,
        ?array $cc = null,
        ?string $icsContent = null,
    ): bool {
        $token = $this->getUserToken($user);

        if (! $token) {
            Log::warning('[GraphMail] Track 2 sendAsUser: no valid token for user', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        $senderEmail = $user->microsoftAccount?->email ?? $user->email;

        $recipients = collect((array) $to)->map(fn ($address) => [
            'emailAddress' => ['address' => $address],
        ])->values()->all();

        try {
            $message = [
                'subject' => $subject,
                'body'    => [
                    'contentType' => 'Text',
                    'content'     => $body,
                ],
                'toRecipients' => $recipients,
            ];

            if (! empty($cc)) {
                $message['ccRecipients'] = collect($cc)->map(fn ($address) => [
                    'emailAddress' => ['address' => $address],
                ])->values()->all();
            }

            if ($icsContent) {
                $mime = $this->buildMimeWithCalendar(
                    from:       $senderEmail,
                    fromName:   $senderEmail,
                    to:         (array) $to,
                    replyTo:    $senderEmail,
                    subject:    $subject,
                    body:       $body,
                    icsContent: $icsContent,
                    attachment: $attachment,
                    cc:         $cc ?? [],
                );

                $response = Http::withToken($token)
                    ->withHeaders(['Content-Type' => 'text/plain'])
                    ->withBody($mime, 'text/plain')
                    ->post('https://graph.microsoft.com/v1.0/me/sendMail');
            } else {
                $attachments = [];
                if ($attachment) {
                    $attachments[] = [
                        '@odata.type'  => '#microsoft.graph.fileAttachment',
                        'name'         => $attachment['filename'],
                        'contentType'  => 'application/pdf',
                        'contentBytes' => $attachment['content'],
                    ];
                }
                if (! empty($attachments)) {
                    $message['attachments'] = $attachments;
                }

                $response = Http::withToken($token)
                    ->acceptJson()
                    ->post('https://graph.microsoft.com/v1.0/me/sendMail', [
                        'message'         => $message,
                        'saveToSentItems' => true,
                    ]);
            }

            if ($response->successful()) {
                Log::info('[GraphMail] Track 2 email sent', [
                    'user_id'  => $user->id,
                    'from'     => $senderEmail,
                    'to'       => $to,
                    'subject'  => $subject,
                ]);

                foreach ((array) $to as $address) {
                    MailLog::create([
                        'to'        => $address,
                        'subject'   => $subject,
                        'status'    => 'sent',
                        'type'      => $type,
                        'track'     => 2,
                        'sent_from' => $senderEmail,
                    ]);
                }

                return true;
            }

            $errorBody = $response->body();

            Log::error('[GraphMail] Track 2 send failed', [
                'user_id' => $user->id,
                'from'    => $senderEmail,
                'to'      => $to,
                'subject' => $subject,
                'status'  => $response->status(),
                'body'    => $errorBody,
            ]);

            foreach ((array) $to as $address) {
                MailLog::create([
                    'to'        => $address,
                    'subject'   => $subject,
                    'status'    => 'failed',
                    'type'      => $type,
                    'track'     => 2,
                    'sent_from' => $senderEmail,
                    'error'     => $errorBody,
                ]);
            }

            return false;

        } catch (\Throwable $e) {
            Log::error('[GraphMail] Track 2 exception during sendAsUser', [
                'user_id' => $user->id,
                'to'      => $to,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);

            foreach ((array) $to as $address) {
                MailLog::create([
                    'to'        => $address,
                    'subject'   => $subject,
                    'status'    => 'failed',
                    'type'      => $type,
                    'track'     => 2,
                    'sent_from' => $senderEmail ?? null,
                    'error'     => $e->getMessage(),
                ]);
            }

            return false;
        }
    }
}
