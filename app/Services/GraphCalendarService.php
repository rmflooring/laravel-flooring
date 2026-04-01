<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\ExternalEventLink;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GraphCalendarService
{
    /**
     * Ensure the access token is valid, refreshing it if expired.
     * Returns a usable access token string.
     */
    public function ensureAccessToken(MicrosoftAccount $account): string
    {
        if (!$account->token_expires_at || now()->lt($account->token_expires_at)) {
            return (string) $account->access_token;
        }

        $response = Http::asForm()->post(
            'https://login.microsoftonline.com/' . $account->tenant_id . '/oauth2/v2.0/token',
            [
                'client_id'     => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'grant_type'    => 'refresh_token',
                'refresh_token' => $account->refresh_token,
                'scope'         => 'https://graph.microsoft.com/.default offline_access',
            ]
        );

        if (!$response->successful()) {
            $account->is_connected    = false;
            $account->disconnected_at = now();
            $account->save();
            throw new \Exception('Microsoft token refresh failed: ' . $response->body());
        }

        $data = $response->json();

        $account->access_token = $data['access_token'] ?? $account->access_token;
        if (!empty($data['refresh_token'])) {
            $account->refresh_token = $data['refresh_token'];
        }
        $account->token_expires_at = now()->addSeconds(max(60, (int)($data['expires_in'] ?? 3600) - 60));
        $account->save();

        return (string) $account->access_token;
    }

    /**
     * Create an event in a Microsoft 365 calendar (group or personal).
     *
     * $eventData keys:
     *   title    (string, required)
     *   start    (string|Carbon, required)
     *   end      (string|Carbon, required)
     *   location (string, optional)
     *   notes    (string, optional)
     *
     * Returns the external MS event ID on success.
     * Throws \Exception on failure.
     */
    public function createEvent(MicrosoftAccount $account, MicrosoftCalendar $calendar, array $eventData): string
    {
        $accessToken = $this->ensureAccessToken($account);

        $start = $eventData['start'] instanceof \Carbon\Carbon
            ? $eventData['start']->format('Y-m-d\TH:i:s')
            : date('Y-m-d\TH:i:s', strtotime($eventData['start']));

        $end = $eventData['end'] instanceof \Carbon\Carbon
            ? $eventData['end']->format('Y-m-d\TH:i:s')
            : date('Y-m-d\TH:i:s', strtotime($eventData['end']));

        $payload = [
            'subject' => $eventData['title'],
            'body'    => [
                'contentType' => 'text',
                'content'     => $eventData['notes'] ?? '',
            ],
            'start' => ['dateTime' => $start, 'timeZone' => 'Pacific Standard Time'],
            'end'   => ['dateTime' => $end,   'timeZone' => 'Pacific Standard Time'],
        ];

        if (!empty($eventData['location'])) {
            $payload['location'] = ['displayName' => $eventData['location']];
        }

        if (!empty($eventData['attendees'])) {
            $payload['attendees'] = array_map(fn($a) => [
                'emailAddress' => ['address' => $a['email'], 'name' => $a['name'] ?? $a['email']],
                'type'         => 'required',
            ], $eventData['attendees']);
        }

        $url = !empty($calendar->group_id)
            ? "https://graph.microsoft.com/v1.0/groups/{$calendar->group_id}/events"
            : "https://graph.microsoft.com/v1.0/me/calendars/{$calendar->calendar_id}/events";

        $response = Http::withToken($accessToken)->acceptJson()->post($url, $payload);

        if (!$response->successful()) {
            throw new \Exception(
                "Graph API create failed (HTTP {$response->status()}): " . $response->body()
            );
        }

        $externalId = $response->json('id');

        if (empty($externalId)) {
            throw new \Exception('Graph API returned no event ID.');
        }

        return $externalId;
    }

    /**
     * Update an existing event in Microsoft 365 via PATCH.
     * $eventData accepts the same keys as createEvent().
     * Throws \Exception on failure.
     */
    public function updateEvent(MicrosoftAccount $account, \App\Models\ExternalEventLink $link, array $eventData): void
    {
        $accessToken = $this->ensureAccessToken($account);

        $start = $eventData['start'] instanceof \Carbon\Carbon
            ? $eventData['start']->format('Y-m-d\TH:i:s')
            : date('Y-m-d\TH:i:s', strtotime($eventData['start']));

        $end = $eventData['end'] instanceof \Carbon\Carbon
            ? $eventData['end']->format('Y-m-d\TH:i:s')
            : date('Y-m-d\TH:i:s', strtotime($eventData['end']));

        $payload = [
            'subject' => $eventData['title'],
            'body'    => [
                'contentType' => 'text',
                'content'     => $eventData['notes'] ?? '',
            ],
            'start' => ['dateTime' => $start, 'timeZone' => 'Pacific Standard Time'],
            'end'   => ['dateTime' => $end,   'timeZone' => 'Pacific Standard Time'],
        ];

        if (array_key_exists('location', $eventData)) {
            $payload['location'] = ['displayName' => $eventData['location'] ?? ''];
        }

        if (!empty($eventData['attendees'])) {
            $payload['attendees'] = array_map(fn($a) => [
                'emailAddress' => ['address' => $a['email'], 'name' => $a['name'] ?? $a['email']],
                'type'         => 'required',
            ], $eventData['attendees']);
        }

        $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
            ->where('calendar_id', $link->external_calendar_id)
            ->first();

        $url = ($calendar && ! empty($calendar->group_id))
            ? "https://graph.microsoft.com/v1.0/groups/{$calendar->group_id}/events/{$link->external_event_id}"
            : "https://graph.microsoft.com/v1.0/me/events/{$link->external_event_id}";

        $response = Http::withToken($accessToken)->acceptJson()->patch($url, $payload);

        if (! $response->successful()) {
            throw new \Exception(
                "Graph API update failed (HTTP {$response->status()}): " . $response->body()
            );
        }

        $link->update(['last_synced_at' => now()]);
    }

    /**
     * Delete an event from Microsoft 365 and remove the ExternalEventLink record.
     * Throws \Exception on failure.
     */
    public function deleteEvent(MicrosoftAccount $account, \App\Models\ExternalEventLink $link): void
    {
        $accessToken = $this->ensureAccessToken($account);

        $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
            ->where('calendar_id', $link->external_calendar_id)
            ->first();

        $url = ($calendar && ! empty($calendar->group_id))
            ? "https://graph.microsoft.com/v1.0/groups/{$calendar->group_id}/events/{$link->external_event_id}"
            : "https://graph.microsoft.com/v1.0/me/events/{$link->external_event_id}";

        $response = Http::withToken($accessToken)->delete($url);

        if (! in_array($response->status(), [200, 204], true)) {
            throw new \Exception(
                "Graph API delete failed (HTTP {$response->status()}): " . $response->body()
            );
        }

        $link->delete();
    }

    /**
     * Create a local CalendarEvent + ExternalEventLink and return the CalendarEvent.
     */
    public function persistLocalEvent(
        MicrosoftAccount  $account,
        MicrosoftCalendar $calendar,
        string            $externalEventId,
        array             $eventData,
        ?string           $relatedType = null,
        ?int              $relatedId   = null
    ): CalendarEvent {
        $local = CalendarEvent::create([
            'owner_user_id' => $account->user_id,
            'title'         => $eventData['title'],
            'starts_at'     => $eventData['start'],
            'ends_at'       => $eventData['end'],
            'location'      => $eventData['location'] ?? null,
            'description'   => $eventData['notes'] ?? null,
            'related_type'  => $relatedType,
            'related_id'    => $relatedId,
            'created_by'    => $account->user_id,
            'updated_by'    => $account->user_id,
        ]);

        ExternalEventLink::create([
            'provider'             => 'microsoft',
            'microsoft_account_id' => $account->id,
            'calendar_event_id'    => $local->id,
            'external_calendar_id' => $calendar->calendar_id,
            'external_event_id'    => $externalEventId,
            'last_synced_at'       => now(),
        ]);

        return $local;
    }
}
