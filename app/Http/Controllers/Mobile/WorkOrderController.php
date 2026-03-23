<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\WorkOrder;

class WorkOrderController extends Controller
{
    public function show(WorkOrder $workOrder)
    {
        $workOrder->load([
            'sale',
            'installer',
            'items.saleItem.room',
            'items.relatedMaterials.saleItem',
            'creator',
        ]);

        return view('mobile.work-orders.show', compact('workOrder'));
    }
}
