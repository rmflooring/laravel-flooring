<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\Opportunity;
use App\Models\Rfm;
use App\Services\GraphCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RfmController extends Controller
{
    public function create(Opportunity $opportunity)
    {
        $estimators = Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return view('pages.rfms.create', [
            'opportunity' => $opportunity->load(['parentCustomer', 'jobSiteCustomer', 'projectManager']),
            'estimators'  => $estimators,
            'flooringTypes' => Rfm::FLOORING_TYPES,
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
        try {
            $account = MicrosoftAccount::where('user_id', auth()->id())
                ->where('is_connected', true)
                ->first();

            if ($account) {
                $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                    ->where('group_id', 'b8483c56-fc4b-4734-8011-335b88c7e4ad')
                    ->first();

                if ($calendar) {
                    $rfm->load(['estimator', 'parentCustomer']);

                    $customerName = $opportunity->parentCustomer?->company_name
                        ?: $opportunity->parentCustomer?->name
                        ?: 'Unknown Customer';

                    $estimatorName = $rfm->estimator
                        ? trim($rfm->estimator->first_name . ' ' . $rfm->estimator->last_name)
                        : null;

                    $flooringLabel = implode(' / ', (array) $rfm->flooring_type);
                    $title = 'RFM: ' . $customerName . ' – ' . $flooringLabel;
                    if ($opportunity->job_no) {
                        $title = 'RFM #' . $opportunity->job_no . ': ' . $customerName . ' – ' . $flooringLabel;
                    }

                    $start = \Carbon\Carbon::parse($rfm->scheduled_at);
                    $end   = $start->copy()->addHours(2);

                    $notesParts = [];
                    if ($estimatorName) $notesParts[] = 'Estimator: ' . $estimatorName;
                    $addressLine = implode(', ', array_filter([$rfm->site_address, $rfm->site_city, $rfm->site_postal_code]));
                    if ($addressLine) $notesParts[] = 'Address: ' . $addressLine;
                    if ($rfm->special_instructions) $notesParts[] = "\nNotes:\n" . $rfm->special_instructions;

                    $fullAddress = implode(', ', array_filter([
                        $rfm->site_address,
                        $rfm->site_city,
                        $rfm->site_postal_code,
                    ]));

                    $eventData = [
                        'title'    => $title,
                        'start'    => $start,
                        'end'      => $end,
                        'location' => $fullAddress ?: null,
                        'notes'    => implode("\n", $notesParts),
                    ];

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

                    Log::info('[RFM] Calendar event created', [
                        'rfm_id'          => $rfm->id,
                        'calendar_event_id' => $localEvent->id,
                        'external_event_id' => $externalId,
                    ]);
                } else {
                    Log::warning('[RFM] RM–RFM/Measures calendar not found for account', [
                        'rfm_id'     => $rfm->id,
                        'account_id' => $account->id,
                    ]);
                }
            } else {
                Log::info('[RFM] No connected Microsoft account for user — skipping calendar event', [
                    'rfm_id'  => $rfm->id,
                    'user_id' => auth()->id(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('[RFM] Calendar event creation failed', [
                'rfm_id'  => $rfm->id,
                'error'   => $e->getMessage(),
            ]);
        }
        // --- end calendar ---

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

        $estimators = Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

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

        $rfm->update($data);

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
}
