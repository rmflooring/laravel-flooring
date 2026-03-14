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
        ]);

        // All material line items across all rooms
        $materialItems = $sale->rooms
            ->flatMap(fn ($room) => $room->items->where('item_type', 'material'))
            ->values();

        $totalMaterialItems = $materialItems->count();

        // Non-cancelled POs (soft-deleted already excluded by SoftDeletes scope)
        $activePOs  = $sale->purchaseOrders->filter(fn ($po) => $po->status !== 'cancelled');
        $posCreated = $activePOs->count();
        $posPending = $sale->purchaseOrders->where('status', 'pending')->count();

        // Map sale_item_id → all matching [po, poItem] pairs from non-cancelled POs
        $poItemsBySaleItemId = [];
        foreach ($activePOs as $po) {
            foreach ($po->items as $poItem) {
                $poItemsBySaleItemId[$poItem->sale_item_id][] = [
                    'po'     => $po,
                    'poItem' => $poItem,
                ];
            }
        }

        // Items received = count of po_item records on received POs
        $itemsReceived = $activePOs
            ->where('status', 'received')
            ->flatMap(fn ($po) => $po->items)
            ->count();

        // Progress percentage (0 if no POs)
        $progressPercent = ($totalMaterialItems > 0 && $posCreated > 0)
            ? (int) round($itemsReceived / $totalMaterialItems * 100)
            : 0;

        // Coverage: one entry per material sale item with derived dot status + PO reference
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

        $overallStatus = $this->deriveOverallStatus(
            $totalMaterialItems,
            $coverageItems,
            $posCreated,
            $activePOs
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
        ));
    }

    private function deriveOverallStatus(
        int $totalMaterialItems,
        $coverageItems,
        int $posCreated,
        $activePOs
    ): string {
        if ($posCreated === 0) {
            return 'Not started';
        }

        // All material items covered by a received PO → Ready
        if ($totalMaterialItems > 0) {
            $allReceived = $coverageItems->every(fn ($c) => $c['dot_status'] === 'received');
            if ($allReceived) {
                return 'Ready';
            }
        }

        // Any item with no PO linked → Needs action
        $hasUnlinked = $coverageItems->contains(fn ($c) => $c['dot_status'] === 'none');
        if ($hasUnlinked) {
            return 'Needs action';
        }

        // At least one PO ordered or received
        $hasProgress = $activePOs->contains(
            fn ($po) => in_array($po->status, ['ordered', 'received'])
        );
        if ($hasProgress) {
            return 'In progress';
        }

        return 'Needs action';
    }
}
