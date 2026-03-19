<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\PickTicketItem;
use App\Models\Sale;
use App\Services\InventoryService;
use Illuminate\View\View;

class SaleStatusController extends Controller
{
    public function show(Sale $sale, InventoryService $inventory): View
    {
        $sale->load([
            'rooms.items',
            'purchaseOrders.vendor',
            'purchaseOrders.items',
            'workOrders.installer',
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
            ->whereIn('status', ['received', 'delivered'])
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

        // ── Inventory coverage ─────────────────────────────────────
        // Build a map of sale_item_id → total allocated qty from inventory
        $saleItemIds = $materialItems->pluck('id')->all();

        // Pick tickets for each sale item: keyed by sale_item_id → first non-cancelled PT
        $ptBySaleItemId = [];
        if (! empty($saleItemIds)) {
            PickTicketItem::query()
                ->join('pick_tickets', 'pick_tickets.id', '=', 'pick_ticket_items.pick_ticket_id')
                ->whereIn('pick_ticket_items.sale_item_id', $saleItemIds)
                ->where('pick_tickets.status', '<>', 'cancelled')
                ->orderBy('pick_tickets.id')
                ->select([
                    'pick_ticket_items.sale_item_id',
                    'pick_tickets.id as pt_id',
                    'pick_tickets.pt_number',
                    'pick_tickets.status as pt_status',
                ])
                ->get()
                ->each(function ($row) use (&$ptBySaleItemId) {
                    // Keep the first (lowest id) PT per sale item
                    $ptBySaleItemId[$row->sale_item_id] ??= [
                        'id'        => $row->pt_id,
                        'pt_number' => $row->pt_number,
                        'status'    => $row->pt_status,
                    ];
                });
        }

        $invAllocatedQtys = \App\Models\InventoryAllocation::whereIn('sale_item_id', $saleItemIds)
            ->selectRaw('sale_item_id, SUM(quantity) as total')
            ->groupBy('sale_item_id')
            ->pluck('total', 'sale_item_id')
            ->map(fn ($v) => (float) $v)
            ->all();

        // For uncovered items: find available receipts matching the item's product_style_id
        // Receipts grouped by product_style_id, with available qty pre-computed
        $styleIds = $materialItems->pluck('product_style_id')->filter()->unique()->all();

        $receiptsByStyleId = [];
        if (! empty($styleIds)) {
            $receipts = InventoryReceipt::with('allocations')
                ->whereIn('product_style_id', $styleIds)
                ->orderBy('received_date')
                ->get();

            foreach ($receipts as $receipt) {
                $available = max(0, (float) $receipt->quantity_received - $receipt->allocations->sum('quantity'));
                if ($available > 0) {
                    $receiptsByStyleId[$receipt->product_style_id][] = [
                        'id'         => $receipt->id,
                        'item_name'  => $receipt->item_name,
                        'unit'       => $receipt->unit,
                        'available'  => $available,
                        'date'       => $receipt->received_date?->format('M j, Y'),
                    ];
                }
            }
        }

        // ── Coverage items ─────────────────────────────────────────
        // Priority: delivered(4) > received(3) > inventory(2.5) > ordered(2) > pending(1) > none(0)
        $statusPriority = ['received' => 3, 'ordered' => 2, 'pending' => 1];

        $coverageItems = $materialItems->map(function ($item) use (
            $poItemsBySaleItemId,
            $statusPriority,
            $invAllocatedQtys,
            $receiptsByStyleId,
            $ptBySaleItemId
        ) {
            $matches        = $poItemsBySaleItemId[$item->id] ?? [];
            $invQty         = $invAllocatedQtys[$item->id] ?? 0;
            $hasInvCoverage = $invQty > 0;
            $pickTicket     = $ptBySaleItemId[$item->id] ?? null;

            if (empty($matches)) {
                if ($hasInvCoverage) {
                    $invDotStatus = ($pickTicket && $pickTicket['status'] === 'delivered') ? 'delivered' : 'inventory';
                    return [
                        'item'               => $item,
                        'dot_status'         => $invDotStatus,
                        'po'                 => null,
                        'inv_qty'            => $invQty,
                        'available_receipts' => $receiptsByStyleId[$item->product_style_id] ?? [],
                        'pick_ticket'        => $pickTicket,
                    ];
                }

                return [
                    'item'               => $item,
                    'dot_status'         => 'none',
                    'po'                 => null,
                    'inv_qty'            => 0,
                    'available_receipts' => $receiptsByStyleId[$item->product_style_id] ?? [],
                    'pick_ticket'        => null,
                ];
            }

            $best = collect($matches)
                ->sortByDesc(fn ($m) => $statusPriority[$m['po']->status] ?? 0)
                ->first();

            // If best PO is only pending and inventory also covers it, prefer inventory badge
            $poStatus = $best['po']->status;
            if ($hasInvCoverage && $poStatus === 'pending') {
                return [
                    'item'               => $item,
                    'dot_status'         => 'inventory',
                    'po'                 => $best['po'],
                    'inv_qty'            => $invQty,
                    'available_receipts' => $receiptsByStyleId[$item->product_style_id] ?? [],
                    'pick_ticket'        => $pickTicket,
                ];
            }

            $finalStatus = $poStatus;

            // Upgrade to 'delivered' if the linked PT has been fully delivered
            if ($pickTicket && $pickTicket['status'] === 'delivered') {
                $finalStatus = 'delivered';
            }

            return [
                'item'               => $item,
                'dot_status'         => $finalStatus,
                'po'                 => $best['po'],
                'inv_qty'            => $invQty,
                'available_receipts' => $receiptsByStyleId[$item->product_style_id] ?? [],
                'pick_ticket'        => $pickTicket,
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
            'ptBySaleItemId',
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

        // Ready: all materials received (or from inventory) AND all WOs scheduled or completed
        $allMaterialsReceived = $totalMaterialItems === 0
            || $coverageItems->every(fn ($c) => in_array($c['dot_status'], ['received', 'delivered', 'inventory']));

        $allWOsDone = $totalWOs === 0
            || $activeWOs->every(fn ($wo) => in_array($wo->status, ['scheduled', 'in_progress', 'completed']));

        if ($allMaterialsReceived && $allWOsDone) {
            return 'Ready';
        }

        // In progress: at least one PO ordered/received OR one WO scheduled/in-progress
        $hasPoProgress = $activePOs->contains(fn ($po) => in_array($po->status, ['ordered', 'received', 'delivered']));
        $hasWoProgress = $activeWOs->contains(fn ($wo) => in_array($wo->status, ['scheduled', 'in_progress', 'completed']));

        if ($hasPoProgress || $hasWoProgress) {
            return 'In progress';
        }

        return 'Needs action';
    }
}
