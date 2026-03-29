<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Mail\RfmCreatedMail;
use App\Mail\RfmUpdatedMail;
use App\Models\Employee;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\Opportunity;
use App\Models\Rfm;
use App\Models\Setting;
use App\Services\CalendarTemplateService;
use App\Services\GraphCalendarService;
use App\Services\SmsService;
use App\Services\SmsTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RfmController extends Controller
{
    public function index(Request $request)
    {
        $q            = trim($request->input('q', ''));
        $status       = $request->input('status', '');
        $estimatorId  = $request->input('estimator_id', '');
        $flooringType = $request->input('flooring_type', '');
        $dateFrom     = $request->input('date_from', '');
        $dateTo       = $request->input('date_to', '');

        $rfms = Rfm::with(['opportunity.projectManager', 'parentCustomer', 'estimator'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('site_address', 'like', "%{$q}%")
                        ->orWhere('site_city', 'like', "%{$q}%")
                        ->orWhereHas('parentCustomer', fn ($cq) => $cq->where('company_name', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
                        ->orWhereHas('estimator', fn ($eq) => $eq->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"))
                        ->orWhereHas('opportunity', fn ($oq) => $oq->where('job_no', 'like', "%{$q}%")->orWhere('job_name', 'like', "%{$q}%"));
                });
            })
            ->when($status,       fn ($query) => $query->where('status', $status))
            ->when($estimatorId,  fn ($query) => $query->where('estimator_id', $estimatorId))
            ->when($flooringType, fn ($query) => $query->whereJsonContains('flooring_type', $flooringType))
            ->when($dateFrom,     fn ($query) => $query->whereDate('scheduled_at', '>=', $dateFrom))
            ->when($dateTo,       fn ($query) => $query->whereDate('scheduled_at', '<=', $dateTo))
            ->orderByDesc('scheduled_at')
            ->paginate(25)
            ->withQueryString();

        $estimators   = Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $statusOptions = Rfm::STATUSES;
        $flooringTypes = Rfm::FLOORING_TYPES;

        return view('pages.rfms.index', compact(
            'rfms', 'estimators', 'statusOptions', 'flooringTypes',
            'q', 'status', 'estimatorId', 'flooringType', 'dateFrom', 'dateTo'
        ));
    }

    public function create(Opportunity $opportunity)
    {
        $estimators = Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email', 'phone']);

        return view('pages.rfms.create', [
            'opportunity'          => $opportunity->load(['parentCustomer', 'jobSiteCustomer', 'projectManager']),
            'estimators'           => $estimators,
            'flooringTypes'        => Rfm::FLOORING_TYPES,
            'smsRfmBookedEnabled'  => (bool) Setting::get('sms_notify_rfm_booked', '0'),
        ]);
    }

    public function store(Opportunity $opportunity, Request $request)
    {
        $data = $request->validate([
            'estimator_id'        => ['required', 'integer', 'exists:employees,id'],
            'flooring_type'       => ['required', 'array', 'min:1'],
            'flooring_type.*'     => ['string', 'in:' . implode(',', Rfm::FLOORING_TYPES)],
            'scheduled_at'        => ['required', 'date'],
            'site_address'        => ['nullable', 'string', 'max:500'],
            'site_city'           => ['nullable', 'string', 'max:255'],
            'site_postal_code'    => ['nullable', 'string', 'max:20'],
            'special_instructions'=> ['nullable', 'string'],
        ]);

        $notifyEstimator    = $request->boolean('notify_estimator', false);
        $notifyPm           = $request->boolean('notify_pm', false);
        $smsNotifyEstimator = $request->boolean('sms_notify_estimator', false);
        $smsNotifyPm        = $request->boolean('sms_notify_pm', false);

        $rfm = Rfm::create([
            'opportunity_id'      => $opportunity->id,
            'estimator_id'        => $data['estimator_id'],
            'parent_customer_id'  => $opportunity->parent_customer_id,
            'job_site_customer_id'=> $opportunity->job_site_customer_id,
            'flooring_type'       => $data['flooring_type'],
            'scheduled_at'        => $data['scheduled_at'],
            'site_address'        => $data['site_address'] ?? null,
            'site_city'           => $data['site_city'] ?? null,
            'site_postal_code'    => $data['site_postal_code'] ?? null,
            'special_instructions'=> $data['special_instructions'] ?? null,
            'status'              => 'pending',
        ]);

        // --- MS365 Calendar Event (best-effort, never blocks the save) ---
        $this->syncCalendarCreate($rfm, $opportunity);
        // --- end calendar ---

        // --- Email notification (best-effort, never blocks the save) ---
        try {
            $rfm->load(['estimator']);
            $opportunity->load(['parentCustomer', 'jobSiteCustomer', 'projectManager']);
            (new RfmCreatedMail($rfm, $opportunity, $notifyEstimator, $notifyPm))->send();
        } catch (\Throwable $e) {
            Log::error('[RFM] Email notification failed', [
                'rfm_id' => $rfm->id,
                'error'  => $e->getMessage(),
            ]);
        }
        // --- end email ---

        // --- SMS notification (best-effort, never blocks the save) ---
        if (Setting::get('sms_notify_rfm_booked')) {
            try {
                $rfm->loadMissing(['estimator']);
                $opportunity->loadMissing(['parentCustomer', 'projectManager']);

                $estimator     = $rfm->estimator;
                $pm            = $opportunity->projectManager;
                $customerName  = $opportunity->parentCustomer?->company_name
                    ?: $opportunity->parentCustomer?->name
                    ?: 'Customer';
                $estimatorName = $estimator ? trim($estimator->first_name . ' ' . $estimator->last_name) : '';
                $fullAddress   = implode(', ', array_filter([
                    $rfm->site_address, $rfm->site_city, $rfm->site_postal_code,
                ]));
                $scheduled     = \Carbon\Carbon::parse($rfm->scheduled_at);

                $vars = [
                    'customer_name'        => $customerName,
                    'rfm_date'             => $scheduled->format('M j, Y'),
                    'rfm_time'             => $scheduled->format('g:ia'),
                    'site_address'         => $fullAddress,
                    'special_instructions' => $rfm->special_instructions ?? '',
                    'estimator_name'       => $estimatorName,
                    'estimator_first_name' => explode(' ', trim($estimatorName))[0],
                    'pm_name'              => $pm?->name ?? '',
                    'pm_first_name'        => explode(' ', trim($pm?->name ?? ''))[0],
                    'rfm_link'             => route('mobile.rfms.show', $rfm->id),
                ];

                $recipients = array_filter(explode(',', Setting::get('sms_rfm_booked_to', 'estimator,pm')));
                $sms        = new SmsService();
                $tpl        = new SmsTemplateService();
                $body       = $tpl->renderTemplate('rfm_booked', $vars);

                if ($smsNotifyEstimator && in_array('estimator', $recipients) && $estimator?->phone) {
                    $sms->send($estimator->phone, $body, 'rfm_booked', $rfm);
                }

                if ($smsNotifyPm && in_array('pm', $recipients) && $pm?->mobile) {
                    $sms->send($pm->mobile, $body, 'rfm_booked', $rfm);
                }

                if (in_array('customer', $recipients)) {
                    $phone = $opportunity->parentCustomer?->mobile ?? $opportunity->parentCustomer?->phone ?? null;
                    if ($phone) {
                        $sms->send($phone, $body, 'rfm_booked', $rfm);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('[RFM SMS] booked send failed', [
                    'rfm_id' => $rfm->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }
        // --- end SMS ---

        return redirect()
            ->route('pages.opportunities.show', $opportunity->id)
            ->with('success', 'RFM created successfully.');
    }

    public function show(Opportunity $opportunity, Rfm $rfm)
    {
        abort_if($rfm->opportunity_id !== $opportunity->id, 404);

        $rfm->load(['estimator', 'parentCustomer', 'jobSiteCustomer', 'calendarEvent']);

        return view('pages.rfms.show', [
            'opportunity' => $opportunity->load(['parentCustomer', 'jobSiteCustomer', 'projectManager']),
            'rfm'         => $rfm,
        ]);
    }

    public function edit(Opportunity $opportunity, Rfm $rfm)
    {
        abort_if($rfm->opportunity_id !== $opportunity->id, 404);

        $estimators = Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('pages.rfms.edit', [
            'opportunity'   => $opportunity->load(['parentCustomer', 'jobSiteCustomer', 'projectManager']),
            'rfm'           => $rfm,
            'estimators'    => $estimators,
            'flooringTypes' => Rfm::FLOORING_TYPES,
        ]);
    }

    public function update(Opportunity $opportunity, Rfm $rfm, Request $request)
    {
        abort_if($rfm->opportunity_id !== $opportunity->id, 404);

        $data = $request->validate([
            'estimator_id'        => ['required', 'integer', 'exists:employees,id'],
            'flooring_type'       => ['required', 'array', 'min:1'],
            'flooring_type.*'     => ['string', 'in:' . implode(',', Rfm::FLOORING_TYPES)],
            'scheduled_at'        => ['required', 'date'],
            'site_address'        => ['nullable', 'string', 'max:500'],
            'site_city'           => ['nullable', 'string', 'max:255'],
            'site_postal_code'    => ['nullable', 'string', 'max:20'],
            'special_instructions'=> ['nullable', 'string'],
        ]);

        $notifyEstimator = $request->boolean('notify_estimator', false);
        $notifyPm        = $request->boolean('notify_pm', false);

        // Snapshot values before save for change detection
        $oldEstimatorName = $rfm->estimator
            ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
            : '—';
        $oldScheduled  = $rfm->scheduled_at->format('M j, Y g:i A');
        $oldAddress    = $rfm->site_address ?? '';
        $oldCity       = $rfm->site_city ?? '';
        $oldPostal     = $rfm->site_postal_code ?? '';

        $rfm->update($data);

        // If scheduled_at changed, clear the reminder stamp so it fires again for the new date
        $newScheduledRaw = \Carbon\Carbon::parse($data['scheduled_at'])->format('M j, Y g:i A');
        if ($oldScheduled !== $newScheduledRaw) {
            $rfm->update(['sms_reminder_sent_at' => null]);
        }

        $rfm->load(['estimator']);

        // Build change list
        $changes = [];

        $newEstimatorName = $rfm->estimator
            ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
            : '—';
        if ($oldEstimatorName !== $newEstimatorName) {
            $changes['Estimator'] = ['from' => $oldEstimatorName, 'to' => $newEstimatorName];
        }

        $newScheduled = $rfm->scheduled_at->format('M j, Y g:i A');
        if ($oldScheduled !== $newScheduled) {
            $changes['Scheduled'] = ['from' => $oldScheduled, 'to' => $newScheduled];
        }

        $newAddress = $data['site_address'] ?? '';
        if ($oldAddress !== $newAddress) {
            $changes['Street Address'] = ['from' => $oldAddress ?: '—', 'to' => $newAddress ?: '—'];
        }

        $newCity = $data['site_city'] ?? '';
        if ($oldCity !== $newCity) {
            $changes['City'] = ['from' => $oldCity ?: '—', 'to' => $newCity ?: '—'];
        }

        $newPostal = $data['site_postal_code'] ?? '';
        if ($oldPostal !== $newPostal) {
            $changes['Postal Code'] = ['from' => $oldPostal ?: '—', 'to' => $newPostal ?: '—'];
        }

        // --- Email notification (best-effort, never blocks the save) ---
        if ($notifyEstimator || $notifyPm) {
            try {
                $opportunity->load(['parentCustomer', 'jobSiteCustomer', 'projectManager']);
                (new RfmUpdatedMail($rfm, $opportunity, $changes, $notifyEstimator, $notifyPm))->send();
            } catch (\Throwable $e) {
                Log::error('[RFM] Update email notification failed', [
                    'rfm_id' => $rfm->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }
        // --- end email ---

        // --- SMS notification (best-effort, never blocks the save) ---
        if (Setting::get('sms_notify_rfm_updated')) {
            try {
                $rfm->loadMissing(['estimator']);
                $opportunity->loadMissing(['parentCustomer', 'projectManager']);

                $estimator     = $rfm->estimator;
                $pm            = $opportunity->projectManager;
                $customerName  = $opportunity->parentCustomer?->company_name
                    ?: $opportunity->parentCustomer?->name
                    ?: 'Customer';
                $estimatorName = $estimator ? trim($estimator->first_name . ' ' . $estimator->last_name) : '';
                $fullAddress   = implode(', ', array_filter([
                    $rfm->site_address, $rfm->site_city, $rfm->site_postal_code,
                ]));
                $scheduled     = \Carbon\Carbon::parse($rfm->scheduled_at);

                $vars = [
                    'customer_name'        => $customerName,
                    'rfm_date'             => $scheduled->format('M j, Y'),
                    'rfm_time'             => $scheduled->format('g:ia'),
                    'site_address'         => $fullAddress,
                    'special_instructions' => $rfm->special_instructions ?? '',
                    'estimator_name'       => $estimatorName,
                    'estimator_first_name' => explode(' ', trim($estimatorName))[0],
                    'pm_name'              => $pm?->name ?? '',
                    'pm_first_name'        => explode(' ', trim($pm?->name ?? ''))[0],
                    'rfm_link'             => route('mobile.rfms.show', $rfm->id),
                ];

                $recipients = array_filter(explode(',', Setting::get('sms_rfm_updated_to', 'estimator,pm')));
                $sms        = new SmsService();
                $tpl        = new SmsTemplateService();
                $body       = $tpl->renderTemplate('rfm_updated', $vars);

                if (in_array('estimator', $recipients) && $estimator?->phone) {
                    $sms->send($estimator->phone, $body, 'rfm_updated', $rfm);
                }

                if (in_array('pm', $recipients) && $pm?->mobile) {
                    $sms->send($pm->mobile, $body, 'rfm_updated', $rfm);
                }

                if (in_array('customer', $recipients)) {
                    $phone = $opportunity->parentCustomer?->mobile ?? $opportunity->parentCustomer?->phone ?? null;
                    if ($phone) {
                        $sms->send($phone, $body, 'rfm_updated', $rfm);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('[RFM SMS] updated send failed', [
                    'rfm_id' => $rfm->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }
        // --- end SMS ---

        // --- MS365 Calendar sync (best-effort, never blocks the save) ---
        $this->syncCalendarUpdate($rfm, $opportunity);
        // --- end calendar ---

        return redirect()
            ->route('pages.opportunities.show', $opportunity->id)
            ->with('success', 'RFM updated.');
    }

    public function updateStatus(Opportunity $opportunity, Rfm $rfm, Request $request)
    {
        abort_if($rfm->opportunity_id !== $opportunity->id, 404);

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Rfm::STATUSES)],
        ]);

        $rfm->update(['status' => $request->input('status')]);

        return back()->with('success', 'RFM status updated.');
    }

    // ── Calendar helpers ─────────────────────────────────────────────

    private function buildRfmEventData(Rfm $rfm, Opportunity $opportunity): array
    {
        $rfm->loadMissing(['estimator']);
        $opportunity->loadMissing(['parentCustomer', 'projectManager']);

        $customerName  = $opportunity->parentCustomer?->company_name
            ?: $opportunity->parentCustomer?->name
            ?: 'Unknown Customer';
        $estimatorName = $rfm->estimator
            ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
            : null;
        $flooringLabel = implode(' / ', (array) $rfm->flooring_type);
        $fullAddress   = implode(', ', array_filter([
            $rfm->site_address,
            $rfm->site_city,
            $rfm->site_postal_code,
        ]));
        $pmName = $opportunity->projectManager?->name ?? '';

        $vars = [
            'customer_name'        => $customerName,
            'estimator_name'       => $estimatorName ?? '',
            'job_number'           => $opportunity->job_no ?? '',
            'flooring_type'        => $flooringLabel,
            'site_address'         => $fullAddress,
            'special_instructions' => $rfm->special_instructions ?? '',
            'pm_name'              => $pmName,
            'pm_first_name'        => explode(' ', trim($pmName))[0],
        ];

        $start    = \Carbon\Carbon::parse($rfm->scheduled_at);
        $rendered = app(CalendarTemplateService::class)->renderTemplate('rfm_calendar', $vars);

        return [
            'title'    => $rendered['title'],
            'start'    => $start,
            'end'      => $start->copy()->addHours(2),
            'location' => $fullAddress ?: null,
            'notes'    => $rendered['notes'],
        ];
    }

    private function syncCalendarUpdate(Rfm $rfm, Opportunity $opportunity): void
    {
        if (empty($rfm->calendar_event_id)) {
            // No existing event — try to create one now
            $this->syncCalendarCreate($rfm, $opportunity);
            return;
        }

        try {
            $rfm->loadMissing(['calendarEvent.externalLink']);

            $link = $rfm->calendarEvent?->externalLink;
            if (! $link) {
                Log::warning('[RFM] No ExternalEventLink found for update — skipping', ['rfm_id' => $rfm->id]);
                return;
            }

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if (! $account) return;

            $eventData = $this->buildRfmEventData($rfm, $opportunity);
            $service   = new GraphCalendarService();
            $service->updateEvent($account, $link, $eventData);

            $rfm->calendarEvent?->update([
                'title'       => $eventData['title'],
                'starts_at'   => $eventData['start'],
                'ends_at'     => $eventData['end'],
                'location'    => $eventData['location'],
                'description' => $eventData['notes'],
            ]);

            Log::info('[RFM] Calendar event updated', ['rfm_id' => $rfm->id]);
        } catch (\Throwable $e) {
            Log::error('[RFM] Calendar event update failed', [
                'rfm_id' => $rfm->id,
                'error'  => $e->getMessage(),
            ]);
            session()->flash('warning', 'RFM saved, but the calendar event could not be updated. Your Microsoft 365 connection may have expired — check Settings → Integrations to reconnect.');
        }
    }

    private function syncCalendarCreate(Rfm $rfm, Opportunity $opportunity): void
    {
        try {
            $account = MicrosoftAccount::where('user_id', auth()->id())
                ->where('is_connected', true)
                ->first();

            if (! $account) {
                Log::info('[RFM] No connected Microsoft account for user — skipping calendar event', [
                    'rfm_id'  => $rfm->id,
                    'user_id' => auth()->id(),
                ]);
                return;
            }

            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->where('group_id', 'b8483c56-fc4b-4734-8011-335b88c7e4ad')
                ->first();

            if (! $calendar) {
                Log::warning('[RFM] RM–RFM/Measures calendar not found for account', [
                    'rfm_id'     => $rfm->id,
                    'account_id' => $account->id,
                ]);
                return;
            }

            $eventData   = $this->buildRfmEventData($rfm, $opportunity);
            $service     = new GraphCalendarService();
            $externalId  = $service->createEvent($account, $calendar, $eventData);
            $localEvent  = $service->persistLocalEvent(
                $account,
                $calendar,
                $externalId,
                $eventData,
                Rfm::class,
                $rfm->id
            );

            $rfm->update([
                'microsoft_calendar_id' => $calendar->id,
                'calendar_event_id'     => $localEvent->id,
            ]);

            Log::info('[RFM] Calendar event created', ['rfm_id' => $rfm->id]);
        } catch (\Throwable $e) {
            Log::error('[RFM] Calendar event creation failed', [
                'rfm_id' => $rfm->id,
                'error'  => $e->getMessage(),
            ]);
            session()->flash('warning', 'RFM saved, but the calendar event could not be created. Your Microsoft 365 connection may have expired — check Settings → Integrations to reconnect.');
        }
    }
}
