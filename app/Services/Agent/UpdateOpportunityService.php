<?php

namespace App\Services\Agent;

use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Models\ProjectManager;
use App\Services\Agent\Concerns\ValidatesAgentAttachments;

/**
 * Executes the `update_opportunity` Claude tool. Deliberately narrow field scope for v1
 * (confirmed with the business owner): only `requires_rfm` (boolean) and
 * `project_manager_id` (resolved from a freetext name, never accepted as a raw ID).
 * Excluded on purpose: `status` (gated lifecycle transition, has its own business rules
 * in OpportunityController::update — e.g. blocking "Lost" while active sales exist),
 * `job_no` (job identifier, human-only), `status_reason` (only meaningful when status is
 * Lost/Closed, which this tool can't set), `sales_person_1/2` (not real FKs today —
 * validated as plain strings in OpportunityController, not safe to populate from
 * agent-inferred text), and customer-linkage fields (structural, human-only).
 */
class UpdateOpportunityService
{
    use ValidatesAgentAttachments;

    /**
     * @return array{opportunity_id: int, changes: array<string, mixed>}
     */
    public function execute(
        AgentTask $task,
        int $opportunityId,
        ?bool $requiresRfm,
        ?string $projectManagerName,
    ): array {
        $opportunity = $this->assertOpportunityMatches($task, $opportunityId);

        if ($requiresRfm === null && $projectManagerName === null) {
            throw new AgentToolValidationException(
                'At least one of requires_rfm or project_manager_name is required.'
            );
        }

        $changes = [];

        if ($requiresRfm !== null) {
            $changes['requires_rfm'] = $requiresRfm;
        }

        if ($projectManagerName !== null) {
            $changes['project_manager_id'] = $this->resolveProjectManagerId($opportunity, $projectManagerName);
        }

        $changes['updated_by'] = $task->requester_user_id;
        $opportunity->update($changes);

        unset($changes['updated_by']);

        return [
            'opportunity_id' => $opportunity->id,
            'changes' => $changes,
        ];
    }

    /**
     * Exact (case-insensitive) name match only — no fuzzy guessing for an FK write.
     * Scoped first to the opportunity's parent customer (where project managers are
     * normally attached, per OpportunityController::projectManagersForCustomer), falling
     * back to the job-site customer if none found there.
     */
    private function resolveProjectManagerId(Opportunity $opportunity, string $name): int
    {
        $name = trim($name);

        foreach (array_filter([$opportunity->parent_customer_id, $opportunity->job_site_customer_id]) as $customerId) {
            $matches = ProjectManager::where('customer_id', $customerId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->get(['id', 'name']);

            if ($matches->count() === 1) {
                return $matches->first()->id;
            }

            if ($matches->count() > 1) {
                throw new AgentToolValidationException(
                    "Multiple project managers named \"{$name}\" found for this customer — cannot resolve unambiguously."
                );
            }
        }

        throw new AgentToolValidationException(
            "No project manager named \"{$name}\" found for this opportunity's customer."
        );
    }
}
