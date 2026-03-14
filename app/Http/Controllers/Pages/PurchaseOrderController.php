<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\GraphMailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    // -------------------------------------------------------------------------
    // Create form — scoped to a sale
    // -------------------------------------------------------------------------

    public function create(Sale $sale)
    {
        $sale->load([
            'rooms' => fn ($q) => $q->orderBy('sort_order'),
            'rooms.items' => fn ($q) => $q->where('item_type', 'material')
                                          ->where('is_removed', false)
                                          ->orderBy('sort_order'),
        ]);

        $vendors = Vendor::where('status', 'active')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'email', 'address', 'address2', 'city', 'province', 'postal_code']);

        $warehouseAddress = $this->warehouseAddress();

        // Remaining qty available per sale item (across all non-cancelled POs)
        $orderedQtys   = $this->orderedQtys($sale->id);
        $remainingQtys = [];
        foreach ($sale->rooms as $room) {
            foreach ($room->items as $item) {
                $remainingQtys[$item->id] = max(0, (float) $item->quantity - ($orderedQtys[$item->id] ?? 0));
            }
        }

        return view('pages.purchase-orders.create', compact('sale', 'vendors', 'warehouseAddress', 'remainingQtys'));
    }

    // -------------------------------------------------------------------------
    // Store — create a new PO for a sale
    // -------------------------------------------------------------------------

    public function store(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'vendor_id'               => ['required', 'integer', 'exists:vendors,id'],
            'expected_delivery_date'  => ['nullable', 'date'],
            'fulfillment_method'      => ['required', 'in:delivery_site,delivery_warehouse,delivery_custom,pickup'],
            'delivery_address'        => ['nullable', 'string', 'max:500'],
            'special_instructions'    => ['nullable', 'string'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*'                 => ['integer', 'exists:sale_items,id'],
            'qty'                     => ['nullable', 'array'],
            'qty.*'                   => ['nullable', 'numeric', 'min:0'],
            'cost'                    => ['nullable', 'array'],
            'cost.*'                  => ['nullable', 'numeric', 'min:0'],
        ]);

        // Validate qty overrides don't exceed remaining available per item
        $orderedQtys   = $this->orderedQtys($sale->id);
        $qtyOverrides  = $data['qty'] ?? [];
        $saleItemsForValidation = $sale->items()
            ->where('item_type', 'material')
            ->where('is_removed', false)
            ->whereIn('id', $data['items'])
            ->get()
            ->keyBy('id');

        $qtyErrors = [];
        foreach ($data['items'] as $itemId) {
            if (! isset($saleItemsForValidation[$itemId])) {
                continue;
            }
            $saleItem  = $saleItemsForValidation[$itemId];
            $remaining = max(0, (float) $saleItem->quantity - ($orderedQtys[$itemId] ?? 0));
            $submitted = isset($qtyOverrides[$itemId]) && $qtyOverrides[$itemId] !== ''
                ? (float) $qtyOverrides[$itemId]
                : (float) $saleItem->quantity;

            if ($submitted > $remaining + 0.001) {
                $qtyErrors["qty.{$itemId}"] = '"' . $this->buildItemName($saleItem) . '" — qty ' . $submitted . ' exceeds remaining available qty of ' . $remaining . '.';
            }
        }

        if (! empty($qtyErrors)) {
            return back()->withErrors($qtyErrors)->withInput();
        }

        $resolvedAddress = $this->resolveDeliveryAddress(
            $data['fulfillment_method'],
            $data['delivery_address'] ?? null,
            $sale,
        );

        $po = DB::transaction(function () use ($data, $sale, $resolvedAddress) {

            $po = PurchaseOrder::create([
                'sale_id'                => $sale->id,
                'opportunity_id'         => $sale->opportunity_id,
                'vendor_id'              => $data['vendor_id'],
                'status'                 => 'pending',
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'fulfillment_method'     => $data['fulfillment_method'],
                'delivery_address'       => $resolvedAddress,
                'special_instructions'   => $data['special_instructions'] ?? null,
            ]);

            $saleItems = $sale->items()
                ->where('item_type', 'material')
                ->where('is_removed', false)
                ->whereIn('id', $data['items'])
                ->orderBy('sort_order')
                ->get();

            $qtyOverrides  = $data['qty']  ?? [];
            $costOverrides = $data['cost'] ?? [];

            foreach ($saleItems as $i => $item) {
                $qty   = isset($qtyOverrides[$item->id])  && $qtyOverrides[$item->id]  !== '' ? (float) $qtyOverrides[$item->id]  : (float) $item->quantity;
                $cost  = isset($costOverrides[$item->id]) && $costOverrides[$item->id] !== '' ? (float) $costOverrides[$item->id] : (float) $item->cost_price;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'sale_item_id'      => $item->id,
                    'item_name'         => $this->buildItemName($item),
                    'quantity'          => $qty,
                    'unit'              => $item->unit,
                    'cost_price'        => $cost,
                    'sort_order'        => $i,
                ]);
            }

            return $po;
        });

        return redirect()
            ->route('pages.purchase-orders.show', $po)
            ->with('success', 'Purchase order created successfully.');
    }

    // -------------------------------------------------------------------------
    // Show — read-only
    // -------------------------------------------------------------------------

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items', 'sale', 'orderedBy']);

        return view('pages.purchase-orders.show', compact('purchaseOrder'));
    }

    // -------------------------------------------------------------------------
    // Edit form
    // -------------------------------------------------------------------------

    public function edit(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items.saleItem', 'sale']);

        $vendors = Vendor::where('status', 'active')
            ->orderBy('company_name')
            ->get(['id', 'company_name', 'email', 'address', 'address2', 'city', 'province', 'postal_code']);

        $warehouseAddress = $this->warehouseAddress();

        // Max qty each PO item can be set to (sale item qty minus what other non-cancelled POs have)
        $orderedByOthers = $this->orderedQtys($purchaseOrder->sale_id, $purchaseOrder->id);
        $maxQtys = $purchaseOrder->items->mapWithKeys(function ($poItem) use ($orderedByOthers) {
            $saleQty = (float) ($poItem->saleItem->quantity ?? 0);
            $max     = max(0, $saleQty - ($orderedByOthers[$poItem->sale_item_id] ?? 0));
            return [$poItem->id => ['max' => $max, 'sale_qty' => $saleQty]];
        })->toArray();

        return view('pages.purchase-orders.edit', compact('purchaseOrder', 'vendors', 'warehouseAddress', 'maxQtys'));
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $rules = [
            'vendor_id'              => ['required', 'integer', 'exists:vendors,id'],
            'status'                 => ['required', 'in:pending,ordered,received,cancelled'],
            'vendor_order_number'    => ['nullable', 'string', 'max:255'],
            'expected_delivery_date' => ['nullable', 'date'],
            'fulfillment_method'     => ['required', 'in:delivery_site,delivery_warehouse,delivery_custom,pickup'],
            'delivery_address'       => ['nullable', 'string', 'max:500'],
            'special_instructions'   => ['nullable', 'string'],
            'po_items'               => ['nullable', 'array'],
            'po_items.*.quantity'    => ['nullable', 'numeric', 'min:0'],
            'po_items.*.cost_price'  => ['nullable', 'numeric', 'min:0'],
        ];

        $data = $request->validate($rules);

        // Gate: moving to "ordered" requires a vendor order number
        if ($data['status'] === 'ordered' && empty($data['vendor_order_number'])) {
            return back()
                ->withErrors(['vendor_order_number' => 'A vendor order number is required to mark this PO as Ordered.'])
                ->withInput();
        }

        $resolvedAddress = $this->resolveDeliveryAddress(
            $data['fulfillment_method'],
            $data['delivery_address'] ?? null,
            $purchaseOrder->sale,
        );

        $purchaseOrder->update([
            'vendor_id'              => $data['vendor_id'],
            'status'                 => $data['status'],
            'vendor_order_number'    => $data['vendor_order_number'] ?? null,
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'fulfillment_method'     => $data['fulfillment_method'],
            'delivery_address'       => $resolvedAddress,
            'special_instructions'   => $data['special_instructions'] ?? null,
        ]);

        // Validate and update item qty / cost overrides
        if (! empty($data['po_items'])) {
            $purchaseOrder->loadMissing('items.saleItem');
            $orderedByOthers = $this->orderedQtys($purchaseOrder->sale_id, $purchaseOrder->id);

            $qtyErrors = [];
            foreach ($data['po_items'] as $poItemId => $overrides) {
                $poItem = $purchaseOrder->items->firstWhere('id', (int) $poItemId);
                if (! $poItem || ! $poItem->saleItem) {
                    continue;
                }
                $saleQty  = (float) $poItem->saleItem->quantity;
                $maxQty   = max(0, $saleQty - ($orderedByOthers[$poItem->sale_item_id] ?? 0));
                $newQty   = isset($overrides['quantity']) && $overrides['quantity'] !== ''
                    ? (float) $overrides['quantity']
                    : (float) $poItem->quantity;

                if ($newQty > $maxQty + 0.001) {
                    $qtyErrors["po_items.{$poItemId}.quantity"] = '"' . $poItem->item_name . '" — qty ' . $newQty . ' exceeds max available of ' . $maxQty . ' (sale qty: ' . $saleQty . ').';
                }
            }

            if (! empty($qtyErrors)) {
                return back()->withErrors($qtyErrors)->withInput();
            }

            foreach ($data['po_items'] as $poItemId => $overrides) {
                $poItem = $purchaseOrder->items->firstWhere('id', (int) $poItemId);
                if (! $poItem) {
                    continue;
                }
                $poItem->update([
                    'quantity'   => $overrides['quantity']   ?? $poItem->quantity,
                    'cost_price' => $overrides['cost_price'] ?? $poItem->cost_price,
                ]);
            }
        }

        return redirect()
            ->route('pages.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order updated.');
    }

    // -------------------------------------------------------------------------
    // PDF preview
    // -------------------------------------------------------------------------

    public function previewPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'items', 'sale', 'orderedBy']);

        $pdf      = Pdf::loadView('pdf.purchase-order', compact('purchaseOrder'));
        $filename = $purchaseOrder->po_number . '.pdf';

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    // -------------------------------------------------------------------------
    // Send email to vendor (Track 1 — shared mailbox)
    // -------------------------------------------------------------------------

    public function sendEmail(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        $purchaseOrder->load(['vendor', 'items', 'sale', 'orderedBy']);

        $mailer     = app(GraphMailService::class);
        $pdfContent = Pdf::loadView('pdf.purchase-order', compact('purchaseOrder'))->output();

        $attachment = [
            'filename' => $purchaseOrder->po_number . '.pdf',
            'content'  => base64_encode($pdfContent),
        ];

        $sent = $mailer->send(
            $request->input('to'),
            $request->input('subject'),
            $request->input('body'),
            'purchase_order',
            null,
            $attachment,
        );

        if ($sent) {
            $purchaseOrder->update(['sent_at' => now()]);
        }

        if (! $sent) {
            return back()->with('error', 'Failed to send email. Check the mail log for details.');
        }

        return back()->with('success', 'Purchase order emailed to ' . $request->input('to') . '.');
    }

    // -------------------------------------------------------------------------
    // Soft delete
    // -------------------------------------------------------------------------

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->delete();

        return redirect()
            ->route('pages.sales.show', $purchaseOrder->sale_id)
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' deleted.');
    }

    // -------------------------------------------------------------------------
    // Force delete (admin only — permanently removes from DB)
    // -------------------------------------------------------------------------

    public function forceDestroy(PurchaseOrder $purchaseOrder)
    {
        $saleId = $purchaseOrder->sale_id;
        $poNumber = $purchaseOrder->po_number;

        $purchaseOrder->forceDelete();

        return redirect()
            ->route('pages.sales.show', $saleId)
            ->with('success', 'Purchase order ' . $poNumber . ' permanently deleted.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Sum of PO item quantities per sale_item_id for a given sale,
     * excluding cancelled and soft-deleted POs.
     * Pass $excludePurchaseOrderId to ignore the current PO (used when editing).
     *
     * @return array<int, float>  [sale_item_id => total_ordered_qty]
     */
    private function orderedQtys(int $saleId, ?int $excludePurchaseOrderId = null): array
    {
        return PurchaseOrderItem::query()
            ->whereHas('purchaseOrder', function ($q) use ($saleId, $excludePurchaseOrderId) {
                $q->where('sale_id', $saleId)
                  ->where('status', '<>', 'cancelled');
                if ($excludePurchaseOrderId) {
                    $q->where('id', '<>', $excludePurchaseOrderId);
                }
            })
            ->selectRaw('sale_item_id, SUM(quantity) as total_ordered')
            ->groupBy('sale_item_id')
            ->pluck('total_ordered', 'sale_item_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();
    }

    private function buildItemName(\App\Models\SaleItem $item): string
    {
        $parts = array_filter([
            $item->product_type,
            $item->manufacturer,
            $item->style,
            $item->color_item_number,
        ]);

        return implode(' — ', $parts) ?: 'Material Item';
    }

    private function resolveDeliveryAddress(string $method, ?string $custom, Sale $sale): ?string
    {
        return match ($method) {
            'delivery_site'      => $sale->job_address,
            'delivery_warehouse' => $this->warehouseAddress(),
            'delivery_custom'    => $custom,
            'pickup'             => null,
            default              => null,
        };
    }

    private function warehouseAddress(): string
    {
        $parts = array_filter([
            Setting::get('branding_street'),
            Setting::get('branding_city'),
            Setting::get('branding_province'),
            Setting::get('branding_postal'),
        ]);

        return implode(', ', $parts) ?: '';
    }
}
