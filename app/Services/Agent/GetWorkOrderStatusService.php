<?php

namespace App\Services\Agent;

use App\Models\User;
use App\Models\WorkOrder;

/**
 * Executes the `get_work_order_status` chat tool. Read-only.
 */
class GetWorkOrderStatusService
{
    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $orderId): array
    {
        if (! $this->gate->canUseTool($user, 'get_work_order_status')) {
            return $this->gate->unauthorizedResult();
        }

        $workOrder = WorkOrder::where('wo_number', $orderId)
            ->orWhere('id', $orderId)
            ->with(['installer', 'sale'])
            ->first();

        if (! $workOrder) {
            return ['authorized' => true, 'found' => false, 'message' => "No work order found matching \"{$orderId}\"."];
        }

        return [
            'authorized' => true,
            'found' => true,
            'wo_number' => $workOrder->wo_number,
            'status' => $workOrder->status_label,
            'scheduled_date' => $workOrder->scheduled_date?->toDateString(),
            'scheduled_end_date' => $workOrder->scheduled_end_date?->toDateString(),
            'installer' => $workOrder->installer?->company_name,
            'sale_number' => $workOrder->sale?->sale_number,
        ];
    }
}
