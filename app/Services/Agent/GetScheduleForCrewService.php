<?php

namespace App\Services\Agent;

use App\Models\Installer;
use App\Models\User;
use App\Models\WorkOrder;
use Carbon\Carbon;

/**
 * Executes the `get_schedule_for_crew` chat tool. Read-only. This schema has no
 * separate "crew" concept — an Installer (company/person assigned to a WorkOrder via
 * installer_id) is the closest equivalent, so crew_id resolves against Installer,
 * either by id or a loose company_name match.
 */
class GetScheduleForCrewService
{
    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $crewId, string $date): array
    {
        if (! $this->gate->canUseTool($user, 'get_schedule_for_crew')) {
            return $this->gate->unauthorizedResult();
        }

        $installer = is_numeric($crewId)
            ? Installer::find((int) $crewId)
            : Installer::where('company_name', 'like', "%{$crewId}%")->first();

        if (! $installer) {
            return ['authorized' => true, 'found' => false, 'message' => "No crew found matching \"{$crewId}\"."];
        }

        try {
            $targetDate = Carbon::parse($date)->toDateString();
        } catch (\Exception) {
            throw new AgentToolValidationException("Could not parse date \"{$date}\".");
        }

        $workOrders = WorkOrder::where('installer_id', $installer->id)
            ->where(function ($q) use ($targetDate) {
                $q->whereDate('scheduled_date', $targetDate)
                    ->orWhere(function ($q2) use ($targetDate) {
                        $q2->whereDate('scheduled_date', '<=', $targetDate)
                            ->whereDate('scheduled_end_date', '>=', $targetDate);
                    });
            })
            ->with('sale')
            ->get();

        return [
            'authorized' => true,
            'crew' => $installer->company_name,
            'date' => $targetDate,
            'work_orders' => $workOrders->map(fn (WorkOrder $wo) => [
                'wo_number' => $wo->wo_number,
                'status' => $wo->status_label,
                'sale_number' => $wo->sale?->sale_number,
                'scheduled_date' => $wo->scheduled_date?->toDateString(),
                'scheduled_end_date' => $wo->scheduled_end_date?->toDateString(),
            ])->values()->all(),
        ];
    }
}
