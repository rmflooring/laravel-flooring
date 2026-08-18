<?php

namespace App\Services\Agent;

use App\Models\AgentTask;
use App\Services\Agent\Concerns\ValidatesAgentAttachments;

/**
 * Executes the `check_status` Claude tool. Read-only — assembles a status summary for
 * the resolved opportunity, used for status-inquiry auto-replies. Unlike the other
 * opportunity tools this always concludes the task: the summary built from this data
 * (see ProcessAgentTask::formatStatusSummary) becomes the auto-reply the requester
 * actually reads, so there's nothing further for Claude to decide once it has this.
 */
class CheckStatusService
{
    use ValidatesAgentAttachments;

    /**
     * @return array{
     *     job_no: ?string, status: ?string, status_reason: ?string, requires_rfm: bool,
     *     project_manager: ?string, latest_rfm_status: ?string, latest_rfm_scheduled_at: ?string,
     *     latest_estimate_status: ?string, latest_sale_status: ?string,
     * }
     */
    public function execute(AgentTask $task, int $opportunityId): array
    {
        $opportunity = $this->assertOpportunityMatches($task, $opportunityId);
        $opportunity->load(['projectManager', 'rfms', 'sales', 'estimates']);

        // rfms() is already ordered newest-scheduled-first; sales/estimates have no
        // guaranteed order, so take the most recently created of each.
        $latestRfm = $opportunity->rfms->first();
        $latestSale = $opportunity->sales->sortByDesc('id')->first();
        $latestEstimate = $opportunity->estimates->sortByDesc('id')->first();

        return [
            'job_no' => $opportunity->job_no,
            'status' => $opportunity->status,
            'status_reason' => $opportunity->status_reason,
            'requires_rfm' => (bool) $opportunity->requires_rfm,
            'project_manager' => $opportunity->projectManager?->name,
            'latest_rfm_status' => $latestRfm?->status,
            'latest_rfm_scheduled_at' => $latestRfm?->scheduled_at?->toDateTimeString(),
            'latest_estimate_status' => $latestEstimate?->status,
            'latest_sale_status' => $latestSale?->status,
        ];
    }
}
