<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function show(InventoryReceipt $inventoryReceipt): View
    {
        $inventoryReceipt->load([
            'purchaseOrder.vendor',
            'purchaseOrder.sale',
            'purchaseOrderItem',
            'productStyle',
            'allocations.saleItem.room',
            'allocations.saleItem.sale',
            'allocations.pickTicketItems.pickTicket',
            'creator',
        ]);

        $allocated = $inventoryReceipt->allocations->sum('quantity');
        $available = max(0, (float) $inventoryReceipt->quantity_received - $allocated);
        $tagFormat = Setting::get('label_printer_format', 'standard');

        return view('pages.inventory.show', compact('inventoryReceipt', 'allocated', 'available', 'tagFormat'));
    }

    public function index(Request $request): View
    {
        $q              = trim($request->input('q', ''));
        $recordId       = $request->input('record_id', '');
        $productStyleId = $request->input('product_style_id', '');
        $dateFrom       = $request->input('date_from', '');
        $dateTo         = $request->input('date_to', '');
        $showDepleted   = $request->boolean('show_depleted', false);

        $receipts = InventoryReceipt::query()
            ->withSum('allocations', 'quantity')
            ->with(['purchaseOrder', 'creator'])
            ->when($recordId, fn ($query) => $query->where('id', (int) $recordId))
            ->when($productStyleId, fn ($query) => $query->where('product_style_id', (int) $productStyleId))
            ->when($q, fn ($query) => $query->where('item_name', 'like', "%{$q}%"))
            ->when($dateFrom, fn ($query) => $query->whereDate('received_date', '>=', $dateFrom))
            ->when($dateTo,   fn ($query) => $query->whereDate('received_date', '<=', $dateTo))
            ->when(! $showDepleted, fn ($query) => $query->whereRaw(
                'quantity_received > COALESCE((SELECT SUM(quantity) FROM inventory_allocations WHERE inventory_receipt_id = inventory_receipts.id), 0)'
            ))
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // Summary stats (unfiltered — whole inventory)
        $totalReceipts   = InventoryReceipt::count();
        $totalInStock    = InventoryReceipt::whereRaw(
            'quantity_received > COALESCE((SELECT SUM(quantity) FROM inventory_allocations WHERE inventory_receipt_id = inventory_receipts.id), 0)'
        )->count();
        $totalDepleted   = $totalReceipts - $totalInStock;

        return view('pages.inventory.index', compact(
            'receipts',
            'q', 'recordId', 'productStyleId', 'dateFrom', 'dateTo', 'showDepleted',
            'totalReceipts', 'totalInStock', 'totalDepleted',
        ));
    }
}
