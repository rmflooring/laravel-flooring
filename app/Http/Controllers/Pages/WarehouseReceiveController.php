<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class WarehouseReceiveController extends Controller
{
    public function index(Request $request)
    {
        $q     = trim($request->input('q', ''));
        $error = null;

        // If a PO number was submitted, look it up and redirect
        if ($q !== '') {
            $po = PurchaseOrder::where('po_number', $q)->first();

            if (! $po) {
                $error = "No purchase order found with number \"{$q}\".";
            } elseif ($po->status === 'received') {
                $error = "PO {$q} has already been received.";
            } elseif ($po->status === 'cancelled') {
                $error = "PO {$q} is cancelled and cannot be received.";
            } elseif ($po->status !== 'ordered') {
                $error = "PO {$q} is not ready to receive (status: {$po->status_label}).";
            } else {
                return redirect()->route('pages.purchase-orders.receive.form', $po);
            }
        }

        // Load all ordered POs for the quick-pick list
        // Sort: expected delivery ascending (soonest first), nulls last, then created_at
        $orderedPos = PurchaseOrder::with(['vendor', 'sale', 'items'])
            ->where('status', 'ordered')
            ->orderByRaw('expected_delivery_date IS NULL, expected_delivery_date ASC')
            ->orderBy('created_at')
            ->get();

        return view('pages.warehouse.receive-lookup', compact('orderedPos', 'q', 'error'));
    }
}
