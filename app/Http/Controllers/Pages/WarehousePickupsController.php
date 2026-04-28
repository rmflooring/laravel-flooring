<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PickTicket;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class WarehousePickupsController extends Controller
{
    // Fulfillment methods that involve the warehouse
    const WAREHOUSE_METHODS = ['pickup', 'delivery_warehouse'];

    public function index(Request $request)
    {
        $q        = trim($request->input('q', ''));
        $type     = $request->input('type', '');     // pickup | delivery_warehouse
        $status   = $request->input('status', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');

        // PO table sort
        $sort = $request->input('sort', 'scheduled_date');
        $dir  = $request->input('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        if (!in_array($sort, ['po_number', 'type', 'vendor', 'scheduled_date', 'sale', 'install_date', 'status'])) {
            $sort = 'scheduled_date';
        }

        // PT table sort
        $ptSort = $request->input('pt_sort', 'scheduled_date');
        $ptDir  = $request->input('pt_direction', 'asc') === 'desc' ? 'desc' : 'asc';
        if (!in_array($ptSort, ['pt_number', 'type', 'scheduled_date', 'status'])) {
            $ptSort = 'scheduled_date';
        }

        $poQuery = PurchaseOrder::with(['vendor', 'sale.workOrders.installer', 'items'])
            ->select('purchase_orders.*')
            ->whereIn('purchase_orders.fulfillment_method', self::WAREHOUSE_METHODS)
            ->when($type, fn ($q) => $q->where('purchase_orders.fulfillment_method', $type))
            ->when($status, fn ($q) => $q->where('purchase_orders.status', $status))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('po_number', 'like', "%{$q}%")
                        ->orWhereHas('vendor', fn ($vq) => $vq->where('company_name', 'like', "%{$q}%"))
                        ->orWhereHas('sale', fn ($sq) => $sq->where('sale_number', 'like', "%{$q}%"));
                });
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->where(function ($sub) use ($dateFrom) {
                    $sub->whereDate('pickup_at', '>=', $dateFrom)
                        ->orWhereDate('expected_delivery_date', '>=', $dateFrom);
                });
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->where(function ($sub) use ($dateTo) {
                    $sub->whereDate('pickup_at', '<=', $dateTo)
                        ->orWhereDate('expected_delivery_date', '<=', $dateTo);
                });
            });

        // Apply sort-specific joins and ordering
        match ($sort) {
            'vendor' => $poQuery
                ->leftJoin('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
                ->orderBy('vendors.company_name', $dir)
                ->orderByDesc('purchase_orders.created_at'),

            'sale' => $poQuery
                ->leftJoin('sales', 'purchase_orders.sale_id', '=', 'sales.id')
                ->orderByRaw("purchase_orders.sale_id IS NULL ASC, CAST(sales.sale_number AS UNSIGNED) {$dir}")
                ->orderByDesc('purchase_orders.created_at'),

            'install_date' => $poQuery
                ->leftJoinSub(
                    \DB::table('work_orders')
                        ->select('sale_id', \DB::raw('MIN(scheduled_date) as next_install_date'))
                        ->whereNotIn('status', ['cancelled', 'completed'])
                        ->whereNotNull('scheduled_date')
                        ->groupBy('sale_id'),
                    'wo_next',
                    'wo_next.sale_id',
                    '=',
                    'purchase_orders.sale_id'
                )
                ->orderByRaw("wo_next.next_install_date IS NULL ASC, wo_next.next_install_date {$dir}")
                ->orderByDesc('purchase_orders.created_at'),

            'po_number' => $poQuery
                ->orderBy('purchase_orders.po_number', $dir)
                ->orderByDesc('purchase_orders.created_at'),

            'type' => $poQuery
                ->orderBy('purchase_orders.fulfillment_method', $dir)
                ->orderByDesc('purchase_orders.created_at'),

            'status' => $poQuery
                ->orderBy('purchase_orders.status', $dir)
                ->orderByDesc('purchase_orders.created_at'),

            default => $poQuery
                ->orderByRaw("COALESCE(pickup_at, expected_delivery_date) IS NULL ASC, COALESCE(pickup_at, expected_delivery_date) {$dir}")
                ->orderByDesc('purchase_orders.created_at'),
        };

        $purchaseOrders = $poQuery->paginate(30)->withQueryString();

        $statusOptions = ['pending', 'ordered', 'received', 'delivered', 'cancelled'];

        // Sale-direct pick tickets (no WO, fulfillment_type set)
        $ptType = match($type) {
            'pickup'             => 'pickup',
            'delivery_warehouse' => 'delivery',
            default              => null,
        };

        $ptQuery = PickTicket::with(['sale.workOrders', 'creator'])
            ->select('pick_tickets.*')
            ->whereNull('work_order_id')
            ->whereIn('fulfillment_type', ['pickup', 'delivery'])
            ->whereNotIn('pick_tickets.status', ['cancelled', 'returned'])
            ->when($ptType, fn ($q) => $q->where('fulfillment_type', $ptType))
            ->when($status && in_array($status, PickTicket::STATUSES), fn ($q) => $q->where('pick_tickets.status', $status))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('pt_number', 'like', "%{$q}%")
                        ->orWhereHas('sale', fn ($sq) => $sq->where('sale_number', 'like', "%{$q}%")
                            ->orWhere('customer_name', 'like', "%{$q}%")
                            ->orWhere('homeowner_name', 'like', "%{$q}%"));
                });
            })
            ->when($dateFrom, fn ($q) => $q->whereDate('delivery_date', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('delivery_date', '<=', $dateTo));

        match ($ptSort) {
            'pt_number'  => $ptQuery->orderBy('pt_number', $ptDir)->orderByDesc('pick_tickets.created_at'),
            'type'       => $ptQuery->orderBy('fulfillment_type', $ptDir)->orderByDesc('pick_tickets.created_at'),
            'status'     => $ptQuery->orderBy('pick_tickets.status', $ptDir)->orderByDesc('pick_tickets.created_at'),
            default      => $ptQuery->orderByRaw("delivery_date IS NULL ASC, delivery_date {$ptDir}, delivery_time {$ptDir}")->orderByDesc('pick_tickets.created_at'),
        };

        $pickTickets = $ptQuery->get();

        return view('pages.warehouse.pickups.index', compact(
            'purchaseOrders', 'statusOptions', 'pickTickets',
            'q', 'type', 'status', 'dateFrom', 'dateTo',
            'sort', 'dir', 'ptSort', 'ptDir'
        ));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        // Only show POs that belong in the warehouse view
        abort_unless(
            in_array($purchaseOrder->fulfillment_method, self::WAREHOUSE_METHODS),
            404
        );

        $purchaseOrder->load([
            'vendor',
            'items',
            'documents.uploader',
            'sale.workOrders.installer',
            'sale.opportunity.projectManager',
            'orderedBy',
        ]);

        // Find the next upcoming install date from work orders on this sale
        $nextInstall = null;
        if ($purchaseOrder->sale) {
            $nextInstall = $purchaseOrder->sale->workOrders
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->whereNotNull('scheduled_date')
                ->sortBy('scheduled_date')
                ->first();
        }

        return view('pages.warehouse.pickups.show', compact('purchaseOrder', 'nextInstall'));
    }
}
