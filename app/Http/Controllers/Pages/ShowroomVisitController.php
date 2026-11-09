<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Opportunity;
use App\Models\Setting;
use App\Models\ShowroomVisit;
use App\Services\ShowroomVisitService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShowroomVisitController extends Controller
{
    public function __construct(private ShowroomVisitService $service) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request)
    {
        $scope  = $request->input('scope', 'upcoming');
        $status = $request->input('status', '');
        $hostId = $request->input('host_id', '');
        $q      = trim((string) $request->input('q', ''));

        $visits = ShowroomVisit::with(['customer', 'host', 'opportunity.jobSiteCustomer'])
            ->when($scope === 'upcoming', fn ($qb) => $qb->where('scheduled_at', '>=', now()->startOfDay())->orderBy('scheduled_at'))
            ->when($scope === 'past', fn ($qb) => $qb->where('scheduled_at', '<', now()->startOfDay())->orderByDesc('scheduled_at'))
            ->when($scope === 'all', fn ($qb) => $qb->orderByDesc('scheduled_at'))
            ->when($status, fn ($qb) => $qb->where('status', $status))
            ->when($hostId, fn ($qb) => $qb->where('host_employee_id', $hostId))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('contact_name', 'like', "%{$q}%")
                        ->orWhere('contact_email', 'like', "%{$q}%")
                        ->orWhere('contact_phone', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$q}%")->orWhere('company_name', 'like', "%{$q}%"))
                        ->orWhereHas('opportunity', fn ($o) => $o->where('job_no', 'like', "%{$q}%"));
                });
            })
            ->paginate(25)
            ->withQueryString();

        $changeRequestCount = ShowroomVisit::where('status', 'change_requested')->count();

        return view('pages.showroom-visits.index', [
            'visits'             => $visits,
            'scope'              => $scope,
            'status'             => $status,
            'hostId'             => $hostId,
            'q'                  => $q,
            'hosts'              => $this->hosts(),
            'changeRequestCount' => $changeRequestCount,
        ]);
    }

    // -------------------------------------------------------------------------
    // Create / store
    // -------------------------------------------------------------------------

    public function create(Request $request)
    {
        // Optional pre-selection when launched from an opportunity page
        $preset = null;
        if ($request->old('customer_id') && ($oldCustomer = Customer::find($request->old('customer_id')))) {
            // Re-populate the picker after a validation error
            $preset = [
                'customer'       => $this->customerPayload($oldCustomer),
                'opportunity_id' => (int) $request->old('opportunity_id') ?: null,
            ];
        } elseif ($request->filled('opportunity_id')) {
            $opp = Opportunity::with(['parentCustomer', 'jobSiteCustomer'])->find($request->integer('opportunity_id'));
            $customer = $opp?->jobSiteCustomer ?? $opp?->parentCustomer;
            if ($opp && $customer) {
                $preset = [
                    'customer'       => $this->customerPayload($customer),
                    'opportunity_id' => $opp->id,
                ];
            }
        }

        return view('pages.showroom-visits.create', [
            'hosts'        => $this->hosts(),
            'durations'    => ShowroomVisit::DURATIONS,
            'preset'       => $preset,
            'mailEnabled'  => (bool) Setting::get('mail_notifications_enabled', '1'),
            'smsEnabled'   => (bool) Setting::get('sms_enabled', '0'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        $opportunity = Opportunity::findOrFail($data['opportunity_id']);
        if (! in_array((int) $data['customer_id'], [(int) $opportunity->parent_customer_id, (int) $opportunity->job_site_customer_id], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'opportunity_id' => 'That opportunity does not belong to the selected customer.',
            ]);
        }

        $visit = ShowroomVisit::create([
            'opportunity_id'   => $opportunity->id,
            'customer_id'      => $data['customer_id'],
            'host_employee_id' => $data['host_employee_id'] ?? null,
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'],
            'notes'            => $data['notes'] ?? null,
            'contact_name'     => $data['contact_name'],
            'contact_email'    => $data['contact_email'] ?? null,
            'contact_phone'    => $data['contact_phone'] ?? null,
            'notify_email'     => $request->boolean('notify_email'),
            'notify_sms'       => $request->boolean('notify_sms'),
            'status'           => 'scheduled',
        ]);

        $warning = $this->service->syncCalendar($visit);
        $sent    = $this->sendAndRecord($visit, 'booked');

        return redirect()
            ->route('pages.showroom-visits.show', $visit)
            ->with('success', 'Showroom visit booked.' . $this->sentSuffix($sent))
            ->with('warning', $warning);
    }

    // -------------------------------------------------------------------------
    // Show / edit / update
    // -------------------------------------------------------------------------

    public function show(ShowroomVisit $showroomVisit)
    {
        $showroomVisit->load(['customer', 'host', 'creator', 'opportunity.jobSiteCustomer', 'opportunity.parentCustomer', 'calendarEvent']);

        return view('pages.showroom-visits.show', ['visit' => $showroomVisit]);
    }

    public function edit(ShowroomVisit $showroomVisit)
    {
        $showroomVisit->load(['customer', 'opportunity.jobSiteCustomer']);

        return view('pages.showroom-visits.edit', [
            'visit'       => $showroomVisit,
            'hosts'       => $this->hosts(),
            'durations'   => ShowroomVisit::DURATIONS,
            'mailEnabled' => (bool) Setting::get('mail_notifications_enabled', '1'),
            'smsEnabled'  => (bool) Setting::get('sms_enabled', '0'),
        ]);
    }

    public function update(Request $request, ShowroomVisit $showroomVisit)
    {
        $data  = $this->validated($request, false);
        $visit = $showroomVisit;

        $oldStart     = $visit->scheduled_at->copy();
        $newStart     = Carbon::parse($data['scheduled_at']);
        $timeChanged  = ! $oldStart->equalTo($newStart) || (int) $visit->duration_minutes !== (int) $data['duration_minutes'];
        $wasRequested = $visit->status === 'change_requested';

        $visit->fill([
            'host_employee_id' => $data['host_employee_id'] ?? null,
            'scheduled_at'     => $newStart,
            'duration_minutes' => $data['duration_minutes'],
            'notes'            => $data['notes'] ?? null,
            'contact_name'     => $data['contact_name'],
            'contact_email'    => $data['contact_email'] ?? null,
            'contact_phone'    => $data['contact_phone'] ?? null,
            'notify_email'     => $request->boolean('notify_email'),
            'notify_sms'       => $request->boolean('notify_sms'),
        ]);

        if ($timeChanged) {
            // New time → reminders must fire again for the new slot
            $visit->reminder_day_before_sent_at = null;
            $visit->reminder_morning_sent_at    = null;
        }

        // Saving a visit that had a pending change request resolves it
        if ($wasRequested) {
            $visit->status = 'scheduled';
        }

        $visit->save();

        $warning = $this->service->syncCalendar($visit);

        $sent = ['email' => false, 'sms' => false];
        if (($timeChanged || $wasRequested) && $request->boolean('send_update')) {
            $sent = $this->sendAndRecord($visit, 'updated');
        }

        return redirect()
            ->route('pages.showroom-visits.show', $visit)
            ->with('success', 'Showroom visit updated.' . $this->sentSuffix($sent))
            ->with('warning', $warning);
    }

    public function updateStatus(Request $request, ShowroomVisit $showroomVisit)
    {
        $data = $request->validate([
            'status'              => ['required', Rule::in(['scheduled', 'completed', 'no_show', 'cancelled'])],
            'cancellation_reason' => ['nullable', 'string', 'max:2000'],
            'notify_customer'     => ['nullable', 'boolean'],
        ]);

        $visit = $showroomVisit;
        $visit->status = $data['status'];

        if ($data['status'] === 'cancelled') {
            $visit->cancelled_at        = now();
            $visit->cancelled_by        = 'staff';
            $visit->cancellation_reason = $data['cancellation_reason'] ?? null;
        } elseif ($data['status'] === 'scheduled') {
            // Re-opening a cancelled visit
            $visit->cancelled_at        = null;
            $visit->cancelled_by        = null;
            $visit->cancellation_reason = null;
        }

        $visit->save();
        $warning = $this->service->syncCalendar($visit);

        $sent = ['email' => false, 'sms' => false];
        if ($data['status'] === 'cancelled' && $request->boolean('notify_customer') && $visit->scheduled_at->isFuture()) {
            $sent = $this->service->notifyCustomer($visit, 'cancelled');
        }

        return back()
            ->with('success', 'Visit marked ' . strtolower($visit->statusLabel()) . '.' . $this->sentSuffix($sent))
            ->with('warning', $warning);
    }

    public function resend(ShowroomVisit $showroomVisit)
    {
        abort_unless($showroomVisit->isActive(), 422, 'Only upcoming visits can be resent.');

        $sent = $this->sendAndRecord($showroomVisit, 'booked');

        return back()->with(
            $sent['email'] || $sent['sms'] ? 'success' : 'warning',
            $sent['email'] || $sent['sms']
                ? 'Confirmation resent.' . $this->sentSuffix($sent)
                : 'Nothing was sent — check the contact email/phone and notification options on this visit.'
        );
    }

    public function destroy(ShowroomVisit $showroomVisit)
    {
        $this->service->deleteCalendarEvent($showroomVisit);
        $showroomVisit->delete();

        return redirect()
            ->route('pages.showroom-visits.index')
            ->with('success', 'Showroom visit deleted.');
    }

    // -------------------------------------------------------------------------
    // AJAX — customer search + their opportunities
    // -------------------------------------------------------------------------

    public function searchCustomers(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $customers = Customer::where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('company_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('mobile', 'like', "%{$q}%");
            })
            ->where(function ($query) {
                $query->whereHas('opportunitiesAsParent')->orWhereHas('opportunitiesAsJobSite');
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($customers->map(fn (Customer $c) => $this->customerPayload($c))->values());
    }

    public function customerOpportunities(Customer $customer)
    {
        $opps = Opportunity::with(['jobSiteCustomer', 'parentCustomer'])
            ->where(fn ($q) => $q->where('parent_customer_id', $customer->id)->orWhere('job_site_customer_id', $customer->id))
            ->orderByRaw("status in ('Lost','Closed')")
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($opps->map(fn (Opportunity $o) => [
            'id'             => $o->id,
            'job_no'         => $o->job_no,
            'status'         => $o->status,
            'job_site'       => $o->jobSiteCustomer?->name ?: $o->jobSiteCustomer?->company_name,
            'parent'         => $o->parentCustomer?->company_name ?: $o->parentCustomer?->name,
            'address'        => implode(', ', array_filter([$o->jobSiteCustomer?->address, $o->jobSiteCustomer?->city])),
            'created'        => $o->created_at?->format('M j, Y'),
            'sales_person_1' => $o->sales_person_1,
        ])->values());
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function validated(Request $request, bool $creating): array
    {
        $rules = [
            'host_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'notes'            => ['nullable', 'string', 'max:5000'],
            'contact_name'     => ['required', 'string', 'max:255'],
            'contact_email'    => ['nullable', 'email', 'max:255'],
            'contact_phone'    => ['nullable', 'string', 'max:50'],
        ];

        if ($creating) {
            $rules['customer_id']    = ['required', 'integer', 'exists:customers,id'];
            $rules['opportunity_id'] = ['required', 'integer', 'exists:opportunities,id'];
            $rules['scheduled_at'][] = 'after:now';
        }

        return $request->validate($rules, [
            'opportunity_id.required' => 'Select the opportunity this visit is for.',
            'customer_id.required'    => 'Search for and select a customer.',
            'scheduled_at.after'      => 'The visit must be in the future.',
        ]);
    }

    /** @return array{email: bool, sms: bool} */
    private function sendAndRecord(ShowroomVisit $visit, string $kind): array
    {
        $sent = $this->service->notifyCustomer($visit, $kind);
        if ($sent['email'] || $sent['sms']) {
            $visit->forceFill(['confirmation_sent_at' => now()])->saveQuietly();
        }
        return $sent;
    }

    private function sentSuffix(array $sent): string
    {
        $channels = array_keys(array_filter($sent));
        if (! $channels) {
            return '';
        }
        $labels = ['email' => 'email', 'sms' => 'text'];
        return ' Customer notified by ' . implode(' and ', array_map(fn ($c) => $labels[$c], $channels)) . '.';
    }

    private function hosts()
    {
        return Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'email']);
    }

    private function customerPayload(Customer $c): array
    {
        $label = $c->company_name && $c->name && $c->name !== $c->company_name
            ? "{$c->company_name} ({$c->name})"
            : ($c->company_name ?: $c->name);

        return [
            'id'    => $c->id,
            'label' => $label,
            'name'  => $c->name ?: $c->company_name,
            'email' => $c->email,
            'phone' => $c->mobile ?: $c->phone,
            'city'  => $c->city,
        ];
    }
}
