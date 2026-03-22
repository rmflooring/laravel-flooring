<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;

class InventoryController extends Controller
{
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
