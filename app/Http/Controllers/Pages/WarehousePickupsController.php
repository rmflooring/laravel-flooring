<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class WarehousePickupsController extends Controller
{
    // Fulfillment methods that involve the warehouse
    const WAREHOUSE_METHODS = ['pickup', 'delivery_warehouse'];

    public function index(Request $request)
    {
        $q       = trim($request->input('q', ''));
        $type    = $request->input('type', '');     // pickup | delivery_warehouse
        $status  = $request->input('status', '');
        $dateFrom = $request->input('date_from', '');
        $dateTo   = $request->input('date_to', '');

        $purchaseOrders = PurchaseOrder::with(['vendor', 'sale.workOrders.installer', 'items'])
            ->whereIn('fulfillment_method', self::WAREHOUSE_METHODS)
            ->when($type, fn ($q) => $q->where('fulfillment_method', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
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
            })
            ->orderByRaw('COALESCE(pickup_at, expected_delivery_date) IS NULL ASC, COALESCE(pickup_at, expected_delivery_date) ASC')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $statusOptions = ['pending', 'ordered', 'received', 'delivered', 'cancelled'];

        return view('pages.warehouse.pickups.index', compact(
            'purchaseOrders', 'statusOptions',
            'q', 'type', 'status', 'dateFrom', 'dateTo'
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
