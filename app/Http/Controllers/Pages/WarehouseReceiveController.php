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
            $po = PurchaseOrder::with(['items.receipts', 'receipts'])->where('po_number', $q)->first();

            if (! $po) {
                $error = "No purchase order found with number \"{$q}\".";
            } elseif ($po->status === 'received') {
                $error = "PO {$q} has already been received.";
            } elseif ($po->status === 'cancelled') {
                $error = "PO {$q} is cancelled and cannot be received.";
            } elseif ($po->status !== 'ordered') {
                $error = "PO {$q} is not ready to receive (status: {$po->status_label}).";
            } elseif (! $po->canReceive()) {
                $error = "PO {$q} has nothing left to receive — already fully received on {$po->receivedSummary()}.";
            } else {
                return redirect()->route('pages.purchase-orders.receive.form', $po);
            }
        }

        // Load ordered POs that still have something to receive, for the quick-pick list.
        // "Still to receive" comes from the inventory receipts per line, not the status —
        // a fully received PO set back to Ordered by hand must not reappear here.
        // Sort: expected delivery ascending (soonest first), nulls last, then created_at
        $orderedPos = PurchaseOrder::with(['vendor', 'sale', 'items.receipts', 'receipts'])
            ->where('status', 'ordered')
            ->orderByRaw('expected_delivery_date IS NULL, expected_delivery_date ASC')
            ->orderBy('created_at')
            ->get()
            ->filter(fn (PurchaseOrder $po) => $po->canReceive())
            ->values();

        return view('pages.warehouse.receive-lookup', compact('orderedPos', 'q', 'error'));
    }
}
