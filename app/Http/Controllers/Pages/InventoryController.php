<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\InventoryReceipt;
use App\Models\ProductStyle;
use App\Models\Setting;
use App\Models\UnitMeasure;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    /**
     * SQL expression for a receipt's available quantity, mirroring
     * InventoryReceipt::getAvailableQtyAttribute() exactly (received − allocated −
     * outbound return_to_vendor/fulfilled + signed adjustments), for use in raw
     * WHERE/COUNT queries where loading the full Eloquent accessor isn't practical.
     */
    private const AVAILABLE_QTY_SQL = <<<'SQL'
        (
            quantity_received
            - COALESCE((SELECT SUM(quantity) FROM inventory_allocations WHERE inventory_allocations.inventory_receipt_id = inventory_receipts.id), 0)
            - COALESCE((SELECT SUM(ABS(quantity)) FROM inventory_transactions WHERE inventory_transactions.inventory_receipt_id = inventory_receipts.id AND type IN ('return_to_vendor', 'fulfilled')), 0)
            + COALESCE((SELECT SUM(quantity) FROM inventory_transactions WHERE inventory_transactions.inventory_receipt_id = inventory_receipts.id AND type = 'adjustment'), 0)
        )
        SQL;

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
            'transactions.createdBy',
            'creator',
        ]);

        $allocated = $inventoryReceipt->allocations->sum('quantity');
        $available = $inventoryReceipt->available_qty;
        $adjustments = $inventoryReceipt->transactions
            ->where('type', 'adjustment')
            ->sortByDesc('created_at')
            ->values();
        $tagFormat = Setting::get('label_printer_format', 'standard');

        return view('pages.inventory.show', compact('inventoryReceipt', 'allocated', 'available', 'adjustments', 'tagFormat'));
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
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('item_name', 'like', "%{$q}%")
                    ->orWhereHas('productStyle', function ($ps) use ($q) {
                        $ps->where('name', 'like', "%{$q}%")
                            ->orWhereHas('productLine', function ($pl) use ($q) {
                                $pl->where('name', 'like', "%{$q}%")
                                    ->orWhere('manufacturer', 'like', "%{$q}%");
                            });
                    });
            }))
            ->when($dateFrom, fn ($query) => $query->whereDate('received_date', '>=', $dateFrom))
            ->when($dateTo,   fn ($query) => $query->whereDate('received_date', '<=', $dateTo))
            ->when(! $showDepleted, fn ($query) => $query->whereRaw(self::AVAILABLE_QTY_SQL . ' > 0'))
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // Summary stats (unfiltered — whole inventory)
        $totalReceipts   = InventoryReceipt::count();
        $totalInStock    = InventoryReceipt::whereRaw(self::AVAILABLE_QTY_SQL . ' > 0')->count();
        $totalDepleted   = $totalReceipts - $totalInStock;

        // When search returns nothing, check if depleted records would match — so we can prompt the user
        $depletedMatchCount = 0;
        if ($receipts->isEmpty() && ! $showDepleted && ($q || $recordId || $productStyleId || $dateFrom || $dateTo)) {
            $depletedMatchCount = InventoryReceipt::query()
                ->when($recordId, fn ($query) => $query->where('id', (int) $recordId))
                ->when($productStyleId, fn ($query) => $query->where('product_style_id', (int) $productStyleId))
                ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                    $sub->where('item_name', 'like', "%{$q}%")
                        ->orWhereHas('productStyle', function ($ps) use ($q) {
                            $ps->where('name', 'like', "%{$q}%")
                                ->orWhereHas('productLine', function ($pl) use ($q) {
                                    $pl->where('name', 'like', "%{$q}%")
                                        ->orWhere('manufacturer', 'like', "%{$q}%");
                                });
                        });
                }))
                ->when($dateFrom, fn ($query) => $query->whereDate('received_date', '>=', $dateFrom))
                ->when($dateTo,   fn ($query) => $query->whereDate('received_date', '<=', $dateTo))
                ->whereRaw(self::AVAILABLE_QTY_SQL . ' <= 0')
                ->count();
        }

        return view('pages.inventory.index', compact(
            'receipts',
            'q', 'recordId', 'productStyleId', 'dateFrom', 'dateTo', 'showDepleted',
            'totalReceipts', 'totalInStock', 'totalDepleted', 'depletedMatchCount',
        ));
    }

    public function summary(Request $request): View
    {
        $q             = trim($request->input('q', ''));
        $showZeroStock = $request->boolean('show_zero_stock', false);

        $receipts = InventoryReceipt::query()
            ->whereNotNull('product_style_id')
            ->with(['allocations', 'transactions', 'productStyle.productLine'])
            ->when($q, fn ($query) => $query->whereHas('productStyle', function ($ps) use ($q) {
                $ps->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('style_number', 'like', "%{$q}%")
                    ->orWhereHas('productLine', function ($pl) use ($q) {
                        $pl->where('name', 'like', "%{$q}%")
                            ->orWhere('manufacturer', 'like', "%{$q}%");
                    });
            }))
            ->get();

        $summary = $receipts
            ->groupBy('product_style_id')
            ->map(function ($group) {
                $style = $group->first()->productStyle;
                $line  = $style?->productLine;

                return (object) [
                    'product_style_id' => $group->first()->product_style_id,
                    'style_name'       => $style?->name,
                    'sku'              => $style?->sku,
                    'style_number'     => $style?->style_number,
                    'color'            => $style?->color,
                    'line_name'        => $line?->name,
                    'manufacturer'     => $line?->manufacturer,
                    'unit'             => $group->first()->unit,
                    'total_received'   => (float) $group->sum('quantity_received'),
                    'total_available'  => (float) $group->sum('available_qty'),
                    'record_count'     => $group->count(),
                ];
            })
            ->when(! $showZeroStock, fn ($rows) => $rows->filter(fn ($row) => $row->total_available > 0))
            ->sortByDesc('total_available')
            ->values();

        $totalStyles         = $summary->count();
        $totalUnitsAvailable = $summary->sum('total_available');

        // RFC receipts with no linked product style — grouped by item name
        $unlinkedRfcReceipts = InventoryReceipt::query()
            ->whereNotNull('customer_return_item_id')
            ->whereNull('product_style_id')
            ->with('allocations')
            ->when($q, fn ($query) => $query->where('item_name', 'like', "%{$q}%"))
            ->get();

        $unlinkedSummary = $unlinkedRfcReceipts
            ->groupBy('item_name')
            ->map(function ($group) {
                return (object) [
                    'item_name'       => $group->first()->item_name,
                    'unit'            => $group->first()->unit,
                    'total_received'  => (float) $group->sum('quantity_received'),
                    'total_available' => (float) $group->sum('available_qty'),
                    'record_count'    => $group->count(),
                    'receipt_ids'     => $group->pluck('id')->implode(','),
                ];
            })
            ->when(! $showZeroStock, fn ($rows) => $rows->filter(fn ($row) => $row->total_available > 0))
            ->sortByDesc('total_available')
            ->values();

        return view('pages.inventory.summary', compact(
            'summary', 'q', 'showZeroStock', 'totalStyles', 'totalUnitsAvailable', 'unlinkedSummary',
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

    public function edit(InventoryReceipt $inventoryReceipt): View
    {
        $inventoryReceipt->load(['productStyle.productLine.productType', 'productStyle.productLine.unit']);
        $units = UnitMeasure::orderBy('label')->get(['id', 'code', 'label']);

        $currentProduct = null;
        if ($s = $inventoryReceipt->productStyle) {
            $currentProduct = [
                'id'           => $s->id,
                'name'         => $s->name,
                'sku'          => $s->sku,
                'color'        => $s->color,
                'cost_price'   => $s->cost_price,
                'line_name'    => $s->productLine?->name,
                'manufacturer' => $s->productLine?->manufacturer,
                'product_type' => $s->productLine?->productType?->name,
                'unit_code'    => $s->productLine?->unit?->code,
            ];
        }

        return view('pages.inventory.edit', compact('inventoryReceipt', 'units', 'currentProduct'));
    }

    public function update(Request $request, InventoryReceipt $inventoryReceipt)
    {
        $data = $request->validate([
            'product_style_id'  => ['nullable', 'exists:product_styles,id'],
            'item_name'         => ['required', 'string', 'max:255'],
            'unit'              => ['required', 'string', 'max:50'],
            'quantity_received' => ['required', 'numeric', 'min:0.01'],
            'cost_price'        => ['nullable', 'numeric', 'min:0'],
            'received_date'     => ['required', 'date'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        // Don't allow reducing quantity below what's already allocated
        $allocated = $inventoryReceipt->allocations()->sum('quantity');
        if ((float) $data['quantity_received'] < (float) $allocated) {
            return back()->withErrors([
                'quantity_received' => "Cannot reduce below already-allocated quantity ({$allocated}).",
            ])->withInput();
        }

        $inventoryReceipt->update([
            'product_style_id'  => $data['product_style_id'] ?? null,
            'item_name'         => $data['item_name'],
            'unit'              => $data['unit'],
            'quantity_received' => $data['quantity_received'],
            'cost_price'        => $data['cost_price'] ?? null,
            'received_date'     => $data['received_date'],
            'notes'             => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('pages.inventory.show', $inventoryReceipt)
            ->with('success', 'Inventory record updated.');
    }

    public function adjust(Request $request, InventoryReceipt $inventoryReceipt, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:increase,decrease'],
            'quantity'  => ['required', 'numeric', 'min:0.01'],
            'reason'    => ['required', 'in:' . implode(',', InventoryService::ADJUSTMENT_REASONS)],
            'note'      => ['nullable', 'string', 'max:500'],
        ]);

        $signedQuantity = $data['direction'] === 'increase' ? (float) $data['quantity'] : -(float) $data['quantity'];

        try {
            $inventoryService->adjust(
                $inventoryReceipt,
                $signedQuantity,
                $data['reason'],
                $data['note'] ?? null,
                auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('pages.inventory.show', $inventoryReceipt)
            ->with('success', 'Stock adjustment recorded.');
    }

    public function searchProducts(Request $request)
    {
        $q = trim($request->input('q', ''));

        $styles = ProductStyle::with(['productLine.unit', 'productLine.productType'])
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
            'product_type' => $s->productLine?->productType?->name,
            'unit_code'    => $s->productLine?->unit?->code,
        ]));
    }
}
