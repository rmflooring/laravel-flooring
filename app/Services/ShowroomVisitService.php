<?php

namespace App\Services;

use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\Setting;
use App\Models\ShowroomVisit;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Notifications + MS365 calendar sync for showroom visits.
 *
 * Every public method is best-effort: failures are logged and reported back
 * to the caller as a boolean / warning string, never thrown — a booking must
 * never fail because Graph or VoIP.ms is having a bad day.
 */
class ShowroomVisitService
{
    // Customer-facing notice kinds → [email subject suffix, SMS template type]
    public const CUSTOMER_NOTICES = [
        'booked'              => ['Your showroom visit is booked',         'showroom_booked_customer'],
        'updated'             => ['Your showroom visit has been updated',  'showroom_updated_customer'],
        'reminder_day_before' => ['Reminder: your showroom visit is tomorrow', 'showroom_reminder_customer'],
        'reminder_morning'    => ['Reminder: your showroom visit is today', 'showroom_reminder_morning_customer'],
        'cancelled'           => ['Your showroom visit has been cancelled', 'showroom_cancelled_customer'],
    ];

    // -------------------------------------------------------------------------
    // Customer notifications
    // -------------------------------------------------------------------------

    /**
     * Send a customer notice by email and/or SMS, honouring the visit's
     * notify_email / notify_sms flags plus the global mail/SMS switches.
     *
     * @return array{email: bool, sms: bool}
     */
    public function notifyCustomer(ShowroomVisit $visit, string $kind): array
    {
        $result = ['email' => false, 'sms' => false];

        if (! isset(self::CUSTOMER_NOTICES[$kind])) {
            return $result;
        }

        [$subjectLine, $smsType] = self::CUSTOMER_NOTICES[$kind];
        $visit->loadMissing(['host', 'customer', 'opportunity']);
        $vars = $this->templateVars($visit);

        // ── Email ──────────────────────────────────────────────
        if ($visit->notify_email && $visit->contact_email) {
            try {
                $html = view('emails.showroom-visits.customer', array_merge($vars, [
                    'kind'  => $kind,
                    'visit' => $visit,
                ]))->render();

                $result['email'] = app(GraphMailService::class)->send(
                    to: $visit->contact_email,
                    subject: "{$subjectLine} — {$vars['visit_date']} at {$vars['visit_time']}",
                    body: $html,
                    type: 'showroom_visit_' . $kind,
                    relatedId: $visit->id,
                    relatedType: ShowroomVisit::class,
                    isHtml: true,
                );
            } catch (\Throwable $e) {
                Log::error('[ShowroomVisit] Customer email failed', [
                    'visit_id' => $visit->id,
                    'kind'     => $kind,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        // ── SMS ────────────────────────────────────────────────
        if ($visit->notify_sms && $visit->contact_phone && ! $visit->customer?->sms_opted_out) {
            try {
                $body = (new SmsTemplateService())->renderTemplate($smsType, $vars);
                if ($body !== '') {
                    $result['sms'] = (new SmsService())->send($visit->contact_phone, $body, $smsType, $visit);
                }
            } catch (\Throwable $e) {
                Log::error('[ShowroomVisit] Customer SMS failed', [
                    'visit_id' => $visit->id,
                    'kind'     => $kind,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Staff alerts (customer cancelled / asked for a change via their link)
    // -------------------------------------------------------------------------

    public function notifyStaff(ShowroomVisit $visit, string $kind): void
    {
        $visit->loadMissing(['host', 'customer', 'opportunity', 'creator']);

        $recipients = collect([
            Setting::get('showroom_visit_notify_email') ?: Setting::get('mail_from_address'),
            $visit->host?->email,
            $visit->creator?->email,
        ])->filter()->map(fn ($e) => strtolower(trim($e)))->unique()->values()->all();

        if (empty($recipients)) {
            return;
        }

        $vars     = $this->templateVars($visit);
        $customer = $vars['customer_name'];
        $subject  = $kind === 'cancelled'
            ? "Showroom visit CANCELLED by customer — {$customer} ({$vars['visit_date']} {$vars['visit_time']})"
            : "Showroom visit change requested — {$customer} ({$vars['visit_date']} {$vars['visit_time']})";

        try {
            $html = view('emails.showroom-visits.staff-alert', array_merge($vars, [
                'kind'    => $kind,
                'visit'   => $visit,
                'fm_link' => route('pages.showroom-visits.show', $visit->id),
            ]))->render();

            app(GraphMailService::class)->send(
                to: $recipients,
                subject: $subject,
                body: $html,
                type: 'showroom_visit_staff_' . $kind,
                relatedId: $visit->id,
                relatedType: ShowroomVisit::class,
                isHtml: true,
            );
        } catch (\Throwable $e) {
            Log::error('[ShowroomVisit] Staff alert failed', [
                'visit_id' => $visit->id,
                'kind'     => $kind,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Template variables (shared by email, SMS and calendar)
    // -------------------------------------------------------------------------

    public function templateVars(ShowroomVisit $visit): array
    {
        $customerName = $visit->contact_name
            ?: $visit->customer?->name
            ?: $visit->customer?->company_name
            ?: 'there';

        $address = implode(', ', array_filter([
            Setting::get('branding_address'),
            Setting::get('branding_city'),
            Setting::get('branding_province'),
            Setting::get('branding_postal'),
        ]));

        return [
            'customer_name'       => $customerName,
            'customer_first_name' => explode(' ', trim($customerName))[0],
            'visit_date'          => $visit->scheduled_at->format('D, M j, Y'),
            'visit_time'          => $visit->scheduled_at->format('g:ia'),
            'visit_end_time'      => $visit->endsAt()->format('g:ia'),
            'host_name'           => $visit->hostName(),
            'job_number'          => $visit->opportunity?->job_no ?? '',
            'showroom_address'    => $address,
            'showroom_phone'      => (string) Setting::get('branding_phone', ''),
            'showroom_email'      => (string) (Setting::get('branding_email') ?: Setting::get('mail_from_address', '')),
            'company_name'        => (string) Setting::get('branding_company_name', 'RM Flooring'),
            'manage_link'         => $visit->publicUrl(),
        ];
    }

    // -------------------------------------------------------------------------
    // MS365 calendar ("Team . Showroom" group calendar)
    // -------------------------------------------------------------------------

    /**
     * Create the calendar event, or update it if one already exists.
     * Returns a warning message for the UI on failure, null on success/skip.
     */
    public function syncCalendar(ShowroomVisit $visit): ?string
    {
        if ($visit->status === 'cancelled') {
            $this->deleteCalendarEvent($visit);
            return null;
        }

        // Completed / no-show: leave the event in place as history.
        if (! $visit->isActive()) {
            return null;
        }

        $visit->loadMissing(['calendarEvent.externalLink']);
        $link = $visit->calendarEvent?->externalLink;

        try {
            $service   = new GraphCalendarService();
            $eventData = $this->eventData($visit);

            if ($link && ($account = MicrosoftAccount::find($link->microsoft_account_id))) {
                $service->updateEvent($account, $link, $eventData);
                $visit->calendarEvent->update([
                    'title'       => $eventData['title'],
                    'starts_at'   => $eventData['start'],
                    'ends_at'     => $eventData['end'],
                    'location'    => $eventData['location'],
                    'description' => $eventData['notes'],
                ]);
                return null;
            }

            [$account, $calendar] = $this->resolveCalendar();
            if (! $account || ! $calendar) {
                Log::warning('[ShowroomVisit] Showroom calendar not found for any connected Microsoft account', [
                    'visit_id' => $visit->id,
                ]);
                return 'Visit saved, but the Showroom calendar was not found. In Settings → Integrations → Microsoft, run "Discover calendars" so the Team Showroom calendar is picked up.';
            }

            $externalId = $service->createEvent($account, $calendar, $eventData);
            $local      = $service->persistLocalEvent($account, $calendar, $externalId, $eventData, ShowroomVisit::class, $visit->id);

            $visit->forceFill([
                'microsoft_calendar_id' => $calendar->id,
                'calendar_event_id'     => $local->id,
            ])->saveQuietly();

            return null;
        } catch (\Throwable $e) {
            Log::error('[ShowroomVisit] Calendar sync failed', [
                'visit_id' => $visit->id,
                'error'    => $e->getMessage(),
            ]);
            return 'Visit saved, but the Showroom calendar event could not be saved. The Microsoft 365 connection may have expired — check Settings → Integrations.';
        }
    }

    public function deleteCalendarEvent(ShowroomVisit $visit): void
    {
        if (empty($visit->calendar_event_id)) {
            return;
        }

        try {
            $visit->loadMissing(['calendarEvent.externalLink']);
            $link = $visit->calendarEvent?->externalLink;

            if ($link && ($account = MicrosoftAccount::find($link->microsoft_account_id))) {
                (new GraphCalendarService())->deleteEvent($account, $link);
            }

            $visit->calendarEvent?->delete();
            $visit->forceFill(['calendar_event_id' => null, 'microsoft_calendar_id' => null])->saveQuietly();
        } catch (\Throwable $e) {
            Log::error('[ShowroomVisit] Calendar delete failed', [
                'visit_id' => $visit->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find the Showroom group calendar. Prefers the current user's connected
     * account; falls back to any connected account that has it (group calendars
     * are shared, so staff without an MS connection can still book).
     *
     * @return array{0: ?MicrosoftAccount, 1: ?MicrosoftCalendar}
     */
    private function resolveCalendar(): array
    {
        $accounts = MicrosoftAccount::where('is_connected', true)
            ->orderByRaw('user_id = ? desc', [auth()->id() ?? 0])
            ->get();

        foreach ($accounts as $account) {
            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->where('name', 'like', '%Showroom%')
                ->orderByRaw('group_id is null') // prefer the real group calendar over a personal subscription
                ->first();

            if ($calendar) {
                return [$account, $calendar];
            }
        }

        return [null, null];
    }

    private function eventData(ShowroomVisit $visit): array
    {
        $visit->loadMissing(['host', 'customer', 'opportunity']);
        $vars = $this->templateVars($visit);

        $title = 'Showroom Visit — ' . $vars['customer_name']
            . ($vars['job_number'] ? " ({$vars['job_number']})" : '')
            . ($vars['host_name'] ? " w/ {$vars['host_name']}" : '');

        if ($visit->status === 'change_requested') {
            $title = '[CHANGE REQUESTED] ' . $title;
        }

        $notes = implode("\n", array_filter([
            'Customer: ' . $vars['customer_name'],
            $visit->contact_phone ? 'Phone: ' . $visit->contact_phone : null,
            $visit->contact_email ? 'Email: ' . $visit->contact_email : null,
            $vars['job_number'] ? 'Job #: ' . $vars['job_number'] : null,
            $vars['host_name'] ? 'Host: ' . $vars['host_name'] : null,
            $visit->notes ? "\nNotes:\n" . $visit->notes : null,
            "\nFloor Manager: " . route('pages.showroom-visits.show', $visit->id),
        ]));

        $attendees = [];
        if ($visit->host?->email) {
            $attendees[] = ['name' => $vars['host_name'], 'email' => $visit->host->email];
        }

        return [
            'title'     => $title,
            'start'     => $visit->scheduled_at->copy(),
            'end'       => $visit->endsAt(),
            'location'  => $vars['company_name'] . ' Showroom',
            'notes'     => $notes,
            'attendees' => $attendees,
        ];
    }
}
