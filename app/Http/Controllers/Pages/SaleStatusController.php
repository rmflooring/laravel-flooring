<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\View\View;

class SaleStatusController extends Controller
{
    public function show(Sale $sale): View
    {
        $sale->load([
            'rooms.items',
            'purchaseOrders.vendor',
            'purchaseOrders.items',
            'workOrders.assignedTo',
        ]);

        // ── Material items ─────────────────────────────────────────
        $materialItems      = $sale->rooms
            ->flatMap(fn ($room) => $room->items->where('item_type', 'material'))
            ->values();
        $totalMaterialItems = $materialItems->count();

        // ── PO stats ───────────────────────────────────────────────
        $activePOs  = $sale->purchaseOrders->filter(fn ($po) => $po->status !== 'cancelled');
        $posCreated = $activePOs->count();
        $posPending = $sale->purchaseOrders->where('status', 'pending')->count();

        $poItemsBySaleItemId = [];
        foreach ($activePOs as $po) {
            foreach ($po->items as $poItem) {
                $poItemsBySaleItemId[$poItem->sale_item_id][] = [
                    'po'     => $po,
                    'poItem' => $poItem,
                ];
            }
        }

        $itemsReceived = $activePOs
            ->where('status', 'received')
            ->flatMap(fn ($po) => $po->items)
            ->count();

        // ── WO stats ───────────────────────────────────────────────
        $activeWOs              = $sale->workOrders->filter(fn ($wo) => $wo->status !== 'cancelled');
        $totalWOs               = $activeWOs->count();
        $wosScheduledOrProgress = $activeWOs
            ->filter(fn ($wo) => in_array($wo->status, ['scheduled', 'in_progress', 'completed']))
            ->count();

        // ── Progress % ─────────────────────────────────────────────
        // Numerator: received material items + WOs that are scheduled/in-progress/completed
        // Denominator: total material items + total non-cancelled WOs
        $denominator     = $totalMaterialItems + $totalWOs;
        $numerator       = $itemsReceived + $wosScheduledOrProgress;
        $progressPercent = $denominator > 0
            ? (int) round($numerator / $denominator * 100)
            : 0;

        // ── Coverage items ─────────────────────────────────────────
        $statusPriority = ['received' => 3, 'ordered' => 2, 'pending' => 1];

        $coverageItems = $materialItems->map(function ($item) use ($poItemsBySaleItemId, $statusPriority) {
            $matches = $poItemsBySaleItemId[$item->id] ?? [];

            if (empty($matches)) {
                return ['item' => $item, 'dot_status' => 'none', 'po' => null];
            }

            $best = collect($matches)
                ->sortByDesc(fn ($m) => $statusPriority[$m['po']->status] ?? 0)
                ->first();

            return [
                'item'       => $item,
                'dot_status' => $best['po']->status,
                'po'         => $best['po'],
            ];
        });

        // ── Overall status badge ───────────────────────────────────
        $overallStatus = $this->deriveOverallStatus(
            $totalMaterialItems,
            $coverageItems,
            $posCreated,
            $activePOs,
            $totalWOs,
            $activeWOs
        );

        return view('pages.sales.status', compact(
            'sale',
            'totalMaterialItems',
            'posCreated',
            'itemsReceived',
            'posPending',
            'progressPercent',
            'overallStatus',
            'coverageItems',
            'activePOs',
            'totalWOs',
            'activeWOs',
        ));
    }

    private function deriveOverallStatus(
        int $totalMaterialItems,
        $coverageItems,
        int $posCreated,
        $activePOs,
        int $totalWOs,
        $activeWOs
    ): string {
        // Not started: no POs and no WOs
        if ($posCreated === 0 && $totalWOs === 0) {
            return 'Not started';
        }

        // Needs action: any material item has no PO, OR any WO is unassigned (status = created)
        $hasUnlinkedMaterial = $coverageItems->contains(fn ($c) => $c['dot_status'] === 'none');
        $hasUnscheduledWO    = $activeWOs->contains(fn ($wo) => $wo->status === 'created');

        if ($hasUnlinkedMaterial || $hasUnscheduledWO) {
            return 'Needs action';
        }

        // Ready: all materials received AND all WOs scheduled or completed
        $allMaterialsReceived = $totalMaterialItems === 0
            || $coverageItems->every(fn ($c) => $c['dot_status'] === 'received');

        $allWOsDone = $totalWOs === 0
            || $activeWOs->every(fn ($wo) => in_array($wo->status, ['scheduled', 'in_progress', 'completed']));

        if ($allMaterialsReceived && $allWOsDone) {
            return 'Ready';
        }

        // In progress: at least one PO ordered/received OR one WO scheduled/in-progress
        $hasPoProgress = $activePOs->contains(fn ($po) => in_array($po->status, ['ordered', 'received']));
        $hasWoProgress = $activeWOs->contains(fn ($wo) => in_array($wo->status, ['scheduled', 'in_progress', 'completed']));

        if ($hasPoProgress || $hasWoProgress) {
            return 'In progress';
        }

        return 'Needs action';
    }
}
