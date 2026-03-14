<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\ExternalEventLink;
use App\Models\MicrosoftAccount;
use App\Models\MicrosoftCalendar;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\GraphCalendarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    // ── CRUD ──────────────────────────────────────────────────────

    public function create(Sale $sale)
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('pages.work-orders.create', compact('sale', 'users'));
    }

    public function store(Sale $sale, Request $request)
    {
        $data = $request->validate([
            'work_type'           => ['required', 'string', 'max:255'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_date'      => ['nullable', 'date'],
            'scheduled_time'      => ['nullable', 'date_format:H:i'],
            'notes'               => ['nullable', 'string'],
        ]);

        // Auto-advance to scheduled if assignee + date are set
        $status = 'created';
        if (! empty($data['assigned_to_user_id']) && ! empty($data['scheduled_date'])) {
            $status = 'scheduled';
        }

        $workOrder = WorkOrder::create([
            'sale_id'             => $sale->id,
            'work_type'           => $data['work_type'],
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
            'scheduled_date'      => $data['scheduled_date'] ?? null,
            'scheduled_time'      => $data['scheduled_time'] ?? null,
            'notes'               => $data['notes'] ?? null,
            'status'              => $status,
        ]);

        $this->syncCalendarCreate($workOrder);

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', 'Work order ' . $workOrder->wo_number . ' created.');
    }

    public function show(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $workOrder->load(['assignedTo', 'calendarEvent.externalLink']);

        return view('pages.work-orders.show', compact('sale', 'workOrder'));
    }

    public function edit(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $workOrder->load(['assignedTo', 'calendarEvent.externalLink']);

        return view('pages.work-orders.edit', compact('sale', 'workOrder', 'users'));
    }

    public function update(Sale $sale, WorkOrder $workOrder, Request $request)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $data = $request->validate([
            'work_type'           => ['required', 'string', 'max:255'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_date'      => ['nullable', 'date'],
            'scheduled_time'      => ['nullable', 'date_format:H:i'],
            'notes'               => ['nullable', 'string'],
            'status'              => ['required', 'string', 'in:' . implode(',', WorkOrder::STATUSES)],
        ]);

        // Enforce transition rules
        if ($data['status'] === 'scheduled' && $workOrder->status === 'created') {
            if (empty($data['assigned_to_user_id']) || empty($data['scheduled_date'])) {
                return back()
                    ->withInput()
                    ->withErrors(['status' => 'Cannot mark as Scheduled without an assignee and scheduled date.']);
            }
        }

        // Detect calendar-relevant changes
        $calendarFieldsChanged =
            (int) ($data['assigned_to_user_id'] ?? 0) !== (int) ($workOrder->assigned_to_user_id ?? 0)
            || ($data['scheduled_date'] ?? '') !== ($workOrder->scheduled_date?->format('Y-m-d') ?? '')
            || ($data['scheduled_time'] ?? '') !== ($workOrder->scheduled_time ?? '');

        $wasCancelled   = $workOrder->status === 'cancelled';
        $beingCancelled = $data['status'] === 'cancelled';

        $workOrder->update($data);
        $workOrder->refresh();

        if (! $wasCancelled) {
            if ($beingCancelled) {
                $this->cancelCalendarEvent($workOrder);
            } elseif ($calendarFieldsChanged) {
                if ($workOrder->calendar_event_id) {
                    $this->syncCalendarUpdate($workOrder);
                } else {
                    $this->syncCalendarCreate($workOrder);
                }
            }
        }

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', 'Work order ' . $workOrder->wo_number . ' updated.');
    }

    public function destroy(Sale $sale, WorkOrder $workOrder)
    {
        abort_if($workOrder->sale_id !== $sale->id, 404);

        $this->cancelCalendarEvent($workOrder);
        $workOrder->delete();

        return redirect()
            ->route('pages.sales.show', $sale)
            ->with('success', 'Work order deleted.');
    }

    // ── Calendar helpers ──────────────────────────────────────────

    /**
     * Build the event data array for a WO.
     */
    private function buildEventData(WorkOrder $workOrder): array
    {
        $sale = $workOrder->sale ?? Sale::find($workOrder->sale_id);

        $date = $workOrder->scheduled_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $time = $workOrder->scheduled_time ?? '08:00';

        $start = Carbon::parse($date . ' ' . $time);
        $end   = $start->copy()->addHours(2);

        $title = $workOrder->wo_number . ': ' . $workOrder->work_type;
        if ($sale->job_name) {
            $title = $workOrder->wo_number . ' · ' . $sale->job_name . ' — ' . $workOrder->work_type;
        }

        $notesParts = [];
        if ($sale->sale_number) {
            $notesParts[] = 'Sale: ' . $sale->sale_number;
        }
        if ($workOrder->assignedTo) {
            $notesParts[] = 'Assigned to: ' . $workOrder->assignedTo->name;
        }
        if ($workOrder->notes) {
            $notesParts[] = "\nNotes:\n" . $workOrder->notes;
        }

        return [
            'title'    => $title,
            'start'    => $start,
            'end'      => $end,
            'location' => $sale->job_address ?? null,
            'notes'    => implode("\n", $notesParts),
        ];
    }

    /**
     * Create a new calendar event for the WO (best-effort).
     * Only fires when assigned_to + scheduled_date are both set.
     */
    private function syncCalendarCreate(WorkOrder $workOrder): void
    {
        if (empty($workOrder->assigned_to_user_id) || empty($workOrder->scheduled_date)) {
            return;
        }

        try {
            $workOrder->load(['assignedTo', 'sale']);

            $account = MicrosoftAccount::where('user_id', $workOrder->assigned_to_user_id)
                ->where('is_connected', true)
                ->first();

            if (! $account) {
                Log::info('[WO] No connected Microsoft account for assigned user — skipping calendar', [
                    'wo_id'   => $workOrder->id,
                    'user_id' => $workOrder->assigned_to_user_id,
                ]);
                return;
            }

            $calendar = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->where('is_enabled', true)
                ->orderByDesc('is_primary')
                ->first();

            if (! $calendar) {
                Log::warning('[WO] No enabled calendar for assigned user — skipping calendar', [
                    'wo_id'      => $workOrder->id,
                    'account_id' => $account->id,
                ]);
                return;
            }

            $eventData  = $this->buildEventData($workOrder);
            $service    = new GraphCalendarService();
            $externalId = $service->createEvent($account, $calendar, $eventData);
            $localEvent = $service->persistLocalEvent(
                $account,
                $calendar,
                $externalId,
                $eventData,
                WorkOrder::class,
                $workOrder->id
            );

            $workOrder->update(['calendar_event_id' => $localEvent->id]);

            Log::info('[WO] Calendar event created', [
                'wo_id'             => $workOrder->id,
                'calendar_event_id' => $localEvent->id,
                'external_event_id' => $externalId,
            ]);
        } catch (\Throwable $e) {
            Log::error('[WO] Calendar event creation failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update the existing calendar event (best-effort).
     */
    private function syncCalendarUpdate(WorkOrder $workOrder): void
    {
        if (empty($workOrder->calendar_event_id)) {
            return;
        }

        try {
            $workOrder->loadMissing(['assignedTo', 'sale', 'calendarEvent.externalLink']);

            $link = $workOrder->calendarEvent?->externalLink;
            if (! $link) {
                Log::warning('[WO] No ExternalEventLink found for update — skipping', ['wo_id' => $workOrder->id]);
                return;
            }

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if (! $account) {
                return;
            }

            $eventData = $this->buildEventData($workOrder);
            $service   = new GraphCalendarService();
            $service->updateEvent($account, $link, $eventData);

            // Also update the local CalendarEvent record
            $workOrder->calendarEvent?->update([
                'title'       => $eventData['title'],
                'starts_at'   => $eventData['start'],
                'ends_at'     => $eventData['end'],
                'location'    => $eventData['location'],
                'description' => $eventData['notes'],
            ]);

            Log::info('[WO] Calendar event updated', ['wo_id' => $workOrder->id]);
        } catch (\Throwable $e) {
            Log::error('[WO] Calendar event update failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete the calendar event when a WO is cancelled or destroyed (best-effort).
     */
    private function cancelCalendarEvent(WorkOrder $workOrder): void
    {
        if (empty($workOrder->calendar_event_id)) {
            return;
        }

        try {
            $workOrder->loadMissing(['calendarEvent.externalLink']);

            $link = $workOrder->calendarEvent?->externalLink;
            if (! $link) {
                return;
            }

            $account = MicrosoftAccount::find($link->microsoft_account_id);
            if ($account) {
                $service = new GraphCalendarService();
                $service->deleteEvent($account, $link);
            }

            // Soft-delete the local CalendarEvent and clear the FK
            $workOrder->calendarEvent?->delete();
            $workOrder->update(['calendar_event_id' => null]);

            Log::info('[WO] Calendar event deleted', ['wo_id' => $workOrder->id]);
        } catch (\Throwable $e) {
            Log::error('[WO] Calendar event deletion failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
