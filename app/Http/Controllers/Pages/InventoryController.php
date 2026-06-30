<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\ProductStyle;
use App\Models\Setting;
use App\Models\UnitMeasure;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function show(InventoryReceipt $inventoryReceipt): View
    {
        $inventoryReceipt->load([
            'purchaseOrder.vendor',
            'purchaseOrder.sale',
            'purchaseOrderItem',
            'productStyle.productLine.unit',
            'allocations.saleItem.room',
            'allocations.saleItem.sale',
            'allocations.pickTicketItems.pickTicket',
            'creator',
        ]);

        $allocated = $inventoryReceipt->allocations->sum('quantity');
        $available = max(0, (float) $inventoryReceipt->quantity_received - $allocated);
        $tagFormat = Setting::get('label_printer_format', 'standard');

        return view('pages.inventory.show', compact('inventoryReceipt', 'allocated', 'available', 'tagFormat'));
    }

    public function index(Request $request): View
    {
        $q              = trim($request->input('q', ''));
        $recordId       = $request->input('record_id', '');
        $productStyleId = $request->input('product_style_id', '');
        $dateFrom       = $request->input('date_from', '');
        $dateTo         = $request->input('date_to', '');
        $showDepleted   = $request->boolean('show_depleted', false);

        $receipts = InventoryReceipt::query()
            ->withSum('allocations', 'quantity')
            ->with(['purchaseOrder', 'creator'])
            ->when($recordId, fn ($query) => $query->where('id', (int) $recordId))
            ->when($productStyleId, fn ($query) => $query->where('product_style_id', (int) $productStyleId))
            ->when($q, fn ($query) => $query->where('item_name', 'like', "%{$q}%"))
            ->when($dateFrom, fn ($query) => $query->whereDate('received_date', '>=', $dateFrom))
            ->when($dateTo,   fn ($query) => $query->whereDate('received_date', '<=', $dateTo))
            ->when(! $showDepleted, fn ($query) => $query->whereRaw(
                'quantity_received > COALESCE((SELECT SUM(quantity) FROM inventory_allocations WHERE inventory_receipt_id = inventory_receipts.id), 0)'
            ))
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // Summary stats (unfiltered — whole inventory)
        $totalReceipts   = InventoryReceipt::count();
        $totalInStock    = InventoryReceipt::whereRaw(
            'quantity_received > COALESCE((SELECT SUM(quantity) FROM inventory_allocations WHERE inventory_receipt_id = inventory_receipts.id), 0)'
        )->count();
        $totalDepleted   = $totalReceipts - $totalInStock;

        return view('pages.inventory.index', compact(
            'receipts',
            'q', 'recordId', 'productStyleId', 'dateFrom', 'dateTo', 'showDepleted',
            'totalReceipts', 'totalInStock', 'totalDepleted',
        ));
    }

    public function create(): View
    {
        $units = UnitMeasure::orderBy('label')->get(['id', 'code', 'label']);

        return view('pages.inventory.create', compact('units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_style_id' => ['required', 'exists:product_styles,id'],
            'item_name'        => ['required', 'string', 'max:255'],
            'unit'             => ['required', 'string', 'max:50'],
            'quantity_received' => ['required', 'numeric', 'min:0.01'],
            'cost_price'       => ['nullable', 'numeric', 'min:0'],
            'received_date'    => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $receipt = InventoryReceipt::create([
            'product_style_id'  => $data['product_style_id'],
            'item_name'         => $data['item_name'],
            'unit'              => $data['unit'],
            'quantity_received' => $data['quantity_received'],
            'cost_price'        => $data['cost_price'] ?? null,
            'received_date'     => $data['received_date'],
            'notes'             => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('pages.inventory.show', $receipt)
            ->with('success', 'Inventory record created.');
    }

    public function searchProducts(Request $request)
    {
        $q = trim($request->input('q', ''));

        $styles = ProductStyle::with(['productLine.unit'])
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
            ->get(['id', 'product_line_id', 'name', 'sku', 'color', 'cost_price']);

        return response()->json($styles->map(fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'sku'          => $s->sku,
            'color'        => $s->color,
            'cost_price'   => $s->cost_price,
            'line_name'    => $s->productLine?->name,
            'manufacturer' => $s->productLine?->manufacturer,
            'unit_code'    => $s->productLine?->unit?->code,
        ]));
    }
}
