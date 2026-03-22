<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;

class InventoryTagController extends Controller
{
    /**
     * Print a single inventory tag for one receipt.
     */
    public function tag(InventoryReceipt $receipt)
    {
        $receipt->load([
            'purchaseOrder.vendor',
            'purchaseOrder.sale',
            'allocations.sale',
            'transactions',
        ]);

        $pdf = Pdf::loadView('pdf.inventory-tag', compact('receipt'))
            ->setPaper([0, 0, 226.77, 170.08]); // ~8cm × 6cm label in points

        return $pdf->stream("tag-{$receipt->id}.pdf");
    }

    /**
     * Print all tags for every receipt linked to a PO (one page each).
     */
    public function poTags(PurchaseOrder $purchaseOrder)
    {
        $receipts = InventoryReceipt::where('purchase_order_id', $purchaseOrder->id)
            ->with([
                'purchaseOrder.vendor',
                'purchaseOrder.sale',
                'allocations.sale',
                'transactions',
            ])
            ->get();

        abort_if($receipts->isEmpty(), 404, 'No inventory receipts found for this PO.');

        $pdf = Pdf::loadView('pdf.inventory-tags', compact('receipts', 'purchaseOrder'))
            ->setPaper([0, 0, 226.77, 170.08]); // ~8cm × 6cm label in points

        return $pdf->stream("tags-{$purchaseOrder->po_number}.pdf");
    }
}
