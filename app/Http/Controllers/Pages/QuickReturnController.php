<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\InventoryTransaction;
use App\Models\InvoicePayment;
use App\Models\QuickReturn;
use App\Models\QuickReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickReturnController extends Controller
{
    public function create()
    {
        $refundMethods = InvoicePayment::PAYMENT_METHODS;

        return view('pages.quick-returns.create', compact('refundMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_id'          => ['required', 'exists:sales,id'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'refund_method'    => ['required', 'in:' . implode(',', array_keys(InvoicePayment::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $sale = Sale::with(['customer', 'rooms.items'])->findOrFail($request->sale_id);

        // Validate quantities — can't return more than was sold minus already returned
        $errors = [];
        foreach ($request->items as $idx => $itemData) {
            $saleItem = SaleItem::find($itemData['sale_item_id']);

            // Ensure this item actually belongs to this sale
            if (!$saleItem || $saleItem->sale_id !== $sale->id) {
                $errors["items.{$idx}.sale_item_id"] = ['Invalid item.'];
                continue;
            }

            $alreadyReturned = QuickReturnItem::where('sale_item_id', $saleItem->id)->sum('quantity');
            $returnable      = (float) $saleItem->quantity - (float) $alreadyReturned;

            if ((float) $itemData['quantity'] > $returnable) {
                $label = $saleItem->style ?? $saleItem->description ?? 'Item';
                $errors["items.{$idx}.quantity"] = [
                    "Cannot return more than {$returnable} of \"{$label}\" (already returned: {$alreadyReturned})."
                ];
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $quickReturn = DB::transaction(function () use ($request, $sale) {
            // Pull tax from the sale
            $taxRate    = (float) $sale->tax_rate_percent;
            $taxDecimal = $taxRate / 100;

            // Calculate totals from sale item prices
            $subtotal = 0;
            foreach ($request->items as $itemData) {
                $saleItem  = SaleItem::find($itemData['sale_item_id']);
                $subtotal += round((float) $itemData['quantity'] * (float) $saleItem->sell_price, 2);
            }
            $taxAmount  = round($subtotal * $taxDecimal, 2);
            $grandTotal = $subtotal + $taxAmount;

            // Resolve customer name for display
            $customerName = $sale->homeowner_name
                ?? $sale->customer?->company_name
                ?? $sale->customer_name
                ?? 'Unknown';

            $quickReturn = QuickReturn::create([
                'sale_id'          => $sale->id,
                'customer_id'      => $sale->customer_id,
                'customer_name'    => $customerName,
                'tax_group_id'     => $sale->tax_group_id,
                'tax_rate_percent' => $taxRate,
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'grand_total'      => $grandTotal,
                'refund_method'    => $request->refund_method,
                'reference_number' => $request->reference_number ?: null,
                'notes'            => $request->notes,
                'created_by'       => auth()->id(),
                'updated_by'       => auth()->id(),
            ]);

            foreach ($request->items as $idx => $itemData) {
                $saleItem  = SaleItem::with('productStyle.productLine')->find($itemData['sale_item_id']);
                $lineTotal = round((float) $itemData['quantity'] * (float) $saleItem->sell_price, 2);

                $label = $saleItem->style
                    ? ($saleItem->style . ($saleItem->color_item_number ? ' — ' . $saleItem->color_item_number : ''))
                    : ($saleItem->description ?? 'Item');

                $returnItem = QuickReturnItem::create([
                    'quick_return_id'  => $quickReturn->id,
                    'sale_item_id'     => $saleItem->id,
                    'product_style_id' => $saleItem->product_style_id,
                    'description'      => $label,
                    'quantity'         => $itemData['quantity'],
                    'unit'             => $saleItem->unit ?? '',
                    'unit_price'       => $saleItem->sell_price,
                    'line_total'       => $lineTotal,
                    'sort_order'       => $idx,
                ]);

                // Return stock to inventory (catalog items only)
                if ($saleItem->product_style_id) {
                    $receipt = InventoryReceipt::create([
                        'product_style_id'  => $saleItem->product_style_id,
                        'item_name'         => $returnItem->description,
                        'unit'              => $returnItem->unit ?? '',
                        'quantity_received' => $returnItem->quantity,
                        'received_date'     => today()->toDateString(),
                        'notes'             => 'Quick Return ' . $quickReturn->return_number
                            . ' (Sale #' . $sale->sale_number . ')',
                    ]);

                    InventoryTransaction::create([
                        'inventory_receipt_id' => $receipt->id,
                        'type'                 => 'customer_return',
                        'quantity'             => $returnItem->quantity,
                        'reference_type'       => QuickReturn::class,
                        'reference_id'         => $quickReturn->id,
                        'note'                 => 'Quick Return ' . $quickReturn->return_number,
                        'created_by_user_id'   => auth()->id(),
                    ]);
                }
            }

            return $quickReturn;
        });

        return redirect()->route('pages.quick-returns.show', $quickReturn)
            ->with('success', 'Return ' . $quickReturn->return_number . ' recorded. Refund of $' . number_format($quickReturn->grand_total, 2) . ' issued.');
    }

    public function show(QuickReturn $quickReturn)
    {
        $quickReturn->load('sale', 'customer', 'items.saleItem');
        $settings = $this->brandingSettings();

        return view('pages.quick-returns.show', compact('quickReturn', 'settings'));
    }

    public function receipt(QuickReturn $quickReturn)
    {
        $quickReturn->load('sale', 'customer', 'items');
        $settings = $this->brandingSettings();

        $logoData = null;
        if (!empty($settings['branding_logo_path']) && file_exists(storage_path('app/public/' . $settings['branding_logo_path']))) {
            $logoData = 'data:image/' . pathinfo($settings['branding_logo_path'], PATHINFO_EXTENSION)
                . ';base64,' . base64_encode(file_get_contents(storage_path('app/public/' . $settings['branding_logo_path'])));
        }

        $pdf = Pdf::loadView('pdf.quick-return-receipt', compact('quickReturn', 'settings', 'logoData'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('return-' . $quickReturn->return_number . '.pdf');
    }

    // AJAX: search sales by number or customer name
    public function searchSales(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $sales = Sale::withoutTrashed()
            ->where(function ($query) use ($q) {
                $query->where('sale_number', 'like', "%{$q}%")
                      ->orWhere('homeowner_name', 'like', "%{$q}%")
                      ->orWhere('customer_name', 'like', "%{$q}%")
                      ->orWhere('job_name', 'like', "%{$q}%");
            })
            ->with('customer')
            ->orderByRaw("CAST(sale_number AS UNSIGNED) DESC")
            ->limit(10)
            ->get(['id', 'sale_number', 'homeowner_name', 'customer_name', 'job_name', 'customer_id', 'is_quick_sale', 'created_at', 'status']);

        return response()->json($sales->map(fn ($s) => [
            'id'               => $s->id,
            'sale_number'      => $s->sale_number,
            'label'            => $s->homeowner_name ?? $s->customer?->company_name ?? $s->customer_name ?? 'Unknown',
            'job_name'         => $s->job_name,
            'date'             => $s->created_at->format('M j, Y'),
            'status'           => $s->status,
            'is_quick'         => (bool) $s->is_quick_sale,
            'tax_rate_percent' => (float) $s->tax_rate_percent,
        ]));
    }

    // AJAX: load material items from a sale with returnable quantities
    public function getSaleItems(Sale $sale)
    {
        $items = SaleItem::where('sale_id', $sale->id)
            ->where('item_type', 'material')
            ->where('is_removed', false)
            ->orderBy('sort_order')
            ->get();

        return response()->json($items->map(function ($item) {
            $alreadyReturned = (float) QuickReturnItem::where('sale_item_id', $item->id)->sum('quantity');
            $returnable      = max(0, (float) $item->quantity - $alreadyReturned);

            $label = $item->style
                ? ($item->style . ($item->color_item_number ? ' — ' . $item->color_item_number : ''))
                : ($item->description ?? 'Item');

            return [
                'id'               => $item->id,
                'label'            => $label,
                'manufacturer'     => $item->manufacturer,
                'unit'             => $item->unit,
                'quantity_sold'    => (float) $item->quantity,
                'already_returned' => $alreadyReturned,
                'returnable'       => $returnable,
                'sell_price'       => (float) $item->sell_price,
                'product_style_id' => $item->product_style_id,
            ];
        }));
    }

    private function brandingSettings(): array
    {
        return [
            'branding_company_name' => Setting::get('branding_company_name', ''),
            'branding_phone'        => Setting::get('branding_phone', ''),
            'branding_email'        => Setting::get('branding_email', ''),
            'branding_street'       => Setting::get('branding_street', ''),
            'branding_city'         => Setting::get('branding_city', ''),
            'branding_province'     => Setting::get('branding_province', ''),
            'branding_postal'       => Setting::get('branding_postal', ''),
            'branding_logo_path'    => Setting::get('branding_logo_path', ''),
        ];
    }
}
