<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\PurchaseOrder;
use App\Models\Setting;
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

        $format = Setting::get('label_printer_format', 'standard');
        // Standard: ~3" × 2.4"  |  Zebra: 4" × 6" portrait (288 × 432 pt)
        $paper = $format === 'zebra' ? [0, 0, 288, 432] : [0, 0, 226.77, 170.08];

        $pdf = Pdf::loadView('pdf.inventory-tag', compact('receipt', 'format'))
            ->setPaper($paper);

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

        $format = Setting::get('label_printer_format', 'standard');
        // Standard: ~3" × 2.4"  |  Zebra: 4" × 6" portrait (288 × 432 pt)
        $paper = $format === 'zebra' ? [0, 0, 288, 432] : [0, 0, 226.77, 170.08];

        $pdf = Pdf::loadView('pdf.inventory-tags', compact('receipts', 'purchaseOrder', 'format'))
            ->setPaper($paper);

        return $pdf->stream("tags-{$purchaseOrder->po_number}.pdf");
    }
}
