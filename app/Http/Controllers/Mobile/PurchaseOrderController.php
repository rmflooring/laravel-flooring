<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;

class PurchaseOrderController extends Controller
{
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items', 'sale', 'orderedBy']);

        return view('mobile.purchase-orders.show', compact('purchaseOrder'));
    }

    public function receiveForm(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->status === 'ordered', 403, 'Only ordered POs can be received.');

        $purchaseOrder->load(['vendor', 'items', 'sale']);

        return view('mobile.purchase-orders.receive', compact('purchaseOrder'));
    }
}
