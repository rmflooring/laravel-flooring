<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\ExternalEventLink;
use App\Models\CalendarEvent;


class CalendarEventController extends Controller
{
    /**
     * Refresh Microsoft access token if expired (or missing), and return a valid access token.
     * Throws \Exception on failure.
     */
    protected function ensureMicrosoftAccessToken(MicrosoftAccount $account): string
    {
        if (!$account->token_expires_at || now()->lt($account->token_expires_at)) {
            return (string) $account->access_token;
        }

        $tokenResponse = Http::asForm()->post('https://login.microsoftonline.com/' . $account->tenant_id . '/oauth2/v2.0/token', [
            'client_id'     => config('services.microsoft.client_id'),
            'client_secret' => config('services.microsoft.client_secret'),
            'grant_type'    => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'scope'         => 'https://graph.microsoft.com/.default offline_access',
        ]);

        if (!$tokenResponse->successful()) {
            throw new \Exception('Microsoft token refresh failed: ' . $tokenResponse->body());
        }

        $tokenData = $tokenResponse->json();
        $account->access_token = $tokenData['access_token'] ?? $account->access_token;

        if (!empty($tokenData['refresh_token'])) {
            $account->refresh_token = $tokenData['refresh_token'];
        }

        $expiresIn = (int) ($tokenData['expires_in'] ?? 3600);
        $account->token_expires_at = now()->addSeconds(max(60, $expiresIn - 60)); // buffer
        $account->save();

        return (string) $account->access_token;
    }

    /**
     * Build Graph create/update payload.
     */
    protected function buildGraphPayload(array $data): array
    {
        $payload = [
            'subject' => $data['title'],
            'body' => [
                'contentType' => 'text',
                'content'     => $data['notes'] ?? '',
            ],
        ];

        $isAllDay = !empty($data['allDay']) || !empty($data['is_all_day']);

        if ($isAllDay) {
            $startDate = date('Y-m-d', strtotime($data['start']));
            $endDate   = date('Y-m-d', strtotime($data['end']));

            if ($endDate === $startDate) {
                $endDate = date('Y-m-d', strtotime($startDate . ' +1 day'));
            }

            $payload['isAllDay'] = true;
            $payload['start'] = [
    'dateTime' => $startDate . 'T00:00:00',
    'timeZone' => 'Pacific Standard Time',
];
$payload['end'] = [
    'dateTime' => $endDate . 'T00:00:00',
    'timeZone' => 'Pacific Standard Time',
];
        } else {
            $payload['start'] = [
                'dateTime' => date('Y-m-d\TH:i:s', strtotime($data['start'])),
                'timeZone' => 'Pacific Standard Time',
            ];
            $payload['end'] = [
                'dateTime' => date('Y-m-d\TH:i:s', strtotime($data['end'])),
                'timeZone' => 'Pacific Standard Time',
            ];
        }

        if (!empty($data['location'])) {
            $payload['location'] = ['displayName' => $data['location']];
        } elseif (array_key_exists('location', $data)) {
            $payload['location'] = ['displayName' => ''];
        }

        return $payload;
    }

public function store(Request $request)
{
    try {
        $data = $request->validate([
            'microsoft_calendar_id' => ['required', 'integer'],
            'title'      => ['required', 'string', 'max:255'],
            'start'      => ['required', 'date'],
            'end'        => ['required', 'date', 'after:start'],
            'location'   => ['nullable', 'string', 'max:255'],
            'notes'      => ['nullable', 'string'],
            'is_all_day' => ['nullable'],
        ]);

        $user = Auth::user();

        $account = MicrosoftAccount::where('user_id', $user->id)
            ->where('is_connected', 1)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'No connected Microsoft account found'], 400);
        }

        // This is the DB row for the calendar (id = 24/25/26/22 in your UI options)
        $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
            ->where('id', (int)$data['microsoft_calendar_id'])
            ->first();

        if (!$calendar) {
            return response()->json(['message' => 'Selected calendar is not available (not found, not enabled, or not linked to this account).'], 404);
        }

        $accessToken = $this->ensureMicrosoftAccessToken($account);

        $payload = $this->buildGraphPayload($data);

        // Create the event in the correct place (group calendar vs personal)
        if (!empty($calendar->group_id)) {
            $url = "https://graph.microsoft.com/v1.0/groups/{$calendar->group_id}/events";
        } else {
            // For non-group calendars, create in the target calendar
            $url = "https://graph.microsoft.com/v1.0/me/calendars/{$calendar->calendar_id}/events";
        }

        $resp = Http::withToken($accessToken)
            ->acceptJson()
            ->post($url, $payload);

        if (!$resp->successful()) {
            return response()->json([
                'message' => 'Microsoft create failed',
                'status'  => $resp->status(),
                'error'   => $resp->json(),
                'debug'   => [
                    'used_group_endpoint' => !empty($calendar->group_id),
                    'group_id'            => $calendar->group_id,
                    'calendar_id'         => $calendar->calendar_id,
                ],
            ], $resp->status());
        }

        $msEvent = $resp->json();
        $externalEventId = $msEvent['id'] ?? null;

        if (!$externalEventId) {
            return response()->json([
                'message' => 'Microsoft create succeeded but no event id returned',
                'raw' => $msEvent,
            ], 500);
        }

        // Save local CalendarEvent (so feed can show it immediately)
        $local = \App\Models\CalendarEvent::create([
            'owner_user_id' => $user->id,
            'title'         => $data['title'],
            'starts_at'     => $data['start'],
            'ends_at'       => $data['end'],
            'location'      => $data['location'] ?? null,
            'description'   => $data['notes'] ?? null,
        ]);

        // Link local event to Microsoft event + calendar id (this is what your feed filters on)
        ExternalEventLink::create([
            'provider'              => 'microsoft',
            'microsoft_account_id'  => $account->id,
            'calendar_event_id'     => $local->id,
            'external_calendar_id'  => $calendar->calendar_id,
            'external_event_id'     => $externalEventId,
            'last_synced_at'        => now(),
        ]);

        return response()->json([
            'message' => 'Created',
            'id'      => $local->id,
        ]);
    } catch (\Throwable $e) {
        \Log::error('Create event failed', [
            'exception' => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => 'Failed to create event',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Update event in Microsoft calendar.
     */
    public function update(Request $request, $event)
    {
        \Log::info('Update called', [
            'event_param' => $event,
            'user_id'     => auth()->id(),
            'ip'          => $request->ip(),
        ]);

        try {
            $data = $request->validate([
                'title'      => ['required', 'string', 'max:255'],
                'start'      => ['required', 'date'],
                'end'        => ['required', 'date', 'after:start'],
                'location'   => ['nullable', 'string', 'max:255'],
                'notes'      => ['nullable', 'string'],
                'is_all_day' => ['nullable'],
            ]);

            $user = Auth::user();

            $account = MicrosoftAccount::where('user_id', $user->id)
                ->where('is_connected', 1)
                ->first();

            if (!$account) {
                \Log::warning('No connected Microsoft account found', ['user_id' => $user->id]);
                return response()->json(['message' => 'No connected Microsoft account found'], 400);
            }

            $link = ExternalEventLink::where('calendar_event_id', (int) $event)
    ->where('microsoft_account_id', $account->id)
    ->where('provider', 'microsoft')
    ->first();

            if (!$link) {
                \Log::warning('ExternalEventLink not found', ['event_param' => $event]);
                return response()->json(['message' => 'Event link not found in database'], 404);
            }

            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
    ->where('calendar_id', $link->external_calendar_id)  // ← key change!
    ->first();

if (!$calendar) {
    \Log::warning('MicrosoftCalendar not found by external_calendar_id', [
        'link_id' => $link->id,
        'external_calendar_id' => $link->external_calendar_id
    ]);
    return response()->json(['message' => 'Associated calendar not found in database'], 404);
}

            $accessToken = $this->ensureMicrosoftAccessToken($account);

            $payload = $this->buildGraphPayload($data);

            // IMPORTANT: Include the EXTERNAL event ID in the URL!
            if (!empty($calendar->group_id)) {
                $url = "https://graph.microsoft.com/v1.0/groups/{$calendar->group_id}/events/{$link->external_event_id}";
            } else {
                $url = "https://graph.microsoft.com/v1.0/me/events/{$link->external_event_id}";
            }

            $resp = Http::withToken($accessToken)
                ->acceptJson()
                ->patch($url, $payload);

            if (!$resp->successful()) {
                return response()->json([
                    'message' => 'Microsoft update failed',
                    'status'  => $resp->status(),
                    'error'   => $resp->json(),
                    'debug'   => [
                        'used_group_endpoint' => !empty($calendar->group_id),
                        'group_id'            => $calendar->group_id,
                        'external_event_id'   => $link->external_event_id,
                    ],
                ], $resp->status());
            }

            $link->update(['last_synced_at' => now()]);

            return response()->json(['message' => 'Updated']);
        } catch (\Exception $e) {
            \Log::error('Update event failed', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
                'event'     => $event,
            ]);

            return response()->json([
                'message' => 'Failed to update event',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete event in Microsoft calendar.
     */
    public function destroy(Request $request, $event)
    {
        try {
            $user = Auth::user();

            $account = MicrosoftAccount::where('user_id', $user->id)
                ->where('is_connected', 1)
                ->first();

            if (!$account) {
                return response()->json(['message' => 'No connected Microsoft account found'], 400);
            }

            $link = ExternalEventLink::where('calendar_event_id', (int) $event)
			->where('microsoft_account_id', $account->id)
			->where('provider', 'microsoft')
			->first();

            if (!$link) {
                return response()->json(['message' => 'Event link not found'], 404);
            }

            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
			->where('calendar_id', $link->external_calendar_id)
			->first();

            if (!$calendar) {
                return response()->json(['message' => 'Calendar not found'], 404);
            }

            $accessToken = $this->ensureMicrosoftAccessToken($account);

            if (!empty($calendar->group_id)) {
                $url = "https://graph.microsoft.com/v1.0/groups/{$calendar->group_id}/events/{$link->external_event_id}";
            } else {
                $url = "https://graph.microsoft.com/v1.0/me/events/{$link->external_event_id}";
            }

            $resp = Http::withToken($accessToken)->delete($url);

            if (!in_array($resp->status(), [204, 200], true)) {
                return response()->json([
                    'message' => 'Microsoft delete failed',
                    'status'  => $resp->status(),
                    'error'   => $resp->json(),
                ], $resp->status());
            }

            $link->delete();

            return response()->json(['message' => 'Deleted']);
        } catch (\Exception $e) {
            \Log::error('Delete event failed', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete event', 'error' => $e->getMessage()], 500);
        }
    }
	
	public function move(Request $request, CalendarEvent $event)
{
    $request->validate([
        'microsoft_calendar_id' => ['required', 'integer'],
    ]);

    // Ensure user owns the local event
    if ((int) $event->owner_user_id !== (int) auth()->id()) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // Find the Microsoft link for this event (scoped to provider)
    $link = ExternalEventLink::where('calendar_event_id', $event->id)
        ->where('provider', 'microsoft')
        ->first();

    if (!$link) {
        return response()->json(['message' => 'Microsoft link not found for this event.'], 404);
    }

    // Account + token
    $account = MicrosoftAccount::where('id', $link->microsoft_account_id)
        ->where('is_connected', 1)
        ->first();

    if (!$account) {
        return response()->json(['message' => 'Microsoft account not found or not connected.'], 404);
    }

    $accessToken = $this->ensureMicrosoftAccessToken($account);

    // Source calendar (based on the link’s external_calendar_id)
    $sourceCal = MicrosoftCalendar::where('microsoft_account_id', $account->id)
        ->where('calendar_id', $link->external_calendar_id)
        ->first();

    if (!$sourceCal) {
        return response()->json(['message' => 'Source calendar not found in database.'], 404);
    }

    // Destination calendar (DB id from dropdown)
    $destCal = MicrosoftCalendar::where('microsoft_account_id', $account->id)
        ->where('id', (int) $request->microsoft_calendar_id)
        ->first();

    if (!$destCal) {
        return response()->json(['message' => 'Destination calendar not found.'], 404);
    }

    // Same calendar? no-op
    if ($link->external_calendar_id === $destCal->calendar_id) {
        return response()->json(['success' => true, 'moved' => false]);
    }

    /**
     * MOVE IMPLEMENTATION:
     * GET original -> CREATE in destination -> DELETE original
     */

    // 1) GET original event (group vs me)
    if (!empty($sourceCal->group_id)) {
        $getUrl = "https://graph.microsoft.com/v1.0/groups/{$sourceCal->group_id}/events/" . rawurlencode($link->external_event_id);
    } else {
        $getUrl = "https://graph.microsoft.com/v1.0/me/events/" . rawurlencode($link->external_event_id);
    }

    $getResp = Http::withToken($accessToken)->acceptJson()->get($getUrl);
    $getJson = $getResp->json();

    if (!$getResp->successful()) {
        return response()->json([
            'message' => 'Microsoft get-event failed',
            'status'  => $getResp->status(),
            'error'   => $getJson,
            'debug'   => [
                'source_is_group' => !empty($sourceCal->group_id),
                'source_group_id' => $sourceCal->group_id,
            ],
        ], $getResp->status());
    }

    // 2) CREATE in destination (group vs calendar)
    if (!empty($destCal->group_id)) {
        $createUrl = "https://graph.microsoft.com/v1.0/groups/{$destCal->group_id}/events";
    } else {
        $createUrl = "https://graph.microsoft.com/v1.0/me/calendars/" . rawurlencode($destCal->calendar_id) . "/events";
    }

    // Graph GET returns body as HTML (per docs), so keep HTML to avoid content loss
    $createPayload = [
        'subject' => $getJson['subject'] ?? $event->title ?? '',
        'body' => [
            'contentType' => 'HTML',
            'content' => $getJson['body']['content'] ?? ($event->description ?? ''),
        ],
        'location' => [
            'displayName' => $getJson['location']['displayName'] ?? ($event->location ?? ''),
        ],
        'isAllDay' => $getJson['isAllDay'] ?? false,
        'start'    => $getJson['start'] ?? null,
        'end'      => $getJson['end'] ?? null,
    ];

    // Remove only null top-level values (Graph will complain about null start/end)
    foreach ($createPayload as $k => $v) {
        if ($v === null) unset($createPayload[$k]);
    }

    $createResp = Http::withToken($accessToken)->acceptJson()->post($createUrl, $createPayload);
    $createJson = $createResp->json();

    if (!$createResp->successful()) {
        return response()->json([
            'message' => 'Microsoft create-in-destination failed',
            'status'  => $createResp->status(),
            'error'   => $createJson,
            'debug'   => [
                'dest_is_group' => !empty($destCal->group_id),
                'dest_group_id' => $destCal->group_id,
                'dest_calendar_id' => $destCal->calendar_id,
            ],
        ], $createResp->status());
    }

    $newExternalEventId = $createJson['id'] ?? null;
    if (!$newExternalEventId) {
        return response()->json([
            'message' => 'Microsoft create succeeded but returned no event id.',
            'error'   => $createJson,
        ], 500);
    }

    // 3) DELETE original event (group vs me)
    if (!empty($sourceCal->group_id)) {
        $deleteUrl = "https://graph.microsoft.com/v1.0/groups/{$sourceCal->group_id}/events/" . rawurlencode($link->external_event_id);
    } else {
        $deleteUrl = "https://graph.microsoft.com/v1.0/me/events/" . rawurlencode($link->external_event_id);
    }

    $deleteResp = Http::withToken($accessToken)->acceptJson()->delete($deleteUrl);

    if (!in_array($deleteResp->status(), [204, 200], true)) {
        return response()->json([
            'message' => 'Microsoft delete-original failed (destination event was created)',
            'status'  => $deleteResp->status(),
            'error'   => $deleteResp->json(),
            'new_external_event_id' => $newExternalEventId,
        ], $deleteResp->status());
    }

    // 4) Update our link to the new calendar + event id
    $link->external_calendar_id = $destCal->calendar_id;
    $link->external_event_id    = $newExternalEventId;
    $link->last_synced_at       = now();
    $link->save();

    return response()->json([
        'success' => true,
        'moved'   => true,
        'external_calendar_id' => $link->external_calendar_id,
        'external_event_id'    => $link->external_event_id,
    ]);
}

	
	
}