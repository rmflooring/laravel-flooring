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
     * @return array{opportunity_id: int, changes: array<string, mixed>, previous_values: array<string, mixed>}
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

        // Captured before the update so undo_last_action can restore these exact values.
        $previousValues = $opportunity->only(array_keys($changes));

        $changes['updated_by'] = $task->requester_user_id;
        $opportunity->update($changes);

        unset($changes['updated_by']);

        return [
            'opportunity_id' => $opportunity->id,
            'changes' => $changes,
            'previous_values' => $previousValues,
        ];
    }

    /**
     * Exact (case-insensitive) name match first, falling back to a "starts with" match
     * (e.g. "Andrew" -> "Andrew Bou-Antoun") only if nothing exact was found anywhere —
     * still no arbitrary fuzzy guessing for an FK write, just tolerating a bare first
     * name, which is how these get mentioned in practice (email correction requests,
     * e.g. "update the PM to Andrew"). Scoped first to the opportunity's parent customer
     * (where project managers are normally attached, per
     * OpportunityController::projectManagersForCustomer), falling back to the job-site
     * customer if none found there.
     */
    private function resolveProjectManagerId(Opportunity $opportunity, string $name): int
    {
        $name = trim($name);
        $customerIds = array_filter([$opportunity->parent_customer_id, $opportunity->job_site_customer_id]);

        $match = $this->findProjectManager($customerIds, $name, exact: true)
            ?? $this->findProjectManager($customerIds, $name, exact: false);

        if ($match !== null) {
            return $match;
        }

        throw new AgentToolValidationException(
            "No project manager named \"{$name}\" found for this opportunity's customer."
        );
    }

    /**
     * @param  iterable<int>  $customerIds  Checked in order; the first scope with any
     *     match (exact or ambiguous) wins/throws without falling through to the next.
     * @return ?int  The matched PM's id, or null if no scope had any match at all.
     */
    private function findProjectManager(iterable $customerIds, string $name, bool $exact): ?int
    {
        $needle = mb_strtolower($name);

        foreach ($customerIds as $customerId) {
            $query = ProjectManager::where('customer_id', $customerId);
            $query = $exact
                ? $query->whereRaw('LOWER(name) = ?', [$needle])
                : $query->whereRaw('LOWER(name) LIKE ?', [$needle . ' %']);

            $matches = $query->get(['id', 'name']);

            if ($matches->count() === 1) {
                return $matches->first()->id;
            }

            if ($matches->count() > 1) {
                throw new AgentToolValidationException(
                    "Multiple project managers named \"{$name}\" found for this customer — cannot resolve unambiguously."
                );
            }
        }

        return null;
    }
}
