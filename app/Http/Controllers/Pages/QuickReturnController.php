<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\InventoryReceipt;
use App\Models\InventoryTransaction;
use App\Models\InvoicePayment;
use App\Models\ProductStyle;
use App\Models\QuickReturn;
use App\Models\QuickReturnItem;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuickReturnController extends Controller
{
    public function create()
    {
        $taxGroups = DB::table('tax_rate_groups')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $refundMethods = InvoicePayment::PAYMENT_METHODS;

        return view('pages.quick-returns.create', compact('taxGroups', 'refundMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_mode'        => ['required', 'in:existing,new,walkin'],
            'customer_id'          => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'new_customer_name'    => ['required_if:customer_mode,new', 'nullable', 'string', 'max:255'],
            'new_customer_phone'   => ['nullable', 'string', 'max:50'],
            'new_customer_email'   => ['nullable', 'email', 'max:255'],
            'original_sale_number' => ['nullable', 'string', 'max:50'],

            'items'                    => ['required', 'array', 'min:1'],
            'items.*.description'      => ['required', 'string', 'max:255'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'             => ['nullable', 'string', 'max:50'],
            'items.*.unit_price'       => ['required', 'numeric', 'min:0'],
            'items.*.product_style_id' => ['nullable', 'exists:product_styles,id'],

            'tax_group_id'  => ['nullable', 'exists:tax_rate_groups,id'],
            'refund_method' => ['required', 'in:' . implode(',', array_keys(InvoicePayment::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ]);

        $quickReturn = DB::transaction(function () use ($request) {
            // 1. Resolve customer
            $customer     = null;
            $customerName = 'Walk-in';

            if ($request->customer_mode === 'existing') {
                $customer     = Customer::findOrFail($request->customer_id);
                $customerName = $customer->company_name;
            } elseif ($request->customer_mode === 'new') {
                $customer = Customer::create([
                    'company_name'  => $request->new_customer_name,
                    'phone'         => $request->new_customer_phone,
                    'email'         => $request->new_customer_email,
                    'customer_type' => 'individual',
                    'created_by'    => auth()->id(),
                    'updated_by'    => auth()->id(),
                ]);
                $customerName = $customer->company_name;
            }

            // 2. Resolve tax
            $taxGroupId = $request->tax_group_id;
            $taxRate    = 0;
            $taxGroup   = null;

            if ($taxGroupId) {
                $taxGroup = DB::table('tax_rate_groups')->where('id', $taxGroupId)->first();
                $rateCol  = Schema::hasColumn('tax_rates', 'tax_rate_sales') ? 'tax_rate_sales' : 'sales_rate';
                $taxRate  = (float) DB::table('tax_rate_group_items as tgi')
                    ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
                    ->where('tgi.tax_rate_group_id', $taxGroupId)
                    ->sum("tr.{$rateCol}");
            }

            $taxDecimal = $taxRate / 100;

            // 3. Calculate totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            }
            $taxAmount  = round($subtotal * $taxDecimal, 2);
            $grandTotal = $subtotal + $taxAmount;

            // 4. Create QuickReturn
            $quickReturn = QuickReturn::create([
                'customer_id'          => $customer?->id,
                'customer_name'        => $customerName,
                'original_sale_number' => $request->original_sale_number ?: null,
                'tax_group_id'         => $taxGroup?->id,
                'tax_rate_percent'     => $taxRate,
                'subtotal'             => $subtotal,
                'tax_amount'           => $taxAmount,
                'grand_total'          => $grandTotal,
                'refund_method'        => $request->refund_method,
                'reference_number'     => $request->reference_number ?: null,
                'notes'                => $request->notes,
                'created_by'           => auth()->id(),
                'updated_by'           => auth()->id(),
            ]);

            // 5. Create items + inventory receipts
            foreach ($request->items as $idx => $item) {
                $lineTotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);

                $style = isset($item['product_style_id']) && $item['product_style_id']
                    ? ProductStyle::with('productLine')->find($item['product_style_id'])
                    : null;

                $returnItem = QuickReturnItem::create([
                    'quick_return_id'  => $quickReturn->id,
                    'product_style_id' => $style?->id,
                    'description'      => $style
                        ? ($style->name . ($style->color ? ' — ' . $style->color : ''))
                        : $item['description'],
                    'quantity'         => $item['quantity'],
                    'unit'             => $item['unit'] ?? null,
                    'unit_price'       => $item['unit_price'],
                    'line_total'       => $lineTotal,
                    'sort_order'       => $idx,
                ]);

                // Add returned stock back to inventory
                if ($style) {
                    $receipt = InventoryReceipt::create([
                        'product_style_id' => $style->id,
                        'item_name'        => $returnItem->description,
                        'unit'             => $returnItem->unit ?? '',
                        'quantity_received'=> $returnItem->quantity,
                        'received_date'    => today()->toDateString(),
                        'notes'            => 'Quick Return ' . $quickReturn->return_number
                            . ($request->original_sale_number ? ' (Sale #' . $request->original_sale_number . ')' : ''),
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
            ->with('success', 'Return ' . $quickReturn->return_number . ' recorded. Refund issued.');
    }

    public function show(QuickReturn $quickReturn)
    {
        $quickReturn->load('customer', 'items.productStyle');
        $settings = $this->brandingSettings();

        return view('pages.quick-returns.show', compact('quickReturn', 'settings'));
    }

    public function receipt(QuickReturn $quickReturn)
    {
        $quickReturn->load('customer', 'items.productStyle');
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

    public function searchCustomers(Request $request)
    {
        $q = $request->input('q', '');

        $customers = Customer::when($q, fn ($query) =>
                $query->where('company_name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
            )
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

    public function searchProducts(Request $request)
    {
        $q = $request->input('q', '');

        $styles = ProductStyle::with('productLine')
            ->where('status', '<>', 'archived')
            ->when($q, fn ($query) =>
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                          ->orWhere('sku', 'like', "%{$q}%")
                          ->orWhere('color', 'like', "%{$q}%")
                          ->orWhere('style_number', 'like', "%{$q}%")
                          ->orWhereHas('productLine', fn ($pl) =>
                              $pl->where('name', 'like', "%{$q}%")
                                 ->orWhere('manufacturer', 'like', "%{$q}%")
                          );
                })
            )
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
