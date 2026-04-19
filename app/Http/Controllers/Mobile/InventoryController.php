<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->input('search');
        $showDepleted = $request->boolean('show_depleted', false);

        $receipts = InventoryReceipt::with(['purchaseOrder.vendor', 'allocations', 'transactions'])
            ->when($search, fn ($q) => $q->where('item_name', 'like', "%{$search}%")
                ->orWhere('id', is_numeric($search) ? $search : -1)
            )
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Filter depleted client-side since available_qty is a computed attribute
        if (! $showDepleted) {
            $receipts->setCollection(
                $receipts->getCollection()->filter(fn ($r) => $r->available_qty > 0)->values()
            );
        }

        return view('mobile.inventory.index', compact('receipts', 'search', 'showDepleted'));
    }

    public function show(InventoryReceipt $receipt)
    {
        $receipt->load([
            'purchaseOrder.vendor',
            'purchaseOrder.sale',
            'allocations.sale',
            'allocations.saleItem',
            'transactions',
        ]);

        return view('mobile.inventory.show', compact('receipt'));
    }
}
