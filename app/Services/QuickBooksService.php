<?php

namespace App\Services;

use App\Models\QboConnection;
use App\Models\QboSyncLog;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuickBooksService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $environment;

    private string $authBaseUrl   = 'https://appcenter.intuit.com/connect/oauth2';
    private string $tokenUrl      = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    private string $sandboxApiUrl = 'https://sandbox-quickbooks.api.intuit.com/v3/company';
    private string $productionApiUrl = 'https://quickbooks.api.intuit.com/v3/company';

    public function __construct()
    {
        $this->clientId     = config('services.quickbooks.client_id');
        $this->clientSecret = config('services.quickbooks.client_secret');
        $this->redirectUri  = config('services.quickbooks.redirect_uri');
        $this->environment  = config('services.quickbooks.environment', 'sandbox');
    }

    // =========================================================================
    // OAuth
    // =========================================================================

    /**
     * Build the Intuit OAuth authorization URL.
     */
    public function getAuthorizationUrl(string $state): string
    {
        return $this->authBaseUrl . '?' . http_build_query([
            'client_id'     => $this->clientId,
            'response_type' => 'code',
            'scope'         => 'com.intuit.quickbooks.accounting',
            'redirect_uri'  => $this->redirectUri,
            'state'         => $state,
        ]);
    }

    /**
     * Exchange the authorization code for access + refresh tokens.
     * Returns the QboConnection record.
     */
    public function handleCallback(string $code, string $realmId, int $userId): QboConnection
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => $this->redirectUri,
            ]);

        if (! $response->successful()) {
            Log::error('[QBO] Token exchange failed', ['body' => $response->body()]);
            throw new \RuntimeException('QBO: token exchange failed — ' . $response->body());
        }

        $data = $response->json();

        // Only one connection record ever exists — update or create
        $connection = QboConnection::first() ?? new QboConnection();
        $connection->fill([
            'realm_id'         => $realmId,
            'environment'      => $this->environment,
            'access_token'     => Crypt::encryptString($data['access_token']),
            'refresh_token'    => Crypt::encryptString($data['refresh_token']),
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            'connected_at'     => now(),
            'connected_by'     => $userId,
        ])->save();

        return $connection;
    }

    /**
     * Disconnect — clears all token data but keeps the record for audit.
     */
    public function disconnect(): void
    {
        $connection = QboConnection::first();
        if (! $connection) {
            return;
        }

        // Revoke the refresh token with Intuit
        if ($connection->refresh_token) {
            try {
                Http::withBasicAuth($this->clientId, $this->clientSecret)
                    ->asForm()
                    ->post('https://developer.api.intuit.com/v2/oauth2/tokens/revoke', [
                        'token' => Crypt::decryptString($connection->refresh_token),
                    ]);
            } catch (\Exception $e) {
                Log::warning('[QBO] Token revoke request failed', ['error' => $e->getMessage()]);
            }
        }

        $connection->update([
            'access_token'     => null,
            'refresh_token'    => null,
            'token_expires_at' => null,
            'realm_id'         => null,
        ]);
    }

    // =========================================================================
    // Token management
    // =========================================================================

    /**
     * Return a valid access token, refreshing if needed.
     */
    public function getAccessToken(): string
    {
        $connection = QboConnection::first();

        if (! $connection || ! $connection->refresh_token) {
            throw new \RuntimeException('QBO: no active connection.');
        }

        if ($connection->isExpired()) {
            $connection = $this->refreshToken($connection);
        }

        return Crypt::decryptString($connection->access_token);
    }

    /**
     * Use the refresh token to get a new access token.
     */
    private function refreshToken(QboConnection $connection): QboConnection
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post($this->tokenUrl, [
                'grant_type'    => 'refresh_token',
                'refresh_token' => Crypt::decryptString($connection->refresh_token),
            ]);

        if (! $response->successful()) {
            Log::error('[QBO] Token refresh failed', ['body' => $response->body()]);
            // Clear tokens so the admin knows they need to reconnect
            $connection->update([
                'access_token'     => null,
                'refresh_token'    => null,
                'token_expires_at' => null,
            ]);
            throw new \RuntimeException('QBO: token refresh failed. Please reconnect.');
        }

        $data = $response->json();

        $connection->update([
            'access_token'     => Crypt::encryptString($data['access_token']),
            'refresh_token'    => Crypt::encryptString($data['refresh_token']),
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $connection->fresh();
    }

    // =========================================================================
    // API requests
    // =========================================================================

    /**
     * Return the base API URL for the current environment + realm.
     */
    public function apiUrl(): string
    {
        $connection = QboConnection::first();
        $base = $this->environment === 'production' ? $this->productionApiUrl : $this->sandboxApiUrl;
        return "{$base}/{$connection->realm_id}";
    }

    /**
     * GET request to QBO API.
     */
    public function get(string $endpoint, array $query = []): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->accept('application/json')
            ->get($this->apiUrl() . '/' . ltrim($endpoint, '/'), $query);

        if (! $response->successful()) {
            throw new \RuntimeException("QBO GET {$endpoint} failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * POST request to QBO API (create/update entity).
     */
    public function post(string $endpoint, array $payload): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->accept('application/json')
            ->post($this->apiUrl() . '/' . ltrim($endpoint, '/'), $payload);

        if (! $response->successful()) {
            throw new \RuntimeException("QBO POST {$endpoint} failed: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Run a QBO query (SQL-like query language).
     * e.g. query("SELECT * FROM Vendor WHERE DisplayName = 'Acme'")
     */
    public function query(string $sql): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->accept('application/json')
            ->get($this->apiUrl() . '/query', ['query' => $sql]);

        if (! $response->successful()) {
            throw new \RuntimeException("QBO query failed: " . $response->body());
        }

        return $response->json();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Check whether there is an active, connected QBO session.
     */
    public function isConnected(): bool
    {
        $connection = QboConnection::first();
        return $connection
            && $connection->realm_id
            && $connection->refresh_token;
    }

    /**
     * Write a line to the sync log.
     */
    public function log(
        string $entityType,
        ?int $entityId,
        string $direction,
        string $status,
        ?string $qboId = null,
        ?string $message = null,
        ?array $payload = null,
        ?array $response = null
    ): void {
        QboSyncLog::create([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'direction'   => $direction,
            'qbo_id'      => $qboId,
            'status'      => $status,
            'message'     => $message,
            'payload'     => $payload,
            'response'    => $response,
            'created_at'  => now(),
        ]);
    }
}
