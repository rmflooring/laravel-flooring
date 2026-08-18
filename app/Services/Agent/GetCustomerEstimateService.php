<?php

namespace App\Services\Agent;

use App\Models\Estimate;
use App\Models\User;

/**
 * Executes the `get_customer_estimate` chat tool. Read-only summary — no pricing
 * breakdown/line items, just what a staff member would need to answer "what's the
 * status of this estimate."
 */
class GetCustomerEstimateService
{
    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $estimateId): array
    {
        if (! $this->gate->canUseTool($user, 'get_customer_estimate')) {
            return $this->gate->unauthorizedResult();
        }

        $estimate = Estimate::where('estimate_number', $estimateId)
            ->orWhere('id', $estimateId)
            ->first();

        if (! $estimate) {
            return ['authorized' => true, 'found' => false, 'message' => "No estimate found matching \"{$estimateId}\"."];
        }

        return [
            'authorized' => true,
            'found' => true,
            'estimate_number' => $estimate->estimate_number,
            'status' => $estimate->status,
            'customer_name' => $estimate->homeowner_name ?: $estimate->customer_name,
            'job_name' => $estimate->job_name,
            'job_no' => $estimate->job_no,
            'grand_total' => round((float) $estimate->grand_total, 2),
            'project_manager' => $estimate->pm_name,
        ];
    }
}
