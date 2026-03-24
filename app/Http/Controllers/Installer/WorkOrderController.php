<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Models\Installer;
use App\Models\WorkOrder;
use App\Services\GraphMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WorkOrderController extends Controller
{
    public function updateStatus(Request $request, WorkOrder $workOrder)
    {
        // Verify this WO belongs to the logged-in installer
        $installer = Installer::where('user_id', auth()->id())->firstOrFail();

        if ((int) $workOrder->installer_id !== (int) $installer->id) {
            abort(403);
        }

        $request->validate([
            'status'          => ['required', 'string', 'in:' . implode(',', array_keys(WorkOrder::INSTALLER_STATUSES))],
            'installer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldStatus = $workOrder->status;
        $newStatus = $request->input('status');

        $workOrder->update([
            'status'          => $newStatus,
            'installer_notes' => $request->input('installer_notes') ?: $workOrder->installer_notes,
        ]);

        // Notify team@rmflooring.ca
        $this->notifyTeam($workOrder, $installer, $newStatus, $request->input('installer_notes'));

        $label = WorkOrder::STATUS_LABELS[$newStatus] ?? $newStatus;

        return redirect()
            ->route('mobile.work-orders.show', $workOrder)
            ->with('success', "Status updated to \"{$label}\".");
    }

    private function notifyTeam(WorkOrder $workOrder, Installer $installer, string $status, ?string $notes): void
    {
        try {
            $workOrder->loadMissing(['sale']);

            $statusLabel  = WorkOrder::STATUS_LABELS[$status] ?? $status;
            $customerName = $workOrder->sale?->homeowner_name
                ?? $workOrder->sale?->customer_name
                ?? $workOrder->sale?->job_name
                ?? 'Unknown Customer';
            $jobAddress   = $workOrder->sale?->job_address ?? '';
            $saleId       = $workOrder->sale_id;
            $staffUrl     = url("/pages/sales/{$saleId}/work-orders/{$workOrder->id}");

            $isCompleted  = $status === 'completed';

            $subject = $isCompleted
                ? "WO {$workOrder->wo_number} Completed — {$customerName}"
                : "WO {$workOrder->wo_number} — {$statusLabel}: {$customerName}";

            $body = "Work Order: {$workOrder->wo_number}\n"
                . "Installer: {$installer->company_name}\n"
                . "Customer: {$customerName}\n"
                . ($jobAddress ? "Address: {$jobAddress}\n" : '')
                . "Status: {$statusLabel}\n"
                . ($notes ? "\nInstaller Notes:\n{$notes}\n" : '')
                . "\nView WO: {$staffUrl}";

            app(GraphMailService::class)->send(
                to:          'team@rmflooring.ca',
                subject:     $subject,
                body:        $body,
                type:        'work_order_status',
            );
        } catch (\Throwable $e) {
            Log::error('[Installer] Status notification failed', [
                'wo_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
