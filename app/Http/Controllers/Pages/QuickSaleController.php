<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceRoom;
use App\Models\InventoryReceipt;
use App\Models\InventoryTransaction;
use App\Models\ProductStyle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleRoom;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuickSaleController extends Controller
{
    public function create()
    {
        $taxGroups = DB::table('tax_rate_groups')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        return view('pages.quick-sales.create', compact('taxGroups'));
    }

    public function store(Request $request)
    {
        $paymentMethods = implode(',', array_keys(\App\Models\InvoicePayment::PAYMENT_METHODS));

        $request->validate([
            'fulfillment_mode'   => ['required', 'in:complete_now,open_sale'],

            // Customer — either existing or new
            'customer_mode'      => ['required', 'in:existing,new'],
            'customer_id'        => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'new_customer_name'  => ['required_if:customer_mode,new', 'nullable', 'string', 'max:255'],
            'new_customer_phone' => ['nullable', 'string', 'max:50'],
            'new_customer_email' => ['nullable', 'email', 'max:255'],

            // Items
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.description'      => ['required', 'string', 'max:255'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'             => ['nullable', 'string', 'max:50'],
            'items.*.sell_price'       => ['required', 'numeric', 'min:0'],
            'items.*.product_style_id' => ['nullable', 'exists:product_styles,id'],

            // Tax
            'tax_group_id' => ['required', 'exists:tax_rate_groups,id'],

            // Payment — only required when completing now
            'payment_method'   => ['required_if:fulfillment_mode,complete_now', 'nullable', 'in:' . $paymentMethods],
            'amount_tendered'  => ['required_if:fulfillment_mode,complete_now', 'nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = DB::transaction(function () use ($request) {
            // 1. Resolve or create customer
            if ($request->customer_mode === 'existing') {
                $customer = Customer::findOrFail($request->customer_id);
            } else {
                $customer = Customer::create([
                    'company_name'  => $request->new_customer_name,
                    'phone'         => $request->new_customer_phone,
                    'email'         => $request->new_customer_email,
                    'customer_type' => 'individual',
                    'created_by'    => auth()->id(),
                    'updated_by'    => auth()->id(),
                ]);
            }

            // 2. Resolve tax
            $taxGroupId = $request->tax_group_id;
            $taxGroup   = DB::table('tax_rate_groups')->where('id', $taxGroupId)->first();
            $rateCol    = Schema::hasColumn('tax_rates', 'tax_rate_sales') ? 'tax_rate_sales' : 'sales_rate';
            $taxRate    = (float) DB::table('tax_rate_group_items as tgi')
                ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
                ->where('tgi.tax_rate_group_id', $taxGroupId)
                ->sum("tr.{$rateCol}");
            $taxDecimal = $taxRate / 100;

            // 3. Calculate totals
            $pretaxTotal = 0;
            foreach ($request->items as $item) {
                $pretaxTotal += round((float) $item['quantity'] * (float) $item['sell_price'], 2);
            }
            $taxAmount  = round($pretaxTotal * $taxDecimal, 2);
            $grandTotal = $pretaxTotal + $taxAmount;

            $completing = $request->fulfillment_mode === 'complete_now';

            // 4. Create Sale
            $sale = Sale::create([
                'is_quick_sale'     => true,
                'customer_id'       => $customer->id,
                'homeowner_name'    => $customer->company_name,
                'tax_group_id'      => $taxGroup->id,
                'tax_rate_percent'  => $taxRate,
                'grand_total'       => $grandTotal,
                'status'            => $completing ? 'completed' : 'open',
                'locked_at'         => $completing ? now() : null,
                'locked_by'         => $completing ? auth()->id() : null,
                'locked_pretax_total'     => $completing ? $pretaxTotal : null,
                'locked_tax_rate_percent' => $completing ? $taxRate : null,
                'locked_tax_amount'       => $completing ? $taxAmount : null,
                'locked_grand_total'      => $completing ? $grandTotal : null,
                'notes'             => $request->notes,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);

            // 5. Create one room
            $room = SaleRoom::create([
                'sale_id'    => $sale->id,
                'room_name'  => 'Items',
                'sort_order' => 0,
            ]);

            // 6. Create sale items
            $saleItems = [];
            foreach ($request->items as $idx => $item) {
                $lineTotal = round((float) $item['quantity'] * (float) $item['sell_price'], 2);

                $style = isset($item['product_style_id'])
                    ? ProductStyle::with('productLine')->find($item['product_style_id'])
                    : null;

                $saleItems[] = SaleItem::create([
                    'sale_id'          => $sale->id,
                    'sale_room_id'     => $room->id,
                    'item_type'        => 'material',
                    'quantity'         => $item['quantity'],
                    'unit'             => $item['unit'] ?? null,
                    'sell_price'       => $item['sell_price'],
                    'line_total'       => $lineTotal,
                    'sort_order'       => $idx,
                    'product_style_id' => $style?->id,
                    'style'            => $style ? $style->name : $item['description'],
                    'color_item_number'=> $style ? ($style->color ?? null) : null,
                    'manufacturer'     => $style?->productLine?->manufacturer,
                    'description'      => !$style ? $item['description'] : null,
                ]);
            }

            $stockWarning = null;

            if ($completing) {
                // 7. Deduct inventory (FIFO, warn but don't block)
                $stockWarning = $this->deductInventory($sale, $saleItems);

                // 8. Create Invoice (status = paid)
                $invoice = Invoice::create([
                    'sale_id'     => $sale->id,
                    'status'      => 'paid',
                    'due_date'    => today(),
                    'subtotal'    => $pretaxTotal,
                    'tax_amount'  => $taxAmount,
                    'grand_total' => $grandTotal,
                    'amount_paid' => $grandTotal,
                ]);

                // 9. Create InvoiceRoom + InvoiceItems
                $invoiceRoom = InvoiceRoom::create([
                    'invoice_id'   => $invoice->id,
                    'sale_room_id' => $room->id,
                    'name'         => 'Items',
                    'sort_order'   => 0,
                ]);

                foreach ($saleItems as $idx => $saleItem) {
                    $lineTotal = round($saleItem->quantity * $saleItem->sell_price, 2);
                    $taxAmt    = round($lineTotal * $taxDecimal, 2);

                    InvoiceItem::create([
                        'invoice_id'      => $invoice->id,
                        'invoice_room_id' => $invoiceRoom->id,
                        'sale_item_id'    => $saleItem->id,
                        'item_type'       => 'material',
                        'label'           => $saleItem->style ?? $saleItem->description ?? 'Item',
                        'quantity'        => $saleItem->quantity,
                        'unit'            => $saleItem->unit,
                        'sell_price'      => $saleItem->sell_price,
                        'line_total'      => $lineTotal,
                        'tax_rate'        => $taxRate,
                        'tax_amount'      => $taxAmt,
                        'tax_group_id'    => $taxGroup->id,
                        'sort_order'      => $idx,
                    ]);
                }

                // 10. Create InvoicePayment
                InvoicePayment::create([
                    'invoice_id'       => $invoice->id,
                    'amount'           => min((float) $request->amount_tendered, $grandTotal),
                    'payment_date'     => today(),
                    'payment_method'   => $request->payment_method,
                    'reference_number' => $request->reference_number,
                    'recorded_by'      => auth()->id(),
                ]);
            }

            return compact('sale', 'stockWarning', 'completing');
        });

        $sale         = $result['sale'];
        $stockWarning = $result['stockWarning'];
        $completing   = $result['completing'];

        if ($stockWarning) {
            session()->flash('warning', $stockWarning);
        }

        if ($completing) {
            return redirect()->route('pages.quick-sales.show', $sale)
                ->with('success', 'Quick sale #' . $sale->sale_number . ' completed.');
        }

        return redirect()->route('pages.sales.show', $sale)
            ->with('success', 'Sale #' . $sale->sale_number . ' created. Add purchase orders or other details as needed.');
    }

    public function show(Sale $sale)
    {
        abort_unless($sale->is_quick_sale, 404);

        $sale->load([
            'customer',
            'rooms.items',
            'invoices.items',
            'invoices.payments',
        ]);

        $invoice = $sale->invoices->first();
        $payment = $invoice?->payments->first();
        $grandTotal = $invoice?->grand_total ?? 0;
        $amountTendered = $payment?->amount ?? $grandTotal;
        $changeDue = ($payment?->payment_method === 'cash')
            ? max(0, $amountTendered - $grandTotal)
            : 0;

        $settings = $this->brandingSettings();

        return view('pages.quick-sales.show', compact(
            'sale', 'invoice', 'payment', 'grandTotal', 'amountTendered', 'changeDue', 'settings'
        ));
    }

    public function receipt(Sale $sale)
    {
        abort_unless($sale->is_quick_sale, 404);

        $sale->load(['customer', 'rooms.items', 'invoices.items', 'invoices.payments']);

        $invoice        = $sale->invoices->first();
        $payment        = $invoice?->payments->first();
        $grandTotal     = $invoice?->grand_total ?? 0;
        $amountTendered = $payment?->amount ?? $grandTotal;
        $changeDue      = ($payment?->payment_method === 'cash')
            ? max(0, $amountTendered - $grandTotal)
            : 0;

        $settings = $this->brandingSettings();

        $logoData = null;
        if (!empty($settings['branding_logo_path']) && file_exists(storage_path('app/public/' . $settings['branding_logo_path']))) {
            $logoData = 'data:image/' . pathinfo($settings['branding_logo_path'], PATHINFO_EXTENSION)
                . ';base64,' . base64_encode(file_get_contents(storage_path('app/public/' . $settings['branding_logo_path'])));
        }

        $pdf = Pdf::loadView('pdf.quick-sale-receipt', compact(
            'sale', 'invoice', 'payment', 'grandTotal', 'amountTendered', 'changeDue', 'settings', 'logoData'
        ))->setPaper('letter', 'portrait');

        return $pdf->stream('receipt-' . $sale->sale_number . '.pdf');
    }

    // AJAX: search existing customers
    public function searchCustomers(Request $request)
    {
        $q = $request->input('q', '');

        $customers = Customer::when($q, function ($query) use ($q) {
                $query->where('company_name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('company_name')
            ->limit(15)
            ->get(['id', 'company_name', 'phone', 'email']);

        return response()->json($customers->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->company_name,
            'phone' => $c->phone,
            'email' => $c->email,
        ]));
    }

    // AJAX: search product catalog
    public function searchProducts(Request $request)
    {
        $q = $request->input('q', '');

        $styles = ProductStyle::with('productLine')
            ->where('status', '<>', 'archived')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                          ->orWhere('sku', 'like', "%{$q}%")
                          ->orWhere('color', 'like', "%{$q}%")
                          ->orWhere('style_number', 'like', "%{$q}%")
                          ->orWhereHas('productLine', fn ($pl) =>
                              $pl->where('name', 'like', "%{$q}%")
                                 ->orWhere('manufacturer', 'like', "%{$q}%")
                          );
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'product_line_id', 'name', 'sku', 'color', 'sell_price']);

        return response()->json($styles->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'sku'          => $s->sku,
            'color'        => $s->color,
            'sell_price'   => $s->sell_price,
            'line_name'    => $s->productLine?->name,
            'manufacturer' => $s->productLine?->manufacturer,
        ]));
    }

    /**
     * Deduct stock FIFO for completed quick sale items.
     * Warns but does not block if stock is insufficient.
     * Returns a warning string if any item had low stock, null otherwise.
     */
    private function deductInventory(Sale $sale, array $saleItems): ?string
    {
        $warnings = [];

        foreach ($saleItems as $saleItem) {
            if (!$saleItem->product_style_id) {
                continue;
            }

            $needed = (float) $saleItem->quantity;

            // Load all receipts for this product FIFO (oldest first)
            $receipts = InventoryReceipt::with(['allocations', 'transactions'])
                ->where('product_style_id', $saleItem->product_style_id)
                ->orderBy('received_date')
                ->orderBy('id')
                ->get();

            $totalAvailable = $receipts->sum('available_qty');

            if ($totalAvailable < $needed) {
                $label = $saleItem->style ?? $saleItem->description ?? 'Item';
                $warnings[] = "Low stock for \"{$label}\": {$totalAvailable} available, {$needed} sold.";
            }

            $remaining = $needed;
            foreach ($receipts as $receipt) {
                if ($remaining <= 0) break;

                $avail = $receipt->available_qty;
                if ($avail <= 0) continue;

                $deduct = min($avail, $remaining);

                InventoryTransaction::create([
                    'inventory_receipt_id' => $receipt->id,
                    'type'                 => 'fulfilled',
                    'quantity'             => -$deduct,
                    'reference_type'       => Sale::class,
                    'reference_id'         => $sale->id,
                    'note'                 => "Quick Sale #{$sale->sale_number}",
                    'created_by_user_id'   => auth()->id(),
                ]);

                $remaining -= $deduct;
            }
        }

        return empty($warnings) ? null : implode(' ', $warnings);
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
