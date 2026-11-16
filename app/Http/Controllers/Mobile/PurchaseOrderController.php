<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;

class PurchaseOrderController extends Controller
{
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.receipts', 'sale', 'orderedBy', 'receipts']);

        return view('mobile.purchase-orders.show', compact('purchaseOrder'));
    }

    public function receiveForm(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.saleItem.room', 'items.receipts', 'sale', 'receipts']);

        if ($reason = $purchaseOrder->receiveBlockedReason()) {
            return redirect()->route('mobile.purchase-orders.show', $purchaseOrder)->with('warning', $reason);
        }

        return view('mobile.purchase-orders.receive', compact('purchaseOrder'));
    }
}
