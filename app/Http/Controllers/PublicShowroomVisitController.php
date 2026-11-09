<?php

namespace App\Http\Controllers;

use App\Models\ShowroomVisit;
use App\Services\ShowroomVisitService;
use Illuminate\Http\Request;

/**
 * Customer-facing "manage my appointment" page, reached from the link in the
 * confirmation / reminder email + SMS. No auth — the 48-char token is the key.
 */
class PublicShowroomVisitController extends Controller
{
    public function __construct(private ShowroomVisitService $service) {}

    public function show(string $token)
    {
        $visit = $this->find($token);

        return view('showroom-visit.show', [
            'visit' => $visit,
            'vars'  => $this->service->templateVars($visit),
        ]);
    }

    public function cancel(Request $request, string $token)
    {
        $visit = $this->find($token);

        if (! $visit->customerCanModify()) {
            return redirect()->route('showroom-visit.public.show', $token);
        }

        $data = $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $visit->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancelled_by'        => 'customer',
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
        ]);

        $this->service->syncCalendar($visit);
        $this->service->notifyStaff($visit, 'cancelled');

        return redirect()
            ->route('showroom-visit.public.show', $token)
            ->with('public_status', 'cancelled');
    }

    public function requestChange(Request $request, string $token)
    {
        $visit = $this->find($token);

        if (! $visit->customerCanModify()) {
            return redirect()->route('showroom-visit.public.show', $token);
        }

        $data = $request->validate([
            'change_request_preferred' => ['required', 'string', 'max:1000'],
            'change_request_note'      => ['nullable', 'string', 'max:2000'],
        ], [
            'change_request_preferred.required' => 'Please let us know which days or times work better for you.',
        ]);

        $visit->update([
            'status'                   => 'change_requested',
            'change_requested_at'      => now(),
            'change_request_preferred' => $data['change_request_preferred'],
            'change_request_note'      => $data['change_request_note'] ?? null,
        ]);

        $this->service->syncCalendar($visit); // flags the event title as [CHANGE REQUESTED]
        $this->service->notifyStaff($visit, 'change_requested');

        return redirect()
            ->route('showroom-visit.public.show', $token)
            ->with('public_status', 'change_requested');
    }

    private function find(string $token): ShowroomVisit
    {
        return ShowroomVisit::with(['host', 'customer', 'opportunity'])
            ->where('token', $token)
            ->firstOrFail();
    }
}
