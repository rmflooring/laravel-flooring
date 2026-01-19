<?php

namespace App\Http\Controllers;

use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\CalendarEvent;
use App\Models\ExternalEventLink;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MicrosoftCalendarConnectController extends Controller
{
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        session(['microsoft_oauth_state' => $state]);

        $query = http_build_query([
              'client_id'     => config('services.microsoft.client_id'),
				'response_type' => 'code',
				'redirect_uri'  => route('pages.microsoft.callback'),
				'response_mode' => 'query',
				'scope' => 'offline_access User.Read Calendars.ReadWrite Group.ReadWrite.All Group.Read.All GroupMember.Read.All',
				'state'         => $state,
				'prompt'        => 'consent',
				'include_granted_scopes' => 'true',
        ]);

        $tenantId = config('services.microsoft.tenant_id');
        $authUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/authorize?" . $query;

        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        // 1) Validate state
        $expectedState = session('microsoft_oauth_state');
        $providedState = $request->get('state');

        if (!$expectedState || !$providedState || !hash_equals($expectedState, $providedState)) {
            abort(403, 'Invalid OAuth state.');
        }

        // clear one-time state
        session()->forget('microsoft_oauth_state');

        // 2) Handle Microsoft error responses
        if ($request->filled('error')) {
            return response()->json([
                'error' => $request->get('error'),
                'error_description' => $request->get('error_description'),
            ], 400);
        }

        $code = $request->get('code');
        if (!$code) {
            return response()->json(['error' => 'Missing authorization code.'], 400);
        }

        // 3) Exchange code for token
        $tenantId = config('services.microsoft.tenant_id');

        $tokenResponse = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
            'client_id'     => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => route('pages.microsoft.callback'),
        ]);

        if (!$tokenResponse->successful()) {
            return response()->json([
                'error' => 'Token exchange failed',
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->json(),
            ], 400);
        }

        $token = $tokenResponse->json();

        // 4) Store/update the MicrosoftAccount for this user
        $user = Auth::user();

        $account = MicrosoftAccount::updateOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id'        => $tenantId,
                'access_token'     => $token['access_token'] ?? null,
                'refresh_token'    => $token['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds((int)($token['expires_in'] ?? 0)),
                'is_connected'     => true,
                'connected_at'     => now(),
                'disconnected_at'  => null,
            ]
        );

        // 5) Fetch /me to store microsoft_user_id + email
        $me = Http::withToken($token['access_token'])
            ->get('https://graph.microsoft.com/v1.0/me');

        if ($me->successful()) {
            $meJson = $me->json();

            $account->update([
                'microsoft_user_id' => $meJson['id'] ?? null,
                'email' => $meJson['mail'] ?? $meJson['userPrincipalName'] ?? null,
            ]);
        }

        return redirect('/dashboard')->with('success', 'Microsoft calendar connected!');
    }
	
	public function discoverCalendars(Request $request)
	{
		$user = $request->user();

		$account = $user->microsoftAccount;
		if (!$account || !$account->is_connected) {
			return back()->with('error', 'Microsoft account is not connected.');
		}

		// Refresh token if expired (or about to expire)
if (!$account->token_expires_at || now()->gte($account->token_expires_at)) {
    $tokenResponse = Http::asForm()->post('https://login.microsoftonline.com/' . $account->tenant_id . '/oauth2/v2.0/token', [
        'client_id'     => config('services.microsoft.client_id'),
        'client_secret' => config('services.microsoft.client_secret'),
        'grant_type'    => 'refresh_token',
        'refresh_token' => $account->refresh_token,
        'scope'         => 'https://graph.microsoft.com/.default offline_access',
    ]);

    if (!$tokenResponse->successful()) {
        return back()->with('error', 'Microsoft token refresh failed: ' . $tokenResponse->status());
    }

    $tokenData = $tokenResponse->json();

    $account->access_token = $tokenData['access_token'] ?? $account->access_token;

    // Microsoft may or may not return a new refresh_token each time
    if (!empty($tokenData['refresh_token'])) {
        $account->refresh_token = $tokenData['refresh_token'];
    }

    $expiresIn = (int)($tokenData['expires_in'] ?? 3600);
    $account->token_expires_at = now()->addSeconds($expiresIn - 60); // 60s buffer
    $account->save();
}

$accessToken = $account->access_token;

		$response = Http::withToken($accessToken)
			->acceptJson()
			->get('https://graph.microsoft.com/v1.0/me/calendars');

		if (!$response->successful()) {
			return back()->with('error', 'Failed to fetch calendars from Microsoft: ' . $response->status());
		}

		$calendars = $response->json('value') ?? [];

		foreach ($calendars as $cal) {
			$existingEnabled = MicrosoftCalendar::where('microsoft_account_id', $account->id)
				->where('calendar_id', $cal['id'])
				->value('is_enabled');

			MicrosoftCalendar::updateOrCreate(
				[
					'microsoft_account_id' => $account->id,
					'calendar_id'          => $cal['id'],
				],
				[
					'name'       => $cal['name'] ?? 'Unnamed Calendar',
					'is_primary' => (bool)($cal['isDefaultCalendar'] ?? false),
					'is_enabled' => is_null($existingEnabled) ? false : (bool)$existingEnabled,
				]
			);
		}

        // ---- Also discover Microsoft 365 Group calendars ----
        $memberOfResp = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://graph.microsoft.com/v1.0/me/memberOf?$select=id,displayName&$top=999');

        if ($memberOfResp->successful()) {
            $memberOf = $memberOfResp->json('value') ?? [];

            $groupIds = collect($memberOf)
                ->filter(function ($item) {
                    return isset($item['@odata.type']) && $item['@odata.type'] === '#microsoft.graph.group' && !empty($item['id']);
                })
                ->map(fn ($item) => $item['id'])
                ->values();

            foreach ($groupIds as $groupId) {
                try {
                    // Fetch group details to get a reliable displayName/mail
                    $groupResp = Http::withToken($accessToken)
                        ->acceptJson()
                        ->get('https://graph.microsoft.com/v1.0/groups/' . $groupId . '?$select=id,displayName,mail');

                    if (!$groupResp->successful()) {
                        continue;
                    }

                    $group = $groupResp->json();
                    $groupName = $group['displayName'] ?? null;

                    // Fetch the group calendar
                    $groupCalResp = Http::withToken($accessToken)
                        ->acceptJson()
                        ->get('https://graph.microsoft.com/v1.0/groups/' . $groupId . '/calendar');

                    if (!$groupCalResp->successful()) {
                        continue;
                    }

                    $groupCalendar = $groupCalResp->json();
                    if (empty($groupCalendar['id'])) {
                        continue;
                    }

                    $existingEnabled = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                        ->where('calendar_id', $groupCalendar['id'])
                        ->value('is_enabled');

                    MicrosoftCalendar::updateOrCreate(
                        [
                            'microsoft_account_id' => $account->id,
                            'calendar_id'          => $groupCalendar['id'],
                        ],
                        [
                            // Prefer group display name; fallback to mail; fallback generic
                            'group_id'   => $groupId,
							'name'       => $groupName
                                ?: ($group['mail'] ?? null)
                                ?: ($groupCalendar['name'] ?? 'Group Calendar'),
                            'is_primary' => false,
                            'is_enabled' => is_null($existingEnabled) ? false : (bool)$existingEnabled,
                        ]
                    );
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }


$groupCount = isset($groupIds) ? $groupIds->count() : 0;

return back()->with(
    'success',
    'Calendars discovered: ' . count($calendars) . ' personal, ' . $groupCount . ' group(s).'
);
	}
	
	public function syncNow(Request $request)
{
    $user = $request->user();

    $account = $user->microsoftAccount;
    if (!$account || !$account->is_connected) {
        return back()->with('error', 'Microsoft account is not connected.');
    }

    // Refresh token if expired (reuse the same logic you added in discoverCalendars)
    if (!$account->token_expires_at || now()->gte($account->token_expires_at)) {
        $tokenResponse = Http::asForm()->post('https://login.microsoftonline.com/' . $account->tenant_id . '/oauth2/v2.0/token', [
            'client_id'     => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type'    => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'scope'         => 'https://graph.microsoft.com/.default offline_access',
        ]);

        if (!$tokenResponse->successful()) {
            return back()->with('error', 'Microsoft token refresh failed: ' . $tokenResponse->status());
        }

        $tokenData = $tokenResponse->json();

        $account->access_token = $tokenData['access_token'] ?? $account->access_token;

        if (!empty($tokenData['refresh_token'])) {
            $account->refresh_token = $tokenData['refresh_token'];
        }

        $expiresIn = (int)($tokenData['expires_in'] ?? 3600);
        $account->token_expires_at = now()->addSeconds($expiresIn - 60);
        $account->save();
    }

    $enabledCalendars = MicrosoftCalendar::where('microsoft_account_id', $account->id)
        ->where('is_enabled', true)
        ->get();

    if ($enabledCalendars->count() < 1) {
        return back()->with('error', 'No calendars are enabled for sync.');
    }

    $accessToken = $account->access_token;

    $results = [];
$totalUpserted = 0;

foreach ($enabledCalendars as $cal) {
    $baseUrl = $cal->group_id
    ? "https://graph.microsoft.com/v1.0/groups/{$cal->group_id}/calendar/events"
    : "https://graph.microsoft.com/v1.0/me/calendars/" . rawurlencode($cal->calendar_id) . "/events";

$params = [
    '$top' => 200,
    '$orderby' => 'lastModifiedDateTime desc',
];

// NO FILTER — Graph does not support filtering by calendarId

Log::error('Microsoft syncNow request debug', [
    'calendar_name' => $cal->name,
    'url' => $baseUrl,
    'params' => $params,
]);

$resp = Http::withToken($accessToken)
    ->acceptJson()
    ->get($baseUrl, $params);

    if (!$resp->successful()) {
    Log::error('Microsoft syncNow calendar fetch failed', [
        'calendar_name' => $cal->name,
        'calendar_id'   => $cal->calendar_id,
        'group_id'      => $cal->group_id,
        'url'           => $baseUrl,
        'status'        => $resp->status(),
        'body'          => $resp->body(),
		'json'          => $resp->json(),

    ]);

    $results[] = $cal->name . ': error ' . $resp->status();
    continue;
}

    $events = $resp->json('value') ?? [];
    $upserted = 0;

    foreach ($events as $ev) {
        $externalEventId = $ev['id'] ?? null;
        if (!$externalEventId) continue;

        $startDt = $ev['start']['dateTime'] ?? null;
        $startTz = $ev['start']['timeZone'] ?? null;
        $endDt   = $ev['end']['dateTime'] ?? null;
        $endTz   = $ev['end']['timeZone'] ?? null;

        if (!$startDt || !$endDt) continue;

        $link = \App\Models\ExternalEventLink::where('provider', 'microsoft')
            ->where('microsoft_account_id', $account->id)
            ->where('external_calendar_id', $cal->calendar_id)
            ->where('external_event_id', $externalEventId)
            ->first();

        $calendarEvent = $link
            ? \App\Models\CalendarEvent::withTrashed()->find($link->calendar_event_id)
            : new \App\Models\CalendarEvent();

        if (!$calendarEvent) {
            $calendarEvent = new \App\Models\CalendarEvent();
        }

        $calendarEvent->owner_user_id = $user->id;
        $calendarEvent->title = $ev['subject'] ?? '(No title)';
        $calendarEvent->description = $ev['bodyPreview'] ?? null;
        $calendarEvent->location = $ev['location']['displayName'] ?? null;

        $calendarEvent->starts_at = \Carbon\Carbon::parse($startDt, $startTz ?: 'UTC')->utc();
        $calendarEvent->ends_at   = \Carbon\Carbon::parse($endDt, $endTz ?: 'UTC')->utc();
        $calendarEvent->timezone = $startTz ?: 'UTC';

        $calendarEvent->status = $calendarEvent->status ?? 'active';
        $calendarEvent->created_by = $calendarEvent->created_by ?? $user->id;
        $calendarEvent->updated_by = $user->id;

        if (method_exists($calendarEvent, 'restore') && !is_null($calendarEvent->deleted_at)) {
            $calendarEvent->restore();
        }

        $calendarEvent->save();

        \App\Models\ExternalEventLink::updateOrCreate(
            [
                'provider' => 'microsoft',
                'microsoft_account_id' => $account->id,
                'external_calendar_id' => $cal->calendar_id,
                'external_event_id' => $externalEventId,
            ],
            [
                'calendar_event_id' => $calendarEvent->id,
                'last_synced_at' => now(),
            ]
        );

        $upserted++;
    }

    $totalUpserted += $upserted;
    $results[] = $cal->name . ': upserted ' . $upserted . ' events';
}

return back()->with('success', 'Sync complete. Total upserted: ' . $totalUpserted . ' | ' . implode(' | ', $results));

	}
}
