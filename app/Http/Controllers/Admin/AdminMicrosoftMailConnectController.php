<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MicrosoftAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminMicrosoftMailConnectController extends Controller
{
    /**
     * Start the OAuth flow for the given user.
     * Admin initiates this on behalf of the target user.
     */
    public function redirect(User $user)
    {
        $state = Str::random(40);

        // Store both the CSRF token and the target user_id in session
        session([
            'microsoft_mail_oauth_state'   => $state,
            'microsoft_mail_oauth_user_id' => $user->id,
        ]);

        $query = http_build_query([
            'client_id'     => config('services.microsoft.client_id'),
            'response_type' => 'code',
            'redirect_uri'  => route('admin.settings.mail.callback'),
            'response_mode' => 'query',
            'scope'         => 'offline_access User.Read Mail.Send',
            'state'         => $state,
            'prompt'        => 'select_account',  // forces Microsoft login picker so admin picks the right user
        ]);

        $tenantId = config('services.microsoft.tenant_id');
        $authUrl  = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?" . $query;

        return redirect($authUrl);
    }

    /**
     * Handle the OAuth callback from Microsoft.
     * Stores the token against the target user identified in session.
     */
    public function callback(Request $request)
    {
        // 1) Validate CSRF state
        $expectedState = session('microsoft_mail_oauth_state');
        $providedState = $request->get('state');

        if (! $expectedState || ! $providedState || ! hash_equals($expectedState, $providedState)) {
            Log::warning('[Track2Mail] OAuth callback: invalid state', [
                'expected' => $expectedState,
                'provided' => $providedState,
            ]);
            return redirect()->route('admin.settings.mail')
                ->with('error', 'OAuth state mismatch — please try connecting again.');
        }

        $targetUserId = session('microsoft_mail_oauth_user_id');

        // Clear one-time session values
        session()->forget(['microsoft_mail_oauth_state', 'microsoft_mail_oauth_user_id']);

        if (! $targetUserId) {
            return redirect()->route('admin.settings.mail')
                ->with('error', 'Session expired — please try connecting again.');
        }

        $targetUser = User::find($targetUserId);
        if (! $targetUser) {
            return redirect()->route('admin.settings.mail')
                ->with('error', 'Target user not found.');
        }

        // 2) Handle Microsoft error responses
        if ($request->filled('error')) {
            Log::error('[Track2Mail] OAuth error from Microsoft', [
                'user_id' => $targetUserId,
                'error'   => $request->get('error'),
                'desc'    => $request->get('error_description'),
            ]);
            return redirect()->route('admin.settings.mail')
                ->with('error', 'Microsoft returned an error: ' . $request->get('error_description'));
        }

        $code = $request->get('code');
        if (! $code) {
            return redirect()->route('admin.settings.mail')
                ->with('error', 'No authorization code returned from Microsoft.');
        }

        // 3) Exchange code for token
        $tenantId = config('services.microsoft.tenant_id');

        $tokenResponse = Http::asForm()->post(
            "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token",
            [
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => route('admin.settings.mail.callback'),
                'scope'         => 'offline_access User.Read Mail.Send',
            ]
        );

        if (! $tokenResponse->successful()) {
            Log::error('[Track2Mail] Token exchange failed', [
                'user_id' => $targetUserId,
                'status'  => $tokenResponse->status(),
                'body'    => $tokenResponse->body(),
            ]);
            return redirect()->route('admin.settings.mail')
                ->with('error', 'Token exchange with Microsoft failed. Check logs for details.');
        }

        $token = $tokenResponse->json();

        // 4) Fetch /me to confirm the Microsoft identity
        $me = Http::withToken($token['access_token'])
            ->get('https://graph.microsoft.com/v1.0/me');

        $meEmail = null;
        $meId    = null;
        if ($me->successful()) {
            $meJson  = $me->json();
            $meEmail = $meJson['mail'] ?? $meJson['userPrincipalName'] ?? null;
            $meId    = $meJson['id'] ?? null;
        }

        // 5) Upsert MicrosoftAccount — preserve existing calendar fields, add mail fields
        $account = MicrosoftAccount::updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'tenant_id'         => $tenantId,
                'microsoft_user_id' => $meId,
                'email'             => $meEmail,
                'access_token'      => $token['access_token'],
                'refresh_token'     => $token['refresh_token'] ?? null,
                'token_expires_at'  => now()->addSeconds((int) ($token['expires_in'] ?? 3600)),
                'is_connected'      => true,
                'connected_at'      => now(),
                'disconnected_at'   => null,
                'mail_connected'    => true,
                'mail_connected_at' => now(),
            ]
        );

        Log::info('[Track2Mail] Mail connection established', [
            'target_user_id' => $targetUser->id,
            'ms_email'       => $meEmail,
            'account_id'     => $account->id,
        ]);

        return redirect()->route('admin.settings.mail')
            ->with('success', "Mail connected for {$targetUser->name}" . ($meEmail ? " ({$meEmail})" : '') . '.');
    }

    /**
     * Revoke the user's mail connection (does not affect calendar connection).
     */
    public function disconnect(User $user)
    {
        $account = $user->microsoftAccount;

        if ($account) {
            $account->update([
                'mail_connected'    => false,
                'mail_connected_at' => null,
            ]);

            Log::info('[Track2Mail] Mail disconnected', [
                'user_id'    => $user->id,
                'account_id' => $account->id,
            ]);
        }

        return redirect()->route('admin.settings.mail')
            ->with('success', "Mail disconnected for {$user->name}.");
    }
}
